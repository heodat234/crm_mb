<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Chat extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		header('Content-type: application/json');
		$this->load->library("mongo_db");
		$this->load->library("session");
		$this->load->model("language_model");
	}

    public function change_status_chat() {
		$request = json_decode(file_get_contents('php://input'), TRUE);
		
		try {
			$this->load->model("chatstatus_model");
			$result = $this->chatstatus_model->change($request);
			if(!$result) throw new Exception("@Change not success@");
			$message = $this->language_model->translate("Chat @change to status@ @".($request["statuscode"] ? "Ready":"Busy") . "@", "NOTIFICATION");
			echo json_encode(array("status" => 1, "message" => $message));			
		} catch (Exception $e) {
			echo json_encode(array('status' => 0, "message" => $e->getMessage()));
		}
    }
}