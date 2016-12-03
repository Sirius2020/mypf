<?php

class Db_Pdo_Table{

	private $_table;
	private $_dbConn;
	private $_transactionBegin = false;
	private $_statement;

	public function __construct($tableName){
		$this->_table = $tableName;
		$this->_statement = new Db_Pdo_Sql();
	}

	/**
	 * @desc 执行一个sql语句（注意：sql参数必须为经过escape处理，否则可能存在sql注入风险！）
	 * @params sql 经过escape处理过的sql语句
	 */
	public function query($sql, $isMaster = false){
		$isSelect = ('select' === strtolower(substr(trim($sql), 0, 6)));
		$queryType = ($isSelect && !$isMaster/*&& !$this->_transactionBegin*/) ? 'slave' : 'master';
		$db = $this->getDb($queryType);
		$stmt = $db->query($sql);
		if ($isSelect){
			$result = array();
			foreach($stmt as $v){
				$result[] = $v;
			}
			return $result;
		}
		else{
			return $stmt->rowCount();
		}
	}

	public function excute($statement, $bindParams, $isMaster = false){
		$queryType = (('select' === strtolower(substr(trim($statement), 0, 6))) && $isMaster == false /*&& !$this->_transactionBegin*/) ? 'slave' : 'master';
		$db = $this->getDb($queryType);
		$stmt = $db->prepare($statement);
		$res = $stmt->execute($bindParams);
		if (!$res){
			$errorInfo = $stmt->errorInfo();
			// 重连数据库
			if ($errorInfo[1] == 2006){
				$db = $this->getDb($queryType, true);
				$stmt = $db->prepare($statement);
				$res = $stmt->execute($bindParams);
			}
		}
		if ($res){
			return $stmt;
		}
		else{
			$errorInfo = $stmt->errorInfo();
			Sys_log::error('Mysql excute error. Code: '.$errorInfo[1].'. Msg :'.$errorInfo[2]);
			return false;
		}
	}

	public function getTbRow($fields, $where, $readMaster=false){
		$this->_statement->clear()
			->select($fields)
			->from($this->_table)
			->where($where)
			->limit(0,1)
			->prepare($statement, $bindParams);
		$stmt = $this->excute($statement, $bindParams, $readMaster);
		if ($stmt){
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			print_r($result);
		}
	}

	public function transBegin(){
		$this->getDb()->beginTransaction();
	}

	public function transRollback(){
		$this->getDb()->rollBack();
	}

	public function transCommit(){
		$this->getDb()->commit();
	}

	private function getDb($queryType = 'master', $isReconnect = false){
		if (!isset($this->_dbConn[$queryType])){
			$this->_dbConn[$queryType] = Db_Pdo_Connect::getConnect($this->_table, $queryType=='master' ? true : false, $isReconnect);
		}
		return $this->_dbConn[$queryType];
	}
}