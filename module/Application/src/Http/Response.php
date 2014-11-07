<?php

namespace Http;

use Application\Exception as Exception;

class Response implements \ArrayAccess {

	const VERSION_10 = 'HTTP/1.0';
    const VERSION_11 = 'HTTP/1.1';

	const STATUS_CODE_CUSTOM = 0;
    const STATUS_CODE_100 = 100;
    const STATUS_CODE_101 = 101;
    const STATUS_CODE_102 = 102;
    const STATUS_CODE_200 = 200;
    const STATUS_CODE_201 = 201;
    const STATUS_CODE_202 = 202;
    const STATUS_CODE_203 = 203;
    const STATUS_CODE_204 = 204;
    const STATUS_CODE_205 = 205;
    const STATUS_CODE_206 = 206;
    const STATUS_CODE_207 = 207;
    const STATUS_CODE_208 = 208;
    const STATUS_CODE_300 = 300;
    const STATUS_CODE_301 = 301;
    const STATUS_CODE_302 = 302;
    const STATUS_CODE_303 = 303;
    const STATUS_CODE_304 = 304;
    const STATUS_CODE_305 = 305;
    const STATUS_CODE_306 = 306;
    const STATUS_CODE_307 = 307;
    const STATUS_CODE_400 = 400;
    const STATUS_CODE_401 = 401;
    const STATUS_CODE_402 = 402;
    const STATUS_CODE_403 = 403;
    const STATUS_CODE_404 = 404;
    const STATUS_CODE_405 = 405;
    const STATUS_CODE_406 = 406;
    const STATUS_CODE_407 = 407;
    const STATUS_CODE_408 = 408;
    const STATUS_CODE_409 = 409;
    const STATUS_CODE_410 = 410;
    const STATUS_CODE_411 = 411;
    const STATUS_CODE_412 = 412;
    const STATUS_CODE_413 = 413;
    const STATUS_CODE_414 = 414;
    const STATUS_CODE_415 = 415;
    const STATUS_CODE_416 = 416;
    const STATUS_CODE_417 = 417;
    const STATUS_CODE_418 = 418;
    const STATUS_CODE_422 = 422;
    const STATUS_CODE_423 = 423;
    const STATUS_CODE_424 = 424;
    const STATUS_CODE_425 = 425;
    const STATUS_CODE_426 = 426;
    const STATUS_CODE_428 = 428;
    const STATUS_CODE_429 = 429;
    const STATUS_CODE_431 = 431;
    const STATUS_CODE_500 = 500;
    const STATUS_CODE_501 = 501;
    const STATUS_CODE_502 = 502;
    const STATUS_CODE_503 = 503;
    const STATUS_CODE_504 = 504;
    const STATUS_CODE_505 = 505;
    const STATUS_CODE_506 = 506;
    const STATUS_CODE_507 = 507;
    const STATUS_CODE_508 = 508;
    const STATUS_CODE_511 = 511;

    protected $recommendedReasonPhrases = array(
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    );

	protected $sendHeaders = true;

	public $content = '';

	public $headers = array();

	public $cookies = array();

	protected $httpVersion = self::VERSION_11;

	public $statusCode = 200;

	public $reasonPhrase = '';

	public $raw = '';

	public $headersSent = false;

	public function __construct($message = null) {
		if ($message !== null) {
			$this->parseResponse($message);
		}
	}

	public function getSendHeaders() {
		return (boolean)$this->sendHeaders;
	}

	public function setSendHeaders($value) {
		$this->sendHeaders = (boolean)$value;
	}

	public function getContent() {
		return (string)$this->content;
	}

	public function setContent($content) {
		$this->content = $content;
		return $this;
	}

	public function setHeaders($headers = array()) {
		$this->headers = $headers;
		return $this;
	}

