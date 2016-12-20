<?php

class Data_Redis_Redis{

	public $config = null;

	private $connected = false;

	private $Redis = null;

	private $masterConnected = false;

	private $masterRedis = null;

	public function __construct(){
		$redisConfig = Sys_Config::getInstance('redis');
		$slave = $redisConfig->getKey('slave');
		$master = $redisConfig->getKey('master');
		$this->_initConnect($slave['host'], $slave['port']);
		$this->_initMasterConnect($master['host'], $master['port']);
	}

	public function isConnected()
	{
		return $this->connected;
	}

	public function isMasterConnected()
	{
		return $this->masterConnected;
	}

	public function close($isMaster = false){
		if ($this->Redis && !$isMaster){
			$this->Redis->close();
		}
		if ($this->masterRedis && $isMaster){
			$this->masterRedis->close();
		}
		return true;
	}

	/**
	 * @desc 得到 Redis 原始对象可以有更多的操作
	 */
	public function getRedis($isMaster = false){
		if (!$isMaster){
			return $this->Redis;
		}else{
			return $this->masterRedis;
		}
	}

	/**
	 * @desc 写缓存
	 */
	public function set($key, $value, $expire=0){
		if($expire == 0){
			$ret = $this->masterRedis->set($key, $value);
		}else{
			$ret = $this->masterRedis->setex($key, $expire, $value);
		}
		return $ret;
	}

	/**
	 * @desc 读缓存
	 */
	public function get($key){
		$func= is_array($key) ? 'mGet': 'get';
		$result = $this->getRedis()->{$func}($key);
		return $result;
	}

	/**
	 * @desc 条件形式设置缓存，如果 key 不存时就设置，存在时设置失败
	 */
	public function setnx($key, $value){
		$ret = $this->masterRedis->setnx($key, $value);
		return $ret;
	}

	/**
	 * @desc 删除缓存
	 */
	public function remove($key){
		$ret = $this->masterRedis->delete($key);
		return $ret;
	}

	/**
	 * @desc 给名称为key的List数据结构左边（头）添加一个value值
	 */
	public function lPush($key, $value)
	{
		$ret = $this->masterRedis->lPush($key, $value);
		return $ret;
	}

	/**
	 * @desc 给名称为key的List数据结构右边（尾）添加一个value值
	 */
	public function rPush($key, $value)
	{
		$ret = $this->masterRedis->rPush($key, $value);
		return $ret;
	}

	/**
	 * @desc 将值 value 插入到列表 key 的表头，当且仅当 key 存在并且是一个列表。
	 */
	public function lPushx($key, $value)
	{
		$ret = $this->masterRedis->lPushx($key, $value);
		return $ret;
	}

	/**
	 * @desc 将值 value 插入到列表 key 的表尾，当且仅当 key 存在并且是一个列表。
	 */
	public function rPushx($key, $value)
	{
		$ret = $this->masterRedis->rPushx($key, $value);
		return $ret;
	}

	/**
	 * @desc 输出名称为key的List数据结构左(头)起的第一个元素，删除该元素
	 */
	public function lPop($key)
	{
		$result = $this->masterRedis->lPop($key);
		return $result;
	}

	/**
	 * @desc 输出名称为key的List数据结构右（尾）起的第一个元素，删除该元素
	 */
	public function rPop($key)
	{
		$result = $this->masterRedis->rPop($key);
		return $result;
	}

	/**
	 * @desc 返回名称为key的list有多少个元素
	 */
	public function lSize($key)
	{
		return $this->getRedis()->lSize($key);
	}

	/**
	 * @desc 返回名称为key的list中index位置的元素
	 */
	public function lGet($key, $index)
	{
		$result = $this->getRedis()->lGet($key, $index);
		return $result;
	}

	/**
	 * @desc 给名称为key的list中index位置的元素赋值为value
	 * 当 index 参数超出范围，或对一个空列表( key 不存在)进行 LSET 时，返回一个错误。
	 */
	public function lSet($key, $index, $value)
	{
		$result = $this->masterRedis->lSet($key, $index, $value);
		return $result;
	}

	/**
	 * @desc 返回名称为key的list中start至end之间的元素（end为 -1 ，返回所有）
	 * 下标(index)参数 start 和 stop 都以 0 为底，也就是说，以 0 表示列表的第一个元素，以 1 表示列表的第二个元素，以此类推。
	 * 也可以使用负数下标，以 -1 表示列表的最后一个元素， -2 表示列表的倒数第二个元素，以此类推。
	 */
	public function lRange($key, $start = 0, $end = -1)
	{
		$result = $this->getRedis()->lRange($key, $start, $end);
		return $result;
	}

