<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Realo\Api\RealoApi;
use Realo\Api\RealoApiException;

function pretty_print($data)
{
	global $forcePrint;

	if (is_string($data)) {
		// not JSON
		if ($forcePrint) {
			print($data);
		} else {
			fprintf(STDERR, "Warning: the response is probably binary data, if you do want to continue re-run this command with -f.\n");
		}
		return;
	} else {
		// print JSON
		print(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
	}
}

function print_usage()
{
	global $argv;
	fprintf(STDERR, "Usage: %s [-m|--http-method=...] [-b|--http-body=...] [-f|--force] <--public-key=...> <--private-key=...> <PATH>\n", $argv[0]);
}

$options = getopt('m::b::f', ['public-key:', 'private-key:', 'http-method::', '--http-body::', '--force']);
if ($options === false) {
	print_usage();
	exit(1);
}

$publicKey = null;
$privateKey = null;
$httpMethod = 'GET';
$httpPath = $argv[$argc - 1];
$httpBody = null;
$forcePrint = false;
foreach ($options as $k => $v) {
	switch ($k) {
		case 'public-key':
			$publicKey = $v;
			break;
		case 'private-key':
			$privateKey = $v;
			break;
		case 'm':
		case 'http-method':
			$httpMethod = $v;
			break;
		case 'b':
		case 'http-body':
			$httpBody = $v;
			break;
		case 'f':
		case 'force':
			$forcePrint = true;
			break;
	}
}

if ($publicKey === null || $privateKey === null || $httpPath === null) {
	print_usage();
	exit(1);
}

$api = RealoApi::create($options['public-key'], $options['private-key']);
try {
	$response = $api->request($httpPath, $httpMethod, $httpBody);
	pretty_print($response);
	exit(0);
} catch (RealoApiException $ex) {
	if ($ex->getErrors() !== null) {
		$response = ['errors' => $ex->getErrors()];
		pretty_print($response);
	} else {
		fprintf(STDERR,"Error %d: %s\n", $ex->getCode(), $ex->getMessage());
	}
	exit(1);
}
