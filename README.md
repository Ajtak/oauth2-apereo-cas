# Apereo CAS Provider for OAuth 2.0 Client
[![Latest Version](https://img.shields.io/github/release/ajtak/oauth2-apereo-cas.svg?style=flat-square)](https://github.com/ajtak/oauth2-apereo-cas/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/ajtak/oauth2-apereo-cas/master.svg?style=flat-square)](https://travis-ci.org/ajtak/oauth2-apereo-cas)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/ajtak/oauth2-apereo-cas.svg?style=flat-square)](https://scrutinizer-ci.com/g/ajtak/oauth2-apereo-cas/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/ajtak/oauth2-apereo-cas.svg?style=flat-square)](https://scrutinizer-ci.com/g/ajtak/oauth2-apereo-cas)
[![Total Downloads](https://img.shields.io/packagist/dt/ajtak/oauth2-apereo-cas.svg?style=flat-square)](https://packagist.org/packages/ajtak/oauth2-apereo-cas)

This package provides Apereo CAS OAuth 2.0 / OIDC support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require ajtak/oauth2-apereo-cas
```

## Usage

Usage is the same as The League's OAuth client, using `\Ajtak\OAuth2\Client\Provider\ApereoCas` as the provider.

### Authorization Code Flow

```php
$provider = new Ajtak\OAuth2\Client\Provider\ApereoCas([
    'authServerUrl'     => 'cas-server-url',
    'clientId'          => '{cas-client-id}',
    'clientSecret'      => '{cas-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s (%s)!', $user->getName(), $user->getEmail());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/ajtak/oauth2-apereo-cas/blob/master/CONTRIBUTING.md) for details.

## Credits
- [Jakub Fridrich](https://github.com/ajtak)
- [Steven Maguire](https://github.com/stevenmaguire)
- [All Contributors](https://github.com/ajtak/oauth2-apereo-cas/contributors)


## License
The MIT License (MIT). Please see [License File](https://github.com/ajtak/oauth2-apereo-cas/blob/master/LICENSE) for more information.
