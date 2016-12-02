<?php

/**
 * @日志类
 */

define('LEVEL_WARNING', 1);
define('LEVEL_ERROR', 2);
define('LEVEL_INFO', 3);

class Sys_Log{

	static public function warning($msg){
		self::writeLog(LEVEL_WARNING, $msg);
	}

	static public function error($msg){
		self::writeLog(LEVEL_ERROR, $msg);
	}

	static public function info($msg){
		self::writeLog(LEVEL_INFO, $msg);
	}

	static private function writeLog($level, $msg){
		static $validLevels = array(
			LEVEL_ERROR  => 'error',
			LEVEL_WARNING  => 'warning',
			LEVEL_INFO  => 'info',
		);
		$strLevel = $validLevels[$level];
		if (!isset($validLevels[$level])){
			$level = LEVEL_INFO;
		}
		$logDir = ROOT.'/logs'.'/'.date('Ym');
		if (!is_dir($logDir)){
			@unlink($logDir);
			mkdir($logDir, 0777, true);
		}
		$logFileName = $logDir.'/'.date('Y-m-d').'.'.strtolower($strLevel).'.log';
		$logContent = "[".date('Y-m-d H:i:s')."]\t$msg\n";
		file_put_contents($logFileName, $logContent, FILE_APPEND | LOCK_EX);
	}

}