	public function getHeader($name, $headers = null) {
		if (!is_array($headers)) {
			$headers =& $this->getHeaders();
		}
		if (isset($headers[$name])) {
			return $headers[$name];
		}
		foreach ($headers as $key => $value) {
			if (strcasecmp($key, $name) == 0) {
				return $value;
			}
		}
		return null;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function getHeaderString() {
		$headers = '';
        foreach ($this->getHeaders() as $fieldValue) {
            if (is_array($fieldValue)) {
                // Handle multi-value headers
                foreach ($fieldValue as $value) {
                    $headers .= $value . "\r\n";
                }
                continue;
            }
            // Handle single-value headers
            $headers .= $fieldValue . "\r\n";
        }
        return $headers;
	}

	public function setCookies($cookies = array()) {
		$this->cookies = $cookies;
		return $this;
	}

	public function getCookies() {
		return $this->cookies;
	}

	public function setVersion($version) {
		if ($version != self::VERSION_10 && $version != self::VERSION_11) {
			throw new Exception(__('Not valid or not supported HTTP httpVersion: %s', array($version)));
		}
		$this->httpVersion = $version;
		return $this;
	}

	public function getVersion() {
		return $this->httpVersion;
	}

    public function setStatusCode($code) {
    	$const = get_class($this) . '::STATUS_CODE_' . $code;
    	if (!is_numeric($code) || !defined($const)) {
    		$code = is_scalar($code) ? $code : gettype($code);
    		throw new Exception(__("Invalid status code: %s", array($code)));
    	}
    	$this->statusCode = (int) $code;
    	return $this;
    }

    public function getStatusCode() {
    	return $this->statusCode;
    }

    public function setReasonPhrase($reasonPhrase) {
    	$this->reasonPhrase = trim($reasonPhrase);
    	return $this;
    }

    public function getReasonPhrase() {
    	if ($this->reasonPhrase == null) {
    		return $this->recommendedReasonPhrases[$this->statusCode];
    	}
    	return $this->reasonPhrase;
    }

    public function isClientError() {
    	$code = $this->getStatusCode();
    	return ($code < 500 && $code >= 400);
    }

    public function isForbidden() {
    	return (403 == $this->getStatusCode());
    }

    public function isInformational() {
    	$code = $this->getStatusCode();
    	return ($code >= 100 && $code < 200);
    }

    public function isNotFound() {
    	return (404 === $this->getStatusCode());
    }

    public function isOK() {
    	return (200 === $this->getStatusCode());
    }

    public function isServerError() {
    	$code = $this->getStatusCode();
    	return (500 <= $code && 600 > $code);
    }

    public function isRedirect() {
    	$code = $this->getStatusCode();
    	return ((300 <= $code && 400 > $code) && !is_null($this->getHeader('Location')));
    }

    public function isSuccess() {
    	$code = $this->getStatusCode();
    	return (200 <= $code && 300 > $code);
    }


	public function parseResponse($message) {
		if (!is_string($message)) {
			throw new Exception(__('Invalid response.'));
		}

		if (!preg_match("/^(.+\r\n)(.*)(?<=\r\n)\r\n/Us", $message, $match)) {
			throw new Exception(__('Invalid HTTP response.'));
		}

		list(, $statusLine, $header) = $match;
		$this->raw = $message;
		$this->setContent((string)substr($message, strlen($match[0])));

		if (preg_match("/(.+) ([0-9]{3}) (.+)\r\n/DU", $statusLine, $match)) {
			$this->setVersion($match[1]);
			$this->setStatusCode($match[2]);
			$this->setReasonPhrase($match[3]);
		}

		$this->setHeaders($this->_parseHeader($header));
		$transferEncoding = $this->getHeader('Transfer-Encoding');
		$decoded = $this->_decodeContent($this->getContent(), $transferEncoding);
		$this->setContent($decoded['content']);

		if (!empty($decoded['header'])) {
			$this->setHeaders($this->_parseHeader($this->_buildHeader($this->headers) . $this->_buildHeader($decoded['header'])));
		}

		$headers = $this->getHeaders();
		if (!empty($headers)) {
			$this->setCookies($this->parseCookies($this->getHeaders()));
		}
	}

	protected function _decodeContent($content, $encoding = 'chunked') {
		if (!is_string($content)) {
			return false;
		}
		if (empty($encoding)) {
			return array('content' => $content, 'header' => false);
		}
		$decodeMethod = '_decode' . ucwords(str_replace('-', ' ', $encoding)) . 'Body';

		if (!is_callable(array(&$this, $decodeMethod))) {
			return array('content' => $content, 'header' => false);
		}
		return $this->{$decodeMethod}($content);
	}

	protected function _decodeChunkedBody($content) {
		if (!is_string($content)) {
			return false;
		}

		$decodedBody = null;
		$chunkLength = null;

		while ($chunkLength !== 0) {
			if (!preg_match('/^([0-9a-f]+) *(?:;(.+)=(.+))?(?:\r\n|\n)/iU', $content, $match)) {
				throw new Exception(__('Socket::_decodeChunkedBody - Could not parse malformed chunk.'));
			}

			$chunkSize = 0;
			$hexLength = 0;
			$chunkExtensionName = '';
			$chunkExtensionValue = '';
			if (isset($match[0])) {
				$chunkSize = $match[0];
			}
			if (isset($match[1])) {
				$hexLength = $match[1];
			}
			if (isset($match[2])) {
				$chunkExtensionName = $match[2];
			}
			if (isset($match[3])) {
				$chunkExtensionValue = $match[3];
			}

			$content = substr($content, strlen($chunkSize));
			$chunkLength = hexdec($hexLength);
			$chunk = substr($content, 0, $chunkLength);
			if (!empty($chunkExtensionName)) {
				 // @todo See if there are popular chunk extensions we should implement
			}
			$decodedBody .= $chunk;
			if ($chunkLength !== 0) {
				$content = substr($content, $chunkLength + strlen("\r\n"));
			}
		}

		$entityHeader = false;
		if (!empty($content)) {
			$entityHeader = $this->_parseHeader($content);
		}
		return array('content' => $decodedBody, 'header' => $entityHeader);
	}

	protected function _parseHeader($header) {
		if (is_array($header)) {
			return $header;
		} elseif (!is_string($header)) {
			return false;
		}

		preg_match_all("/(.+):(.+)(?:(?<![\t ])\r\n|\$)/Uis", $header, $matches, PREG_SET_ORDER);

		$header = array();
		foreach ($matches as $match) {
			list(, $field, $value) = $match;

			$value = trim($value);
			$value = preg_replace("/[\t ]\r\n/", "\r\n", $value);

			$field = $this->_unescapeToken($field);

			if (!isset($header[$field])) {
				$header[$field] = $value;
			} else {
				$header[$field] = array_merge((array)$header[$field], (array)$value);
			}
		}
		return $header;
	}

	public function parseCookies($header) {
		$cookieHeader = $this->getHeader('Set-Cookie', $header);
		if (!$cookieHeader) {
			return false;
		}

		$cookies = array();
		foreach ((array)$cookieHeader as $cookie) {
			if (strpos($cookie, '";"') !== false) {
				$cookie = str_replace('";"', "{__cookie_replace__}", $cookie);
				$parts = str_replace("{__cookie_replace__}", '";"', explode(';', $cookie));
			} else {
				$parts = preg_split('/\;[ \t]*/', $cookie);
			}

			list($name, $value) = explode('=', array_shift($parts), 2);
			$cookies[$name] = compact('value');

			foreach ($parts as $part) {
				if (strpos($part, '=') !== false) {
					list($key, $value) = explode('=', $part);
				} else {
					$key = $part;
					$value = true;
				}

				$key = strtolower($key);
				if (!isset($cookies[$name][$key])) {
					$cookies[$name][$key] = $value;
				}
			}
		}
		return $cookies;
	}

	protected function _unescapeToken($token, $chars = null) {
		$regex = '/"([' . implode('', $this->_tokenEscapeChars(true, $chars)) . '])"/';
		$token = preg_replace($regex, '\\1', $token);
		return $token;
	}

	protected function _tokenEscapeChars($hex = true, $chars = null) {
		if (!empty($chars)) {
			$escape = $chars;
		} else {
			$escape = array('"', "(", ")", "<", ">", "@", ",", ";", ":", "\\", "/", "[", "]", "?", "=", "{", "}", " ");
			for ($i = 0; $i <= 31; $i++) {
				$escape[] = chr($i);
			}
			$escape[] = chr(127);
		}

		if ($hex == false) {
			return $escape;
		}
		foreach ($escape as $key => $char) {
			$escape[$key] = '\\x' . str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
		}
		return $escape;
	}

	public function offsetExists($offset) {
		return in_array($offset, array('raw', 'status', 'header', 'content', 'cookies'));
	}

	public function offsetGet($offset) {
		switch ($offset) {
			case 'raw':
				$firstLineLength = strpos($this->raw, "\r\n") + 2;
				if ($this->raw[$firstLineLength] === "\r") {
					$header = null;
				} else {
					$header = substr($this->raw, $firstLineLength, strpos($this->raw, "\r\n\r\n") - $firstLineLength) . "\r\n";
				}
				return array(
					'status-line' => $this->getVersion() . ' ' . $this->getStatusCode() . ' ' . $this->getReasonPhrase() . "\r\n",
					'header' => $header,
					'content' => $this->getContent(),
					'response' => $this->raw
				);
			case 'status':
				return array(
					'http-httpVersion' => $this->getVersion(),
					'code' => $this->getStatusCode(),
					'reason-phrase' => $this->getReasonPhrase()
				);
			case 'header':
				return $this->getHeaders();
			case 'content':
				return $this->getContent();
			case 'cookies':
				return $this->getCookies();
		}
		return null;
	}

	public function offsetSet($offset, $value) {
	}


	public function offsetUnset($offset) {
	}

	public function renderStatusLine() {
        $status = sprintf(
            '%s %d %s',
            $this->getVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        return trim($status);
    }

	public function send() {
		if ($this->headersSent) {
            return $this;
        }

        $status  = $this->renderStatusLine();
        if ($this->getSendHeaders()) {
	        header($status);
	        foreach ($this->getHeaders() as $header) {
	        	header($header);
	        }
    	}
        $this->headersSent = true;
        return $this;
	}

	public function __toString() {
		return $this->getContent();
	}

}