	/**
	 * @desc 截取名称为key的list，保留start至end之间的元素
	 */
	public function lTrim($key, $start, $end)
	{
		$result = $this->masterRedis->lTrim($key, $start, $end);
		return $result;
	}

	/**
	 * @desc 删除count个名称为key的list中值为value的元素。
	 * count为0，删除所有值为value的元素，
	 * count>0从头至尾删除count个值为value的元素，
	 * count<0从尾到头删除|count|个值为value的元素
	 */
	public function lRem($key, $value, $count = 0)
	{
		$ret = $this->masterRedis->lRem($key, $value, $count);
		return $ret;
	}

	/**
	 * @desc 在名称为为key的list中，找到值为pivot 的value，
	 * 并根据参数Redis::BEFORE | Redis::AFTER，来确定，newvalue
	 * 是放在 pivot 的前面，或者后面。
	 * 当 pivot 不存在于列表 key 时，不执行任何操作 return -1
	 * 当 key 不存在时， key 被视为空列表，不执行任何操作 return 0
	 * 如果 key 不是列表类型，return false
	 * 成功返回当前list的元素个数
	 */
	public function lInsert($key, $posion = Redis::AFTER, $pivot, $newvalue)
	{
		$ret = $this->Redis->lInsert($key, $posion, $pivot, $newvalue);
		return $ret;
	}

	/**
	 * @desc 返回并删除名称为srckey的list的尾元素，并将该元素添加到名称为dstkey的list的头部
	 * 如果 source 不存在，值 并且不执行其他动作。 return false
	 * 如果 source 和 destination 相同，则列表中的表尾元素被移动到表头，并返回该元素，可以把这种特殊情况视作列表的旋转(rotation)操作。
	 */
	public function rpoplpush($srckey, $dstkey)
	{
		$ret = $this->masterRedis->rpoplpush($srckey, $dstkey);
		return $ret;
	}

	/**
	 * @desc 向名称为key的集合中添加元素value,
	 * 假如 key 不存在，则创建一个只包含 member 元素作成员的集合。
	 * 当 key 不是集合类型时，返回false。
	 * 成功返回1
	 * 如果value存在，不写入返回0
	 */
	public function sAdd($key, $value)
	{
		$ret = $this->masterRedis->sAdd($key, $value);
		return $ret;
	}

	/**
	 * @desc 删除名称为key的集合中的元素value
	 */
	public function sRem($key, $value)
	{
		$ret = $this->masterRedis->sRem($key, $value);
		return $ret;
	}

	/**
	 * @desc 将value元素从名称为srckey的集合移到名称为dstkey的集合
	 * 如果 source 集合不存在或不包含指定的 member 元素，则 SMOVE 命令不执行任何操作，return false
	 * 否则， member 元素从 source 集合中被移除，并添加到 destination 集合中去。
	 * 当 destination 集合已经包含 member 元素时， SMOVE 命令只是简单地将 source 集合中的 member 元素删除。
	 * 当 source 或 destination 不是集合类型时，return false
	 */
	public function sMove($srckey, $dstkey, $value)
	{
		$ret = $this->masterRedis->sMove($srckey, $dstkey, $value);
		return $ret;
	}

	/**
	 * @desc 名称为key的集合中查找是否有value元素，有ture 没有 false
	 */
	public function sIsMember($key, $value)
	{
		return $this->getRedis()->sIsMember($key, $value);
	}

	/**
	 * @desc 返回名称为key的集合的元素个数
	 */
	public function sSize($key)
	{
		return $this->getRedis()->sSize($key);
	}

	/**
	 * @desc 随机返回并删除名称为key的集合中一个元素
	 * 成功返回一个集合成员字符串，否则返回false
	 */
	public function sPop($key)
	{
		$result = $this->masterRedis->sPop($key);
		return $result;
	}

	/**
	 * @desc 随机返回名称为key的集合中一个元素，不删除
	 * 成功返回一个集合成员字符串，否则返回false
	 */
	public function sRandMember($key)
	{
		$result = $this->getRedis()->sRandMember($key);
		return $result;
	}

	/**
	 * @desc 求多个集合的交集
	 */
	public function sInter($keyList)
	{
		if (count($keyList) > 1){
			$key_str = "'".implode("','", $keyList)."'";
			$command = "\$res = \$this->getRedis()->sInter($key_str);";
			eval($command);
			return $res;
		}
		return false;
	}

