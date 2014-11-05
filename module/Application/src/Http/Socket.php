<?php

namespace Http;

use Http\Socket\AbstractSocket,
	Http\Socket\SocketException;

class Socket extends AbstractSocket {

	public $quirksMode = false;


	public $request = array(
		'method' => 'GET',
		'uri' => array(
			'scheme' => 'http',
			'host' => null,
			'port' => 80,
			'user' => null,
			'pass' => null,
			'path' => null,
			'query' => null,
			'fragment' => null
		),
		'version' => '1.1',
		'body' => '',
		'line' => null,
		'header' => array(
			'Connection' => 'close',
			'User-Agent' => 'TORO'
		),
		'raw' => null,
		'redirect' => false,
		'cookies' => array()
	);

	public $response = null;

	public $responseClass = 'Http\Response';

	public $config = array(
		'persistent' => false,
		'host' => 'localhost',
		'protocol' => 'tcp',
		'port' => 80,
		'timeout' => 10,
		'request' => array(
			'uri' => array(
				'scheme' => array('http', 'https'),
				'host' => 'localhost',
				'port' => array(80, 443)
			),
			'redirect' => false,
			'cookies' => array()
		)
	);

	protected $_auth = array();

	protected $_proxy = array();

	protected $_contentResource = null;

	public function __construct($config = array()) {
		if (is_string($config)) {
			$this->_configUri($config);
		} elseif (is_array($config)) {
			if (isset($config['request']['uri']) && is_string($config['request']['uri'])) {
				$this->_configUri($config['request']['uri']);
				unset($config['request']['uri']);
			}
			$this->config = array_merge_recursive($this->config, $config);
			//$this->config = merge($this->config, $config);
		}
		parent::__construct($this->config);
	}

	public function configAuth($method, $user = null, $pass = null) {
		if (empty($method)) {
			$this->_auth = array();
			return;
		}
		if (is_array($user)) {
			$this->_auth = array($method => $user);
			return;
		}
		$this->_auth = array($method => compact('user', 'pass'));
	}

	public function configProxy($host, $port = 3128, $method = null, $user = null, $pass = null) {
		if (empty($host)) {
			$this->_proxy = array();
			return;
		}
		if (is_array($host)) {
			$this->_proxy = $host + array('host' => null);
			return;
		}
		$this->_proxy = compact('host', 'port', 'method', 'user', 'pass');
	}

	public function setContentResource($resource) {
		if ($resource === false) {
			$this->_contentResource = null;
			return;
		}
		if (!is_resource($resource)) {
			throw new SocketException(__d('cake_dev', 'Invalid resource.'));
		}
		$this->_contentResource = $resource;
	}

