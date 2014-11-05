<?php

namespace Application\Db;

use PDO;
use Spyc;

use Api\Exception;

class ApiPDO extends WebPDO {

  public static function getInstance($context = 'default') {
      try {
        return parent::getInstance($context);
      } catch (Exception $e) {
        throw new ApiException ($e->getMessage(), $e->getCode());
      }
  }
}
