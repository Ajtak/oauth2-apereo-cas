<?php

namespace Ajtak\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

class ApereoCas extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Keycloak URL, eg. http://localhost:8080/auth.
     *
     * @var string
     */
    public $authServerUrl = null;

    /**
     * Constructs an OAuth 2.0 service provider.
     *
     * @param array $options An array of options to set on this provider.
     *     Options include `clientId`, `clientSecret`, `redirectUri`, and `state`.
     *     Individual providers may introduce more options, as needed.
     * @param array $collaborators An array of collaborators that may be used to
     *     override this provider's default behavior. Collaborators include
     *     `grantFactory`, `requestFactory`, `httpClient`, and `randomFactory`.
     *     Individual providers may introduce more collaborators, as needed.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
    }

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl(): string
    {
        return $this->getBaseUrl() . '/oidc/authorize';
    }

    /**
     * Get access token url to retrieve token
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getBaseUrl() . '/oidc/accessToken';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getBaseUrl() . '/oidc/profile';
    }

    /**
     * Creates base url from provider configuration.
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        return $this->authServerUrl;
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return string[]
     */
    protected function getDefaultScopes()
    {
        $scopes = [
            'openid',
            'profile',
            'email'
        ];


        return $scopes;
    }

    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ','
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }


    /**
     * Check a provider response for errors.
     *
     * @param ResponseInterface $response
     * @param string $data Parsed response data
     * @return void
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $error = $data['error'];
            if (isset($data['error_description'])) {
                $error .= ': ' . $data['error_description'];
            }
            throw new IdentityProviderException($error, 0, $data);
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return ApereoCasResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ApereoCasResourceOwner($response);
    }

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @param AccessToken $token
     * @return ApereoCasResourceOwner
     */
    public function getResourceOwner(AccessToken $token)
    {
        $response = $this->fetchResourceOwnerDetails($token);

        return $this->createResourceOwner($response, $token);
    }

    /**
     * Parses the response according to its content-type header.
     *
     * @param ResponseInterface $response
     * @return array
     * @throws UnexpectedValueException
     */
    protected function parseResponse(ResponseInterface $response): array
    {
        return parent::parseResponse($response);
    }
}