	public function request($request = array()) {
		$this->reset(false);

		if (is_string($request)) {
			$request = array('uri' => $request);
		} elseif (!is_array($request)) {
			return false;
		}

		if (!isset($request['uri'])) {
			$request['uri'] = null;
		}
		$uri = $this->_parseUri($request['uri']);
		if (!isset($uri['host'])) {
			$host = $this->config['host'];
		}
		if (isset($request['host'])) {
			$host = $request['host'];
			unset($request['host']);
		}
		$request['uri'] = $this->url($request['uri']);
		$request['uri'] = $this->_parseUri($request['uri'], true);
		$this->request = merge($this->request, array_diff_key($this->config['request'], array('cookies' => true)), $request);
		#$this->request = merge($this->request, array_diff_key($this->config['request'], array('cookies' => true)), $request);

		$this->_configUri($this->request['uri']);

		$Host = $this->request['uri']['host'];
		if (!empty($this->config['request']['cookies'][$Host])) {
			if (!isset($this->request['cookies'])) {
				$this->request['cookies'] = array();
			}
			if (!isset($request['cookies'])) {
				$request['cookies'] = array();
			}
			$this->request['cookies'] = array_merge($this->request['cookies'], $this->config['request']['cookies'][$Host], $request['cookies']);
		}

		if (isset($host)) {
			$this->config['host'] = $host;
		}
		$this->_setProxy();
		$this->request['proxy'] = $this->_proxy;

		$cookies = null;

		if (is_array($this->request['header'])) {
			if (!empty($this->request['cookies'])) {
				$cookies = $this->buildCookies($this->request['cookies']);
			}
			$scheme = '';
			$port = 0;
			if (isset($this->request['uri']['scheme'])) {
				$scheme = $this->request['uri']['scheme'];
			}
			if (isset($this->request['uri']['port'])) {
				$port = $this->request['uri']['port'];
			}
			if (
				($scheme === 'http' && $port != 80) ||
				($scheme === 'https' && $port != 443) ||
				($port != 80 && $port != 443)
			) {
				$Host .= ':' . $port;
			}
			$this->request['header'] = array_merge(compact('Host'), $this->request['header']);
		}

		if (isset($this->request['uri']['user'], $this->request['uri']['pass'])) {
			$this->configAuth('Basic', $this->request['uri']['user'], $this->request['uri']['pass']);
		}
		$this->_setAuth();
		$this->request['auth'] = $this->_auth;

		if (is_array($this->request['body'])) {
			$this->request['body'] = http_build_query($this->request['body']);
		}

		if (!empty($this->request['body']) && !isset($this->request['header']['Content-Type'])) {
			$this->request['header']['Content-Type'] = 'application/x-www-form-urlencoded';
		}

		if (!empty($this->request['body']) && !isset($this->request['header']['Content-Length'])) {
			$this->request['header']['Content-Length'] = strlen($this->request['body']);
		}

		$connectionType = null;
		if (isset($this->request['header']['Connection'])) {
			$connectionType = $this->request['header']['Connection'];
		}
		$this->request['header'] = $this->_buildHeader($this->request['header']) . $cookies;

		if (empty($this->request['line'])) {
			$this->request['line'] = $this->_buildRequestLine($this->request);
		}

		if ($this->quirksMode === false && $this->request['line'] === false) {
			return false;
		}

		$this->request['raw'] = '';
		if ($this->request['line'] !== false) {
			$this->request['raw'] = $this->request['line'];
		}

		if ($this->request['header'] !== false) {
			$this->request['raw'] .= $this->request['header'];
		}

		$this->request['raw'] .= "\r\n";
		$this->request['raw'] .= $this->request['body'];

		$this->write($this->request['raw']);

		$response = null;
		$inHeader = true;
		while ($data = $this->read()) {
			if ($this->_contentResource) {
				if ($inHeader) {
					$response .= $data;
					$pos = strpos($response, "\r\n\r\n");
					if ($pos !== false) {
						$pos += 4;
						$data = substr($response, $pos);
						fwrite($this->_contentResource, $data);

						$response = substr($response, 0, $pos);
						$inHeader = false;
					}
				} else {
					fwrite($this->_contentResource, $data);
					fflush($this->_contentResource);
				}
			} else {
				$response .= $data;
			}
		}

		if ($connectionType === 'close') {
			$this->disconnect();
		}

		// if (!class_exists($this->responseClass)) {
		// 	throw new SocketException(__('Class %s not found.', array($this->responseClass)));
		// }
		$this->response = new $this->responseClass($response);
		if (!empty($this->response->cookies)) {
			if (!isset($this->config['request']['cookies'][$Host])) {
				$this->config['request']['cookies'][$Host] = array();
			}
			$this->config['request']['cookies'][$Host] = array_merge($this->config['request']['cookies'][$Host], $this->response->cookies);
		}

		if ($this->request['redirect'] && $this->response->isRedirect()) {
			$request['uri'] = $this->response->getHeader('Location');
			$request['redirect'] = is_int($this->request['redirect']) ? $this->request['redirect'] - 1 : $this->request['redirect'];
			$this->response = $this->request($request);
		}
		
		return $this->response;
	}

	public function get($uri = null, $query = array(), $request = array()) {
		if (!empty($query)) {
			$uri = $this->_parseUri($uri, $this->config['request']['uri']);
			if (isset($uri['query'])) {
				$uri['query'] = array_merge($uri['query'], $query);
			} else {
				$uri['query'] = $query;
			}
			$uri = $this->_buildUri($uri);
		}

		$request = merge(array('method' => 'GET', 'uri' => $uri), $request);
		return $this->request($request);
	}

