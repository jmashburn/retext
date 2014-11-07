<?php
namespace Application\Handler;

use ToroHook;

use \Http\Request as HttpRequest;
use \Http\Response as HttpResponse;

#use Application\Api\Exception as Exception;
//use Application\Exception as Exception;
use Application\Api\ApiException as Exception;
use Application\Identity\GuiIdentity as Identity;
use Application\Identity\AbstractIdentity;
use Application\Log;

use \Acl\Acl;

abstract class AbstractHandler {

    const PERMISSION_LIST_ALL   = 'list_all';
    const PERMISSION_LIST       = 'list';
    const PERMISSION_CREATE_ALL = 'create_all';
    const PERMISSION_CREATE     = 'create';
    const PERMISSION_DELETE_ALL = 'delete_all';
    const PERMISSION_DELETE     = 'delete';
    const PERMISSION_UPDATE_ALL = 'update_all';
    const PERMISSION_UPDATE     = 'update';

    public $routes = array();

    protected $authAdapter = 'Authentication\AbstractAuthentication';

    protected $identityClass = "Identity\GuiIdentity";

    protected $mapperClass;

    protected $acl;

    public $content;

    private $request;

    private $response;

    protected $identity;

    protected $resourceRoute;

    protected $resourceAction;

    private static $_authAdapterInstance = array();

    private static $_mapperInstance = array();

    protected $layout;

    protected $title;

    public function __construct() {

        ToroHook::add('before_handler', function($params) {
            try {
                $headers = $this->getRequest()->getHeaders();
                if (!empty($headers['X-Api-Signature'])) {
                    $identity = $this->getIdentity();
                    if ($identity instanceof \Application\Identity\AbstractIdentity) {
                        $auth = $this->getAuthAdapter('\Application\Api\Authentication\SignatureSimple');
                        $auth->setMapper(new \Application\Api\Db\Mapper());

                        $apiIdentity = $auth->authenticate();

                        if (!$apiIdentity instanceof \Application\Identity\AbstractIdentity) {
                            throw new Exception(__('No identity found. Check user'), Exception::AUTH_ERROR);
                        }
                        
                        // Not Sure whats up here and why I need this
                        // if ($identity->isValid()) {
                        //     $identity->setIdentity($apiIdentity->getIdentity());
                        //     $identity->setOwner($apiIdentity->getUsername());
                        // } else {
                        //     $identity = $apiIdentity;
                        // }

                        $identity = $apiIdentity;
            
                        if (!$identity->isValid()) {
                            throw new Exception($apiIdentity->getMessage(), Exception::AUTH_ERROR);
                        }
                    }
                    $this->setIdentity($identity);
                }
            } catch (\Exception $e) {
                echo $this->display('exception/exception.json.phtml', $e);
                exit();
            }

        });

        ToroHook::add('before_handler', function($params) {
            $this->resourceRoute = "route:" . $params['discovered_handler'];
            $this->resourceAction = $params['request_method'];
        });

        ToroHook::add('before_handler', function() { 
            try {
                //  Store in Cache so its not loaded each time.
                $aclMapper = new \Acl\Db\AclMapper();
                $roles = $aclMapper->getRoles();
                if ($roles) {
                    foreach ($roles as $role) {
                        $this->getAcl()->addRole($role['role_name'], $role['parent_name']);
                    }
                }

                $this->getAcl()->addResource('');
                $resources = $aclMapper->getResources();
                if ($resources) {
                    foreach ($resources as $resource) {
                        $this->getAcl()->addResource($resource['resource_name']);
                    }
                }

                $privileges = $aclMapper->getPrivileges();
                if ($privileges) {
                    foreach ($privileges as $privilege) {
                        $this->getAcl()->allow($privilege['role_name'], $privilege['resource_name'], $privilege['allowed_actions']);
                    }
                }
            } catch (Exception $e) {
                $this->display('exception/exception.json.phtml', $e);
            }
        });

        ToroHook::add('after_handler', function($params) {
            if (!empty($params['result'])) {
                echo $params['result'];
            }
        }, -999);
    }

