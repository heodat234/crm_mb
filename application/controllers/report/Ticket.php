<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Ticket extends WFF_Controller {
	function __construct()
    { 
    	parent::__construct();
    	$this->_build_template();
    	$this->output->data["js"][] = KENDOUI_PATH . "js/jszip.min.js";
    }

	public function statistic()
	{
		$this->load->view('report/ticket_statistic_view');
	}
}