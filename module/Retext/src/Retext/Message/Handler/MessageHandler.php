<?php

namespace Retext\Message\Handler;

use Db\Mapper\ApiMapper; 

use Application\Api\Handler\JsonHandler,
    Application\Api\ApiException as Exception;

class MessageHandler extends JsonHandler {

	protected $mapperClass = "Retext\Message\Db\MessageMapper";

	protected $authAdapter = 'Application\Authentication\Authentication';

	//  List All/Single Messages(s)
	public function get($key = null) {
		try {
			// Check if User is allowed to List all Keys
			$owner = null;
			if ($this->getAcl()->hasResource($this->resourceRoute)) {
				$identity = $this->getIdentity();
				if (!$identity->isValid()) {
					throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
				}

				
				if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_LIST_ALL)) {
					$owner = $identity->getUsername();
				}
			}

			if ($key == "chart") {
				return $this->chart(7);
			} 

			if ($key == 'count') {
				return $this->count();
			}

			if ($key) {
				$messages = $this->getMapper()->findMessageByKey($key, $owner);
				if (empty($messages[0]['id'])) {
					throw new Exception(__('Message: "%s" does not exist. Please try again.', array($retext)), Exception::WEBAPI_VALUE_ADD_FAILED);
				}
			} else {
				$messages = $this->getMapper()->findAllMessages($owner);
			}
			\Event::fire('retext.message.found', compact('messages'));
			return $this->display('retext/message/list.json.phtml', compact('messages'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	// Add/Update Message(s)
	public function post($key = null) {
		try {
			if (!$this->hasIdentity()) {
				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}

			$params = $this->getParameters(array('status' => 'pending'));
			$this->validateMandatoryParameters($params, array('code', 'message_sent', 'message_received'));
			$this->validateString($params['code'], 'code');
			$this->validateString($params['message_sent'], 'message_sent');
			$this->validateString($params['message_received'], 'message_received');

			$this->validateString($params['status'], 'status');
			$this->validateAllowedValues($params['status'], 'status', array('pending'));

			$identity = $this->getIdentity();
			if (!$identity->isValid()) {
 				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}
			$params = $this->getParameters(array('username' => $identity->getUsername()));

			if ($identity->getUsername() !== $params['username']) {
				if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_CREATE_ALL)) {
					// Log/Audit
					throw new Exception(__('Unable to create hook: "%s" for "%s". Please check permissions.', array($params['end_point'])));
				}

				// Check Username is valid.
				$account = $this->getMapper('Account\Db\AccountMapper')->findAccountByName($params['username']);
				if (empty($account[0]['id'])) {
					throw new Exception(__('Account: "%s" does not exist. Please try again.', array($params['username'])), Exception::WEBAPI_VALUE_ADD_FAILED);
				}
			}
			$messages = $this->getMapper()->addMessage($params);
			\Event::fire('retext.message.created', compact('messages'));
			return $this->display('retext/message/list.json.phtml', compact('messages'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	// Remove the Retext
	public function delete($key = null) {
		try {
			$params = $this->getParameters(array('key' => $key));
			$owner = null;
			if (!$this->hasIdentity()) {
				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}
			
			$identity = $this->getIdentity();
			if (!$identity->isValid()) {
				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}

			// Either We have ids or disabling a key
			if (!$this->getAcl()->isAllowed($this->getIdentity()->getRole(), $this->resourceRoute, self::PERMISSION_DELETE_ALL)) {
				$owner = $this->getIdentity()->getUsername();
			}

			$deletes = array();
			$messages = array();
			if (!empty($key)) {
				$messages = $this->getMapper()->findMessageByKey($key, $owner);
				if (!empty($messages[0]['id'])) {
					$deletes['ids'][] = $messages[0]['id'];
				}
			} elseif (!empty($params['ids'])) {
				$messages = $this->getMapper()->findAllMessages($owner);
				foreach ($messages->toArray() as $key => $message) {
					if (!in_array($message['key'], $params['ids'])) {
						unset($messages[$key]);
					} else {
						$deletes['ids'][] = $message['id'];
					}
				}
			}

			if (!empty($deletes['ids'])) {
				$result = $this->getMapper()->deleteMessagesById($deletes['ids']);
				\Event::fire('retext.message.deleted', compact('result'));
			} else {
				throw new Exception(__('No Retexts were deleted. Please check permissions'), Exception::AUTH_ERROR);
			}

			return $this->display('retext/message/delete.json.phtml', compact('messages'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	private function chart($day = 7) {
		$this->layout = "views/layout/blank.phtml";
		$end = time();
		$start = strtotime('-'.$day.' days');
		$sql = " WHERE `creation_time` >= '$start' AND `creation_time` <= '$end'";
		// Query Date less than now and greather than 7 days ago.
		$messages = $this->getMapper()->findAllMessages(null, compact('sql') );

		$codes = array();
		$times = array();
		foreach ($messages->toArray() as $message) {
			if (!isset($codes[$message['code']])) {
				$codes[$message['code']] = 1;
			} else {
				++$codes[$message['code']];
			}

			$day = date('Y-m-d', $message['creation_time']);
			if (!isset($times[$day])) {
				$times[$day] = 1;
			} else {
				++$times[$day];
			}
		}
		$result['codes'] = array();
		foreach ($codes as $key => $value) {
			$tmp = array('label' => $key, 'value' => $value);
			array_push($result['codes'], $tmp);
		}

		$result['messages'] = array();
		foreach ($times as $key => $value) {
			$tmp = array('period' => $key, 'messages' => $value);
			array_push($result['messages'], $tmp);
		}

		$messages = json_encode($result);

		return $this->display('retext/message/blank.json.phtml', $messages);
	}

	private function count($day = 7) {
		$this->layout = "views/layout/blank.phtml";

		$end = time();
		$start = strtotime('-'.$day.' days');
		$sql = " WHERE `creation_time` >= '$start' AND `creation_time` <= '$end'";
		$count = $this->getMapper()->count("SELECT COUNT(*) FROM retext_messages" . $sql);
		$count = json_encode(array('count' => $count));
		return $this->display('retext/message/blank.json.phtml', $count);
	}
}
