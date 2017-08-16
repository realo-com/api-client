# Realo API PHP Library

The official PHP library for using [the Realo REST API](https://api.realo.com/docs).

Before using this library, you must have a valid API Key. To get an API Key, please contact your Realo customer success manager.

## Installation

### As a composer dependency (Recommended)

The recommended way to install the Realo PHP API client is through [composer](https://getcomposer.org/download/).

Next, run the `composer` command to install the Realo PHP API client:

```bash
composer require realo/api-client
```

After installing, you need to require Composer's autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

### As a Phar

You may download a ready-to-use version of the Realo API client library as a Phar from our [releases](https://github.com/realo-com/api-client/releases). This includes the API client and all its dependencies.

After downloading, you need to require the bundled autoloader:

```php
require_once 'phar://' . __DIR__ . '/realo-api-client.phar/vendor/autoload.php';
```

## Initialization
#### RealoApi::create(publicKey, privateKey, environment)
* `publicKey`
    * Type: `string`
    * API public key
* `privateKey`
    * Type: `string`
    * API private key
* `environment`
    * Type: `string`
    * API environment (either `production` or `sandbox`)

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

### Use The Bundled CLI Tool
We provide a simple CLI utility which you can use to interact with our API.

```bash
$ php example/simple-cli.php --public-key=xxx --private-key=xxx /valuations/xxx/data/mobility
{
    "data": {
        "mobilityScore": 0.88,
        "distanceToCityCenter": 3130,
        "distanceToBusStop": 323,
        "distanceToTrainStation": 1197,
        "distanceToSchool": 77,
        "distanceToStores": 33,
        "distanceToHighways": 3282,
        "buildingDensity": 1688.01,
        "inhabitantsDensity": 32346.18,
        "transitTypeCityCenter": "cycling-distance",
        "transitTypeBusStop": "walking-distance",
        "transitTypeTrainStation": "walking-distance",
        "transitTypeSchool": "walking-distance",
        "transitTypeStores": "walking-distance"
    }
}
```

### Send An API Call Using The Request Function
We provide a base request function to access any of our API resources.

```php
use Realo\Api\RealoApi;
use Realo\Api\RealoApiException;

$api = RealoApi::create('YOUR_PUBLIC_KEY', 'YOUR_PRIVATE_KEY');
try {
    $response = $api->request('/agencies', 'GET');
    var_dump($response);
} catch (RealoApiException $e) {
    printf("Error %d: %s\n", $e->getCode(), $e->getMessage());
}
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
