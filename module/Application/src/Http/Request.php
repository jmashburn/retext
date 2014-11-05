<?php

namespace Http;

class Request {

    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';

    protected $method = self::METHOD_GET;

    protected $queryParams = null;

    protected $postParams = null;

    protected $fileParams = null;

    protected $headers = null;

    protected $content = null;

    public function __construct() {
        $this->setEnv($_ENV);

        if ($_GET) {
            $this->setQuery($_GET);
        }

        if ($_POST) {
            $this->setPost($_POST);
        }

        if ($_FILES) {
            $files = $this->mapPhpFiles();
            $this->setFiles($files);
        }
        $this->setServer($_SERVER);

        $this->getContent();
    }


    public function setMethod($method) {
        $method = strtoupper($method);
        if (!defined('METHOD_' . $method)) {
            $this->method = $method;
        }
    }

    public function getContent() {
        if (empty($this->content)) {
                $requestBody = file_get_contents('php://input');
            if (strlen($requestBody) > 0) {
                $this->content = $requestBody;
            }
        }
        return $this->content;
    }

    public function getMethod() {
        return $this->method;
    }

    public function setEnv($env) {
        $this->envParams = $env;
        return $this;
    }

    public function getEnv($name = null, $default = null) {
        if ($this->envParams === null) {
            $this->envParams = array();
        }

        if ($name === null) {
            return $this->envParams;
        }

        return (!empty($this->envParams[$name])?$this->envParams[$name]:$default);
    }

    public function setQuery($query = array()) {
        $this->queryParams = $query;
        return $this;
    }

    public function getQuery($name = null, $default = null) {
        if ($this->queryParams === null) {
            $this->queryParams = array();
        }

        if ($name === null) {
            return $this->queryParams;
        }

        return (!empty($this->queryParams[$name])?$this->queryParams[$name]:$default);    
    }

    public function setPost($post = array()) {
        $this->postParams = $post;
        return $this;
    }

    public function getPost($name = null, $default = null) {
        if ($this->postParams === null) {
            $this->postParams = array();
        }

        if ($name === null) {
            return $this->postParams;
        }

        return (!empty($this->postParams[$name])?$this->postParams[$name]:$default);    
    }

    public function setFiles($files = array()) {
        $this->fileParams = $files;
        return $this;
    }

    public function getFiles($name = null, $default = null) {
        if ($this->fileParams === null) {
            $this->fileParams = array();
        }

        if ($name === null) {
            return $this->fileParams;
        }

        return (!empty($this->fileParams[$name])?$this->fileParams[$name]:$default);    
    }

    public function setServer($server = array()) {
        $this->serverParams = $server;

        // This seems to be the only way to get the Authorization header on Apache
        if (function_exists('apache_request_headers')) {
            $apacheRequestHeaders = apache_request_headers();
            if (!isset($this->serverParams['HTTP_AUTHORIZATION'])) {
                if (isset($apacheRequestHeaders['Authorization'])) {
                    $this->serverParams['HTTP_AUTHORIZATION'] = $apacheRequestHeaders['Authorization'];
                } elseif (isset($apacheRequestHeaders['authorization'])) {
                    $this->serverParams['HTTP_AUTHORIZATION'] = $apacheRequestHeaders['authorization'];
                }
            }
        }

        // set headers
        $headers = array();

        foreach ($server as $key => $value) {
            if ($value && strpos($key, 'HTTP_') === 0) {
                if (strpos($key, 'HTTP_COOKIE') === 0) {
                    // Cookies are handled using the $_COOKIE superglobal
                    continue;
                }
                $name = strtr(substr($key, 5), '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');
            } elseif ($value && strpos($key, 'CONTENT_') === 0) {
                $name = substr($key, 8); // Content-
                $name = 'Content-' . (($name == 'MD5') ? $name : ucfirst(strtolower($name)));
            } else {
                continue;
            }

            $headers[$name] = $value;
        }

        $this->setHeaders($headers);

        // set method
        if (isset($this->serverParams['REQUEST_METHOD'])) {
            $this->setMethod($this->serverParams['REQUEST_METHOD']);
        }

        return $this;
    }

    public function getServer($name = null, $default = null) {
        if ($this->serverParams === null) {
            $this->serverParams = array();
        }

        if ($name === null) {
            return $this->serverParams;
        }

        return (!empty($this->serverParams[$name])?$this->serverParams[$name]:$default);    
    }

    public function setHeaders($headers = array()) {
        $this->headerParams = $headers;
        return $this;
    }

    public function getHeaders($name = null, $default = null) {
        if ($this->headerParams === null) {
            $this->headerParams = array();
        }

        if ($name === null) {
            return $this->headerParams;
        }

        return (!empty($this->headerParams[$name])?$this->headerParams[$name]:$default);    
    }

    protected function mapPhpFiles() {
        $files = array();
        foreach ($_FILES  as $fileName => $fileParams) {
            $files[$fileName] = array();
            foreach ($fileParams as $param => $data) {
                if (!is_array($data)) {
                    $files[$fileName][$param] = $data;
                } else {
                    foreach ($data as $i => $v) {
                        $this->mapPhpFileParam($file[$fileName], $param, $i, $v);
                    }
                }
            }
        }
        return $files;
    }

    protected function mapPhpFileParam(&$array, $paramName, $index, $value) {
        if (!is_array($value)) {
            $array[$index][$paramName] = $value;
        } else {
            foreach ($value as $i => $v) {
                $this->mapPhpFileParam($array[$index], $paramName, $i, $v);
            }
        }
    }
}
