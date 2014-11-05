<?php

namespace Application\Api\Authentication;

use Http\Request;

use Application\Identity\AbstractIdentity;

use Application\Authentication\AbstractAuthentication,
	Api\ApiException as Exception;

class SignatureAbstract extends AbstractAuthentication {

	public $mapper;
 
	public function authenticate() {
		$request = new Request();
		$headers = $request->getHeaders();
		$servers = $request->getServer();
		if (!empty($headers['X-Api-Signature'])) {
			$signatureGenerator = new SignatureGenerator();
			list($keyName, $remoteSignature, $timestamp) = @explode(';', $headers['X-Api-Signature']);
			$remoteSignature = trim($remoteSignature);

			$user = $this->getMapper()->findKeyByName($keyName);

			$date = null;
			if (!empty($timestamp)) {
				$date = gmdate('D, d M y H:i:s ', $timestamp) . 'GMT';
			} elseif (!empty($headers['Date'])) {
				$date = $headers['Date'];
			}
			
			$signatureGenerator->setDate(!empty($date)?$date:gmdate('D, d M y H:i:s ') . 'GMT');
			$signatureGenerator->setHost($headers['Host']);
			$signatureGenerator->setRequestUri(!empty($servers['PATH_INFO'])?$servers['PATH_INFO']:(!empty($servers['REQUEST_URI'])?$servers['REQUEST_URI']:'/'));
			$signatureGenerator->setUserAgent($headers['User-Agent']);

			$identity = new $this->identityClass($keyName);
			if (empty($user['id'])) {
				$msg = __('Unknown api username requested');
				$identity->setMessage($msg);
				return $identity;
			}

			$identity->setUsername($user['username']);
			$identity->setOwner($user['username']);
			if (!empty($user['name']) && $user['name'] == 'apiuser') {
				$identity->setRole('root');
			} elseif (!empty($user['name'])) {
				$identity = $this->collectGroups($identity);
			}

			if ($signatureGenerator) {
				if ($signatureGenerator->generate($user['hash']) != $remoteSignature) {
					$msg = __('Api Signature comparison does not match');
					$identity->setMessage($msg);
					return $identity;
				}
			}

			$identity->setValid(true);
			return $identity;
		}
	}

	 public function collectGroups($identity) {
	 	return $identity;
	 }

	 public function getIdentity($keyName = null, $role = null) {
		return new $this->identityClass($keyName, $role);
	 }
}

class SignatureGenerator {
	
	private $date = '';
	
	private $userAgent = '';
	
	private $host = '';
	
	private $requestUri = '';
	
	public function getRequestUri() {
		return $this->requestUri;
	}
	
	public function getHost() {
		return $this->host;
	}
	
	public function getUserAgent() {
		return $this->userAgent;
	}
	
	public function getDate() {
		return $this->date;
	}
	
	public function setRequestUri($requestUri) {
		$this->requestUri = $requestUri;
		return $this;
	}
	
	public function setHost($host) {
		$this->host = $host;
		return $this;
	}
	
	public function setUserAgent($userAgent) {
		$this->userAgent = $userAgent;
		return $this;
	}
	
	public function setDate($date) {
		$this->date = $date;
		return $this;
	}
	
	public function generate($seed) {
		$concatString = $this->getHost() . ':' . $this->getRequestUri() . ':' . $this->getUserAgent() . ':' . $this->getDate();
		return hash_hmac('sha256', $concatString, $seed);
	}
}
