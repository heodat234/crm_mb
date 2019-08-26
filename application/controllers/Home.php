<?php
defined('BASEPATH') OR exit('No direct script access allowed');
Class Home extends WFF_Controller {
	public function index()
	{
		$this->_build_template();
		$this->load->view('home');
	}
}
