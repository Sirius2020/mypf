<?php

class Data_Pdo_Table{

	private $_table;
	private $_dbConn;
	private $_transactionBegin;
	private $_statement;

	public function __construct($tableName){
		$this->_transactionBegin = false;
		$this->_table = $tableName;
		$this->_statement = new Data_Pdo_Sql();
	}

	public function __destruct(){
		if ($this->_transactionBegin){
			$this->transRollback();
		}
	}

	/**
	 * @desc 执行一个sql语句（注意：sql参数必须为经过escape处理，否则可能存在sql注入风险！）
	 * @params sql 经过escape处理过的sql语句
	 */
	public function query($sql, $isMaster = false){
		$isSelect = ('select' === strtolower(substr(trim($sql), 0, 6)));
		$queryType = ($isSelect && !$isMaster && !$this->_transactionBegin) ? 'slave' : 'master';
		$db = $this->getDb($queryType);
		$stmt = $db->query($sql);
		if (!$stmt){
			$errorInfo = $db->errorInfo();
			if ($errorInfo[1] == 2006){
				$db = $this->getDb($queryType, true);
				$stmt = $db->query($sql);
			}
		}
		if (!$stmt){
			$errorInfo = $db->errorInfo();
			Sys_log::error('Mysql execute error. Code: '.$errorInfo[1].'. Msg :'.$errorInfo[2]);
			return false;
		}
		if ($isSelect){
			$result = array();
			foreach($stmt as $k=>$v){
				$result[$k] = $v;
			}
			return $result;
		}
		else{
			return $stmt->rowCount();
		}
	}

	/**
	 * @desc prepare方式执行一个sql语句
	 * @param Data_Pdo_Sql $sql
	 * @param string $isMaster
	 * @return boolean|PDOStatement object
	 */
	public function execute(Data_Pdo_Sql $sql, $isMaster = false){
		if (!$sql->prepare($statement, $bindParams)){
			Sys_Log::error('Prepare statement error, '.var_export($sql, true));
			return false;
		}
		return $this->_execute($statement, $bindParams, $isMaster);
	}

	private function _execute($statement, $bindParams, $isMaster = false){
		$queryType = (('select' === strtolower(substr(trim($statement), 0, 6))) && $isMaster == false && !$this->_transactionBegin) ? 'slave' : 'master';
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
			Sys_log::error('Mysql execute error. Code: '.$errorInfo[1].'. Msg :'.$errorInfo[2]);
			return false;
		}
	}

	public function transBegin(){
		$res = $this->getDb()->beginTransaction();
		if ($res){
			$this->_transactionBegin = true;
		}
		return $res;
	}

	public function transRollback(){
		$res = $this->getDb()->rollBack();
		if ($res){
			$this->_transactionBegin = false;
		}
		return $res;
	}

	public function transCommit(){
		$res = $this->getDb()->commit();
		if ($res){
			$this->_transactionBegin = false;
		}
		return $res;
	}

	private function getDb($queryType = 'master', $isReconnect = false){
		if (!isset($this->_dbConn[$queryType]) || $isReconnect){
			$this->_dbConn[$queryType] = Data_Pdo_Connect::getConnect($this->_table, $queryType=='master' ? true : false, $isReconnect);
		}
		return $this->_dbConn[$queryType];
	}

	/**
	 * @param array $where array(array('field', 'operator', 'value'),...)
	 * @param string $fields
	 * @param string $readMaster
	 * @return array|boolean
	 */
	public function getTb($where, $order = array(), $group = array(), $limit = array(), $fields = '*', $readMaster=false){
		$this->_statement->clear()
			->select($fields)
			->from($this->_table)
			->where($where)
			->group($group)
			->order($order)
			->limit($limit);
		$stmt = $this->execute($this->_statement, $readMaster);
		if ($stmt){
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $result;
		}
		else{
			return false;
		}
	}

	/**
	 * @param array $set array('field'=>'value',...)
	 * @param string $type
	 * @return affective rows|boolean
	 */
	public function addTb($set, $type = 'insert'){
		$this->_statement->clear()
			->insert($this->_table, $type)
			->set($set);
		$stmt = $this->execute($this->_statement);
		if ($stmt){
			return $this->getDb()->lastInsertId();
		}
		else{
			return false;
		}
	}

	/**
	 * @param array $where array(array('field', 'operator', 'value'),...)
	 * @param array $order array('field1 desc', 'field2 asc')
	 * @param array $limit array(m,n)
	 * @return affective rows|boolean
	 */
	public function deleteTb($where, $order = array(), $limit = array()){
		$this->_statement->clear()
			->delete($this->_table)
			->where($where)
			->order($order)
			->limit($limit);
		$stmt = $this->execute($this->_statement);
		if ($stmt){
			return $stmt->rowCount();
		}
		else{
			return false;
		}
	}

	/**
	 * @param array $where array(array('field', 'operator', 'value'),...)
	 * @param array $set array('field'=>'value',...)
	 * @param array $order array('field1 desc', 'field2 asc')
	 * @param array $limit array(m,n)
	 * @return affective rows|boolean
	 */
	public function updateTb($where, $set, $order = array(), $limit = array()){
		$this->_statement->clear()
			->update($this->_table)
			->set($set)
			->where($where)
			->order($order)
			->limit($limit);
		$stmt = $this->execute($this->_statement);
		if ($stmt){
			return $stmt->rowCount();
		}
		else{
			return false;
		}
	}

	public function addBatch($setBatch, $type = 'insert'){
		if (empty($setBatch)){
			return false;
		}
		$fields = array_keys(current($setBatch));
		if (!$fields || empty($fields)){
			return false;
		}
		if ($type == 'replace'){
			$verb = 'replace';
		}
		else if ($type == 'ignore'){
			$verb = 'insert ignore';
		}
		else{
			$verb = 'insert';
		}
		$statement = '';
		$bindParams = array();
		$statement .= $verb.' into '.$this->_table.' (';
		foreach($fields as $v){
			$statement .= '`'.$v.'`,';
		}
		$statement = substr($statement, 0, -1);
		$statement .= ')';
		$statement .= ' values ';
		foreach($setBatch as $set){
			$statement .= '(';
			foreach($fields as $field){
				$statement .= '?,';
				if (isset($set[$field])){
					$bindParams[] = $set[$field];
				}
				else{
					$bindParams[] = '';
				}
			}
			$statement = substr($statement, 0, -1);
			$statement .= '),';
		}
		$statement = substr($statement, 0, -1);
		$stmt = $this->_execute($statement, $bindParams);
		if ($stmt){
			return $stmt->rowCount();
		}
		else{
			return false;
		}
	}
}