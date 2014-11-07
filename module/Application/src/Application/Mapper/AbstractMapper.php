<?php

namespace Application\Mapper;

use Application\Db\WebPDO as PDO;

use Application\Log,
	Application\Set;

use Application\Exception as Exception;
use Http\Request as HttpRequest;


abstract class AbstractMapper {

	const COUNT_HIGH = 25;
	const COUNT_LOW = 1;

	public $context;

	protected $setClass;

	protected $tableName;

	protected $transactionCounter = 0;

	private $_sql = '';

	private $tableGateway;

	private $request;

	public function __construct($context = null) {
		if (null !== $context) {
			\Application\Log::debug('Setting Context '. $context);
			$this->context = $context;
		}
	}

	public function setTableGateway(\PDO $tableGateway) {
		$this->tableGateway = $tableGateway;
		return $this;
	}

	public function getTableGateway() {
		if (!is_object($this->tableGateway)) {
			$this->setTableGateway(PDO::getInstance($this->context));
		}
		return $this->tableGateway;
	}

	public function getTableName() {
		return $this->tableName;
	}

	public function setTableName($tableName) {
		$this->tableName = $tableName;
		return $this;
	}

	public function setContext($context) {
		$this->context = $context;
		return $this;
	}

	public function setEnv($env) {
		$this->env = $env;
		return $this;
	}

	public function count($sql, $params = array()) {
		try {
			$this->setSql($sql);
			if (!preg_match('/COUNT/i', $this->getSql())) {
				throw new Exception("Sql: '{$this->getSql()}' is not a valid COUNT statement");
			}
			$instance = $this->getTableGateway()->prepare($this->getSql());
			$instance->execute($params);
		} catch (\PDOException $e) {
			throw new Exception('db count query preparation failed with the following errors: '. $e->getMessage());
		}

		$resultSet = $this->selectWith($instance, false, false, true, \PDO::FETCH_COLUMN);

		if (!isset($resultSet[0])) {
			Log::warn("db COUNT query '{$this->getSql()}' did not return expected response");
			return 0;
		}
		return (integer)current($resultSet);
	}

	public function select($sql, $params = array(), $fetchAll = true, $returnSet = true) {
		try {
			$this->setSql($sql);
			$instance = $this->getTableGateway()->prepare($this->getSql());
			if (!is_object($instance)) {
				throw new \Exception('Unable to create Database Connection');
			}
			$instance->execute($params);
			return $this->selectWith($instance, $fetchAll, $returnSet);
		} catch (\PDOException $e) {
			throw new Exception();
		}
	}

	public function selectOne($sql, $params = array()) {
		$resultSet =  $this->select($sql, $params, false, true);
		return $resultSet;
	}

	protected function resultSetToArray($resultSet) {
		return $resultSet;
	}

	public function selectWith(\PDOStatement $select, $fetchAll = true, $returnSet = true, $returnRaw = false, $fetchStyle = \PDO::FETCH_ASSOC) {
		Log::debug("Executing query: '{$this->getSql()}'");
		try { 
			$this->beforeFind($select);
			if ($fetchAll) {
				$resultSet = $select->fetchAll($fetchStyle);
			} else {
				if (($result = $select->fetch($fetchStyle)) !== false) {
					$resultSet[] = $result;
				} else {
					$resultSet[] = array();	
				}
			}
			$this->afterFind($resultSet);
		} catch (\PDOException $e) {
			$msg = "db SELECT query '{$this->getSql()}' failed with the following errors: " . $e->getMessage();
			Log::err($msg);
			throw new Exception($msg);
		}

		Log::debug("Query: '{$this->getSql()}' executed OK");
		if ($returnRaw) {
			return $resultSet;
		}
		if ($this->setClass && $returnSet) {
			return new Set($this->resultSetToArray($resultSet), $this->setClass);
		}
		return new Set($this->resultSetToArray($resultSet));
	}

	public function insert($sql, $params = array()) {
		try {
			$this->setSql($sql);
			if (!$this->beforeSave()) {
				$msg = "db INSERT query '{$this->getSql()}' halted in beforeSave()";
				Log::err($msg);
				throw new Exception($msg);
			}
			Log::debug("Executing query: '{$this->getSql()}'");
			$instance = $this->getTableGateway()->prepare($this->getSql());
			$result = $instance->execute($params);
			$this->afterSave($result);
		} catch (\PDOException $e) {
			throw new Exception($e->getMessage());
		}
		$value = $this->getTableGateway()->lastInsertId();
		Log::debug("db INSERT statemenet successfully executed, with the last value {$value}");
		return $value;
	}

	public function update($sql, $params = array()) {
		try {
			$this->setSql($sql);
			if (!$this->beforeSave()) {
				$msg = "db UPDATE query '{$this->getSql()}' halted in beforeSave()";
				Log::err($msg);
				throw new Exception($msg);
			}
			Log::debug("Executing query: '{$this->getSql()}'");
			$instance = $this->getTableGateway()->prepare($this->getSql());
			$affectedRowsCount = $instance->execute($params);
			$this->afterSave($affectedRowsCount);
		} catch (\PDOException $e) {
			throw new Exception("db UPDATE query failed with the following error: ". $e->getMessage());
		}
		Log::debug("db UPDATE statemenet successfully executed on {$affectedRowsCount} rows");
		return $affectedRowsCount;
	}