	/**
	 * @desc 求交集并将交集保存到output的集合
	 */
	public function sInterStore($output, $keyList)
	{
		if ($output && count($keyList) > 1){
			$key_str = "'".implode("','", $keyList)."'";
			$command = "\$res = \$this->masterRedis->sInterStore('$output', $key_str);";
			eval($command);
			return $res;
		}
		return false;
	}

	/**
	 * @desc 求多个集合的并集
	 */
	public function sUnion($keyList)
	{
		if (count($keyList) > 1){
			$key_str = "'".implode("','", $keyList)."'";
			$command = "\$res = \$this->getRedis()->sUnion($key_str);";
			eval($command);
			return $res;
		}
		return false;
	}

	/**
	 * @desc 求并集并将交集保存到output的集合
	 */
	public function sUnionStore($output, $keyList)
	{
		if ($output && count($keyList) > 1){
			$key_str = "'".implode("','", $keyList)."'";
			$command = "\$res = \$this->masterRedis->sUnionStore('$output', $key_str);";
			return $res;
		}
		return false;
	}

	/**
	 * @desc 求多个集合的差集
	 */
	public function sDiff($keyList)
	{
		if (count($keyList) > 1){
			$key_str = "'".implode("','", $keyList)."'";
			$command = "\$res = \$this->getRedis()->sDiff($key_str);";
			eval($command);
			return $res;
		}
		return false;
	}

	/**
	 * @desc 求差集并将交集保存到output的集合
	 */
	public function sDiffStore($output, $keyList)
	{
		if ($output && count($keyList) > 1){
			$key_str = "'".implode("','", $keyList)."'";
			$command = "\$res = \$this->masterRedis->sDiffStore('$output', $key_str);";
			return $res;
		}
		return false;
	}

	/**
	 * @desc 返回名称为key的集合的所有元素
	 */
	public function sMembers($key)
	{
		$result = $this->getRedis()->sMembers($key);
		return $result;
	}

	/**
	 * @desc 对key对应的数据进行排序
	 */
	public function sort($key, $params = array())
	{
		$this->Redis->sort($key, $params);
	}

	/**
	 * @desc 返回原来key中的值，并将value写入key
	 */
	public function getSet($key, $value)
	{
		return $this->masterRedis->getSet($key, $value);
	}

	/**
	 * @desc 返回缓存2进制信息
	 */
	public function getBit($key)
	{
		return $this->getRedis()->getBit($key);
	}

	/**
	 * @desc 设置缓存2进制信息
	 */
	public function setBit($key, $value)
	{
		$ret = $this->masterRedis->setBit($key, $value);
		return $ret;
	}

	/**
	 * @desc 向名称为key的zset中添加元素member，score用于排序。如果该元素已经存在，则根据score更新该元素的顺序。
	 */
	public function zAdd($key, $score, $member)
	{
		$ret = $this->masterRedis->zAdd($key, $score, $member);
		return $ret;
	}

	/**
	 * @desc 返回名称为key的zset（元素已按score从小到大排序）中的index从start到end的所有元素
	 */
	public function zRange($key, $start = 0, $end = -1, $withscore = false)
	{
		$result = $this->getRedis()->zRange($key, $start, $end, $withscore);
		return $result;
	}

	/**
	 * @desc 删除名称为key的zset中的元素member
	 */
	public function zRem($key, $member)
	{
		$ret = $this->masterRedis->zRem($key, $member);
		return $ret;
	}

	/**
	 * @desc 返回名称为key的zset（元素已按score从大到小排序）中的index从start到end的所有元素.
	 * withscores: 是否输出socre的值，默认false，不输出
	 */
	public function zRevRange($key, $start = 0, $end = -1, $withscore = false)
	{
		$result = $this->getRedis()->zRevRange($key, $start, $end, $withscore);
		return $result;
	}

	/**
	 * @desc 返回名称为key的zset中score >= star且score <= end的所有元素
	 */
	public function zRangeByScore($key, $start, $end, $params = array())
	{
		$result = $this->getRedis()->zRangeByScore($key, $start, $end, $params);
		return $result;
	}

	/**
	 * @desc 返回名称为key的zset中score >= star且score <= end的所有元素
	 */
	public function zRevRangeByScore($key, $start, $end, $params = array())
	{
		//注意end 参数 和  start参数要互换位置才生效
		$result = $this->getRedis()->zRevRangeByScore($key, $end, $start, $params);
		return $result;
	}

