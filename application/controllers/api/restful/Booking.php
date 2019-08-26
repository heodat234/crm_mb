<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Booking extends WFF_Controller {

    private $collection = "Booking";

    function __construct()
    {
        parent::__construct();
        header('Content-type: application/json');
        $this->load->library("crud");
    }

    function read()
    {
        try {
            $request = json_decode($this->input->get("q"), TRUE);
            $response = $this->crud->read($this->collection, $request);
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(array("status" => 0, "message" => $e->getMessage()));
        }
    }

    function detail($RecordLocator)
    {
        try {
            $response = $this->crud->where(array('RecordLocator' => $RecordLocator))->getOne($this->collection);
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(array("status" => 0, "message" => $e->getMessage()));
        }
    }
}