<?php
/**
 * @desc 文件配置访问驱动
 */

class Sys_Config{

	static public function getInstance($module){
		static $configs = array();
		if (!isset($configs[$module])){
			$configs[$module] = new Config($module);
		}
		return $configs[$module];
	}
}

class Config{

	private $_data;

	public function __construct($module){
		$this->loadConfig($module);
	}

	private function loadConfig($module){
		if (!isset($this->_data)){
			$configFilePath = ROOT.'/config/'.$module.'.php';
			if (file_exists($configFilePath)){
				include($configFilePath);
				if (isset($$module)){
					$this->_data = $$module;
				}
			}
			else{
				$this->_data = array();
			}
		}
	}

	public function getKey($key = null){
		if ($key === null){
			return $this->_data;
		}
		else{
			return isset($this->_data[$key]) ? $this->_data[$key] : false;
		}
	}
}
?>