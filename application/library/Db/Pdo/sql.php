<?php

class Db_Pdo_Sql{

	public $table = '';
	public $verb = '';
	public $fields = '';
	public $where = array();
	public $set = array();
	public $group = array();
	public $order = array();
	public $limit = array();

	public function __construct(){}

	public function select($fields = '*'){
		$this->verb = 'select';
		$this->fields = $fields;
		return $this;
	}

	public function from($table){
		$this->table = $table;
		return $this;
	}

	public function insert($table, $type){
		if (in_array(strtolower($type), array('replace', 'ignore'))){
			$this->verb = $type;
		}
		$this->verb = 'insert';
		$this->table = $table;
		return $this;
	}

	public function delete($table){
		$this->verb = 'delete';
		$this->table = $table;
		return $this;
	}

	public function update(){
		$this->verb = 'update';
		$this->table = $table;
		return $this;
	}

	public function change(){
		// to do
	}

	public function clear(){
		$this->verb = '';
		$this->table = '';
		$this->fields = '';
		$this->where = array();
		$this->set = array();
		$this->group = array();
		$this->order = array();
		$this->limit = array();
		return $this;
	}

	/**
	 * 数组	array('field'=>'value')
	 */
	public function set($set){
		$this->set = $set;
		return $this;
	}

	/**
	 * 数组或二维数组	array('field', '=', 'value')或array(array(...), array(...));
	 */
	public function where($where){
		$check_dimension = current($where);
		if (is_array($check_dimension)){
			foreach($where as $v){
				$this->where[] = $v;
			}
		}
		else{
			$this->where[] = $where;
		}
		return $this;
	}

	/**
	 * 字符串或数组	'field1 desc'或array('field1 desc', 'field2 asc');
	 */
	public function order($order){
		if (is_array($order)){
			foreach($order as $v){
				$this->order[] = $v;
			}
		}
		else{
			$this->order[] = $v;
		}
		return $this;
	}

	/**
	 * 字符串或数组	'field1'或array('field1', 'field2');
	 */
	public function group($group){
		if (is_array($group)){
			foreach($group as $v){
				$this->group[] = $v;
			}
		}
		else{
			$this->group[] = $v;
		}
		return $this;
	}

	public function limit($m, $n){
		$this->limit = array($m, $n);
	}

	public function prepare(&$statement, &$bindParams){
		switch($this->verb){
		case 'select':
			{
				$statement = 'select '.$this->fields.' from '.$this->table.' ';
				$this->_makeWhere($statement, $bindParams);
				$this->_makeGroup($statement);
				$this->_makeOrder($statement);
				$this->_makeLimit($statement);
			}
			break;
		}
	}

	private function _makeSet(&$statement, &$bindParams){
		if (count($this->set) > 0){
			$statement .= 'set ';
			foreach($this->set as $k=>$v){
				$statement .= $k.'=?,';
				$bindParams[] = $v;
			}
			$statement = substr($statement, 0, -1);
		}
	}

	private function _makeWhere(&$statement, &$bindParams){
		if (count($this->where) > 0){
			$statement .= 'where ';
			foreach($this->where as $v){
				$field = $v[0];
				$operator = $v[1];
				$value = $v[2];
				$statement .= $field.' '.$operator.' ';
				if (strtolower($operator) == 'in'){
					$statement .= empty($value) ? "('') " : '('.implode(',', array_fill(0, count($value), '?')).') ';
					foreach($value as $sv){
						$bindParams[] = $sv;
					}
				}
				else{
					$statement .= '? ';
					$bindParams[] = $value;
				}
				$statement .= 'and ';
			}
			$statement = substr($statement, 0, -4);
		}
	}

	private function _makeGroup(&$statement){
		if (count($this->group) > 0){
			$statement .= 'group by '.implode(', ', $this->group).' ';
		}
	}

	private function _makeOrder(&$statement){
		if (count($this->order) > 0){
			$statement .= 'order by '.implode(', ', $this->order).' ';
		}
	}

	private function _makeLimit(&$statement){
		if ($this->limit){
			$statement .= 'limit '.$this->limit[0].', '.$this->limit[1].' ';
		}
	}
}