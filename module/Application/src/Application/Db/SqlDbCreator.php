<?php

namespace Application\Db;

use Application\Log as Log;

class SqlDbCreator {

	private $guiContext;

	private $pdo = array();

	public function __construct($pdo, $guiContext = 'gui') {
		$this->pdo = $pdo;
		$this->guiContext = $guiContext;
	}

	public function createDb($context, $type, $action) {
		$installDir = \Config::getConfig('install_dir', 'var/install/sql');

		try {
			$this->pdo->beginTransaction();
			foreach (glob($installDir."/{,*_}{".$context."_".$type."_".$action."_database}.sql", GLOB_BRACE) as $file) {
				$schemaFile = file_get_contents($file, true);
				$queries = explode(";", $schemaFile);
				foreach ($queries as $query) {
					if ($query = trim($query)) {
						Log::debug("Executing query '{$query}'");
						$this->pdo->exec($query);
					}
				}
	        }
	        $this->pdo->commit();
	      	$this->_updateMetaData($context);
	    } catch (\PDOException $e) {
	    	print_r($e);
	    	$this->pdo->rollback();
	    }
	}

	private function _updateMetaData($context, $version = '1.0') {
		$sql = 'INSERT INTO `gui_metadata` (`name`, `version`, `creation_time` ) VALUES(\''. $context.'_schema_version\', \'' . $version . '\', \'' . date('Y-m-d H:i:s', time()) . '\')';
		Log::debug($sql);
		WebPDO::getInstance($this->guiContext)->exec($sql);
	}
}