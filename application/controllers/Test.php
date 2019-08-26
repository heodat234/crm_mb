<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Pheanstalk\Pheanstalk;

Class Test extends WFF_Controller {

	function __construct()
    { 
    	parent::__construct();
    	$this->_build_template();
    	$this->load->library('mongo_db');
    	$this->load->library('mongo_private');
    }

    function process() {
        $pheanstalk = new Pheanstalk('127.0.0.1');
        echo "START ".microtime(true).PHP_EOL;
        while ($job = $pheanstalk->watch("newticket")->ignore('default')->reserve(2)) {
            $pheanstalk->bury($job);
            $data = $job->getData();
            $newTicketCondition = $this->mongo_private->where(array('for' => 'ticket', 'trigger_type' => 'create'))->get('Trigger');
            print_r($newTicketCondition);
            $pheanstalk->delete($job);
        }
        echo "END ".microtime(true).PHP_EOL;
    }
}