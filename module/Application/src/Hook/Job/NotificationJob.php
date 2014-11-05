<?php

namespace Hook\Job;

use Http\Socket as HttpSocket;

use Application\Log;

class NotificationJob {
	
	// public function setUp()
	// {
	// 	// ... Set up environment for this job
	// }
	
	public function perform() {

		$hook = $this->args['hook'];
		$event = json_encode($this->args['event']);

    	$HttpSocket = new HttpSocket();
        $request = array(
            'method' => 'POST',
            'uri' => $hook['end_point'],
            'body' => $event,
            'timeout' => 5,
        );
       	$result = $HttpSocket->request($request);
		echo $result;
		//throw new \Exception('Exception');

	}
	
	// public function tearDown()
	// {
	// 	// ... Remove environment for this job
	// }

}