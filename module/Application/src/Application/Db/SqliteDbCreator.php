<?php

namespace Application\Db;

use Application\Log as Log;

class SqliteDbCreator {

	const DATABSE_GUI_FILENAME = 'gui_sqlite_create_database.sql';

	private $pdo;
	public function __construct($pdo) {
		$this->pdo = $pdo;
	}

	public function createGuiDb() {
		$installDir = \Config::getConfig('install_dir', 'var/install/sql');

		$schemaFile = file_get_contents($installDir . "/" . self::DATABSE_GUI_FILENAME, true);

		// \Log::info('Executing queries from ' . $schemaFile->getFilename());
		$queries = explode(";", $schemaFile);
		$this->pdo->exec('BEGIN TRANSACTION');
		foreach ($queries as $query) {
			if ($query = trim($query)) {
				Log::debug("Executing query '{$query}'");
				$this->pdo->exec($query);
			}
		}
		$this->pdo->exec('COMMIT TRANSACTION');
	}
}