<?php

class Data_Http_CurlMulti{

	private $cafilePath = __DIR__.'/cacert.pem';

	public function queryBatch($params){
		if (!is_array($params) || empty($params)){
			return false;
		}
		$handles = array();
		foreach($params as $v){
			if (isset($v['method']) && isset($v['url'])){
				$params = isset($v['params']) ? $v['params'] : array();
				$headers = isset($v['headers']) ? $v['headers'] : array();
				$handles[] = $this->prepareCurlHandle($v['method'], $v['url'], $params, $headers);
			}
		}
		$mh = curl_multi_init();
		foreach($handles as $ch){
			curl_multi_add_handle($mh, $ch);
		}
		$running = null;
		do{
			$res = curl_multi_exec($mh, $running);
			if ($res == CURLM_CALL_MULTI_PERFORM){
				usleep(1000);
				continue;
			}
			curl_multi_select($mh, 1);
		}while($running && ($res !== CURLM_OK || $res !== CURLM_CALL_MULTI_PERFORM));
		$result = array();
		foreach($handles as $k=>$ch){
			$result[] = array(
				'body'=>curl_multi_getcontent($ch),
				'errno'=>curl_errno($ch),
				'error'=>curl_error($ch),
			);
			curl_multi_remove_handle($mh, $ch);
			curl_close($ch);
		}
		curl_multi_close($mh);
		return $result;
	}

	private function prepareCurlHandle($method, $url, $params=array(), $headers=array()){
		$method = strtolower($method);
		$method = in_array($method, array('post', 'get')) ? $method : 'get';
		$isSecure = (strpos($method, 'https://')!==false);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CAINFO, $this->cafilePath);
		// headers
		if (is_array($headers) && !empty($headers)){
			$arrHeaders = array();
			foreach($headers as $k=>$v){
				$arrHeaders[] = "$k: $v";
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeaders);
		}
		// params
		$queryString = '';
		if (is_array($params) && !empty($params)){
			$queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
		}
		if ($method == 'post'){
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
		}
		else {
			if (strpos($url, '?')!==false){
				$url .= '&'.$queryString;
			}
			else{
				$url .= '?'.$queryString;
			}
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		return $ch;
	}
}