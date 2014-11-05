<?php

namespace Cache\Adapter;

use Application\Exception;

abstract class AbstractAdapter {

    protected $capabilities = null;

    protected $capabilityMarker;

    abstract protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null);

	public function getItem($key, & $success = null, & $casToken = null) {
        $this->normalizeKey($key);

        $argn = func_num_args();
        $args = array(
            'key' => & $key,
        );
        if ($argn > 1) {
            $args['success'] = & $success;
        }
        if ($argn > 2) {
            $args['casToken'] = & $casToken;
        }
        $args = new \ArrayObject($args);

        try {
            if ($args->offsetExists('success') && $args->offsetExists('casToken')) {
                $result = $this->internalGetItem($args['key'], $args['success'], $args['casToken']);
            } elseif ($args->offsetExists('success')) {
                $result = $this->internalGetItem($args['key'], $args['success']);
            } else {
                $result = $this->internalGetItem($args['key']);
            }
            return $result;
        } catch (\Exception $e) {
         	return false;
        }
    }

    protected function internalGetItems(array & $normalizedKeys) {
        $success = null;
        $result  = array();
        foreach ($normalizedKeys as $normalizedKey) {
            $value = $this->internalGetItem($normalizedKey, $success);
            if ($success) {
                $result[$normalizedKey] = $value;
            }
        }

        return $result;
    }

	public function getItems(array $keys) {
        $this->normalizeKeys($keys);
        $args = new ArrayObject(array(
            'keys' => & $keys,
        ));

        try {
            return $result = $this->internalGetItems($args['keys']);
        } catch (\Exception $e) {
            return $result = array();
        }
    }

    protected function internalHasItem(& $normalizedKey) {
        $success = null;
        $this->internalGetItem($normalizedKey, $success);
        return $success;
    }

  	public function hasItem($key) {
        $this->normalizeKey($key);
        $args = new \ArrayObject(array(
            'key' => & $key,
        ));

        try {
            return $this->internalHasItem($args['key']);
        } catch (\Exception $e) {
			return $result = false;
        }
    }

    protected function internalHasItems(array & $normalizedKeys) {
        $result = array();
        foreach ($normalizedKeys as $normalizedKey) {
            if ($this->internalHasItem($normalizedKey)) {
                $result[] = $normalizedKey;
            }
        }
        return $result;
    }

    public function hasItems(array $keys) {
        $this->normalizeKeys($keys);
        $args = new \ArrayObject(array(
            'keys' => & $keys,
        ));

        try {
            return $this->internalHasItems($args['keys']);
        } catch (\Exception $e) {
            return $result = array();
        }
    }

    abstract protected function internalSetItem(& $normalizedKey, & $value);

    public function setItem($key, $value) {
        $this->normalizeKey($key);
        $args = new \ArrayObject(array(
            'key'   => & $key,
            'value' => & $value,
        ));

        try {
            $result = $this->internalSetItem($args['key'], $args['value']);
            return $result;
        } catch (\Exception $e) {
            return $result = false;
        }
    }

    protected function internalSetItems(array & $normalizedKeyValuePairs) {
        $failedKeys = array();
        foreach ($normalizedKeyValuePairs as $normalizedKey => $value) {
            if (!$this->internalSetItem($normalizedKey, $value)) {
                $failedKeys[] = $normalizedKey;
            }
        }
        return $failedKeys;
    }

    public function setItems(array $keyValuePairs) {
        $this->normalizeKeyValuePairs($keyValuePairs);
        $args = new \ArrayObject(array(
            'keyValuePairs' => & $keyValuePairs,
        ));

        try {
           return $this->internalSetItems($args['keyValuePairs']);
        } catch (\Exception $e) {
            return array_keys($keyValuePairs);
        }
    }

    protected function internalDecrementItems(array & $normalizedKeyValuePairs) {
        $result = array();
        foreach ($normalizedKeyValuePairs as $normalizedKey => $value) {
            $newValue = $this->decrementItem($normalizedKey, $value);
            if ($newValue !== false) {
                $result[$normalizedKey] = $newValue;
            }
        }
        return $result;
    }

	public function decrementItems(array $keyValuePairs) {
        $this->normalizeKeyValuePairs($keyValuePairs);
        $args = new \ArrayObject(array(
            'keyValuePairs' => & $keyValuePairs,
        ));

        try {
            return $this->internalDecrementItems($args['keyValuePairs']);
        } catch (\Exception $e) {
            return $result = array();
        }
    }

    protected function internalDecrementItem(& $normalizedKey, & $value) {
        $success  = null;
        $value    = (int) $value;
        $get      = (int) $this->internalGetItem($normalizedKey, $success);
        $newValue = $get - $value;

        if ($success) {
            $this->internalReplaceItem($normalizedKey, $newValue);
        } else {
            $this->internalAddItem($normalizedKey, $newValue);
        }

        return $newValue;
    }

  	public function decrementItem($key, $value) {

        $this->normalizeKey($key);
        $args = new \ArrayObject(array(
            'key'   => & $key,
            'value' => & $value,
        ));

        try {
            return $this->internalDecrementItem($args['key'], $args['value']);
        } catch (\Exception $e) {
            return $result = false;
        }
    }

    protected function internalIncrementItems(array & $normalizedKeyValuePairs) {
        $result = array();
        foreach ($normalizedKeyValuePairs as $normalizedKey => $value) {
            $newValue = $this->internalIncrementItem($normalizedKey, $value);
            if ($newValue !== false) {
                $result[$normalizedKey] = $newValue;
            }
        }
        return $result;
    }

    public function incrementItems(array $keyValuePairs) {
        $this->normalizeKeyValuePairs($keyValuePairs);
        $args = new \ArrayObject(array(
            'keyValuePairs' => & $keyValuePairs,
        ));

        try {
            return $this->internalIncrementItems($args['keyValuePairs']);
        } catch (\Exception $e) {
            return $result = array();
        }
    }

    abstract protected function internalIncrementItem(& $normalizedKey, & $value);

	public function incrementItem($key, $value) {
        $this->normalizeKey($key);
        $args = new \ArrayObject(array(
            'key'   => & $key,
            'value' => & $value,
        ));

        try {
            return $this->internalIncrementItem($args['key'], $args['value']);
        } catch (\Exception $e) {
            return $result = false;
        }
    }

    protected function internalRemoveItems(array & $normalizedKeys) {
        $result = array();
        foreach ($normalizedKeys as $normalizedKey) {
            if (!$this->internalRemoveItem($normalizedKey)) {
                $result[] = $normalizedKey;
            }
        }
        return $result;
    }

	public function removeItems(array $keys) {
        $this->normalizeKeys($keys);
        $args = new ArrayObject(array(
            'keys' => & $keys,
        ));

        try {
            return $this->internalRemoveItems($args['keys']);
        } catch (\Exception $e) {
            return false;
        }
    }

    abstract protected function internalRemoveItem(& $normalizedKey);

    public function removeItem($key) {
        $this->normalizeKey($key);
        $args = new \ArrayObject(array(
            'key' => & $key,
        ));

        try {
            return $this->internalRemoveItem($args['key']);
        } catch (\Exception $e) {
            return $result = false;
        }
    }

    protected function internalTouchItems(array & $normalizedKeys) {
        $result = array();
        foreach ($normalizedKeys as $normalizedKey) {
            if (!$this->internalTouchItem($normalizedKey)) {
                $result[] = $normalizedKey;
            }
        }
        return $result;
    }

	public function touchItems(array $keys) {
        $this->normalizeKeys($keys);
        $args = new \ArrayObject(array(
            'keys' => & $keys,
        ));

        try {
            return $this->internalTouchItems($args['keys']);
        } catch (\Exception $e) {
        	return false;
        }
    }

    protected function internalTouchItem(& $normalizedKey) {
        $success = null;
        $value   = $this->internalGetItem($normalizedKey, $success);
        if (!$success) {
            return false;
        }

        return $this->internalReplaceItem($normalizedKey, $value);
    }

	public function touchItem($key) {
        $this->normalizeKey($key);
        $args = new \ArrayObject(array(
            'key' => & $key,
        ));

        try {
          	return $this->internalTouchItem($args['key']);
        } catch (\Exception $e) {
            return $result = false;
        }
    }

    protected function internalCheckAndSetItem(& $token, & $normalizedKey, & $value) {
        $oldValue = $this->internalGetItem($normalizedKey);
        if ($oldValue !== $token) {
            return false;
        }

        return $this->internalSetItem($normalizedKey, $value);
    }

	public function checkAndSetItem($token, $key, $value) {
        $this->normalizeKey($key);
        $args = new \ArrayObject(array(
            'token' => & $token,
            'key'   => & $key,
            'value' => & $value,
        ));

        try {
            return $this->internalCheckAndSetItem($args['token'], $args['key'], $args['value']);
        } catch (\Exception $e) {
            return $result = false;
        }
    }

    protected function internalReplaceItems(array & $normalizedKeyValuePairs) {
        $result = array();
        foreach ($normalizedKeyValuePairs as $normalizedKey => $value) {
            if (!$this->internalReplaceItem($normalizedKey, $value)) {
                $result[] = $normalizedKey;
            }
        }
        return $result;
    }

	public function replaceItems(array $keyValuePairs) {
        $this->normalizeKeyValuePairs($keyValuePairs);
        $args = new \ArrayObject(array(
            'keyValuePairs' => & $keyValuePairs,
        ));

        try {
      		return $this->internalReplaceItems($args['keyValuePairs']);
        } catch (\Exception $e) {
            return $result = array_keys($keyValuePairs);
        }
    }

    protected function internalReplaceItem(& $normalizedKey, & $value) {
        if (!$this->internalhasItem($normalizedKey)) {
            return false;
        }

        return $this->internalSetItem($normalizedKey, $value);
    }

	public function replaceItem($key, $value) {
        $this->normalizeKey($key);
        $args = new \ArrayObject(array(
            'key'   => & $key,
            'value' => & $value,
        ));

        try {
            return $result = $this->internalReplaceItem($args['key'], $args['value']);
        } catch (\Exception $e) {
            return $result = false;
        }
    }

    protected function internalAddItems(array & $normalizedKeyValuePairs) {
        $result = array();
        foreach ($normalizedKeyValuePairs as $normalizedKey => $value) {
            if (!$this->internalAddItem($normalizedKey, $value)) {
                $result[] = $normalizedKey;
            }
        }
        return $result;
    }

	public function addItems(array $keyValuePairs) {
        $this->normalizeKeyValuePairs($keyValuePairs);
        $args = new \ArrayObject(array(
            'keyValuePairs' => & $keyValuePairs,
        ));

        try {
            return $this->internalAddItems($args['keyValuePairs']);
        } catch (\Exception $e) {
            return $result = array_keys($keyValuePairs);
        }
    }

    protected function internalAddItem(& $normalizedKey, & $value) {
        if ($this->internalHasItem($normalizedKey)) {
            return false;
        }
        return $this->internalSetItem($normalizedKey, $value);
    }

	public function addItem($key, $value) {
        $this->normalizeKey($key);
        $args = new \ArrayObject(array(
            'key'   => & $key,
            'value' => & $value,
        ));

        try {
            return $this->internalAddItem($args['key'], $args['value']);
        } catch (\Exception $e) {
            return $result = false;
        }
    }

    protected function internalGetCapabilities() {
        if ($this->capabilities === null) {
            $this->capabilityMarker = new \stdClass();
            $this->capabilities     = array($this, $this->capabilityMarker);
        }
        return $this->capabilities;
    }

    public function getCapabilities() {
        $args = new \ArrayObject();

        try {
            return $this->internalGetCapabilities();
        } catch (\Exception $e) {
            return $result = false;
        }
    }

 	protected function normalizeKey(& $key) {

        $key = (string) $key;
    }

	protected function normalizeKeys(array & $keys) {
        if (!$keys) {
            throw new Exception("An empty list of keys isn't allowed");
        }

        array_walk($keys, array($this, 'normalizeKey'));
        $keys = array_values(array_unique($keys));
    }

	protected function normalizeKeyValuePairs(array & $keyValuePairs) {
        $normalizedKeyValuePairs = array();
        foreach ($keyValuePairs as $key => $value) {
            $this->normalizeKey($key);
            $normalizedKeyValuePairs[$key] = $value;
        }
        $keyValuePairs = $normalizedKeyValuePairs;
    }

}