    private function __clone() { }

    public function display($view, $data = '', $extra = array()) {
        try {
            $response = $this->getResponse();
            $file = false;
            foreach (\Config::getConfig('view_paths', array('view/')) as $path) {
                if (is_file(\Config::getConfig('base_dir') . DIRECTORY_SEPARATOR . $path . $view)) {
                    $file = \Config::getConfig('base_dir') . DIRECTORY_SEPARATOR . $path . $view;
                }
            }
            if (!empty($extra)) extract($extra);
            if ($file) {
                ob_start();
                include $file;
                $content = ob_get_clean();
            }

            $title = $this->getTitle();
            ob_start();
            include $this->getLayout();
            $content = ob_get_clean();
            $response->setContent($content);
            return $response->send();
        } catch (Exception $e) {
            $this->display('exception/exception.json.phtml', $e);
        } catch (\Exception $e) {
            $this->display('exception/exception.json.phtml', $e);
        }
    }

    public function setLayout($layout) {
        $this->layout = $layout;
    }

    public function getLayout() {
        return $this->layout;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    protected function getParameters(array $defaults = array()) {
       # $routeParams = $this->getEvent()->getRouteMatch()->getParams();

        $request = $this->getRequest();

        $parameters = $request->getQuery();
        
        // Gets Empty Parameters if not post params exist
        $post_parameters = $request->getPost();

        // POST Params take precedence over GET
        $parameters = array_merge($defaults, $parameters, $post_parameters);

        return $parameters;
    }
    
    protected function printParameters($parameters) {
        //Log::debug("'{$this->getCmdName()}': the following WebAPI parameters will be used: " . trim(print_r($this->maskParameters($parameters->toArray()), true)));
    }
    
    protected function maskParameters(array $parameters) {
        $maskedParameters = array();
        foreach ($parameters as $name => $value) {
            if (is_array($value)) {
                $maskedParameters[$name] = $this->maskParameters($value);
                continue;
            }
            
            if (preg_match('@password@i', $name)) {
                $maskedParameters[$name] = '***********';
            } else {
                $maskedParameters[$name] = $value;
            }
        }
        return $maskedParameters;
    }
    
    protected function validateMandatoryParameters(array $requestParams, array $params) {
        foreach ($params as $param) {
            if (( !isset($requestParams[$param])) || ('' == $requestParams[$param])) {
                throw new Exception(__('This action requires the %s parameter', array($param)), Exception::MISSING_PARAMETER);
            }
        }
    }
    
    protected function validateArray($array, $parameterName) {
        if (!is_array($array)) {
            throw new Exception(__("Parameter '%s' must be an array", array($parameterName)), Exception::INVALID_PARAMETER);
        }
        return $array;
    }

    protected function validateArrayNotEmpty($array, $parameterName) {
        $array = $this->validateArray($array, $parameterName);
        if (!$array) {
            throw new Exception(__('Parameter "%s" must be a NON-empty array. Empty array received.', array($parameterName)), Exception::INVALID_PARAMETER);
        }
        return $array;
    }
    
    protected function validateInteger($integer, $parameterName) {
        if (!is_numeric($integer)) {
            throw new Exception(__("Parameter '%s' must be a integer", array($parameterName)), Exception::INVALID_PARAMETER);
        }
        return intval($integer);
    }

    protected function validateMaxInterger($integer, $limit, $parameterName) {
        $integer = $this->validateInteger($integer, $parameterName);
        if ($integer > $limit) {
            throw new Exception(__('Parameter "%s" must be smaller than %s', array($parameterName, $limit)), Exception::INVALID_PARAMETER);
        }
        return $integer;
    }
    
    protected function validateString($string, $parameterName) {
        if (!is_string($string)) {
            throw new Exception(__("Parameter '%s' must be a string", array($parameterName)), Exception::INVALID_PARAMETER);
        }
        return $string;
    }
    
    protected function validateBoolean($param, $parameterName) {
        $param = strtoupper($param);
        if ($param == true || $param === '1') {
            return true;
        } elseif ($param == false || $param === '0') {
            return false;
        } else {
            throw new Exception(__("Parameter '%s' must be either TRUE|1 or FALSE|0", array($parameterName)), Exception::INVALID_PARAMETER);
        }
        return $param;
    }

    protected function validateAllowedValues($value, $parameterName, array $allowedValues) {
        $lowerCaseValue = strtolower($value);
        $allowedValuesKeys = array_change_key_case(array_flip($allowedValues), CASE_LOWER);

        if (!isset($allowedValuesKeys[$lowerCaseValue])) {
            $alloweValuesStr = implode(',', $allowedValues);
            throw new Exception (__("Parameter '%s' must be one of the following values: %s. Value passed: '%s'", 
                array($parameterName, $alloweValuesStr, $value)), Exception::INVALID_PARAMETER);
        }
        return $value;
    }

    protected function validateRegex($value, $parameterName) {
        if (@preg_match("#" . $value ."#", "") === false) {
            throw new Exception(__('Parameter "%s" must be a valid regular expression', array($parameterName)), Exception::INVALID_PARAMETER);
        }
    }

    protected function validatePositiveInteger($integer, $parameterName) {
        $integer = $this->validateInteger($integer, $parameterName);
        if ($integer <= 0) {
            throw new Exception(__('Parameter "%s", must be a positive integer', array($parameterName)), Exception::INVALID_PARAMETER);
        }
        return $integer;
    }

    protected function validateEmail($email, $parameterName, $empty = false) {
        if ($this->validateFilter($email, $parameterName, FILTER_VALIDATE_EMAIL, $empty)) {
            throw new Exception(__('Parameter "%s" must be a valid email address.', array($parameterName)), Exception::INVALID_PARAMETER);
        }
        return $email;
    }

    protected function validateUri($uri, $parameterName, $empty = false) {
        if ($this->validateFilter($uri, $parameterName, FILTER_VALIDATE_URL, $empty)) {
            throw new Exception(__('Parameter "%s" must be a valid URL.', array($parameterName)), Exception::INVALID_PARAMETER);
        }
        return $uri;
    }

    protected function validateOffset($offset) {
        return $this->validateInteger($offset, 'offset');
    }

    protected function validateLimit($limit) {
        return $this->validateInteger($limit, 'limit');
    }

    protected function validateFilter($param, $parameterName, $filter, $empty = false) {
        if (!$empty || (isset($param) && ('' != $param))) {
            if (!filter_var($param, $filter)) {
                throw new Exception(__("Parameter '%s' is invalid.", array($parameterName)), Exception::INVALID_PARAMETER);
            }
        }
    }

    protected function validateOwner($key = null) {
        $request = $this->getRequest();
        switch (strtolower($request->getMethod())) {
            case 'get':
                $global = self::PERMISSION_LIST_ALL;
                $local = self::PERMISSION_LIST;
                break;
            case 'post':
                $global = self::PERMISSION_CREATE_ALL;
                $local = self::PERMISSION_CREATE;
                break;
            case 'put':
                $global = self::PERMISSION_UPDATE_ALL;
                $local = self::PERMISSION_UPDATE;
                break;
            case 'delete':
                $global = self::PERMISSION_DELETE_ALL;
                $local = self::PERMISSION_DELETE;
                break;
        }
        $owner = null;
        $identity = $this->getIdentity();

        if ($this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, $global)) {
            return null;
        }
        
        if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, $local)) {
            if (empty($key) || $key !== $identity->getUsername()) {
                throw new Exception(__('Unable access resource. Please check permissions.'), Exception::ACL_PERMISSION_DENIED);
            }
        }
        return $identity->getOwner();

    }

    protected function isMethodPut() {
        if (!$this->isMethod('PUT')) {
            throw new Exception(__('This action requires a HTTP PUT method'), Exception::UNEXPECTED_HTTP_METHOD);
        }
    }
    
    protected function isMethodPost() {
        if (!$this->isMethod('POST')) {
            throw new Exception(__('This action requires a HTTP POST method'), Exception::UNEXPECTED_HTTP_METHOD);
        }
    }
    
    protected function isMethodGet() {
        if (!$this->isMethod('GET')) {
            throw new Exception(__('This action requires a HTTP GET method'), Exception::UNEXPECTED_HTTP_METHOD);
        }
    }

    protected function isMethodDelete() {
        if (!$this->isMethod('DELETE')) {
            throw new Exception(__('This action requires a HTTP DELETE method'), Exception::UNEXPECTED_HTTP_METHOD);
        }
    }
    
    private function isMethod($method) {
        $request = $this->getRequest();
        return strcasecmp($request->getMethod(), $method) === 0;
    }

    public function setRequest(HttpRequest $request) {
        $this->request = $request;
    }

    public function getRequest() {
        if (!$this->request) {
            $this->request = new HttpRequest();
        }
        return $this->request;
    }

    public function getResponse() {
        if (!$this->response) {
            $this->response = new HttpResponse();
        }
        return $this->response;
    }

    public function setIdentity(AbstractIdentity $identity) {
        $this->identity = $identity;
    }

    public function getIdentity() {
        if (!$this->identity) {
            return $this->getAuthAdapter()->getIdentity();
        }
        return $this->identity;
    }

    public function hasIdentity() {
        $identity = $this->getIdentity();
        if ($identity instanceof AbstractIdentity && $identity->isValid()) {
            return true;
        }
        return false;
    }

    public function clearIdentity() {

    }

    public function isAllowed($permission = null) {
        if ($permission) {
            if (!$this->getAcl()->isAllowed($this->getIdentity()->getRole(), $this->resourceRoute, $permission)) {
                // Audit
                $msg = __('No access to privilege: "%s" for User: "%s:%s".', array($permission, $this->getIdentity()->getUsername(), $this->getIdentity()->getRole()));
                Log::err($msg);
                throw new Exception($msg, Exception::INSUFFICIENT_ACCESS_LEVEL);
            }
        }
        return true;
    }

    public function getAuthAdapter($authAdapter=null) {
        if (!is_null($authAdapter) && class_exists($authAdapter)) {
            $this->authAdapter = $authAdapter;
        }

        if (class_exists($this->authAdapter)) {
            if (empty(self::$_authAdapterInstance[$this->authAdapter]) || !self::$_authAdapterInstance[$this->authAdapter]) {
               self::$_authAdapterInstance[$this->authAdapter] = new $this->authAdapter();
            }
            return self::$_authAdapterInstance[$this->authAdapter];
        } else {
            throw new Exception(__('Unable to create "%s", Authentication Adapter not found', array($this->authAdapter)));
        }
        return false;
    }

    public function getMapper($mapperClass = null) {
        if (!is_null($mapperClass) && class_exists($mapperClass)) {
            $this->mapperClass = $mapperClass;
        }      

        if (class_exists($this->mapperClass)) {
            if (empty(self::$_mapperInstance[$this->mapperClass]) || !self::$_mapperInstance[$this->mapperClass]) {
                self::$_mapperInstance[$this->mapperClass] = new $this->mapperClass();
            }
            return self::$_mapperInstance[$this->mapperClass];
        } else {
            throw new Exception (__('Mapper class "%s" does not exist', array($this->mapperClass)));
        }
    }

    public function getAcl() {
        if (!$this->acl) {
            $this->acl = new Acl();
        }
        return $this->acl;
    }
}
