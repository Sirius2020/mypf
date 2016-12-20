<?php
/**
 * @name IndexController
 * @author 20160418-2\administrator
 * @desc 默认控制器
 * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
 */
class IndexController extends Yaf_Controller_Abstract {

	/**
	 * 默认动作
	 * Yaf支持直接把Yaf_Request_Abstract::getParam()得到的同名参数作为Action的形参
	 * 对于如下的例子, 当访问http://yourhost/sample/index/index/index/name/20160418-2\administrator 的时候, 你就会发现不同
	 */
	public function indexAction($name = "Stranger") {
		//1. fetch query
		$get = $this->getRequest()->getQuery("get", "default value");

		//2. fetch model
		$model = new SampleModel();
		echo $model->test();

		$lib = new Db_Sample();
		echo $lib->test();

		//3. assign
		$this->getView()->assign("content", $model->selectSample());
		$this->getView()->assign("name", $name);

		//4. render by Yaf, 如果这里返回FALSE, Yaf将不会调用自动视图引擎Render模板
		return TRUE;
	}
	public function testAction(){
		$redis = new Data_Redis_Redis();
		try {
			$key = 'test_key';
			$redis->set($key, 566652, 60);
			$res = $redis->get($key);
			var_dump($res);
			$ttl = $redis->ttl($key);
			var_dump($ttl);
		} catch (Exception $e) {
			var_dump($e);
		}
// 		$start = microtime(true);
// 		$http = new Data_Http_CurlMulti();
// 		$params = array();
// 		$params[] = array(
// 			'method'=>'get',
// 			'url'=>'http://localhosasdft/wqerwer',
// 		);
// 		$params[] = array(
// 			'method'=>'get',
// 			'url'=>'localhost/multi_curl/test_do_sth.php?type=2',
// 		);
// 		$res = $http->queryBatch($params);
// 		var_dump($res);
// 		$end = microtime(true);
// 		echo "<br/>".($end-$start)."<br/>";
//  		$http = new Data_Http_Curl();
// 		$res = $http->request('get', 'http://localhosasdft/wqerwer', array('aaa'=>'ad fas', 'b'=>'asdgasdg'), array('my_head'=>'asdf'));
// 		if ($res){
// 			var_dump($res);
// 		}
// 		else{
// 			var_dump($http->getError());
// 		}
//		var_dump($http->getResponseHeader());
//		var_dump($http->getCurlInfo());
//		var_dump($http->getResponseHeader());
// 		$mod = new testModel();
// 		$res = $mod->getInfoList(1);
// 		var_dump($res);
// 		$db = new Data_Pdo_Table('test');
// 		$data = array();
// 		$data[] = array(
// 			'name'=>'somebody1',
// 			'comment'=>'comment1',
// 		);
// 		$data[] = array(
// 			'name'=>'somebody2',
// 			'comment'=>'comment2',
// 		);
// 		$res = $db->addBatch($data, 'ignore');
// 		var_dump($res);
// 		$db->transBegin();
// 		$res = $db->query('update test set id=100 where id=1');
// 		var_dump($res);
// 		$tmp = $db->query('select * from test where id=1');
// 		var_dump($tmp);
// 		$db->transCommit();
		return false;
	}
}
