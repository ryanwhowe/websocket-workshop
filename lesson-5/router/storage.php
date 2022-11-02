<?php
// seconds
define('REDIS_TTL', 3600);
define('REDIS_URI', 'redis');

/**
 * @throws RedisException
 */
function redis_connect(): Redis{
	$redis = new Redis();
	$redis->connect(REDIS_URI);

	return $redis;
}

/**
 * @throws RedisException
 */
function redis_get(string $key): ?string{
	if (!$key){
		return null;
	}

	$redis = redis_connect();

	$value = $redis->get($key);

	return trim($value);
}

/**
 * @throws RedisException
 */
function redis_set(string $key, $value) :void{
	if (!$key){
		return;
	}

	$redis = redis_connect();

	$redis->setEx($key, REDIS_TTL, $value);
}

/**
 * @throws RedisException
 * @throws JsonException
 */
function redis_set_array(string $key, array $arr) :void{
	if (!$key){
		return;
	}

	$redis = redis_connect();

	$redis->setEx($key, REDIS_TTL, json_encode($arr, JSON_THROW_ON_ERROR));
}

/**
 * @throws RedisException
 * @throws JsonException
 */
function redis_get_array(string $key) :?array{
	if (!$key){
		return null;
	}

	$redis = redis_connect();

	$list = $redis->get($key);
	$list = json_decode($list, false, 512, JSON_THROW_ON_ERROR);
	if (!is_array($list)){
		$list = [];
	}

	return $list;
}

/**
 * @throws RedisException
 * @throws JsonException
 */
function redis_add_to_array(string $key, $value) :?array{
	if (!$key){
		return null;
	}

	$redis = redis_connect();

	$list = $redis->get($key);
	$list = json_decode($list, false, 512, JSON_THROW_ON_ERROR);
	if (!is_array($list)){
		$list = [];
	}
	$list[] = $value;

	$redis->setEx($key, REDIS_TTL, json_encode($list, JSON_THROW_ON_ERROR));

	return $list;
}
