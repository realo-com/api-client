<?php
namespace Realo\Api;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RealoApi
{
	const DEFAULT_USER_AGENT = 'RealoApiClient/1.0.3';
	const PRODUCTION_ENVIRONMENT = 'production';
	const SANDBOX_ENVIRONMENT = 'sandbox';
	private static $ENVIRONMENT_TO_URL_MAPPING = [
		self::PRODUCTION_ENVIRONMENT => 'https://api.realo.com/1.0/',
		self::SANDBOX_ENVIRONMENT => 'https://api-sandbox.realo.com/1.0/',
	];

	/**
	 * The public key used to identify the API consumer.
	 *
	 * @var string
	 */
	protected $publicKey;

	/**
	 * The private key used to sign the API requests.
	 *
	 * @var string
	 */
	protected $privateKey;

	/**
	 * The HTTP client instance.
	 *
	 * @var Client
	 */
	protected $client;

	/**
	 * RealoApi constructor.
	 * @param string $publicKey
	 * @param string $privateKey
	 * @param Client $client
	 */
	protected function __construct($publicKey, $privateKey, Client $client)
	{
		$this->publicKey = $publicKey;
		$this->privateKey = $privateKey;
		$this->client = $client;
	}

	/**
	 * @param string $publicKey
	 * @param string $privateKey
	 * @param string $environment
	 * @return RealoApi
	 */
	public static function create($publicKey, $privateKey, $environment = self::PRODUCTION_ENVIRONMENT)
	{
		if (!isset(self::$ENVIRONMENT_TO_URL_MAPPING[$environment])) {
			throw new \InvalidArgumentException("Unsupported environment '$environment', please use any of [" . implode(", ", array_keys(self::$ENVIRONMENT_TO_URL_MAPPING)) . "]");
		}

		$client = new Client([
			'base_uri' => self::$ENVIRONMENT_TO_URL_MAPPING[$environment],
			'headers' => [
				'User-Agent' => self::DEFAULT_USER_AGENT
			]
		]);

		return self::createWithClient($publicKey, $privateKey, $client);
	}

	/**
	 * @param string $publicKey
	 * @param string $privateKey
	 * @param Client $client
	 * @return RealoApi
	 */
	public static function createWithClient($publicKey, $privateKey, Client $client)
	{
		return new self($publicKey, $privateKey, $client);
	}

	/**
	 * @param RequestInterface $request
	 * @return RequestInterface
	 */
	protected function signRequest(RequestInterface $request)
	{
		// Sign the request and add authorization header.
		$header = $this->getSigningHeaders($request->getUri(), $request->getMethod(), $request->getBody());
		foreach ($header as $key => $value) {
			return $request->withAddedHeader($key, $value);
		}
	}

	/**
	 * @param $path
	 * @param $method
	 * @param null $payload
	 * @return array
	 */
	protected function getSigningHeaders($path, $method, $payload = null)
	{
		// Sign the request and add authorization header.
		$baseString = strtoupper($method) . '&';
		$baseString .= (string) $path . '&';
		$baseString .= (string) $payload;
		$signature = base64_encode(hash_hmac('sha256', $baseString, $this->privateKey, true));
		return ['Authorization' =>  'Realo key="' . $this->publicKey . '", version="1.0", signature="' . $signature . '"'];
	}

	/**
	 * @param ResponseInterface $response
	 * @return mixed
	 */
	protected function decodeResponse(ResponseInterface $response)
	{
		if (preg_match('|^application/json|', $response->getHeader('Content-Type')[0])) {
			return json_decode((string) $response->getBody(), true);
		} else {
			return (string) $response->getBody();
		}
	}

	/**
	 * @param string $path
	 * @param string $method
	 * @param array|null $payload
	 * @param array $headers
	 * @return Request
	 */
	public function buildRequest($path, $method = 'GET', array $payload = null, array $headers = [])
	{
		// Create request
		$headers['Content-Type'] = 'application/json';
		$request = new Request(
			strtoupper($method),
			$this->client->getConfig('base_uri') . ltrim($path, '/'),
			$headers,
			($payload !== null ? json_encode($payload) : null)
		);

		// Sign the request and add authorization header.
		$request = $this->signRequest($request);

		return $request;
	}

