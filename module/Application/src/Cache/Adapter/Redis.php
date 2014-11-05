<?php

namespace Cache\Adapter;

use Application\Exception;

class Redis extends AbstractAdapter {

	protected $namespace = 'cache';

	protected $namespaceSeparator = ':';

    protected $initialized = false;

    protected $resourceManager;

    protected $resourceId;

    protected $namespacePrefix = '';

    protected $options;

	protected $resource;

	protected $capabilities;

    public function __construct($options = null) {
        if (!extension_loaded('redis')) {
            throw new Exception("Redis extension is not loaded");
        }

    	if ($options && is_array($options)) {
    		$this->setOptions($options);
    	} elseif ($options && is_string($options)) {
    		$this->resourceId = $options;
    		if (!is_file('config/redis.ini')) {
				throw new Exception(__('No redis.ini found'), 100);
			}
			$options = \Spyc::YAMLLoad('config/redis.ini');
			if (empty($options['redis']) || empty($options['redis'][$this->resourceId])) {
				throw new Exception(__('Could not create connection to redis "%s"', array($this->resourceId)), 100);
			}
            $this->setOptions($options['redis'][$this->resourceId]);
    	}
    }

    protected function getRedisResource() {
        if (!$this->initialized) {
            $options = $this->getOptions();

            // get resource manager and resource id
            $this->resourceManager = $this->getResourceManager();

            // init namespace prefix
            $namespace = (!empty($options['namespace']) ? $options['namespace'] : $this->namespace);
            if ($namespace !== '') {
                $this->namespacePrefix = $namespace . (!empty($options['namespace_seperator']) ? $options['namespace_seperator'] : $this->namespaceSeparator);
            } else {
                $this->namespacePrefix = '';
            }

            // update initialized flag
            $this->initialized = true;
        }

        return $this->resourceManager->getResource($this->resourceId);
    }

    public function setOptions(array $options) {
    	if ($this->options !== $options) {
            $initialized = & $this->initialized;
            $initialized = false;

    		$this->options = $options;
    	}
    }

