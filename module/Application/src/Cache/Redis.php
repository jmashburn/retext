<?php

namespace Cache;

use Cache\Adapter\Redis as RedisAdapter;
use Application\Exception;

class Redis extends RedisAdapter {

    // public function subscribe(array $chan, $msg) {
    //     $redis = $this->getRedisResource();
    //     try {
    //         return $redis->subscribe($chan, $msg);
    //     } catch (Exception $e) {
    //         throw new Exception($redis->getLastError(), $e->getCode(), $e);
    //     }
    // }
    
    // public function publish($chan, $msg) {
    //     $redis = $this->getRedisResource();
    //     try {
    //         return $redis->publish($chan, $msg);
    //     } catch (Exception $e) {
    //         throw new Exception($redis->getLastError(), $e->getCode(), $e);
    //     }
    // }
    
    
    // public function lPush($key, $value) {
    //     $redis = $this->getRedisResource();
    //     try {
    //         return $redis->lPush($key, $value);
    //     } catch (Exception $e) {
    //         throw new Exception($redis->getLastError(), $e->getCode(), $e);
    //     }
    // }
    
    // public function lPop($key) {
    //     $redis = $this->getRedisResource();
    //     try {
    //         return $redis->lPop($key);
    //     } catch (Exception $e) {
    //         throw new Exception($redis->getLastError(), $e->getCode(), $e);
    //     }
    // }
    
    // public function rPush($key, $value) {
    //     $redis = $this->getRedisResource();
    //     try {
    //         return $redis->rPush($key, $value);
    //     } catch (Exception $e) {
    //         throw new Exception($redis->getLastError(), $e->getCode(), $e);
    //     }
    // }
    
    // public function lSize($key) {
    //     $redis = $this->getRedisResource();
    //     try {
    //         return $redis->lSize($key);
    //     } catch (Exception $e) {
    //         throw new Exception($redis->getLastError(), $e->getCode(), $e);
    //     }
    // }
}