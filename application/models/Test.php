<?php

class testModel extends modelModel{

	public function addIntoTest($data, $type){
		if (!isset($data['name'])){
			return false;
		}
		$res = $this->pdo_test->addTb($data, $type);
		return $res;
	}

	public function deleteInfoById($ids){
		if (!is_array($ids) || empty($ids)){
			return false;
		}
		$res = $this->pdo_test->deleteTb(array('id', '=', $ids));
		return $res;
	}

	public function saveInfo($id, $data){
		if (!$id){
			return false;
		}
		$update = array();
		if ($data['name']){
			$update['name'] = $data['name'];
		}
		if ($data['comment']){
			$update['comment'] = $data['comment'];
		}
		if (empty($update)){
			return false;
		}
		$res = $this->pdo_test->updateTb(array('id', '=', $id), $update);
		return $res;
	}

	public function getInfoById($id){
		if (!$id){
			return false;
		}
		$res = $this->pdo_test->getTb(array('id', '=', $id));
		return $res;
	}
	//$where, $order = array(), $group = array(), $limit = array(), $fields = '*', $readMaster=false

	public function getInfoList($page, $pageSize = 20){
		$page = $page>=1 ? $page : 1;
		$limit = array(
			($page-1)*$pageSize,
			$pageSize,
		);
		$res = $this->pdo_test->getTb(array(), array(), array(), $limit);
		return $res;
	}
}