<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Select extends WFF_Controller {
	private $sub = "";

	function __construct()
	{
		parent::__construct();
		header('Content-type: application/json');
		$this->load->model("language_model");
		$this->sub = set_sub_collection("");
	}

	function foreign($collection)
	{
		$collection = $this->sub . $collection;
		$this->load->library('crud');
		$request = json_decode($this->input->get("q"), TRUE);
		$select = $match = array();
		if(!empty($request["match"])) 
		{
			$match = $request["match"]; 
		}
		if(!empty($request["field"])) 
		{
			if(is_string($request["field"]))
				$select = [$request["field"]];
			else $select = $request["field"]; 
		}
		$response = $this->crud->read($collection, $request, $select, $match);
		echo json_encode($response);
	}

	function foreign_private($collection)
	{
		if(!in_array($collection, array("ConfigType", "DataType"))) 
		{
			$collection = $this->sub . $collection;
		}
		$this->load->library('crud');
		$this->load->config("_mongo");
		$_db = $this->config->item("_mongo_db");
		$this->crud->select_db($_db);
		$request = json_decode($this->input->get("q"), TRUE);
		$select = $match = array();
		if(!empty($request["match"])) 
		{
			$match = $request["match"]; 
		}
		if(!empty($request["field"])) 
		{
			if(is_string($request["field"]))
				$select = [$request["field"]];
			else $select = $request["field"]; 
		}
		$response = $this->crud->read($collection, $request, $select, $match);
		echo json_encode($response);
	}

	function distinct($collection) 
	{
		$collection = $this->sub . $collection;
		$this->load->library('crud');
		$request = json_decode($this->input->get("q"), TRUE);
		$select = $match = array();
		if(!empty($request["match"])) 
		{
			$match = $request["match"]; 
		}
		if(!empty($request["field"])) 
		{
			if(is_string($request["field"]))
				$select = [$request["field"]];
			else $select = $request["field"]; 
		}
		$response = $this->crud->distinct($collection, $request, $select, $match);
		$data = array();
		foreach ($response["data"] as $value) {
			if(count($select) == 1)
				$data[] = array($select[0] => $value);
			else $data[] = array("value" => $value);
		}
		$response["data"] = $data;
		echo json_encode($response);
	}

	function jsondata()
	{
		$collection = "Jsondata";
		$collection = $this->sub . $collection;
		$this->load->library('crud');
		$this->load->config("_mongo");
		$_db = $this->config->item("_mongo_db");
		$this->crud->select_db($_db);
		$tags = $this->input->get("tags");
		$response = $this->crud->where(array("tags" => $tags))->getOne($collection);
		$response = $this->language_model->translate($response, "CONTENT");
		echo json_encode($response);
	}

	function user()
	{
		$this->load->model("pbx_model");
		$users = $this->pbx_model->list_agent(0, 0, 0);
		echo json_encode(array("data" => $users, "total" => count($users)));
	}

	function widget()
	{
		$list_files = scandir(APPPATH."/views/widgets");
		$list_widget = array();
		foreach ($list_files as $file) {
			if(strpos($file, ".php") > 0) {
				$list_widget[] = str_replace(".php", "", $file);
			}
		}
		echo json_encode(array("data" => $list_widget, "total" => count($list_widget)));
	}

	function path($type = "")
	{
		$api_path = APPPATH."controllers";
		$list_files = find_all_files($api_path);
		$list_uri = array();
		foreach ($list_files as $file) {
			$uri = str_replace([$api_path."/",".php"], ["",""], $file);
			$uriArr = explode("/", $uri);
			foreach ($uriArr as $i => $uriPart) {
				$uriArr[$i] = lcfirst($uriPart);
			}
			$list_uri[] = implode("/", $uriArr);
		}
		switch ($type) {
			case 'api':
				$list_uri = array_filter($list_uri, function ($element){
					return (strpos($element, "api/") !== FALSE || strpos($element, "apis/") !== FALSE || strpos($element, "app/") !== FALSE);
				});
				break;

			case 'view':
				$list_uri = array_filter($list_uri, function ($element){
					return (strpos($element, "api") === FALSE);
				});
				break;
			
			default:
				$list_uri = $list_uri;
				break;
		}
		$response = array_values($list_uri);
		echo json_encode(array("data" => $response, "total" => count($response)));
	} 

	function queues() 
	{
		$collection = "Group";
		$collection = $this->sub . $collection;
		$this->load->library('crud');
		$request = $_GET;
		$select = array("queuename");
		$match = array("type" => "queue");
		$response = $this->crud->read($collection, $request, $select, $match);
		echo json_encode($response);
	}

	function queuemembers()
	{
		$collection = "Group";
		$collection = $this->sub . $collection;
		$this->load->library('mongo_db');
		$members = array();
		$queues = $this->input->get("queues");
		$where = $queues ? array("queuename" => array('$in' => $queues)) : [];
		$groups = $this->mongo_db->where($where)->select(["members"])->get($collection);
		$members = array();
		foreach ($groups as $group) {
			$members = array_merge($members, $group["members"]);
		}
		$members = array_values(array_unique($members));
		echo json_encode(array("data" => $members, "total" => count($members)));
	}

	function groups_and_extensions()
	{
		$data = array();
		$this->load->library("mongo_db");
		$groups = $this->mongo_db->where("active", TRUE)
			->select(["name", "members"])->get($this->sub . "Group");
		foreach ($groups as $group) {
			$data[] = array("text" => $group["name"], "value" => $group["members"]);
		}
		$_db = $this->config->item("_mongo_db");
        $this->mongo_db->switch_db($_db);
		$all_extensions = $this->mongo_db->where(array("issysadmin" => array('$ne' => TRUE)))->distinct($this->sub . "User", "extension");
		foreach ($all_extensions as $extension) {
			$data[] = array("text" => $extension, "value" => [$extension]);
		}
		echo json_encode($data);
	}
}