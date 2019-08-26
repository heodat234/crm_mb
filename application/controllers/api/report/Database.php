<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Database extends WFF_Controller {

    function __construct()
    {
        parent::__construct();
        header('Content-type: application/json');
        if(!$this->session->userdata("issysadmin")) exit();
    }

    function mongodump($db)
    {
        $path = APPPATH . "database";
        $db_path = $path . "/" . $db;
        if(is_dir($db_path)) {
            rename($db_path, $db_path . "_" . filemtime($db_path));
        }
        $command = "mongodump --out $path --db $db";
        $result = exec($command);
        echo json_encode(array("status" => 1));
    }

    function mongorestore($db)
    {
        $this->backup_db($db);
        $path = APPPATH . "database";
        $db_path = $path . "/" . $db;
        $command = "mongorestore --db $db $db_path --drop";
        $result = exec($command);
        echo json_encode(array("status" => 1, "message" => "Restore success $db"));
    }

    function backup_db($db)
    {
        $time = time();
        $command = 'mongo localhost:27017 --eval \'db.copyDatabase("'.$db.'", "'.$db.'_'.$time.'")\'';
        $output = exec($command);
        return $output;
    }

    function mongorestore_collection($db)
    {
        $srcCollection = $this->input->get("srcCollection");
        $desCollection = $this->input->get("desCollection");
        if(!$srcCollection || !$desCollection) exit();
        $drop = $this->input->get("drop");
        $path = APPPATH . "database";
        $db_path = $path . "/" . $db;
        $collection_path = $db_path . "/" . $srcCollection . ".bson";
        $command = "mongorestore --db $db --collection $desCollection $collection_path " . ($drop ? "--drop" : "");
        $result = exec($command);
        echo json_encode(array("status" => 1, "message" => "Restore success $db $desCollection"));
    }

    function collections()
    {
    	$db = $this->input->get("db");
        $file = (int) $this->input->get("file");
    	if(!$db) exit();
        if($file) {
            $path = APPPATH . "database/{$db}/";
            $items = array_diff(scandir($path), array('..', '.'));
            $list = array();
            foreach ($items as $name) {
                $file_path = $path . $name;
                $file_info = new SplFileInfo($file_path);
                $ext = $file_info->getExtension();
                if($ext == "bson")
                    $list[] = array("name" => $file_info->getBasename(".bson"));
            }
        } else {
            $output = "";
            $command = 'mongo localhost:27017 --eval \'db.getSiblingDB("'.$db.'").getCollectionNames()\'';
            exec($command, $output);
            $list = array();
            $length = count($output);
            for ($i = 0; $i < $length - 1; $i++) { 
                if($i > 3) {
                    $list[] = array("name" => str_replace(["\r", "\n", "\v", "\t","\"",","], "", $output[$i]));
                }
            }
        }
        echo json_encode(array("data" => $list, "total" => count($list)));
    }

    function data($database, $collection)
    {
        $this->load->library("crud");
        if($database == "_worldfone4xs") 
        {
            $this->crud->select_db($database);
        }
        $request = json_decode($this->input->get("q"), TRUE);
        $response = $this->crud->read($collection, $request);
        echo json_encode($response);
    }
}