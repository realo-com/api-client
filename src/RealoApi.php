<?php
namespace Realo\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\json_decode, GuzzleHttp\json_encode;

class RealoApi
{
	const DEFAULT_USER_AGENT = 'RealoApiClient/1.0';
	const API_BASE_URI = 'https://api.realo.com/1.0/';

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
	 * @return RealoApi
	 */
	public static function create($publicKey, $privateKey)
	{
		$client = new Client([
			'base_uri' => self::API_BASE_URI,
			'defaults' => [
				'headers' => [
					'User-Agent' => self::DEFAULT_USER_AGENT
				]
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
		$baseString = strtoupper($request->getMethod()) . '&';
		$baseString .= (string) $request->getUri() . '&';
		$baseString .= (string) $request->getBody();
		$signature = base64_encode(hash_hmac('sha256', $baseString, $this->privateKey, true));
		return $request->withAddedHeader('Authorization', 'Realo key="' . $this->publicKey . '", version="1.0", signature="' . $signature . '"');
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
	 * @return array
	 * @throws RealoApiException
	 */
	public function request($path, $method = 'GET', array $payload = null, array $headers = [])
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

		try {
			// Send the request to the API.
			$response = $this->client->send($request);
		} catch (RequestException $e) {
			throw new RealoApiException($e);
		}

		return $this->decodeResponse($response);
	}
}
