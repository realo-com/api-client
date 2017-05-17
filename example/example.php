<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Realo\Api\RealoApi;
use Realo\Api\RealoApiException;

$api = RealoApi::create('YOUR_PUBLIC_KEY', 'YOUR_PRIVATE_KEY');
try {
	$response = $api->request('/agencies', 'GET');
	var_dump($response);
} catch (RealoApiException $e) {
	printf("Error %d: %s\n", $e->getCode(), $e->getMessage());
}
