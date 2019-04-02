<?php
// seconds
define('REDIS_TTL', 3600);
define('REDIS_URI', 'redis');

/**
 * @return Redis
 */
function redis_connect(){
	$redis = new Redis();
	$redis->connect(REDIS_URI);

	return $redis;
}

function redis_get(string $key){
	if (!$key){
		return null;
	}

	$redis = redis_connect();

	$value = $redis->get($key);

	return trim($value);
}

function redis_set(string $key, $value){
	if (!$key){
		return;
	}

	$redis = redis_connect();

	$redis->setEx($key, REDIS_TTL, $value);
}

function redis_set_array(string $key, array $arr){
	if (!$key){
		return;
	}

	$redis = redis_connect();

	$redis->setEx($key, REDIS_TTL, json_encode($arr));
}

function redis_get_array(string $key){
	if (!$key){
		return null;
	}

	$redis = redis_connect();

	$list = $redis->get($key);
	$list = json_decode($list);
	if (!is_array($list)){
		$list = [];
	}

	return $list;
}

function redis_add_to_array(string $key, $value){
	if (!$key){
		return null;
	}

	$redis = redis_connect();

	$list = $redis->get($key);
	$list = json_decode($list);
	if (!is_array($list)){
		$list = [];
	}
	$list[] = $value;

	$redis->setEx($key, REDIS_TTL, json_encode($list));

	return $list;
}
