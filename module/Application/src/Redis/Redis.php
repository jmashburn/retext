<?php

namespace Redis;

use RedisResouce;

class Redis {

	protected $context;

	private $resouce;

	private static $defaultNamespace = 'redis:';

	private $keyCommands = array(
		'exists',
		'del',
		'type',
		'keys',
		'expire',
		'ttl',
		'move',
		'set',
		'get',
		'getset',
		'setnx',
		'incr',
		'incrby',
		'decr',
		'decrby',
		'rpush',
		'lpush',
		'llen',
		'lrange',
		'ltrim',
		'lindex',
		'lset',
		'lrem',
		'lpop',
		'rpop',
		'sadd',
		'srem',
		'spop',
		'scard',
		'sismember',
		'smembers',
		'srandmember',
		'zadd',
		'zrem',
		'zrange',
		'zrevrange',
		'zrangebyscore',
		'zcard',
		'zscore',
		'zremrangebyscore',
		'sort'
	);

	public function __construct($options = null) {

		if ($options && is_string($options)) {
    		$this->context = $options;
    	}

    	if (!$this->context) {
    		throw new \Exception(__('No context found'));
    	}

		if (!is_file('config/redis.ini')) {
			throw new \Exception(__('No redis.ini found'), 100);
		}
		$config = \Spyc::YAMLLoad('config/redis.ini');
		if (empty($config['redis']) || empty($config['redis'][$this->context])) {
			throw new \Exception(__('Could not create connection to redis "%s"', array($this->context)));
		}
		$options = 	$config['redis'][$this->context];

    	if (isset($options['server']['host']) && isset($options['server']['port'])) {
    		$this->resouce = new RedisResource($options['server']['host'], $options['server']['port']);
    	} elseif (isset($options['server']['host'])) {
    		$this->resouce = new RedisResource($options['server']['host']);
    	} else {
    		throw new \Exception(__('Could not create connect to redis. Check settings'));
    	}
    }


	public static function prefix($namespace) {
	    if (strpos($namespace, ':') === false) {
	        $namespace .= ':';
	    }
	    self::$defaultNamespace = $namespace;
	}

	public function __call($name, $args) {
		$args = func_get_args();
		if(in_array($name, $this->keyCommands)) {
		    $args[1][0] = self::$defaultNamespace . $args[1][0];
		}

		try {
			return $this->resouce->__call($name, $args[1]);
		}
		catch(RedisException $e) {
			return false;
		}
	}
}