<?php

namespace Cache\Adapter;

use Redis as RedisResource;
use Application\Exception;

class RedisResourceManager {

	protected $optionns;

	protected $resources = array();

	public function hasResource($id) {
		return isset($this->resources[$id]);
	}

	public function getResource($id) {
		if (!$this->hasResource($id)) {
			throw new Exception("No Resource with id {$id}'");
		}
		$resource = & $this->resources[$id];
		if ($resource['resource'] instanceof RedisResource) {
			if (!$resource['initialized']) {
				$this->connect($resource);
			}
			$info = $resource['resource']->info();
			$resource['version'] = $info['redis_version'];
			return $resource['resource'];
		}

		$redis = new RedisResource();
		$resource['resource'] = $redis;
		$this->connect($resource);

		foreach ($resource['lib_options'] as $k => $v) {
			$redis->setOption($k, $v);
		}

		$info = $redis->info();
		$resource['version'] = $info['redis_version'];
		$this->resources[$id]['resource'] = $redis;
		return $redis;
	}

	protected function connect(array & $resource) {
		$server = $resource['server'];
		$redis = $resource['resource'];
		if ($resource['persistent_id'] !== '') {
			$success = $redis->pconnect($server['host'], $server['port'], $server['timeout'], $server['persistent_id']);
		} elseif ($server['port']) {
            $success = $redis->connect($server['host'], $server['port'], $server['timeout']);
		} elseif ($server['timeout']) {
			$success = $redis->connect($server['host'], $server['timeout']);
		} else {
			$success = $redis->connect($server['host']);
		}

		if (!$success) {
			throw new Exception('Could not establish a connection with Redis instance');
		}

		$resource['initialized'] = true;
		if ($resource['password']) {
			$redis->auth($resource['password']);
		}
		$redis->select($resource['database']);
	}

	public function setResource($id, $resource) {
		$id = (string) $id;
		$defaults = array(
			'persistent_id' => '',
			'lib_options' => array(),
			'server' => array(),
			'password' => '',
			'database' => 0,
			'resource' => null,
			'initialized' => false,
			'version' => 0,
		);
		if (!$resource instanceof RedisResource) {
			if ($resource instanceof \Traversable) {
				$resource = \ArrayUtils::iteratorToArray($resource);
			} elseif (!is_array($resource)) {
				throw new Exception('Resource must be an instance of an array or Traversable');
			}

			$resource = array_merge($defaults, $resource);
			$this->normalizePersistentId($resource['persistent_id']);
			$this->normalizeLibOptions($resource['lib_options']);
			$this->normalizeServer($resource['server']);
		} else {
			$resource = array_merge($defaults, array(
				'resource' => $resource,
				'initialized' => isset($resource->socket)
			));
		}
		$this->resources[$id] = $resource;
		return $this;
	}

	public function removeResource($id) {
		unset($this->resources[$id]);
		return $this;
	}

	public function setPersistentId($id, $persistentId) {
		if(!$this->hasResource($id)) {
			return $this->setResource($id, array(
				'persistent_id' => $persistentId
			));
		}
		$resource = & $this->resources[$id];
		if ($resource instanceof RedisResource) {
			throw new Exception("Can't change persistent id of resource {$id} after instanziation");
		}

		$this->normalizePersistentId($persistentId);
		$resource['persistent_id'] = $persistentId;

		return $this;
	}

	public function getPersistentId($id) {
		if (!$this->hasResource($id)) {
			throw new Exception("No resource with id '{$id}'");
		}

		$resource = & $this->resources[$id];

		if ($resource instanceof RedisResource) {
			throw new Exception("Can't get persistent id of an instantiated redis resource");
		}
		return $resource['persistent_id'];
	}

	public function normalizePersistentId(& $persistentId) {
		$persistentId = (string) $persistentId;
	}

	public function setLibOptions($id, array $libOptions) {
		if (!$this->hasResource($id)) {
            return $this->setResource($id, array(
                'lib_options' => $libOptions
            ));
        }

        $this->normalizeLibOptions($libOptions);
        $resource = & $this->resources[$id];

        $resource['lib_options'] = $libOptions;

        if ($resource['resource'] instanceof RedisResource) {
            $redis = & $resource['resource'];
            if (method_exists($redis, 'setOptions')) {
                $redis->setOptions($libOptions);
            } else {
                foreach ($libOptions as $key => $value) {
                    $redis->setOption($key, $value);
                }
            }
        }
        return $this;
	}

	public function getLibOptions($id) {
        if (!$this->hasResource($id)) {
            throw new Exception\RuntimeException("No resource with id '{$id}'");
        }

        $resource = & $this->resources[$id];

        if ($resource instanceof RedisResource) {
            $libOptions = array();
            $reflection = new ReflectionClass('Redis');
            $constants  = $reflection->getConstants();
            foreach ($constants as $constName => $constValue) {
                if (substr($constName, 0, 4) == 'OPT_') {
                    $libOptions[$constValue] = $resource->getOption($constValue);
                }
            }
            return $libOptions;
        }
        return $resource['lib_options'];
    }

