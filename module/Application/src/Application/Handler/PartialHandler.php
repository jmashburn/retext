<?php

namespace Application\Handler;

class PartialHandler extends AbstractHandler {

	protected $layout = 'views/layout/partial.phtml';

	public function get($module = null, $name = null) {
		return $this->display(sprintf("partials/%s/%s.phtml", $module, $name));
	}

}