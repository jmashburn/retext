<?php

namespace Redis;

// if (!defined('CRLF')) {
//     define('CRLF', );
// }

if (class_exists('RedisException', false)) {
	class RedisException extends \Exception {
	}
}

class RedisResource {

	private $__sock;

	public $host;

	public $port;

    const CRLF = '%s%s';

	function __construct($host, $port = 6379) {
        $this->host = $host;
        $this->port = $port;
				$this->establishConnection();
    }
	
	function establishConnection() {
        $this->__sock = fsockopen($this->host, $this->port, $errno, $errstr);
        if (!$this->__sock) {
            throw new Exception("{$errno} - {$errstr}");
        }
    }

	function __destruct() {
        fclose($this->__sock);
    }

	function __call($name, $args) {

        array_unshift($args, strtoupper($name));

        $command = sprintf('*%d%s%s%s', count($args), sprintf('%s%s', chr(13), chr(10)), 
            implode(array_map(array($this, 'formatArgument'), $args), sprintf('%s%s', chr(13), chr(10))), sprintf('%s%s', chr(13), chr(10)));

        for ($written = 0; $written < strlen($command); $written += $fwrite) {
            $fwrite = fwrite($this->__sock, substr($command, $written));
            if ($fwrite === FALSE) {
                throw new Exception('Failed to write entire command to stream');
            }
        }

        $reply = trim(fgets($this->__sock, 512));
        switch (substr($reply, 0, 1)) {
            /* Error reply */
            case '-':
                throw new RedisException(substr(trim($reply), 4));
                break;
            /* Inline reply */
            case '+':
                $response = substr(trim($reply), 1);
                break;
            /* Bulk reply */
            case '$':
                $response = null;
                if ($reply == '$-1') {
                    break;
                }
                $read = 0;
                $size = substr($reply, 1);
                do {
                    $block_size = ($size - $read) > 1024 ? 1024 : ($size - $read);
                    $response .= fread($this->__sock, $block_size);
                    $read += $block_size;
                } while ($read < $size);
                fread($this->__sock, 2); /* discard crlf */
                break;
            /* Multi-bulk reply */
            case '*':
                $count = substr($reply, 1);
                if ($count == '-1') {
                    return null;
                }
                $response = array();
                for ($i = 0; $i < $count; $i++) {
                    $bulk_head = trim(fgets($this->__sock, 512));
                    $size = substr($bulk_head, 1);
                    if ($size == '-1') {
                        $response[] = null;
                    }
                    else {
                        $read = 0;
                        $block = "";
                        do {
                            $block_size = ($size - $read) > 1024 ? 1024 : ($size - $read);
                            $block .= fread($this->__sock, $block_size);
                            $read += $block_size;
                        } while ($read < $size);
                        fread($this->__sock, 2); /* discard crlf */
                        $response[] = $block;
                    }
                }
                break;
            /* Integer reply */
            case ':':
                $response = intval(substr(trim($reply), 1));
                break;
            default:
                throw new RedisException("invalid server response: {$reply}");
                break;
        }
        /* Party on */
        return $response;
    }

	private function formatArgument($arg) {
        return sprintf('$%d%s%s', strlen($arg), sprintf('%s%s', chr(13), chr(10)), $arg);
    }
      
}