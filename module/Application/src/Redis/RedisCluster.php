<?php

namespace Redis;

use RedisResouce;

class RedisCluster {

	private static $defaultNamespace = 'redis:';
	
	private $resources;

	private $aliases;

	private $hash;

	private $nodes;

	private $replicas = 128;

	private $dont_hash = array(
		'RANDOMKEY', 'DBSIZE',
		'SELECT',    'MOVE',    'FLUSHDB',  'FLUSHALL',
		'SAVE',      'BGSAVE',  'LASTSAVE', 'SHUTDOWN',
		'INFO',      'MONITOR', 'SLAVEOF'
	);

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
		'decrby',
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

	public function __construct($options) {
		if ($options && is_string($options)) {
    		$context = $options;
    		if (!is_file('config/redis.ini')) {
				throw new Exception(__('No redis.ini found'), 100);
			}
			$config = \Spyc::YAMLLoad('config/redis.ini');
			if (empty($config['redis']) || empty($config['redis'][$context])) {
				throw new Exception(__('Could not create connection to redis "%s"', array($context)));
			}
			$options = $config['redis'][$context];
    	}

    	if (isset($options['servers']) && is_array($options['servers'])) {
			$this->hash = array();
			$this->aliases = array();
			foreach ($options['servers'] as $alias => $server) {
				if (isset($server['host']) && isset($server['port'])) {
					$this->resources[] = new RedisResource($server['host'], $server['port']);
				} elseif (isset($server['host'])) {
					$this->resources[] = new RedisResource($server['host']);
				} else {
					continue;
				}

				if (is_string($alias)) {
					$this->aliases[$alias] = $this->resources[count($this->resources)-1];
				}
	 			for ($replica = 1; $replica <= $this->replicas; $replica++) {
					$this->hash[crc32($server['host'].':'.(!empty($server['port'])?$server['port']:6379).'-'.$replica)] = $this->resources[count($this->resources)-1];
				}
			}
			ksort($this->hash, SORT_NUMERIC);
			$this->nodes = array_keys($this->hash);
		}
	}

	public static function prefix($namespace) {
	    if (strpos($namespace, ':') === false) {
	        $namespace .= ':';
	    }
	    self::$defaultNamespace = $namespace;
	}



	function __casll($name, $args) {

		/* Pick a server node to send the command to */
		$name = strtoupper($name);
		if (!in_array($name, $this->dont_hash)) {
			$node = $this->nextNode(crc32($args[0]));
			$redisent = $this->ring[$node];
    	}
    	else {
			$redisent = $this->redisents[0];
    	}

		/* Execute the command on the server */
    	return call_user_func_array(array($redisent, $name), $args);
	}

	public function __scall($name, $args) {
		$args = func_get_args();
		if(in_array($name, $this->keyCommands)) {
			$args[1][0] = self::$defaultNamespace . $args[1][0];
		}
		try {
			return parent::__call($name, $args[1]);
		}
		catch(RedisException $e) {
			return false;
		}
	}

	public function __call($name, $args) {
		$args = func_get_args();
		if(in_array($name, $this->keyCommands)) {
			$args[1][0] = self::$defaultNamespace . $args[1][0];
		}

		// try {
			$name = strtoupper($name);
			if (!in_array($name, $this->dont_hash)) {
				$node = $this->nextNode(crc32($args[1][0]));
				$resource = $this->hash[$node];
	    	}
	    	else {
				$resource = $this->resources[0];
	    	}

			/* Execute the command on the server */
	    	return call_user_func_array(array($resource, $name), $args[1]);
		// }
		// catch(RedisException $e) {
		// 	return false;
		// }
	}

	public function to($alias) {
		if (isset($this->aliases[$alias])) {
			return $this->aliases[$alias];
		}
		else {
			throw new Exception("That Resouce alias does not exist");
		}
	}

	private function nextNode($needle) {
		$haystack = $this->nodes;
		while (count($haystack) > 2) {
			$try = floor(count($haystack) / 2);
			if ($haystack[$try] == $needle) {
				return $needle;
			}
			if ($needle < $haystack[$try]) {
				$haystack = array_slice($haystack, 0, $try + 1);
			}
			if ($needle > $haystack[$try]) {
				$haystack = array_slice($haystack, $try + 1);
			}
		}
		return $haystack[count($haystack)-1];
	}
}