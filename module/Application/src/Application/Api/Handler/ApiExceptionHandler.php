<?php
namespace Application\Api\Handler;

class ApiExceptionHandler extends JsonHandler {

	public function get($data = '') {
		return $this->display('exception/exception.json.phtml', $data);
	}

	public function post($data = '') {
		return $this->display('exception/exception.json.phtml', $data);
	}

}