	/**
	 * @param string $path
	 * @param string $method
	 * @param array|null $payload
	 * @param array $headers
	 * @return mixed
	 * @throws RealoApiException
	 */
	public function request($path, $method = 'GET', array $payload = null, array $headers = [])
	{
		$request = $this->buildRequest($path, $method, $payload, $headers);

		try {
			// Send the request to the API.
			$response = $this->client->send($request);
		} catch (RequestException $e) {
			throw new RealoApiException($e);
		}

		return $this->decodeResponse($response);
	}

//	/**
//	 * @param Request[] $requests
//	 * @return mixed[]
//	 * @throws RealoApiException
//	 */
//	public function doMultipleRequests(array $requests)
//	{
//		try {
//			$responses = $this->client->send($requests);
//		} catch (RequestException $e) {
//			throw new RealoApiException($e);
//		}
//
//		return array_map([$this, 'decodeResponse'], $responses);
//	}

	/**
	 * @param array $requests ['path', 'method', 'params', 'headers']
	 * @return array [http_code, body]
	 */
	public function multiRequest($requests)
	{
		//Use curl instead
		$arrCurls = [];
		$resMultiCurl = curl_multi_init();
		$arrReturn = [];
		foreach ($requests as $requestKey => $request) {
			$resCurl = $this->buildCurlRequest(
				$request['path'],
				$request['method'],
				isset($request['params']) ? $request['params'] : null, isset($request['headers']) ? $request['headers'] : []
			);

			$arrCurls[$requestKey] = $resCurl;
			curl_multi_add_handle($resMultiCurl, $resCurl);
		}
		$intActive = null;

		do {
			$intMultiCurlStatus = curl_multi_exec($resMultiCurl, $intActive);
		} while ($intMultiCurlStatus === CURLM_CALL_MULTI_PERFORM);

		do {
			curl_multi_exec($resMultiCurl, $intActive);
			curl_multi_select($resMultiCurl);
		} while ($intActive && $intMultiCurlStatus === CURLM_OK);

		foreach($arrCurls as $requestKey => $resCurl) {
			$http_code = curl_getinfo($resCurl, CURLINFO_HTTP_CODE);
			$arrReturn[$requestKey] = [$http_code, $this->decodeCurlResponse(curl_multi_getcontent($resCurl))];
		}

		return $arrReturn;
	}

	/**
	 * @param $path
	 * @param string $method
	 * @param array|null $payload
	 * @param array $headers
	 * @return resource
	 */
	protected function buildCurlRequest($path, $method = 'GET', array $payload = null, array $headers = [])
	{
		// Create request
		$headers['Content-Type'] = 'application/json';
		$payload = $payload ? http_build_query($payload) : null;

		$path = $this->client->getConfig('base_uri') . ltrim($path, '/');

		// Sign the request and add authorization header.
		$headers += $this->getSigningHeaders($path, $method,$payload);

		$resCurl = curl_init();

		if ($method === 'POST') {
			curl_setopt($resCurl, CURLOPT_POST, 1);
			curl_setopt($resCurl, CURLOPT_POSTFIELDS, $payload);
		}

		curl_setopt_array(
			$resCurl,
			array(
				CURLOPT_URL => $path,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => true,
				CURLOPT_FOLLOWLOCATION => true,
			)
		);

		$headerStrings = [];
		foreach ($headers as $headerKey => $headerValue) {
			$headerStrings[] = $headerKey.': '.$headerValue;
		}
		if (!empty($headerStrings)) {
			curl_setopt($resCurl, CURLOPT_HTTPHEADER, $headerStrings);
		}

		return $resCurl;
	}

	/**
	 * @param string $result
	 * @return mixed|string
	 */
	protected function decodeCurlResponse($result)
	{
		list($header_text, $body) = explode("\r\n\r\n", $result, 2);

		$headers = [];
		foreach (explode("\r\n", $header_text) as $i => $line) {
			if ($i === 0) {
				$headers['http_code'] = $line;
			}
			else {
				list ($key, $value) = explode(': ', $line);

				if (stripos($value, ';') !== false) {
					$value = explode(';', $value);
				}

				$headers[$key] = $value;
			}
		}

		if (preg_match('|^application/json|', $headers['Content-Type'][0])) {
			return json_decode((string) $body, true);
		} else {
			return (string) $body;
		}
	}
}
