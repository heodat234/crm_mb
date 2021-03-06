<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Wfpbx extends WFF_Controller {
	function __construct()
	{
		parent::__construct();
		header('Content-type: application/json');
		$this->load->library("mongo_db");
		$this->load->library("session");
		$this->load->model("pbx_model");
		$this->load->model("language_model");
	}

	public function change_status() {
		$request = json_decode(file_get_contents('php://input'), TRUE);
		
		try {
			
			$this->load->model("agentstatus_model");
			$result = $this->agentstatus_model->change($request);
			if(!$result) throw new Exception("@Change not success@");
			$current_status = $this->agentstatus_model->getOne(["statuscode"]);
			$message = !empty($current_status["status"]) ? $this->language_model->translate("Call @change to status@ @".$current_status["status"]["text"]."@", "NOTIFICATION") : "";
			echo json_encode(array("status" => 1, "message" => $message));			
		} catch (Exception $e) {
			echo json_encode(array('status' => 0, "message" => $e->getMessage()));
		}
    }

	function change_one_queue()
	{
		try {
			$request = json_decode(file_get_contents('php://input'), TRUE);
			if(!isset($request["queuename"])) throw new Exception("Queuename is empty!");
			// Check status code
			$this->load->model("agentstatus_model");
			$agentstatus = $this->agentstatus_model->getOne(["statuscode"]);
			if($agentstatus["statuscode"] != 1 && $agentstatus["statuscode"] != 2) throw new Exception("@Your status is not available@");
			//
			$queuename = $request["queuename"];
			$action = !empty($request["pause"]) ? "pause" : "unpause";
			$method = $action . "_queue_member";
			$extension = $this->session->userdata("extension");
			$response = $this->pbx_model->$method($queuename, $extension, true);
			if(empty($response['status'])) throw new Exception("No success.");
			$message = $this->language_model->translate("@{$action} success@!", "NOTIFICATION");
			echo json_encode(array("status" => 1, "message" => ucfirst($message)));
		} catch (Exception $e) {
			$message = $this->language_model->translate($e->getMessage(), "NOTIFICATION");
			echo json_encode(array("status" => 0, "message" => $message));
		}
	}

	function makeCall() { 

		$extension 	= $this->session->userdata("extension");
		$phone		= $this->input->get("phone");
		$dialid 	= $this->input->get("dialid");
		$type 		= $this->input->get("type");

		try {        
			$responseArr = $this->pbx_model->make_call_2($extension, $phone, $dialid, $type);
			if($responseArr != 200) throw new Exception("Call error");
			$message = $this->language_model->translate("@Call success@", "NOTIFICATION");
			echo json_encode(array("status" => 1, "message" => $message));
		} catch(Exception $e) {
			$message = $this->language_model->translate("@".$e->getMessage()."@", "NOTIFICATION");
			echo json_encode(array("status" => 0, "message" => $message));
		}
    }

    function hangup() {
    	try {
    		$request = json_decode(file_get_contents('php://input'), TRUE);
    		$calluuid = !empty($request["calluuid"]) ? $request["calluuid"] : "";
    		$responseArr = $this->pbx_model->hangup($calluuid);
    		if($responseArr != 200) throw new Exception("@Hangup@ @error@");
    		$message = $this->language_model->translate("@Hangup@ @success@", "NOTIFICATION");
    		echo json_encode(array("status" => 1, "message" => $message));
    	} catch (Exception $e) {
    		$message = $this->language_model->translate($e->getMessage(), "NOTIFICATION");
    		echo json_encode(array("status" => 0, "message" => $message));
    	}
    }

    function transfer() {
    	try {
    		$request = json_decode(file_get_contents('php://input'), TRUE);
    		$calluuid = !empty($request["calluuid"]) ? $request["calluuid"] : "";
    		$extension = !empty($request["extension"]) ? $request["extension"] : "";
    		$responseArr = $this->pbx_model->transfer($calluuid, $extension);
    		if($responseArr != 200) throw new Exception("@Transfer@ @error@");
    		$message = $this->language_model->translate("@Transfer@ @success@", "NOTIFICATION");
    		echo json_encode(array("status" => 1, "message" => $message));
    	} catch (Exception $e) {
    		$message = $this->language_model->translate($e->getMessage(), "NOTIFICATION");
    		echo json_encode(array("status" => 0, "message" => $message));
    	}
    }

    function call_before_week()
    {
    	$extension = $this->input->get("extension");
    	$week = (int) $this->input->get("week");
    	$end = strtotime('monday next week midnight');
    	$end += 604800 * $week;
    	$start = $end - 604800;
    	$durations = interval_duration($start, $end, 1440);
    	$response = array();
    	$collection = set_sub_collection("worldfonepbxmanager");
    	$match = array();
    	if($extension) $match["userextension"] = $extension;
    	foreach ($durations as $duration) {
    		$match["starttime"] = array('$gte' => $duration["start"], '$lt' => $duration["end"]);
    		$data = $this->mongo_db->aggregate_pipeline($collection, 
	    		array(
	    			array('$match' => $match),
		    		array('$group' => array(
		    				'_id' => '$direction',
		    				"total" => array('$sum' => 1)
		    			)
		    		)
	    		)
	    	);
	    	$day_data = array();
	    	$day_data["date"] = date('d/m/Y', $duration["start"]);
	    	foreach ($data as $doc) {
	    		$day_data[$doc["_id"]] = $doc["total"];
	    	}
	    	$response[] = $day_data;
    	}
    	echo json_encode($response);
    }
}