	/**
	 * @desc 返回名称为key的zset中score >= star且score <= end的所有元素的个数
	 */
	public function zCount($key, $start, $end)
	{
		return $this->getRedis()->zCount($key, $start, $end);
	}

	/**
	 * @desc 返回名称为key的zset的所有元素的个数
	 */
	public function zSize($key)
	{
		return $this->getRedis()->zSize($key);
	}

	/**
	 * @desc 删除名称为key的zset中score >= star且score <= end的所有元素，返回删除个数
	 */
	public function zRemRangByScore($key, $start, $end)
	{
		$ret = $this->masterRedis->zRemRangByScore($key, $start, $end);
		return $ret;
	}

	/**
	 * @desc 返回名称为key的zset中元素val2的score
	 */
	public function zScore($key, $val)
	{
		$result = $this->getRedis()->zScore($key, $val);
		return $result;
	}

	/**
	 * @desc 返回名称为key的zset（元素已按score从小到大排序）中val元素的rank（即index，从0开始），若没有val元素，返回“null”。
	 */
	public function zRank($key, $val)
	{
		$result = $this->getRedis()->zRank($key, $val);
		return $result;
	}

	/**
	 * @desc 返回名称为key的zset（元素已按score从大到小排序）中val元素的rank（即index，从0开始），若没有val元素，返回“null”。
	 */
	public function zRevRank($key, $val)
	{
		$result = $this->getRedis()->zRevRank($key, $val);
		return $result;
	}

	/**
	 * @desc 如果在名称为key的zset中已经存在元素member，则该元素的score增加increment；否则向集合中添加该元素，其score的值为increment
	 */
	public function zIncrBy($key, $increment, $member)
	{
		$ret = $this->masterRedis->zIncrBy($key, $increment, $member);
		return $ret;
	}

	/**
	 * @desc 对N个zset求并集，并将最后的集合保存在dstkeyN中。
	 * 对于集合中每一个元素的score，在进行AGGREGATE运算前，都要乘以对于的WEIGHT参数。
	 * 如果没有提供WEIGHT，默认为1。默认的AGGREGATE是SUM，即结果集合中元素的score是所有集合对应元素进行SUM运算的值，
	 * 而MIN和MAX是指，结果集合中元素的score是所有集合对应元素中最小值和最大值
	 */
	public function zUnion($outputKey, $keys, $weights = array(), $fun = 'SUM')
	{
		$validate = $outputKey && is_array($keys) && count($keys) > 1;
		if (!$validate){return false;}
		if (isset($weights)){
			$validate = is_array($weights) && count($weights) == count($keys);
			if (!$validate){return false;}
		}
		$ret = $this->masterRedis->zUnion($outputKey, $keys, $weights, $fun);
		return $ret;
	}

	/**
	 * @desc 对N个zset求交集，并将最后的集合保存在dstkeyN中。
	 * 对于集合中每一个元素的score，在进行AGGREGATE运算前，都要乘以对于的WEIGHT参数。
	 * 如果没有提供WEIGHT，默认为1。默认的AGGREGATE是SUM，即结果集合中元素的score是所有集合对应元素进行SUM运算的值，
	 * 而MIN和MAX是指，结果集合中元素的score是所有集合对应元素中最小值和最大值
	 */
	public function zInter($outputKey, $keys, $weights = array(), $fun = 'SUM')
	{
		$validate = $outputKey && is_array($keys) && count($keys) > 1;
		if (!$validate){return false;}
		if (isset($weights)){
			$validate = is_array($weights) && count($weights) == count($keys);
			if (!$validate){return false;}
		}
		$ret = $this->masterRedis->zInter($outputKey, $keys, $weights, $fun);
		return $ret;
	}

	/**
	 * @desc 写hash数组缓存
	 * 如果 $key 是哈希表中的一个新建域，并且值设置成功，返回 1 。
	 * 如果哈希表中域 $key 已经存在且旧值已被新值覆盖，返回 0 。
	 */
	public function hSet($h, $key, $value)
	{
		$ret = $this->masterRedis->hSet($h, $key, $value);
		return $ret;
	}

	/**
	 * @desc 读hash数组缓存
	 */
	public function hGet($h, $key)
	{
		$result = $this->getRedis()->hGet($h, $key);
		return $result;
	}