    public function setLibOption($id, $key, $value) {
    	return $this->setLibOptions($id, array($key => $valure));
    }

    public function getLibOption($id, $key) {
    	if (!$this->hasResource($id)) {
			throw new Exception("No resource with id '{$id}'");
    	}

    	$this->normalizeLibOptions($key);
    	$resource = & $this->resources[$id];

    	if ($resource instanceof RedisResource) {
    		return $resource->getOptino($key);
    	}

    	return isset($resource['lib_options'][$key]) ? $resource['lib_options'][$key] : null;
    }

	protected function normalizeLibOptions(& $libOptions) {
        if (!is_array($libOptions) && !($libOptions instanceof Traversable)) {
            throw new Exception("Lib-Options must be an array or an instance of Traversable");
        }

        $result = array();
        foreach ($libOptions as $key => $value) {
            $this->normalizeLibOptionKey($key);
            $result[$key] = $value;
        }

        $libOptions = $result;
    }

	protected function normalizeLibOptionKey(& $key) {
        // convert option name into it's constant value
        if (is_string($key)) {
            $const = 'Redis::OPT_' . str_replace(array(' ', '-'), '_', strtoupper($key));
            if (!defined($const)) {
                throw new Exception("Unknown redis option '{$key}' ({$const})");
            }
            $key = constant($const);
        } else {
            $key = (int) $key;
        }
    }

	public function setServer($id, $server) {
        if (!$this->hasResource($id)) {
            return $this->setResource($id, array(
                'server' => $server
            ));
        }

        $this->normalizeServer($server);

        $resource = & $this->resources[$id];
        if ($resource['resource'] instanceof RedisResource) {
            $this->setResource($id, array('server' => $server));
        } else {
            $resource['server'] = $server;
        }
        return $this;
    }

	public function getServer($id) {
        if (!$this->hasResource($id)) {
            throw new Exception("No resource with id '{$id}'");
        }

        $resource = & $this->resources[$id];
        return $resource['server'];
    }

	public function setPassword($id, $password) {
        if (!$this->hasResource($id)) {
            return $this->setResource($id, array(
                'password' => $password,
            ));
        }

        $resource = & $this->resources[$id];
        $resource['password']    = $password;
        $resource['initialized'] = false;
        return $this;
    }

	public function getPassword($id) {
        if (!$this->hasResource($id)) {
            throw new Exception("No resource with id '{$id}'");
        }

        $resource = & $this->resources[$id];
        return $resource['password'];
    }

	public function setDatabase($id, $database) {
        if (!$this->hasResource($id)) {
            return $this->setResource($id, array(
                'database' => (int)$database,
            ));
        }

        $resource = & $this->resources[$id];
        $resource['database']    = $database;
        $resource['initialized'] = false;
        return $this;
    }

	public function getDatabase($id) {
        if (!$this->hasResource($id)) {
            throw new Exception("No resource with id '{$id}'");
        }

        $resource = & $this->resources[$id];
        return $resource['database'];
    }

	public function getMayorVersion($id) {
        return $this->getMajorVersion($id);
    }

	public function getMajorVersion($id) {
        if (!$this->hasResource($id)) {
            throw new Exception("No resource with id '{$id}'");
        }

        $resource = & $this->resources[$id];
        return (int)$resource['version'];
    }

	protected function normalizeServer(& $server) {
        $host    = null;
        $port    = null;
        $timeout = 0;
        // convert a single server into an array
        if ($server instanceof \Traversable) {
            $server = \ArrayUtils::iteratorToArray($server);
        }

        if (is_array($server)) {
            // array(<host>[, <port>[, <timeout>]])
            if (isset($server[0])) {
                $host    = (string) $server[0];
                $port    = isset($server[1]) ? (int) $server[1] : $port;
                $timeout = isset($server[2]) ? (int) $server[2] : $timeout;
            }

            // array('host' => <host>[, 'port' => <port>, ['timeout' => <timeout>]])
            if (!isset($server[0]) && isset($server['host'])) {
                $host    = (string) $server['host'];
                $port    = isset($server['port'])    ? (int) $server['port']    : $port;
                $timeout = isset($server['timeout']) ? (int) $server['timeout'] : $timeout;
            }

        } else {
            // parse server from URI host{:?port}
            $server = trim($server);
            if (!strpos($server, '/') === 0) {
                //non unix domain socket connection
                $server = parse_url($server);
            } else {
                $server = array('host' => $server);
            }
            if (!$server) {
                throw new Exception("Invalid server given");
            }

            $host    = $server['host'];
            $port    = isset($server['port'])    ? (int) $server['port']    : $port;
            $timeout = isset($server['timeout']) ? (int) $server['timeout'] : $timeout;
        }

        if (!$host) {
            throw new Exception('Missing required server host');
        }

        $server = array(
            'host'    => $host,
            'port'    => $port,
            'timeout' => $timeout,
        );
    }


}