<?php
namespace Application\Handler;

use ToroHook;

use Exception;

use Elements;

class DashboardHandler extends HtmlHandler {

   protected $authAdapter = 'Application\Authentication\Authentication';

    public function get($action=null) {

        $this->setTitle('Dashboard');
        $params = $this->getParameters();
        return $this->display('application/dashboard/index.phtml', compact('params'));

        // $HttpSocket = new \Http\Socket();
        // $request = array(
        //     'method' => 'POST',
        //     'uri' => 'http://toro.iwobble.lcl/receive.php',
        //     'body' => '{"plan":{"key":"pl_52461bb7c3754","alias":"ONETWOssdfSsS","currency":"usd","amount":"2000","interval":"month","interval_count":"2","name":"Test Plan","trial_period_days":0,"trial_amount":"0","auto_start":1},"event":"plan.created","result":[]}',
        //     'timeout' => 5,
        // );
        // $result = $HttpSocket->request($request);
        // echo $result->getContent();
        //print_r($result->getContent());

        #$redis = new \Redis\RedisCluster('cluster');
	#print_r($redis);
	#die();
        // $redis->set('item', 'value');
        
       //  $jobId = \Resque::enqueue('default', 'Application\Handler\GeneralHandler', array('test' => 'test'), true);
       //  #\Event::fire('resque.job');
        
       //  // $result = $redis->listPush(array('test'), 'This is a Test Item');
       // // $result = $redis->subscribe(array('test'), function() { });
       //  \Event::fire('resque.job', array('jobId' => $jobId));
       //  return $this->processRequest($action);
    }
}
