<?php

class modelModel{

	public function __construct(){

	}

	public function __get($name){
		if (isset($this->name)){
			return $this->name;
		}
		list($key, $value) = explode('_', $name);
		if ($key === 'pdo'){
			$this->name = new Data_Pdo_Table($value);
			return $this->name;
		}
		else{
			return false;
		}
	}
}