<?php

if (!class_exists('EventException')) {
    class EventException extends \Exception {}
}

class Event {

    private static $events = array();

    public static function add($event, $callback, $priority = 1) {
        if (!isset(self::$events[$event])) {
            self::$events[$event] = array();
        }
        
        self::$events[$event][] = array('callback' => $callback, 'priority' => (int) $priority);
        return true; 
    }

    public static function fire($event, $params = null, $notify = true) {
        if (!is_array($params)) {
            $params = array($params);
        }
        $eventContainer = new Event\Db\EventContainer($params);
        $eventContainer->setEventName($event);
        $result = array();
        if (isset(self::$events[$event])) {
            uksort(self::$events[$event], function ($a, $b) use($event) { 
                if (self::$events[$event][$a]['priority'] == self::$events[$event][$b]['priority']) {
                    return ($a>$b)?1:-1;
                }
                return self::$events[$event][$a]['priority'] < self::$events[$event][$b]['priority']?1:-1;
            });
        
            foreach (self::$events[$event] as $callback) {
                try {
                    if (!is_callable($callback['callback'])) {
                        continue;
                    }
                    $result[$event][] = call_user_func_array($callback['callback'], array($params));
                } catch (EventException $e) {
                    break;
                }
            } 
        }
       # $eventContainer->result = $result;
        $eventContainer->setResult($result);
        if ($notify) {
            \Event::fire('system.send_notification', $eventContainer->toArray(), false);
        }          
        return true;
    }

    public static function clearEvents() {
        self::$events = array();
    }
}

// class Event
// {
//     private static $instance;

//     private static $hooks = array();

//     private static $identity;

//     private $events = array();

//     private function __construct() {
//         $authAdapter = new Authentication();
//         if ($authAdapter->hasIdentity()) {
//             

//             $redis = new \Redis\Redis('cache');
//             $redis->set($this->identity->getUsername(), 'values');
//         }


//         //     // $cache = new Cache('cache');
//         //     // $hooks = $cache->getItem($this->identity);
//         //     // if (!$hooks) {
//         //         $hookMapper = new Hook\Db\HookMapper();
//         //         print_r($hooksMapper);
//         //         die();
//         //         $hooks = $hookMapper->findAllHooks($this->identity->getUsername());
//         //         print_r($hooks);
//         //         die();
//         //         if ($hooks->valid()) {
//         //             foreach ($hooks as $hook) {
//         //                 $this->hooks[] = $hook;
//         //             }
//         //             $cache->setItem($this->identity, $hooks->toJson());
//         //         }
//         //     // }
//         //     print_r($hooks);
//         // }
//     }

//     private function __clone() {}

//     public static function add($event_name, $fn) {
//         $instance = self::get_instance();
//         $instance->events[$event_name][] = $fn;
//     }

//     public static function fire($event_name, $params = null) {
//         $instance = self::get_instance();
//         $result = array();
//         if (isset($instance->events[$event_name])) {
//             Log::debug(__('Firing Events: %s count: %s', array($event_name, count($instance->events[$event_name]))));
//             foreach ($instance->events[$event_name] as $fn) {
//                 $result[] = call_user_func_array($fn, array($params));
//             }
//         }
//         self::createEventNotification(compact('event_name', 'params', 'result'));
//     }

//     public static function get_instance() {
//         if (empty(self::$instance)) {
//             self::$instance = new Event();
//         }
//         return self::$instance;
//     }

//     public static function get_events() {
//         $instance = self::get_instance();
//         return $instance->events;
//     }

//     private static function createEventNotification($event = null) {
//         $instance = self::get_instance();
//         if (!empty($instance->hooks)) {
//             foreach ($instance->hooks as $hook) {
//                 if ($hook instanceof Hook\Db\HookContainer) {
//                     Log::debug(__('Sending Event: %s to webhook: %s [data: %s]', 
//                         array($event['event_name'], $hook->end_point, 'Data')));


//                         // Log this do the databases
//                         // Add this to ApacheMQ for processing on the backend.
//                             // Apache MQ will update the db (this entry) with a Result

//                     // $HttpSocket = new HttpSocket();
//                     // $request = array(
//                     //     'method' => 'POST',
//                     //     'uri' => $hook->end_point,
//                     //     'body' => sprintf("Sending Content to %s", $hook->end_point),
//                     //     'timeout' => 5,
//                     // );
//                     // $result = $HttpSocket->request($request);


                       
//                 }
//             }
//         }


//     }
// }



// class Resque_Event
// {
//     /**
//      * @var array Array containing all registered callbacks, indexked by event name.
//      */
//     private static $events = array();

//     /**
//      * Raise a given event with the supplied data.
//      *
//      * @param string $event Name of event to be raised.
//      * @param mixed $data Optional, any data that should be passed to each callback.
//      * @return true
//      */
//     public static function trigger($event, $data = null)
//     {
//         if (!is_array($data)) {
//             $data = array($data);
//         }

//         if (empty(self::$events[$event])) {
//             return true;
//         }
        
//         foreach (self::$events[$event] as $callback) {
//             if (!is_callable($callback)) {
//                 continue;
//             }
//             call_user_func_array($callback, $data);
//         }
        
//         return true;
//     }
    
//     /**
//      * Listen in on a given event to have a specified callback fired.
//      *
//      * @param string $event Name of event to listen on.
//      * @param mixed $callback Any callback callable by call_user_func_array.
//      * @return true
//      */
//     public static function listen($event, $callback)
//     {
//         if (!isset(self::$events[$event])) {
//             self::$events[$event] = array();
//         }
        
//         self::$events[$event][] = $callback;
//         return true;
//     }
    
//     /**
//      * Stop a given callback from listening on a specific event.
//      *
//      * @param string $event Name of event.
//      * @param mixed $callback The callback as defined when listen() was called.
//      * @return true
//      */
//     public static function stopListening($event, $callback)
//     {
//         if (!isset(self::$events[$event])) {
//             return true;
//         }
        
//         $key = array_search($callback, self::$events[$event]);
//         if ($key !== false) {
//             unset(self::$events[$event][$key]);
//         }
        
//         return true;
//     }
    
//     /**
//      * Call all registered listeners.
//      */
//     public static function clearListeners()
//     {
//         self::$events = array();
//     }
// }