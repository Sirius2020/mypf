<?php

class Data_Http_Curl{

	private $_responseHeader;
	private $_responseBody;
	private $_error;
	private $_curlInfo;

	private $cafilePath = __DIR__.'/cacert.pem';

	public function __construct(){
		$this->clear();
	}

	private function clear(){
		$this->_responseHeader = '';
		$this->_responseBody = '';
		$this->_curlInfo = array();
		$this->_error = array();
	}

	public function getCurlInfo(){
		return $this->_curlInfo;
	}

	public function getResponseHeader(){
		return $this->_responseHeader;
	}

	public function getRespnseBody(){
		return $this->_responseBody;
	}

	public function getError(){
		return $this->_error;
	}

	public function request($method, $url, $params=array(), $headers=array()){
		$this->clear();
		$method = strtolower($method);
		$method = in_array($method, array('post', 'get')) ? $method : 'get';
		$isSecure = (strpos($method, 'https://')!==false);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
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
		$res = curl_exec($ch);
		if ($res){
			$this->_curlInfo = curl_getinfo($ch);
			$headerSize = $this->_curlInfo['header_size'];
			$this->_responseHeader = $this->parseResponseHeader(substr($res, 0, $headerSize));
			$this->_responseBody = substr($res, $headerSize);
			$this->_error = array();
			curl_close($ch);
			return $this->_responseBody;
		}
		else{
			$this->_curlInfo = curl_getinfo($ch);
			$this->_error = array(
				'errno'=>curl_errno($ch),
				'error'=>curl_error($ch),
			);
			curl_close($ch);
			return false;
		}
	}

	private function parseResponseHeader($headers){
		$headerArray = array();
		$tmpArray = explode("\n", $headers);
		$separator = ': ';
		$separatorLength = strlen($separator);
		foreach ($tmpArray as $line){
			if (!trim($line)){
				continue;
			}
			if (0 === strpos($line, 'HTTP/')){
				$tmp = explode(' ', $line, 3);
				$headerArray['_http_version'] = isset($tmp[0]) ? $tmp[0] : '';
				$headerArray['_response_code'] = isset($tmp[1]) ? $tmp[1] : '';
				$headerArray['_response_text'] = isset($tmp[2]) ? $tmp[2] : '';
			}
			else{
				$pos = strpos($line, $separator);
				$key = substr($line, 0, $pos);
				$val = substr($line, $pos + $separatorLength);
				if (isset($headerArray[$key])){
					if (is_array($headerArray[$key])){
						$headerArray[$key][] = trim($val);
					}
					else{
						$headerArray[$key] = array($headerArray[$key], trim($val));
					}
				}
				else{
					$headerArray[$key] = trim($val);
				}
			}
		}
		return $headerArray;
	}
}