	public function post($uri = null, $data = array(), $request = array()) {
		$request = merge(array('method' => 'POST', 'uri' => $uri, 'body' => $data), $request);
		return $this->request($request);
	}

	public function put($uri = null, $data = array(), $request = array()) {
		$request = merge(array('method' => 'PUT', 'uri' => $uri, 'body' => $data), $request);
		return $this->request($request);
	}

	public function delete($uri = null, $data = array(), $request = array()) {
		$request = merge(array('method' => 'DELETE', 'uri' => $uri, 'body' => $data), $request);
		return $this->request($request);
	}

	public function url($url = null, $uriTemplate = null) {
		if (is_null($url)) {
			$url = '/';
		}
		if (is_string($url)) {
			$scheme = $this->config['request']['uri']['scheme'];
			if (is_array($scheme)) {
				$scheme = $scheme[0];
			}
			$port = $this->config['request']['uri']['port'];
			if (is_array($port)) {
				$port = $port[0];
			}
			if ($url{0} == '/') {
				$url = $this->config['request']['uri']['host'] . ':' . $port . $url;
			}
			if (!preg_match('/^.+:\/\/|\*|^\//', $url)) {
				$url = $scheme . '://' . $url;
			}
		} elseif (!is_array($url) && !empty($url)) {
			return false;
		}

		$base = array_merge($this->config['request']['uri'], array('scheme' => array('http', 'https'), 'port' => array(80, 443)));
		$url = $this->_parseUri($url, $base);

		if (empty($url)) {
			$url = $this->config['request']['uri'];
		}

		if (!empty($uriTemplate)) {
			return $this->_buildUri($url, $uriTemplate);
		}
		return $this->_buildUri($url);
	}

	protected function _setAuth() {
		if (empty($this->_auth)) {
			return;
		}
		$method = key($this->_auth);
		list($plugin, $authClass) = pluginSplit($method, true);
		$authClass = Inflector::camelize($authClass) . 'Authentication';
		App::uses($authClass, $plugin . 'Network/Http');

		if (!class_exists($authClass)) {
			throw new SocketException(__d('cake_dev', 'Unknown authentication method.'));
		}
		if (!method_exists($authClass, 'authentication')) {
			throw new SocketException(sprintf(__d('cake_dev', 'The %s do not support authentication.'), $authClass));
		}
		call_user_func_array("$authClass::authentication", array($this, &$this->_auth[$method]));
	}

	protected function _setProxy() {
		if (empty($this->_proxy) || !isset($this->_proxy['host'], $this->_proxy['port'])) {
			return;
		}
		$this->config['host'] = $this->_proxy['host'];
		$this->config['port'] = $this->_proxy['port'];

		if (empty($this->_proxy['method']) || !isset($this->_proxy['user'], $this->_proxy['pass'])) {
			return;
		}
		list($plugin, $authClass) = pluginSplit($this->_proxy['method'], true);
		$authClass = Inflector::camelize($authClass) . 'Authentication';
		App::uses($authClass, $plugin . 'Network/Http');

		if (!class_exists($authClass)) {
			throw new SocketException(__d('cake_dev', 'Unknown authentication method for proxy.'));
		}
		if (!method_exists($authClass, 'proxyAuthentication')) {
			throw new SocketException(sprintf(__d('cake_dev', 'The %s do not support proxy authentication.'), $authClass));
		}
		call_user_func_array("$authClass::proxyAuthentication", array($this, &$this->_proxy));
	}

	protected function _configUri($uri = null) {
		if (empty($uri)) {
			return false;
		}

		if (is_array($uri)) {
			$uri = $this->_parseUri($uri);
		} else {
			$uri = $this->_parseUri($uri, true);
		}

		if (!isset($uri['host'])) {
			return false;
		}
		$config = array(
			'request' => array(
				'uri' => array_intersect_key($uri, $this->config['request']['uri'])
			)
		);
		$this->config = merge($this->config, $config);
		$this->config = merge($this->config, array_intersect_key($this->config['request']['uri'], $this->config));
		return true;
	}

