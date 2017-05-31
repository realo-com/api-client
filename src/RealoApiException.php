<?php
namespace Realo\Api;

use GuzzleHttp\Exception\RequestException;
use function GuzzleHttp\json_decode;

class RealoApiException extends \RuntimeException
{
	/**
	 * @var string|null
	 */
	protected $type;

	/**
	 * @var array|null
	 */
	protected $errors;

	public function __construct(RequestException $previous)
	{
		// Get the exception information.
		$response = $previous->getResponse();
		$code = $previous->getCode();
		$message = $previous->getMessage();

		$this->errors = null;
		$this->type = null;

		if ($response && preg_match('|^application/json|', $response->getHeader('Content-Type')[0])) {
			$body = json_decode((string) $response->getBody(), true);
			if (isset($body['errors'])) {
				$this->errors = $body['errors'];
			}
		}

		if ($this->errors[0]) {
			$this->type = $this->errors[0]['type'];
			$message = $this->errors[0]['type'] . ': ' . $this->errors[0]['message'];
		}

		parent::__construct($message, $code, $previous);
	}

	/**
	 * @return string|null
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return array|null
	 */
	public function getErrors()
	{
		return $this->errors;
	}
}
