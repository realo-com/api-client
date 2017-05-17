# Realo API PHP Library

The official PHP library for using [the Realo REST API](https://api.realo.com/docs/).

Before using this library, you must have a valid API Key. To get an API Key, please contact your Realo customer success manager.

## Installation
The recommended way to install the Realo PHP API client is through (composer)[https://getcomposer.org].
See [here](https://getcomposer.org/download/) how to install it.

Next, run the `composer` command to install the Realo PHP API client:

```bash
composer require realo/api-client
```

After installing, you need to require Composer's autoloader:

```php
require __DIR__ . '/vendor/autoload.php';

use Realo\Api\RealoApi;
```

## Initialization
#### RealoApi::create(publicKey, privateKey)
* `publicKey`
    * Type: `string`
    * API public key
* `privateKey`
    * Type: `string`
    * API private key

#### RealoApi::createWithClient(publicKey, privateKey, client)
* `publicKey`
    * Type: `string`
    * API public key
* `privateKey`
    * Type: `string`
    * API private key
* `client`
    * Type: `GuzzleHttp\Client`
    * Guzzle HTTP client, used for communicating with the REST API

## Methods
### request(path, method, [, payload [, headers]])
* `path`
    * Type: `string`
    * The path of the resource
* `method`
    * Type: `string`
    * HTTP method for request
* `payload`
    * Type: `array`
    * HTTP payload (to be encoded as JSON), only applies when the HTTP method is not `GET`
* `headers`
    * Type: `array`
    * Custom headers to be sent with the request.

## Examples

### Send An API Call Using The Request Function
We provide a base request function to access any of our API resources.
```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Realo\Api\RealoApi;
use Realo\Api\RealoApiException;

$api = RealoApi::create('YOUR_PUBLIC_KEY', 'YOUR_PRIVATE_KEY');
try {
    $response = $api->request('/agencies', 'GET');
    var_dump($response);
} catch (RealoApiException $e) {
    printf("Error %d: %s\n", $e->getCode(), $e->getMessage());
}
?>
```

## Exceptions
An exception will be thrown in two cases: there is a problem with the request or the server returns a status code of `400` or higher.

### RealoApiException
* **getCode()**
    * Returns the response status code of `400` or higher.
* **getMessage()**
    * Returns the exception message.
* **getErrors()**
    * If there is a response body containing an `array` of errors, return these. Otherwise returns `null`.
* **getType()**
    * If there is a response body containing an error, return its type. Otherwise returns `null`.
