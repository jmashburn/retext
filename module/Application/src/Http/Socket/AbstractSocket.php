<?php

namespace Http\Socket;

abstract class AbstractSocket {

	protected $_baseConfig = array(
		'persistent'	=> false,
		'host'			=> 'localhost',
		'protocol'		=> 'tcp',
		'port'			=> 80,
		'timeout'		=> 5
	);

	public $config = array();

	public $connection = null;

	public $connected = false;

	public $lastError = array();

	public function __construct($config = array()) {
		$this->config = array_merge($this->_baseConfig, $config);
		if (!is_numeric($this->config['protocol'])) {
			$this->config['protocol'] = getprotobyname($this->config['protocol']);
		}
	}

	public function connect() {
		if ($this->connection != null) {
			$this->disconnect();
		}

		$scheme = null;
		if (isset($this->config['request']) && $this->config['request']['uri']['scheme'] == 'https') {
			$scheme = 'ssl://';
		}

		if ($this->config['persistent'] == true) {
			$this->connection = @pfsockopen($scheme . $this->config['host'], $this->config['port'], $errNum, $errStr,
				$this->config['timeout']);
		} else {
			$this->connection = @fsockopen($scheme . $this->config['host'], $this->config['port'], $errNum, $errStr,
				$this->config['timeout']);
		}

		if (!empty($errNum) || !empty($errStr)) {
			$this->setLastError($errNum, $errStr);
			throw new SocketException($errStr, $errNum);
		}

		$this->connected = is_resource($this->connection);
		if ($this->connected) {
			stream_set_timeout($this->connection, $this->config['timeout']);
		}
		return $this->connected;
	}

	public function host() {
		if (filter_var($this->config['host'], FILTER_VALIDATE_IP)) {
			return gethostbyaddr($this->confg['host']);
		}
		return gethostbyaddr($this->address());
	}

	public function address() {
		if (filter_var($this->config['host'], FILTER_VALIDATE_IP)) {
			return $this->config['host'];
		}
		return gethostbyname($this->config['host']);
	}

	public function addresses() {
		if (filter_var($this->config['host'], FILTER_VALIDATE_IP)) {
			return array($this->config['host']);
		}
		return gethostbynamel($this->config['host']);

	}

	public function lastError() {
		if (!empty($this->lastError)) {
			return $this->lastError['num'] . ': '. $this->lastError['str'];
		}
		return null;
	}

	public function setLastError($errNum, $errStr) {
		$this->lastError = array('num' => $errNum, 'str' => $errStr);
	}

	public function write($data) {
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}

		$totalBytes = strlen($data);
		for ($written = 0, $rv = 0; $written < $totalBytes; $written += $rv) {
			$rv = fwrite($this->connection, substr($data, $written));
			if ($rv === false || $rv === 0) {
				return $written;
			}
		}
		return $written;
	}

	public function read($length = 1024) {
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}

		if (!feof($this->connection)) {
			$buffer = stream_get_contents($this->connection);
			$info = stream_get_meta_data($this->connection);
			if ($info['timed_out']) {
				$this->setLastError(E_WARNING, __('Connection time out'));
				return false;
			}
			return $buffer;
		}
		return false;
	}

	public function disconnect() {
		if (!is_resource($this->connection)) {
			$this->connected = false;
			return true;
		}
		$this->connected = !fclose($this->connection);
		if (!$this->connected) {
			$this->connection = null;
		}
		return !$this->connected;
	}

	public function __destruct() {
		$this->disconnect();
	}

	public function reset($state = null) {
		if (empty($state)) {
			static $initialState = array();
			if (empty($initialState)) {
				$initialState = get_class_vars(__CLASS__);
			}
			$state = $initialState;
		}

		foreach ($state as $property => $value) {
			$this->{$property} = $value;
		}
		return true;
	}
}

class SocketException extends \Application\Exception {

}