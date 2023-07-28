<?php

namespace
{
    $mockFileGetContents = null;
}

namespace Ajtak\OAuth2\Client\Provider
{
    function file_get_contents()
    {
        global $mockFileGetContents;
        if (isset($mockFileGetContents) && ! is_null($mockFileGetContents)) {
            if (is_a($mockFileGetContents, 'Exception')) {
                throw $mockFileGetContents;
            }
            return $mockFileGetContents;
        } else {
            return call_user_func_array('\file_get_contents', func_get_args());
        }
    }
}

namespace Ajtak\OAuth2\Client\Test\Provider
{

    use DateInterval;
    use DateTimeImmutable;
    use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
    use League\OAuth2\Client\Tool\QueryBuilderTrait;
    use Mockery as m;
    use PHPUnit\Framework\TestCase;
    use Psr\Http\Message\StreamInterface;
    use Ajtak\OAuth2\Client\Provider\ApereoCas;

    class ApereoCasTest extends TestCase
    {
        use QueryBuilderTrait;

        protected $provider;

        protected function setUp(): void
        {
            $this->provider = new ApereoCas([
                'authServerUrl' => 'http://mock.url/auth',
                'realm' => 'mock_realm',
                'clientId' => 'mock_client_id',
                'clientSecret' => 'mock_secret',
                'redirectUri' => 'none',
            ]);
        }

        public function tearDown(): void
        {
            m::close();
            parent::tearDown();
        }

        public function testAuthorizationUrl()
        {
            $url = $this->provider->getAuthorizationUrl();
            $uri = parse_url($url);
            parse_str($uri['query'], $query);

            $this->assertArrayHasKey('client_id', $query);
            $this->assertArrayHasKey('redirect_uri', $query);
            $this->assertArrayHasKey('state', $query);
            $this->assertArrayHasKey('scope', $query);
            $this->assertArrayHasKey('response_type', $query);
            $this->assertArrayHasKey('approval_prompt', $query);
            $this->assertNotNull($this->provider->getState());
        }

        public function testScopes()
        {
            $scopeSeparator = ' ';
            $options = ['scope' => [uniqid(), uniqid()]];
            $query = ['scope' => implode($scopeSeparator, $options['scope'])];
            $url = $this->provider->getAuthorizationUrl($options);
            $encodedScope = $this->buildQueryString($query);
            $this->assertStringContainsString($encodedScope, $url);
        }

        public function testGetAuthorizationUrl()
        {
            $url = $this->provider->getAuthorizationUrl();
            $uri = parse_url($url);

            $this->assertEquals('/auth/oidc/authorize', $uri['path']);
        }

        public function testGetBaseAccessTokenUrl()
        {
            $params = [];

            $url = $this->provider->getBaseAccessTokenUrl($params);
            $uri = parse_url($url);

            $this->assertEquals('/auth/oidc/accessToken', $uri['path']);
        }

        public function testGetAccessToken()
        {
            $stream = $this->createMock(StreamInterface::class);
            $stream
                ->method('__toString')
                ->willReturn('{"access_token":"mock_access_token","scope":"email","token_type":"bearer"}');

            $response = m::mock('Psr\Http\Message\ResponseInterface');
            $response
                ->shouldReceive('getBody')
                ->andReturn($stream);
            $response
                ->shouldReceive('getHeader')
                ->andReturn(['content-type' => 'json']);

            $client = m::mock('GuzzleHttp\ClientInterface');
            $client->shouldReceive('send')
                ->times(1)
                ->andReturn($response);
            $this->provider->setHttpClient($client);

            $token = $this
                ->provider
                ->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

            $this->assertEquals('mock_access_token', $token->getToken());
            $this->assertNull($token->getExpires());
            $this->assertNull($token->getRefreshToken());
            $this->assertNull($token->getResourceOwnerId());
        }

        public function testUserData()
        {
            $userId = rand(1000, 9999);
            $name = uniqid();
            $email = uniqid();

            $getAccessTokenResponseStream = $this->createMock(StreamInterface::class);
            $getAccessTokenResponseStream
                ->method('__toString')
                ->willReturn(
                    <<<'EOF'
{"access_token":"mock_access_token","expires":"3600","refresh_token":"mock_refresh_token","otherKey":[1234]}
EOF
                );

            $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
            $postResponse
                ->shouldReceive('getBody')
                ->andReturn($getAccessTokenResponseStream);
            $postResponse
                ->shouldReceive('getHeader')
                ->andReturn(['content-type' => 'json']);

            $getResourceOwnerResponseStream = $this->createMock(StreamInterface::class);
            $getResourceOwnerResponseStream
                ->method('__toString')
                ->willReturn(
                    sprintf(
                        '{"sub": "%s", "name": "%s", "email": "%s"}',
                        $userId,
                        $name,
                        $email
                    )
                );

            $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
            $userResponse
                ->shouldReceive('getBody')
                ->andReturn($getResourceOwnerResponseStream);
            $userResponse
                ->shouldReceive('getHeader')
                ->andReturn(['content-type' => 'json']);

            $client = m::mock('GuzzleHttp\ClientInterface');
            $client
                ->shouldReceive('send')
                ->andReturn($postResponse, $userResponse);
            $this->provider->setHttpClient($client);

            $token = $this->provider->getAccessToken(
                'authorization_code',
                [
                    'code' => 'mock_authorization_code',
                    'access_token' => 'mock_access_token',
                ]
            );
            $user = $this->provider->getResourceOwner($token);

            $this->assertEquals($userId, $user->getId());
            $this->assertEquals($userId, $user->toArray()['sub']);
            $this->assertEquals($name, $user->getName());
            $this->assertEquals($name, $user->toArray()['name']);
            $this->assertEquals($email, $user->getEmail());
            $this->assertEquals($email, $user->toArray()['email']);
        }

        public function testErrorResponse()
        {
            $this->expectException(IdentityProviderException::class);

            $accessTokenResponseStream = $this->createMock(StreamInterface::class);
            $accessTokenResponseStream
                ->method('__toString')
                ->willReturn(
                    '{"error": "invalid_grant", "error_description": "Code not found"}'
                );

            $response = m::mock('Psr\Http\Message\ResponseInterface');
            $response
                ->shouldReceive('getBody')
                ->andReturn($accessTokenResponseStream);
            $response
                ->shouldReceive('getHeader')
                ->andReturn(['content-type' => 'json']);

            $client = m::mock('GuzzleHttp\ClientInterface');
            $client
                ->shouldReceive('send')
                ->times(1)
                ->andReturn($response);
            $this->provider->setHttpClient($client);

            $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        }
    }
}
