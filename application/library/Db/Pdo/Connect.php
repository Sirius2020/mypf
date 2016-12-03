<?php

/**
 * @pdo连接类
 */

class Db_Pdo_Connect {

	static private $_conn;

	private function __construct(){}

	static public function getConnect($tableName, $isMaster = false, $isReconnect = false){
		$tableConfig = Sys_Config::getInstance('tables');
		$dbName = $tableConfig->getKey($tableName);
		$dbConfig = Sys_Config::getInstance('db');
		$dbInfo = $dbConfig->getKey($dbName);
		if ($isMaster){
			$dbInfo = isset($dbInfo['master']) ? $dbInfo['master'] : false;
		}
		else{
			$dbInfo = isset($dbInfo['slave']) ? $dbInfo['slave'] : (isset($dbInfo['master']) ? $dbInfo['master'] : false);
		}
		if (!$dbInfo){
			Sys_Log::error('Mysql connect error. Msg: Could not find connect infomations for database: '.$dbName);
			return false;
		}
		@$key = $dbInfo['user'].'@'.$dbInfo['host'].':'.$dbInfo['port'].':'.$dbName;
		if (!isset(self::$_conn[$key]) || $isReconnect){
			@$dsn = 'mysql:dbname='.$dbName.';host='.$dbInfo['host'].';port='.$dbInfo['port'];
			try {
				self::$_conn[$key] = new PDO($dsn, $dbInfo['user'], $dbInfo['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
			}
			catch (PDOException $e) {
				Sys_Log::error('Mysql connect error. '.$dsn.'. Msg: '.$e->getMessage());
				return false;
			}
		}
		return self::$_conn[$key];
	}
}
?>