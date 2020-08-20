# eBay Provider for OAuth 2.0 Client

This package provides an eBay OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require nitromike502/ebay-api-oauth2
```

## Usage

Usage is the same as The League's OAuth client, using `\Nitromike502\OAuth2\Client\Provider\Ebay` as the provider.

### Authorization Code Flow

```php
$provider = new Nitromike502\OAuth2\Client\Provider\Ebay([
    'clientId'          => '{ebay-client-id}',
    'clientSecret'      => '{ebay-client-secret}',
    'redirectUri'       => '{redirect-code}'
]);
```
For further usage of this package please refer to the [core package documentation on "Authorization Code Grant"](https://github.com/thephpleague/oauth2-client#usage).

### Refreshing a Token

```php
$provider = new Nitromike502\OAuth2\Client\Provider\Ebay([
    'clientId'          => '{ebay-client-id}',
    'clientSecret'      => '{ebay-client-secret}',
    'redirectUri'       => '{redirect-code}'
]);

$existingAccessToken = getAccessTokenFromYourDataStore();

if ($existingAccessToken->hasExpired()) {
    $newAccessToken = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $existingAccessToken->getRefreshToken()
    ]);

    // Purge old access token and store new access token to your data store.
}
```

For further usage of this package please refer to the [core package documentation on "Refreshing a Token"](https://github.com/thephpleague/oauth2-client#refreshing-a-token).

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/nitromike502/ebay-api-oauth2/blob/master/LICENSE) for more information.
