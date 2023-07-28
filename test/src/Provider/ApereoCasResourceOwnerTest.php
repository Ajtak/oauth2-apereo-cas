<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\ApereoCasResourceOwner;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ApereoCasResourceOwnerTest extends TestCase
{
    public function testUrlIsNullWithoutDomainOrNickname(): void
    {
        $user = new ApereoCasResourceOwner();

        $url = $user->getUrl();

        $this->assertNull($url);
    }

    public function testUrlIsDomainWithoutNickname(): void
    {
        $domain = uniqid();
        $user = new ApereoCasResourceOwner();
        $user->setDomain($domain);

        $url = $user->getUrl();

        $this->assertEquals($domain, $url);
    }

    public function testUrlIsNicknameWithoutDomain(): void
    {
        $nickname = uniqid();
        $user = new ApereoCasResourceOwner(['login' => $nickname]);

        $url = $user->getUrl();

        $this->assertEquals($nickname, $url);
    }
}