	protected function _buildUri($uri = array(), $uriTemplate = '%scheme://%user:%pass@%host:%port/%path?%query#%fragment') {
		if (is_string($uri)) {
			$uri = array('host' => $uri);
		}
		$uri = $this->_parseUri($uri, true);

		if (!is_array($uri) || empty($uri)) {
			return false;
		}

		$uri['path'] = preg_replace('/^\//', null, $uri['path']);
		$uri['query'] = http_build_query($uri['query']);
		$uri['query'] = rtrim($uri['query'], '=');
		$stripIfEmpty = array(
			'query' => '?%query',
			'fragment' => '#%fragment',
			'user' => '%user:%pass@',
			'host' => '%host:%port/'
		);

		foreach ($stripIfEmpty as $key => $strip) {
			if (empty($uri[$key])) {
				$uriTemplate = str_replace($strip, null, $uriTemplate);
			}
		}

		$defaultPorts = array('http' => 80, 'https' => 443);
		if (array_key_exists($uri['scheme'], $defaultPorts) && $defaultPorts[$uri['scheme']] == $uri['port']) {
			$uriTemplate = str_replace(':%port', null, $uriTemplate);
		}
		foreach ($uri as $property => $value) {
			$uriTemplate = str_replace('%' . $property, $value, $uriTemplate);
		}

		if ($uriTemplate === '/*') {
			$uriTemplate = '*';
		}
		return $uriTemplate;
	}

	protected function _parseUri($uri = null, $base = array()) {
		$uriBase = array(
			'scheme' => array('http', 'https'),
			'host' => null,
			'port' => array(80, 443),
			'user' => null,
			'pass' => null,
			'path' => '/',
			'query' => null,
			'fragment' => null
		);

		if (is_string($uri)) {
			$uri = parse_url($uri);
		}
		if (!is_array($uri) || empty($uri)) {
			return false;
		}
		if ($base === true) {
			$base = $uriBase;
		}

		if (isset($base['port'], $base['scheme']) && is_array($base['port']) && is_array($base['scheme'])) {
			if (isset($uri['scheme']) && !isset($uri['port'])) {
				$base['port'] = $base['port'][array_search($uri['scheme'], $base['scheme'])];
			} elseif (isset($uri['port']) && !isset($uri['scheme'])) {
				$base['scheme'] = $base['scheme'][array_search($uri['port'], $base['port'])];
			}
		}

		if (is_array($base) && !empty($base)) {
			$uri = array_merge($base, $uri);
		}

		if (isset($uri['scheme']) && is_array($uri['scheme'])) {
			$uri['scheme'] = array_shift($uri['scheme']);
		}
		if (isset($uri['port']) && is_array($uri['port'])) {
			$uri['port'] = array_shift($uri['port']);
		}

		if (array_key_exists('query', $uri)) {
			$uri['query'] = $this->_parseQuery($uri['query']);
		}

		if (!array_intersect_key($uriBase, $uri)) {
			return false;
		}
		return $uri;
	}

	protected function _parseQuery($query) {
		if (is_array($query)) {
			return $query;
		}

		if (is_array($query)) {
			return $query;
		}
		$parsedQuery = array();

		if (is_string($query) && !empty($query)) {
			$query = preg_replace('/^\?/', '', $query);
			$items = explode('&', $query);

			foreach ($items as $item) {
				if (strpos($item, '=') !== false) {
					list($key, $value) = explode('=', $item, 2);
				} else {
					$key = $item;
					$value = null;
				}

				$key = urldecode($key);
				$value = urldecode($value);

				if (preg_match_all('/\[([^\[\]]*)\]/iUs', $key, $matches)) {
					$subKeys = $matches[1];
					$rootKey = substr($key, 0, strpos($key, '['));
					if (!empty($rootKey)) {
						array_unshift($subKeys, $rootKey);
					}
					$queryNode =& $parsedQuery;

					foreach ($subKeys as $subKey) {
						if (!is_array($queryNode)) {
							$queryNode = array();
						}

						if ($subKey === '') {
							$queryNode[] = array();
							end($queryNode);
							$subKey = key($queryNode);
						}
						$queryNode =& $queryNode[$subKey];
					}
					$queryNode = $value;
					continue;
				}
				if (!isset($parsedQuery[$key])) {
					$parsedQuery[$key] = $value;
				} else {
					$parsedQuery[$key] = (array)$parsedQuery[$key];
					$parsedQuery[$key][] = $value;
				}
			}
		}
		return $parsedQuery;
	}