	public function delete($sql) {
		try {
			$this->setSql($sql);
			if (!$this->beforeDelete()) {
				$msg = "db DELETE query '{$this->getSql()}' halted in beforeDelete()";
				Log::err($msg);
				throw new Exception($msg);
			}			
			Log::debug("Executing query: '{$this->getSql()}'");
			$affectedRowsCount = $this->getTableGateway()->exec($this->getSql());
			$this->afterSave($affectedRowsCount);
		} catch (\PDOException $e) {
			throw new Exception("db DELETE failed with the following errors: " . $e->getMessage());
		}
		Log::debug("db DELETE statement successfully executed on {$affectedRowsCount} rows");
		return $affectedRowsCount;
	}

	public function beginTransaction() {
		if (!$this->transactionCounter++) {
			Log::debug('db Begin transaction succesfull');
			return $this->getTableGateway()->beginTransaction();
		}
		return $this->transactionCounter >=0;
	}

	public function commit() {
		if (!--$this->transactionCounter) {
			Log::debug('db Commit transaction succesfull');
			return $this->getTableGateway()->commit();
		}
		return $this->transactionCounter >=0;
	}

	public function rollback() {
		if ($this->transactionCounter >=0) {
			$this->transactionCounter = 0;
			$this->getTableGateway()->rollback();
			Log::debug('db Rollback transaction succesfull');
		}
		$this->transactionCounter = 0;
		return false;
	}

	public function getSqlInStatement($field, $inValues) {
		return $field . ' IN("'. implode('","', $inValues) . '")';
	}

	public function getSql() {
		return $this->_sql;
	}

	public function setSql($sql) {
		$this->_sql = $sql;
		return $this;
	}

	public function getLimit() {
		$request = $this->getRequest();
		$parameters = $request->getQuery();

		$parameters = array_merge(array('count' => 10, 'offset' => 0), $parameters);
		if ($parameters['count'] > self::COUNT_HIGH) {
			$parameters['count'] = self::COUNT_HIGH;
		}
		if ($parameters['count'] < self::COUNT_LOW) {
			$parameters['count'] = self::COUNT_LOW;
		}
		return sprintf(" LIMIT %d OFFSET %d ", (int)$parameters['count'], $parameters['offset']);
	}

	private function getRequest() {
        if (!$this->request) {
            $this->request = new HttpRequest();
        }
        return $this->request;
    }

    public function beforeFind(&$queryData) {
    	return true;
    }

    public function afterFind($results) {
    	return $results;
    }

    public function beforeSave($options = array()) {
    	return true;
    }

    public function afterSave($created) {
	}

	public function beforeDelete() {
		return true;
	}

	public function afterDelete() {

	}








	public $count = 10;

	public $offset = 0;

	public $prefix = '';


	function uniqid($in, $to_num = false, $pad_up = false, $passKey = null)
	{
	    $index = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    if ($passKey !== null) {
	        // Although this function's purpose is to just make the
	        // ID short - and not so much secure,
	        // you can optionally supply a password to make it harder
	        // to calculate the corresponding numeric ID

	        for ($n = 0; $n<strlen($index); $n++) {
	            $i[] = substr( $index,$n ,1);
	        }

	        $passhash = \Security::hash($passKey, 'sha256');
	        $passhash = (strlen($passhash) < strlen($index))
	            ? \Security::hash($passKey, 'sha256')
	            : $passhash;

	        for ($n=0; $n < strlen($index); $n++) {
	            $p[] =  substr($passhash, $n ,1);
	        }

	        array_multisort($p,  SORT_DESC, $i);
	        $index = implode($i);
	    }

	    $base  = strlen($index);

	    if ($to_num) {
	        // Digital number  <<--  alphabet letter code
	        $in  = strrev($in);
	        $out = 0;
	        $len = strlen($in) - 1;
	        for ($t = 0; $t <= $len; $t++) {
	            $bcpow = bcpow($base, $len - $t);
	            $out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
	        }

	        if (is_numeric($pad_up)) {
	            $pad_up--;
	            if ($pad_up > 0) {
	                $out -= pow($base, $pad_up);
	            }
	        }
	        $out = sprintf('%F', $out);
	        $out = substr($out, 0, strpos($out, '.'));
	    } else {
	        // Digital number  -->>  alphabet letter code
	        if (is_numeric($pad_up)) {
	            $pad_up--;
	            if ($pad_up > 0) {
	                $in += pow($base, $pad_up);
	            }
	        }

	        $out = "";
	        for ($t = floor(log($in, $base)); $t >= 0; $t--) {
	            $bcp = bcpow($base, $t);
	            $a   = floor($in / $bcp) % $base;
	            $out = $out . substr($index, $a, 1);
	            $in  = $in - ($a * $bcp);
	        }
	        $out = strrev($out); // reverse
	    }

	    return $out;
	}
}
