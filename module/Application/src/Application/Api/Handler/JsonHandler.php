<?php
namespace Application\Api\Handler;

use ToroHook;

use Http\Request;
use Http\Response;
use Application\Log;


use Application\Handler\AbstractHandler,
	Application\Api\ApiException as Exception;

class JsonHandler extends AbstractHandler {

	protected $apiCallback;

	protected $apiKeyName;

	protected $apiMethod;

	protected $apiUrl;

	protected $authAdapter = 'Authentication\SignatureSimple';

	protected $layout = 'views/layout/layout.json.phtml';

	public function __construct() {
        parent::__construct();

		// Send Json Headers
        ToroHook::add('before_handler', function() {
        	$response = $this->getResponse();
        	$request = $this->getRequest();
			$headers = array(
	    		'Expires: Mon, 26 Jul 1997 05:00:00 GMT',
	    		'Last Modified: ' . gmdate('D, d, M T H:i:s') . 'GMT',
	    		'Cache-Control: no-store, no-cache, must-revalidate',
	    		'Cache-Control: post-check=0, pre-check=0',
	    		'Pragma: no-cache'
	    	);

       		if ($callback = $request->getQuery('callback', false)) {
 				$headers[] = 'Content-type: application/javascript';
   			} else {
   				$headers[] = 'Content-type: application/json';
   			}

        	$response->setHeaders($headers);
        }, 1000);

		// Check Permissions
		ToroHook::add('before_handler', function($params) {
            try {
            	$identity = $this->getIdentity();
            	if (!$identity->isValid()) {
					$identity = $this->getAuthAdapter('\Application\Api\Authentication\SignatureSimple')->getIdentity('unknown', 'guest');;
				} else {
		            $request = $this->getRequest();

		            // Auto-Deny all calls to empty resources
		  			$this->getAcl()->deny($identity->getRole(), '');
		            if (!$this->getAcl()->hasResource($this->resourceRoute)) {
		 				// Add the resource to the datbase if it doesn't exist
		         		$mapper = new \Acl\Db\AclMapper();
		    			$mapper->addResource($this->resourceRoute);
		                return null;
		            }
		        }

                try {
                    if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, $this->resourceAction)) {
                    	Log::warn($this->resourceRoute);
                        throw new Exception(__('Insufficent permissions to access this resource.'), Exception::ACL_PERMISSION_DENIED);
                    }
                } catch (Exception $e) {
	    			$msg = __("Role %s failed ACL check form %s:%s", array($identity->getRole(), $this->resourceRoute, $this->resourceAction));
					// Log Warning
	    			throw $e;
                } catch (\Exception $e) {
                	$msg = __("Authorization process failed {$e->getMessage()}");
                	// Log Error
                	throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            } catch (Exception $e) {
				echo $this->display('exception/exception.json.phtml', $e);
				exit();
            }
        });
	}


	public function display($view, $data = '', $extra = array()) {
		try {
			//
         	$apiKeyName = 'unknown';
   			if ($this->hasIdentity()) {
				$apiKeyName = $this->getIdentity();
   			}
   			$apiMethod = $this->getRequest()->getServer('PATH_INFO');
   			$content = $this->content;
   			$response = $this->getResponse();
   			$request = $this->getRequest();
   			if ($data instanceof Exception) {
   				$response->setStatusCode(500);
   				if ($data->getCode() == Exception::ACL_PERMISSION_DENIED) {
   					$response->setStatusCode(401);
   				}
   			}
   			$result =  parent::display($view, $data, compact('apiMethod', 'apiKeyName'));
   			if ($callback = $request->getQuery('callback', false)) {
   				if ($result instanceof Response) {
   					$result->setContent($callback . "(" . $result->getContent() .");");
   				} else {
   					$result = $callback ."(" . $result . ");";
   				}
   			}
   			return $result;
        } catch (Exception $e) {
			$this->display('exception/exception.json.phtml', $e);
		} catch (\Exception $e) {
			$this->display('exception/exception.json.phtml', $e);
		}
	}

	public function webApiDate($timestamp = null, $format = 'c') {
		if ($timestamp) {
			return date($format, strtotime($timestamp));
		}
		return '';
	}

	private function getApiCallback() {
		return $this->apiCallback;
	}

	private function getApiKeyName() {
		return $this->apiKeyName;
	}

	private function getApiMethod() {
		return $this->apiMethod;
	}

	public function getApiUrl() {
		return $this->apiUrl;
	}

	public function getMapper($mapperClass = null) {
		try {
			return parent::getMapper($mapperClass);
		} catch (\Application\Exception $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
	}

	protected function getParameters(array $defaults = array()) {
		$parameters = parent::getParameters($defaults);

        $request = $this->getRequest();

        $post_parameters = array();
        if ($request->getHeaders('Content-Type') == 'application/json') {
        	$post_parameters = json_decode($request->getContent(), true);
		}

		return array_merge($defaults, $parameters, (is_array($post_parameters)?$post_parameters:array()));
    }

    // public function getApiIdentity() {
    //     return $this->getAuthAdapter('\Application\Api\Authentication\SignatureSimple')->getIdentity('unknown');
    // }

    // public function hasApiIdentity() {
    //     $identity = $this->getApiIdentity();
    //     if ($identity instanceof \Application\Identity\AbstractIdentity) {
    //         return true;
    //     }
    //     return false;
    // }

}
