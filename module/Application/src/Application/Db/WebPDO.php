<?php

namespace Application\Db;

use PDO;
use Spyc;


use Application\Exception as Exception;

class WebPDO {

  private static $instance = NULL;

  private function __construct() { }
  private function __clone() { }

  public static function getInstance($context = 'default') {
  	try {
		if (!is_file('config/database.ini')) {
			throw new Exception(__('No database.ini found'), 100);
		}
		$config = Spyc::YAMLLoad('config/database.ini');
		if (empty($config['database']) || empty($config['database'][$context])) {
			throw new Exception(__('Could not create connection to database "%s"', array($context)), 100);
		}
		$config = $config['database'][$context];
		if (strtolower($config['type']) === 'sqlite') {
			$dsn = 'sqlite:' . \Config::getConfig('db_dir', 'var/db/') . '/' . $config['name'];
			$pdo = new Pdo($dsn, '','');
			if ($context == 'gui') {
				if (! $pdo->query('SELECT * FROM `gui_metadata` WHERE `name` = \'gui_schema_version\'')) {
					$creator = new SqliteDbCreator($pdo);
					$creator->createGuiDb();
				}
			}
			return $pdo;
		} else {
			if (empty(self::$instance[$context])) {
				$conn = strtolower($config['type']) . ":host={$config['host']};port={$config['port']};dbname={$config['name']}";
				$driver_options = (!empty($config['driver_options'])?$config['driver_options']:array());

				self::$instance[$context] = new PDO($conn, $config['username'], $config['password'], $driver_options);
				self::$instance[$context]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			return self::$instance[$context];
		}
	} catch (\PDOException $e) {
		die($e->getMessage());
		throw new Exception($e->getMessage(), $e->getCode());
	} catch (\Exception $e) {
		throw new Exception($e->getMessage(), $e->getCode());
	}
  }
}