	/**
	 * @desc 为哈希表key中的域field的值加上增量increment。增量也可以为负数，
	 *       相当于对给定域进行减法操作。
	 */
	public function hIncrby($h, $key,$inc)
	{
		$result = $this->getRedis(true)->hIncrBy($h, $key,$inc);
		return $result;
	}

	/**
	 * @desc 读hash数组的长度
	 */
	public function hLen($h)
	{
		return $this->getRedis()->hLen($h);
	}

	/**
	 * @desc 删除hash数组
	 */
	public function hDel($h, $key)
	{
		$ret = $this->masterRedis->hDel($h, $key);
		return $ret;
	}

	/**
	 * @desc 返回hash数组中所有的键
	 */
	public function hKeys($h)
	{
		$result = $this->getRedis()->hKeys($h);
		return $result;
	}

	/**
	 * @desc 返回hash数组中所有的值
	 */
	public function hVals($h)
	{
		$result = $this->getRedis()->hVals($h);
		return $result;
	}

	/**
	 * @desc 返回hash数组中所有的键值
	 */
	public function hGetAll($h)
	{
		$result = $this->getRedis()->hGetAll($h);
		return $result;
	}

	/**
	 * @desc 返回hash数组中键是否存在
	 */
	public function hExists($h, $key)
	{
		return $this->getRedis()->hExists($h, $key);
	}

	/**
	 * @desc 向名称为key的hash中批量添加元素
	 */
	public function hMset($h, $values)
	{
		$ret = $this->masterRedis->hMset($h, $values);
		return $ret;
	}

	/**
	 * @desc 返回名称为h的hash中field1,field2对应的value
	 */
	public function hMget($h, $fields)
	{
		$result = $this->getRedis()->hMget($h, $fields);
		return $result;
	}

	/**
	 * @desc 清空空当前数据库
	 */
	public function clear(){
		$ret = $this->masterRedis->flushDB();
		return $ret;
	}

	/**
	 * @desc 清空所有数据库
	 */
	public function clearAll(){
		$ret = $this->masterRedis->flushAll();
		return $ret;
	}

	/**
	 * @desc 随机返回key空间的一个key
	 */
	public function randomKey(){
		return $this->getRedis()->randomKey();
	}

	/**
	 * @desc 选择一个数据库
	 */
	public function select($num = 0){
		$num = intval($num);
		if ($num < 0 || $num > 15){
			$num = 0;
		}
		$this->masterRedis->select($num);
		return true;
	}

	/**
	 * @desc 转移一个key到另外一个数据库
	 */
	public function move($key, $num = 0){
		$num = intval($num);
		if ($num < 0 || $num > 15){
			$num = 0;
		}
		$this->masterRedis->move($key, $num);
		return true;
	}

	/**
	 * @desc 判断key是否存在
	 */
	public function exists($key)
	{
		return $this->getRedis()->exists($key);
	}

	/**
	 * @desc 设定一个key的活动时间（s）
	 */
	public function setTimeout($key, $sec)
	{
		$this->masterRedis->setTimeout($key, $sec);
		return true;
	}

	/**
	 * @desc key存活到一个unix时间戳时间
	 */
	public function expireAt($key, $unix_sec)
	{
		$this->masterRedis->expireAt($key, $unix_sec);
		return true;
	}

	/**
	 * @desc Increment the number stored at key by one. If the second argument is filled, it will be used as the integer
	 */
	public function incrBy($key, $value)
	{
		$ret = $this->masterRedis->incrBy($key, $value);
		return $ret;
	}

	/**
	 * @desc 返回满足给定pattern的所有key
	 */
	public function keys($pattern)
	{
		$result = $this->getRedis()->keys($pattern);
		return $result;
	}

	/**
	 * @desc 查看现在数据库有多少key
	 */
	public function dbSize()
	{
		return $this->getRedis()->dbSize();
	}

	/**
	 * @desc 查看Key的存活时间
	 */
	public function ttl($key)
	{
		return $this->getRedis()->ttl($key);
	}

	private function _initConnect($host, $port){
		$this->Redis = new Redis();
		$ret = $this->Redis->connect($host, $port);
		if ($ret){
			$this->connected = true;
		}
		else{
			Sys_Log::error("Redis connect error. host: $host, port: $port");
		}
	}

	private function _initMasterConnect($host, $port){
		$this->masterRedis = new Redis();
		$ret = $this->masterRedis->connect($host, $port);

		if ($ret){
			$this->masterConnected = true;
		}
		else{
			Sys_Log::error("Redis connect error. host: $host, port: $port");
		}
	}
}