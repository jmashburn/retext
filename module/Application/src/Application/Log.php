<?php

namespace Application;

class Log {
	
	const EMERG  = 0;
    const ALERT  = 1;
    const CRIT   = 2;
    const ERR    = 3;
    const WARN   = 4;
    const NOTICE = 5;
    const INFO   = 6;
    const DEBUG  = 7;

	public static $logger;

	protected static $registeredExceptionHandler = false;

	private static $verbosity = 'NOTICE';

	// public function __construct($name, $logVerbosity = 'NOTICE') {
	// 	self::clean();
	// 	switch(strtoupper($logVerbosity)) {
	// 		case 'EMERG':
	// 			self::$verbosity = self::EMERG;
	// 			break;
	// 		case 'ALERT':
	// 			self::$verbosity = self::ALERT;
	// 			break;
	// 		case 'CRIT':
	// 			self::$verbosity = self::CRIT;
	// 			break;
	// 		case 'ERR':
	// 			self::$verbosity = self::ERR;
	// 			break;
	// 		case 'WARN':
	// 			self::$verbosity = self::WARN;
	// 			break;
	// 		case 'NOTICE':
	// 			self::$verbosity = self::NOTICE;
	// 			break;
	// 		case 'INFO':
	// 			self::$verbosity = self::INFO;
	// 			break;
	// 		case 'DEBUG':
	// 			self::$verbosity = self::DEBUG;
	// 			break;
	// 		default:
	// 			throw new \Exception("Incorrect logVerbosity setting: {$logVerbosity}");
	// 	}

	// 	if (!self::$logger) {
	// 		self::$logger = fopen()
	// 	}

	// }
	
	// public static function clean() {
	// 	static::$logger = null;
	// }
	
	public static function logException($title, \Exception $e) {
		$exceptionMessage = (string)$e->getMessage();
		
		$content = (string)$title . PHP_EOL 
		. 'Exception of type \'' . get_class($e) . '\': ' 
		. $exceptionMessage;
		
		self::err($content);
		return true; 
	}

	public static function write($verbosity, $message, $extra = array()) {
		if (!self::$logger) {
			self::$logger = fopen(\Config::getConfig('log_dir', 'var/log/') . "/test.log", 'a', false);
		}
		// if (!is_int($priority) || ($priority<0) || ($priority>=count($this->priorities))) {
  //           throw new Exception\InvalidArgumentException(sprintf(
  //               '$priority must be an integer > 0 and < %d; received %s',
  //               count($this->priorities),
  //               var_export($priority, 1)
  //           ));
  //       }
        if (is_object($message) && !method_exists($message, '__toString')) {
            throw new Exception(
                '$message must implement magic __toString() method'
            );
        }

        if (!is_array($extra) && !$extra instanceof Traversable) {
            throw new Exception(
                '$extra must be an array or implement Traversable'
            );
        } elseif ($extra instanceof Traversable) {
            $extra = ArrayUtils::iteratorToArray($extra);
        }

        // if ($this->writers->count() === 0) {
        //     throw new Exception\RuntimeException('No log writer specified');
        // }

        $timestamp = new \DateTime();

        if (is_array($message)) {
            $message = var_export($message, true);
        }

        $event = array(
            'timestamp'    => $timestamp->getTimestamp(),
            //'priority'     => (int) $priority,
           // 'priorityName' => $this->priorities[$priority],
            'message'      => (string) $message,
            'extra'        => '',#$extra
        );

 

        fwrite(self::$logger, vsprintf("%s %s %s \n", $event));
        // foreach ($this->processors->toArray() as $processor) {
        //     $event = $processor->process($event);
        // }

        // foreach ($this->writers->toArray() as $writer) {
        //     $writer->write($event);
        // }

        // return $this;
	}
	
	public static function debug($message, $extras = array()) {
		self::write(self::DEBUG, $message, $extras);
	}
	
	public static function info($message, $extras = array()) {
		self::write(self::INFO, $message, $extras);
	}
	
	public static function notice($message, $extras = array()) {
		self::write(self::NOTICE, $message, $extras);
	}
	
	public static function warn($message, $extras = array()) {
		self::write(self::WARN, $message, $extras);
	}
	
	public static function err($message, $extras = array()) {
		self::write(self::ERR, $message, $extras);
	}
	
	public static function crit($message, $extras = array()) {
		self::write(self::CRIT, $message, $extras);
	}
	
	public static function alert($message, $extras = array()) {
		self::write(self::ALERT, $message, $extras);
	}
	
	public static function emerg($message, $extras = array()) {
		self::write(self::EMERG, $message, $extras);
	}
	
	public static function registerExceptionHandler() {
		if (self::$registeredExceptionHandler) {
			return false;
		}
		set_exception_handler(function ($exception) {
			self::write(self::ERR, $exception);
		});
		self::$registeredExceptionHandler = true;
		return true;
	}


} 

?>