<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends WFF_Controller {

	function __construct()
    { 
    	parent::__construct();
        $this->_build_template();
    }

    public function index() {
    	$this->output->data["js"][] = PROUI_PATH . "js/pages/readyChat.js";
        $this->load->view('manage/chat_view');
    }
}