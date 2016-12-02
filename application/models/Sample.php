<?php
/**
 * @name SampleModel
 * @desc sample数据获取类, 可以访问数据库，文件，其它系统等
 * @author 20160418-2\administrator
 */
class SampleModel {
	public function __construct() {
	}

	public function test(){
		return 'this is sample model.';
	}

	public function selectSample() {
		return 'Hello World!';
	}

	public function insertSample($arrInfo) {
		return true;
	}
}