    public function getOptions() {

        return $this->options;
    }

    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null) {
        $redis = $this->getRedisResource();
        try {
            $value = $redis->get($this->namespacePrefix . $normalizedKey);
        } catch (RedisException $e) {
            throw new Exception\RuntimeException($redis->getLastError(), $e->getCode(), $e);
        }

        if ($value === false) {
            $success = false;
            return null;
        }

        $success = true;
        $casToken = $value;
        return $value;
    }

    protected function internalGetItems(array & $normalizedKeys) {
        $redis = $this->getRedisResource();

        $namespacedKeys = array();
        foreach ($normalizedKeys as & $normalizedKey) {
            $namespacedKeys[] = $this->namespacePrefix . $normalizedKey;
        }

        try {
            $results = $redis->mGet($namespacedKeys);
        } catch (RedisException $e) {
            throw new Exception\RuntimeException($redis->getLastError(), $e->getCode(), $e);
        }
        //combine the key => value pairs and remove all missing values
        return array_filter(
            array_combine($normalizedKeys, $results),
            function($value) {
                return $value !== false;
            }
        );
    }

    protected function internalHasItem(& $normalizedKey) {
        $redis = $this->getRedisResource();
        try {
            return $redis->exists($this->namespacePrefix . $normalizedKey);
        } catch (RedisException $e) {
            throw new Exception\RuntimeException($redis->getLastError(), $e->getCode(), $e);
        }
    }

    protected function internalSetItem(& $normalizedKey, & $value) {
        $redis = $this->getRedisResource();
        $ttl   = (!empty($this->options['ttl'])?(int)$this->options['ttl']:0);

        try {
            if ($ttl) {
                if ($this->resourceManager->getMajorVersion($this->resourceId) < 2) {
                    throw new Exception\UnsupportedMethodCallException("To use ttl you need version >= 2.0.0");
                }
                $success = $redis->setex($this->namespacePrefix . $normalizedKey, $ttl, $value);
            } else {
                $success = $redis->set($this->namespacePrefix . $normalizedKey, $value);
            }
        } catch (RedisException $e) {
            throw new Exception\RuntimeException($redis->getLastError(), $e->getCode(), $e);
        }

        return $success;
    }

    protected function internalSetItems(array & $normalizedKeyValuePairs) {
        $redis = $this->getRedisResource();
        $ttl   = (!empty($this->options['ttl'])?(int)$this->options['ttl']:0);

        $namespacedKeyValuePairs = array();
        foreach ($normalizedKeyValuePairs as $normalizedKey => & $value) {
            $namespacedKeyValuePairs[$this->namespacePrefix . $normalizedKey] = & $value;
        }
        try {
            if ($ttl > 0) {
                //check if ttl is supported
                if ($this->resourceManager->getMajorVersion($this->resourceId) < 2) {
                    throw new Exception\UnsupportedMethodCallException("To use ttl you need version >= 2.0.0");
                }
                //mSet does not allow ttl, so use transaction
                $transaction = $redis->multi();
                foreach ($namespacedKeyValuePairs as $key => $value) {
                    $transaction->setex($key, $ttl, $value);
                }
                $success = $transaction->exec();
            } else {
                $success = $redis->mSet($namespacedKeyValuePairs);
            }

        } catch (RedisException $e) {
            throw new Exception\RuntimeException($redis->getLastError(), $e->getCode(), $e);
        }
        if (!$success) {
            throw new Exception\RuntimeException($redis->getLastError());
        }

        return array();
    }

    protected function internalAddItem(& $normalizedKey, & $value) {
        $redis = $this->getRedisResource();
        try {
            return $redis->setnx($this->namespacePrefix . $normalizedKey, $value);
        } catch (RedisException $e) {
            throw new Exception\RuntimeException($redis->getLastError(), $e->getCode(), $e);
        }
    }

    protected function internalRemoveItem(& $normalizedKey) {
        $redis = $this->getRedisResource();
        try {
            return (bool) $redis->delete($this->namespacePrefix . $normalizedKey);
        } catch (RedisException $e) {
            throw new Exception\RuntimeException($redis->getLastError(), $e->getCode(), $e);
        }
    }
  
    protected function internalIncrementItem(& $normalizedKey, & $value) {
        $redis = $this->getRedisResource();
        try {
            return $redis->incrBy($this->namespacePrefix . $normalizedKey, $value);
        } catch (RedisException $e) {
            throw new Exception\RuntimeException($redis->getLastError(), $e->getCode(), $e);
        }
    }

    protected function internalDecrementItem(& $normalizedKey, & $value) {
        $redis = $this->getRedisResource();
        try {
            return $redis->decrBy($this->namespacePrefix . $normalizedKey, $value);
        } catch (RedisException $e) {
            throw new Exception($redis->getLastError(), $e->getCode(), $e);
        }
    }

    /*  Pub/Sub Stuff */

    protected function internalSubscribe(array $chan, $msg) {
        $redis = $this->getRedisResource();
        try {
            return $redis->subscribe($chan, $msg);
        } catch (RedisException $e) {
            throw new Exception($redis->getLastError(), $e->getCode(), $e);
        }
    }

    public function subscribe(array $chan, $msg) {
        try {
            return $result = $this->internalSubscribe($chan, $msg);
        } catch (\Exception $e) {
            return $result = array();
        }
    }
    
    protected function internalPublish($chan, $msg) {
        $redis = $this->getRedisResource();
        try {
            return $redis->publish($chan, $msg);
        } catch (RedisException $e) {
            throw new Exception($redis->getLastError(), $e->getCode(), $e);
        }
    }

    public function publish($chan, $msg) {
        try {
            return $result = $this->internalPublish($chan, $msg);
        } catch (\Exception $e) {
            return $result = array();
        }
    }

    protected function internalUnsubscribe($chan) {
        $redis = $this->getRedisResource();
        try {
            return $redis->unsubscribe($chan);
        } catch (RedisException $e) {
            throw new Exception($redis->getLastError(), $e->getCode(), $e);
        }
    }

    public function unsubscribe($chan, $msg) {
        try {
            return $result = $this->internalUnsubscribe($chan);
        } catch (\Exception $e) {
            return $result = array();
        }
    }

    protected function internalListPush(& $normalizedKeyValuePairs ) {
        $redis = $this->getRedisResource();
        $result = array();
        try {
            foreach ($normalizedKeyValuePairs as $normalizedKey => $value) {
                if (!$redis->lPush($this->namespacePrefix . $normalizedKey, $value)) {
                    $result[] = $normalizedKey;
                }
            }
        } catch (RedisException $e) {
            throw new Exception($redis->getLastError(), $e->getCode(), $e);
        }
        return $result;
    }

    public function listPush(array $keyValuePairs) {
        $this->normalizeKeyValuePairs($keyValuePairs);
        $args = new \ArrayObject(array(
            'keyValuePairs' => & $keyValuePairs,
        ));

        try {
            return $this->internalListPush($args['keyValuePairs']);
        } catch (\Exception $e) {
            return $result = array_keys($keyValuePairs);
        }
    }
    
    protected function internalListPop(& $normalizedKeyValuePairs) {
        $redis = $this->getRedisResource();
        $result = array();
        try {
            foreach ($normalizedKeyValuePairs as $normalizedKey => $value) {
                if (!$redis->lPop($this->namespacePrefix . $normalizedKey, $value)) {
                    $result[] = $normalizedKey;
                }
            }
        } catch (RedisException $e) {
            throw new Exception($redis->getLastError(), $e->getCode(), $e);
        }
        return $result;
    }

    public function listPop(array $keyValuePairs) {
        $this->normalizeKeyValuePairs($keyValuePairs);
        $args = new \ArrayObject(array(
            'keyValuePairs' => & $keyValuePairs,
        ));

        try {
            return $this->internalListPop($args['keyValuePairs']);
        } catch (\Exception $e) {
            return $result = array_keys($keyValuePairs);
        }
    }

    protected function internalListAppend(& $normalizedKeyValuePairs) {
        $redis = $this->getRedisResource();
        $result = array();
        try {
            foreach ($normalizedKeyValuePairs as $normalizedKey => $value) {
                if (!$redis->rPush($this->namespacePrefix . $normalizedKey, $value)) {
                    $result[] = $normalizedKey;
                }
            }
        } catch (RedisException $e) {
            throw new Exception($redis->getLastError(), $e->getCode(), $e);
        }
        return $result;
    }

    public function listAppend(array $keyValuePairs) {
        $this->normalizeKeyValuePairs($keyValuePairs);
        $args = new \ArrayObject(array(
            'keyValuePairs' => & $keyValuePairs,
        ));

        try {
            return $this->internalListAppend($args['keyValuePairs']);
        } catch (\Exception $e) {
            return $result = array_keys($keyValuePairs);
        } 
    }
    
    public function lSize($key) {
        $redis = $this->getRedisResource();
        try {
            return $redis->lSize($key);
        } catch (Exception $e) {
            throw new Exception($redis->getLastError(), $e->getCode(), $e);
        }
    }


    public function flush() {
        $redis = $this->getRedisResource();
        try {
            return $redis->flushDB();
        } catch (RedisException $e) {
            throw new Exception\RuntimeException($redis->getLastError(), $e->getCode(), $e);
        }
    }

    public function getTotalSpace() {
        $redis  = $this->getRedisResource();
        try {
            $info = $redis->info();
        } catch (RedisException $e) {
            throw new Exception\RuntimeException($redis->getLastError(), $e->getCode(), $e);
        }

        return $info['used_memory'];
    }

    protected function internalGetCapabilities() {
        if ($this->capabilities === null) {
            $this->capabilityMarker = new \stdClass();
            $minTtl = $this->getResourceManager()->getMajorVersion($this->resourceId) < 2 ? 0 : 1;
            //without serialization redis supports only strings for simple
            //get/set a
            $this->capabilities     = array(
                $this,
                $this->capabilityMarker,
                array(
                    'supportedDatatypes' => array(
                        'NULL'     => 'string',
                        'boolean'  => 'string',
                        'integer'  => 'string',
                        'double'   => 'string',
                        'string'   => true,
                        'array'    => false,
                        'object'   => false,
                        'resource' => false,
                    ),
                    'supportedMetadata'  => array(),
                    'minTtl'             => $minTtl,
                    'maxTtl'             => 0,
                    'staticTtl'          => true,
                    'ttlPrecision'       => 1,
                    'useRequestTime'     => false,
                    'expiredRead'        => false,
                    'maxKeyLength'       => 255,
                    'namespaceIsPrefix'  => true,
                )
            );
        }

        return $this->capabilities;
    }

    protected function getResourceManager() {
    	if (!$this->resource) {
    		$this->resource = new RedisResourceManager();
    		$this->resource->setResource($this->resourceId, $this->options);
    	}
    	return $this->resource;
    }
}