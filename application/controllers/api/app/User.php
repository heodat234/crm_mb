<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller 
{
	function __construct() {
		parent::__construct();
		$this->load->library('mongo_db');		
	}

	public function auth() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_POST['email']) || !isset($_POST['password'])) {				
				throw new Exception('Unauthorized', 401);
			}			
			
			$user = $this->mongo_db
				->select(['email', 'password'])
				->where([
					'email' => $_POST['email'],				
				])
				->getOne('app_users');

			if (!$user) {
				throw new Exception("Email not registered", 401);				
			}

			if (!password_verify($_POST['password'], $user['password'])) {
				throw new Exception("Invalid Password", 401);				
			}

			echo json_encode(array('status' => 1, 'message' => 'Success'));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}
}

?>