	protected function _buildRequestLine($request = array(), $versionToken = 'HTTP/1.1') {
		$asteriskMethods = array('OPTIONS');

		if (is_string($request)) {
			$isValid = preg_match("/(.+) (.+) (.+)\r\n/U", $request, $match);
			if (!$this->quirksMode && (!$isValid || ($match[2] == '*' && !in_array($match[3], $asteriskMethods)))) {
				throw new SocketException(__d('cake_dev', 'HttpSocket::_buildRequestLine - Passed an invalid request line string. Activate quirks mode to do this.'));
			}
			return $request;
		} elseif (!is_array($request)) {
			return false;
		} elseif (!array_key_exists('uri', $request)) {
			return false;
		}

		$request['uri']	= $this->_parseUri($request['uri']);
		$request = array_merge(array('method' => 'GET'), $request);
		if (!empty($this->_proxy['host'])) {
			$request['uri'] = $this->_buildUri($request['uri'], '%scheme://%host:%port/%path?%query');
		} else {
			$request['uri'] = $this->_buildUri($request['uri'], '/%path?%query');
		}

		if (!$this->quirksMode && $request['uri'] === '*' && !in_array($request['method'], $asteriskMethods)) {
			throw new SocketException(__d('cake_dev', 'HttpSocket::_buildRequestLine - The "*" asterisk character is only allowed for the following methods: %s. Activate quirks mode to work outside of HTTP/1.1 specs.', implode(',', $asteriskMethods)));
		}
		return $request['method'] . ' ' . $request['uri'] . ' ' . $versionToken . "\r\n";
	}

	protected function _buildHeader($header, $mode = 'standard') {
		if (is_string($header)) {
			return $header;
		} elseif (!is_array($header)) {
			return false;
		}

		$fieldsInHeader = array();
		foreach ($header as $key => $value) {
			$lowKey = strtolower($key);
			if (array_key_exists($lowKey, $fieldsInHeader)) {
				$header[$fieldsInHeader[$lowKey]] = $value;
				unset($header[$key]);
			} else {
				$fieldsInHeader[$lowKey] = $key;
			}
		}

		$returnHeader = '';
		foreach ($header as $field => $contents) {
			if (is_array($contents) && $mode == 'standard') {
				$contents = implode(',', $contents);
			}
			foreach ((array)$contents as $content) {
				$contents = preg_replace("/\r\n(?![\t ])/", "\r\n ", $content);
				$field = $this->_escapeToken($field);

				$returnHeader .= $field . ': ' . $contents . "\r\n";
			}
		}
		return $returnHeader;
	}

	public function buildCookies($cookies) {
		$header = array();
		foreach ($cookies as $name => $cookie) {
			$header[] = $name . '=' . $this->_escapeToken($cookie['value'], array(';'));
		}
		return $this->_buildHeader(array('Cookie' => implode('; ', $header)), 'pragmatic');
	}

	protected function _escapeToken($token, $chars = null) {
		$regex = '/([' . implode('', $this->_tokenEscapeChars(true, $chars)) . '])/';
		$token = preg_replace($regex, '"\\1"', $token);
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

	public function reset($full = true) {
		static $initalState = array();
		if (empty($initalState)) {
			$initalState = get_class_vars(__CLASS__);
		}
		if (!$full) {
			$this->request = $initalState['request'];
			$this->response = $initalState['response'];
			return true;
		}
		parent::reset($initalState);
		return true;
	}

}

function merge($arr1, $arr2 = null) {
	$args = func_get_args();

	$r = (array)current($args);
	while (($arg = next($args)) !== false) {
		foreach ((array)$arg as $key => $val) {
			if (!empty($r[$key]) && is_array($r[$key]) && is_array($val)) {
				$r[$key] = merge($r[$key], $val);
			} elseif (is_int($key)) {
				$r[] = $val;
			} else {
				$r[$key] = $val;
			}
		}
	}
	return $r;
}
