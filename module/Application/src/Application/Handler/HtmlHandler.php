<?php

namespace Application\Handler;

use ToroHook;

class HtmlHandler extends AbstractHandler {

	protected $authAdapter = 'Application\Authentication\Authentication';

	protected $layout = "views/layout/layout.phtml";

	public $data = array(
		'script' => array()
	);

	public function __construct() {
		parent::__construct();
		ToroHook::add('before_handler', function($params) {
			$pathInfo = ($this->getRequest()->getServer('REQUEST_URI'))?$this->getRequest()->getServer('REQUEST_URI'):'/';
			if (!empty($this->getRequest()->getServer('QUERY_STRING'))) {
				$pathInfo = str_replace('?'.$this->getRequest()->getServer('QUERY_STRING'), '', $pathInfo);
			}
			$identity = $this->getIdentity();
			if (!$identity->isValid() && !in_array($pathInfo, array('/login', '/logout'))) {
				header('Location: /login?redirectUrl='.$pathInfo);
			}
		});
	}

	public function script($script) {
		if (is_array($script)) {
			$this->data['script'] = array_merge($this->data['script'], $script);
		} else {
			$this->data['script'][] = $script;
		}
	}

	public function fetch($value = 'script') {
		if (!empty($this->data[$value])) {
			$output = "";
			foreach ($this->data[$value] as $val) {
				$output .= "<script src=\"$val\"></script>\n";
			}
			return $output;
		}
	}



}
