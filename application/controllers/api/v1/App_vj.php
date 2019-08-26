<?php
class App_vj extends CI_Controller 
{
	function __construct() {
		parent::__construct();
		$this->load->library('mongo_db');		
		$this->load->library('mongo_private');
	}

	private $app_users_collection = 'vj_app_users';
	

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
				->select(['email', 'password', 'token', 'name'])
				->where([
					'email' => strtolower($_POST['email']),
				])
				->getOne($this->app_users_collection);

			if (!$user) {
				throw new Exception("Email not registered", 401);				
			}

			if (!password_verify($_POST['password'], $user['password'])) {
				throw new Exception("Invalid Password", 401);				
			}

			$token = '';
			if (!isset($user['token']) || $user['token'] == '') {
				$token = $_POST['email'] . bin2hex(openssl_random_pseudo_bytes(64));
				$this->mongo_db->where(['email' => $_POST['email']])->set(['token' => $token])->update($this->app_users_collection);	
			} else {
				$token = $user['token'];
			}

			echo json_encode(array('status' => 1, 'message' => 'Success', 'token' => $token, 'username' => isset($user['name']) ? $user['name'] : ''));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function changePassword() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['newPassword']) || !isset($_POST['confirmNewPassword'])) {
				throw new Exception('Unauthorized', 401);
			}

			if ($_POST['newPassword'] != $_POST['confirmNewPassword']) {
				throw new Exception('New Passwords are not the same', 401);	
			}
			
			$user = $this->mongo_db
				->select(['email', 'password'])
				->where([
					'email' => strtolower($_POST['email']),
				])
				->getOne($this->app_users_collection);

			if (!$user) {
				throw new Exception("Email not registered", 401);				
			}

			if (!password_verify($_POST['password'], $user['password'])) {
				throw new Exception("Invalid Password", 401);				
			}

			$newPassword = password_hash($_POST['newPassword'], PASSWORD_BCRYPT);

			$this->mongo_db->where('email', $user['email'])->set(['password' => $newPassword])->update($this->app_users_collection);

			echo json_encode(array('status' => 1, 'message' => 'Success'));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function logout() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_POST['token']) || !isset($_POST['deviceToken'])) {				
				throw new Exception('Unauthorized', 401);
			}			
			
			$user = $this->mongo_db
				->select(['deviceToken'])
				->where([
					'token' => $_POST['token'],
				])
				->getOne($this->app_users_collection);

			$deviceToken = $user['deviceToken'];

			foreach ($deviceToken as $key => $value) {
				if ($value == $_POST['deviceToken']) {
					unset($deviceToken[$key]);
				}
			}						

			$this->mongo_db->where('token', $_POST['token'])->update($this->app_users_collection, ['$set' => ['deviceToken' => $deviceToken]]);

			echo json_encode(array('status' => 1, 'message' => 'Success'));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function postCase() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				throw new Exception('Bad Method Request', 403);
			}	

			if (!isset($_POST['token'])) {				
				throw new Exception('Unauthorized', 401);
			}
			
			$user = $this->mongo_db				
				->where([
					'token' => $_POST['token'],				
				])
				->getOne($this->app_users_collection);			

			if (!$user) {
				throw new Exception("Unauthorized", 401);				
			}			

			$ticket_id = '';
			$lastTicket = $this->mongo_db->select(['ticket_id'])->where('source', 'App Ticket')->order_by(['receive_time' => 'desc'])->getOne('2_Ticket');
			if (!isset($lastTicket['ticket_id'])) {
				$ticket_id = '#TK_APP_1';
			} else {
				$ticket_id_fragments = explode('_', $lastTicket['ticket_id']);
				$ticket_id_fragments[count($ticket_id_fragments) - 1]++;
				$ticket_id = implode("_", $ticket_id_fragments);
			}

			//assign shit
			$assign = [];
			$assignGroup = '';
			$assignGroupName = '';
			$assignView = '';

			if (isset($_POST['isAgentAssign']) && $_POST['isAgentAssign'] == 'true') {
				$assignView = isset($_POST['assign']) ? $_POST['assign'] : "";
				$assign = [$assignView];				
			} else {
				$assignGroup = isset($_POST['assign']) ? $_POST['assign'] : "";
				$_group = $this->mongo_db->select(['name', 'members'])->where('id', $assignGroup)->getOne('2_Group');
				$assign = $_group['members'];
				$assignGroupName = $assignView = $_group['name'];				
			}

			$data = [				
				'source' => 'App Ticket',
				'receive_time' => time(),
				'status' => 'Open',
				'createdAt' => isset($_POST['createdAt']) ? (int)$_POST['createdAt'] : 0,
				'sender_name' => isset($user['email']) ? $user['email'] : "",
				'title' => isset($_POST['title']) ? $_POST['title'] : "",
				'priority' => isset($_POST['priority']) ? $_POST['priority'] : "",
				'content' => isset($_POST['content']) ? $_POST['content'] : "",
				'isAgentAssign' => isset($_POST['isAgentAssign']) ? ($_POST['isAgentAssign'] == 'true' ? true : false) : null,
				'assignView' => $assignView,
				'assign' => $assign,
				'assignGroupName' => $assignGroupName,
				'assignGroup' => $assignGroup,
				'service' => isset($_POST['service']) ? $_POST['service'] : "",
				'PNRList' => isset($_POST['PNR']) ? explode(",", str_replace(' ', '', $_POST['PNR'])) : "",
				'customerFormat' => isset($_POST['customerFormat']) ? json_decode($_POST['customerFormat']) : "",
				'contactPersonInfoList' => isset($_POST['contactPersonInfoList']) ? (array)json_decode($_POST['contactPersonInfoList']) : "",
				'contactPersonInfo' => isset($_POST['contactPersonInfoList']) ? (array)json_decode($_POST['contactPersonInfoList']) : [],
				'createdBy' => $user['email'],
				'uuid' => $_POST['uuid'],
				'ticket_id' => $ticket_id,
				'images' => isset($_POST['images']) ? $_POST['images'] : [],
			];

			$this->mongo_db->insert('2_Ticket', $data);			

			echo json_encode(array('status' => 1, 'message' => 'Success'));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function getCases() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'GET') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_GET['token'])) {				
				throw new Exception('Unauthorized', 401);
			}

			$user = $this->mongo_db
				->select(['token', 'email'])
				->where([
					'token' => $_GET['token'],				
				])
				->getOne($this->app_users_collection);			

			if (!$user) {
				throw new Exception("Unauthorized", 401);				
			}			

			$page = isset($_GET['page']) ? $_GET['page'] : 1;
			$result = isset($_GET['result']) ? $_GET['result'] : 10;

			$data = $this->mongo_db->select(['uuid', 'title', 'ticket_id', 'receive_time', 'status', 'createdAt'])->where('createdBy', $user['email'])->limit($result)->offset(($page - 1)*$result)->order_by(['receive_time' => 'desc'])->get('2_Ticket');

			echo json_encode(array('status' => 1, 'message' => 'Success', 'data' => $data));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function searchCases() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'GET') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_GET['token'])) {				
				throw new Exception('Unauthorized', 401);
			}

			if (!isset($_GET['qs']) || $_GET['qs'] == '') {
				throw new Exception('Bad Request', 400);
			}

			$user = $this->mongo_db
				->select(['token', 'email'])
				->where([
					'token' => $_GET['token'],				
				])
				->getOne($this->app_users_collection);			

			if (!$user) {
				throw new Exception("Unauthorized", 401);				
			}			

			$page = isset($_GET['page']) ? $_GET['page'] : 1;
			$result = isset($_GET['result']) ? $_GET['result'] : 10;

			$data = $this->mongo_db->select(['uuid', 'title', 'ticket_id', 'receive_time', 'status', 'createdAt'])
				->where('createdBy', $user['email'])				
				->where([
					'$or' => [
						['ticket_id' => ['$regex' => $_GET['qs']]],
						['title' => ['$regex' => $_GET['qs']]],
						['content' => ['$regex' => $_GET['qs']]],
					]
				])
				
				->limit($result)
				->offset(($page - 1)*$result)
				->order_by(['receive_time' => 'desc'])
				->get('2_Ticket');

			echo json_encode(array('status' => 1, 'message' => 'Success', 'data' => $data));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function getCaseDetail() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'GET') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_GET['token'])) {				
				throw new Exception('Unauthorized', 401);
			}

			$user = $this->mongo_db
				->select(['token', 'email'])
				->where([
					'token' => $_GET['token'],				
				])
				->getOne($this->app_users_collection);			

			if (!$user) {
				throw new Exception("Unauthorized", 401);				
			}		

			if (!isset($_GET['uuid'])) {	
				throw new Exception('Bad Request', 400);
			}

			$data = $this->mongo_db->where(['uuid' => $_GET['uuid'], 'createdBy' => $user['email']])->getOne('2_Ticket');

			echo json_encode(array('status' => 1, 'message' => 'Success', 'data' => $data));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function pushDeviceToken() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				throw new Exception('Bad Method Request', 403);
			}	

			if (!isset($_POST['token'])) {				
				throw new Exception('Unauthorized', 401);
			}

			if (!isset($_POST['deviceToken'])) {				
				throw new Exception('Unauthorized', 400);
			}

			$user = $this->mongo_db->select('deviceToken')->where('token', $_POST['token'])->getOne($this->app_users_collection);
			$deviceToken = (array)$user['deviceToken'];			
			$deviceToken[] = $_POST['deviceToken'];				
			
			$this->mongo_db->where('token', $_POST['token'])->set(['deviceToken' => (array)array_unique($deviceToken)])->update($this->app_users_collection);			

			echo json_encode(array('status' => 1, 'message' => 'Success'));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function getDdlDataSource() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'GET') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_GET['token'])) {				
				throw new Exception('Unauthorized', 401);
			}

			$user = $this->mongo_db
				->select(['token', 'email'])
				->where([
					'token' => $_GET['token'],				
				])
				->getOne($this->app_users_collection);			

			if (!$user) {
				throw new Exception("Unauthorized", 401);				
			}		

			$customerFormatItems = [];
			$priorityItems = [];
			$jsonData = $this->mongo_private->where(['$or' => [['name' => 'Ticket customer format'], ['name' => 'Ticket priority']]])
			->get("2_Jsondata");
			foreach($jsonData as $item) {
				if ($item['name'] == 'Ticket customer format') {
					$customerFormatItems = $item['data'];
				} elseif ($item['name'] == 'Ticket priority') {
					$priorityItems = $item['data'];
				}				
			}

			$assignItems = [];
			$_assignItems = $this->mongo_private->get("2_User");
			foreach ($_assignItems as $item) {
				$assignItems[] = ['value' => $item['extension']];
			}

			$assignGroupItems = $this->mongo_db->select(['name', 'id'])->get("2_Group");
			foreach ($assignGroupItems as &$item) {
				$item['value'] = $item['name'];
			}

			$serviceItems = array();
			$service_lv1 = $this->mongo_db->where(array("lv" => 1))->select(["name", "lv"])->get('2_Service_level');
			foreach ($service_lv1 as $doc1) {
				$service_lv2 = $this->mongo_db->where(array("lv" => 2, "parent_id" => new MongoDB\BSON\ObjectId($doc1["id"])))
				->select(["name", "lv"])->get('2_Service_level');
				foreach ($service_lv2 as $doc2) {
					$service_lv3 = $this->mongo_db->where(array("lv" => 3, "parent_id" => new MongoDB\BSON\ObjectId($doc2["id"])))
					->select(["name", "lv"])->get('2_Service_level');
					foreach ($service_lv3 as $doc3) {
						$serviceItems[] = array(
							"value" 		=> $doc1["name"] . " / " . $doc2["name"] . " / " . $doc3["name"],
						); 
					}
				}
			}

			$data = [
				'assignItems' => $assignItems,
				'assignGroupItems' => $assignGroupItems,
				'serviceItems' => $serviceItems,
				'priorityItems' => $priorityItems,
				'customerFormatItems' => $customerFormatItems
			];

			echo json_encode(array('status' => 1, 'message' => 'Success', 'data' => $data));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function getLastestDdlUpdateTime() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'GET') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_GET['token'])) {				
				throw new Exception('Unauthorized', 401);
			}

			$user = $this->mongo_db
				->select(['token', 'email'])
				->where([
					'token' => $_GET['token'],				
				])
				->getOne($this->app_users_collection);			

			if (!$user) {
				throw new Exception("Unauthorized", 401);				
			}		

			$config = $this->mongo_db->select(['lastestDdlUpdateTime'])->getOne('app_config');
			$lastestDdlUpdateTime = isset($config['lastestDdlUpdateTime']) ? $config['lastestDdlUpdateTime'] : time();

			echo json_encode(array('status' => 1, 'message' => 'Success', 'data' => (int)$lastestDdlUpdateTime));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function getDdlDataSourceForWF() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'GET') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_GET['token']) || $_GET['token'] != 'linhdeptrai') {				
				throw new Exception('Unauthorized', 401);
			}

			$serviceItems = array();
			$service_lv1 = $this->mongo_db->where(array("lv" => 1))->select(["name", "lv"])->get('2_Service_level');
			foreach ($service_lv1 as $doc1) {
				$service_lv2 = $this->mongo_db->where(array("lv" => 2, "parent_id" => new MongoDB\BSON\ObjectId($doc1["id"])))
				->select(["name", "lv"])->get('2_Service_level');
				foreach ($service_lv2 as $doc2) {
					$service_lv3 = $this->mongo_db->where(array("lv" => 3, "parent_id" => new MongoDB\BSON\ObjectId($doc2["id"])))
					->select(["name", "lv"])->get('2_Service_level');
					foreach ($service_lv3 as $doc3) {
						$serviceItems[] = array(
							"value" 		=> $doc1["name"] . " / " . $doc2["name"] . " / " . $doc3["name"],
						); 
					}
				}
			}

			echo json_encode(array('status' => 1, 'message' => 'Success', 'data' => $serviceItems));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function postCaseFromCWF() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_POST['token']) || $_POST['token'] != 'linhdeptrai') {				
				throw new Exception('Unauthorized', 401);
			}

			$ticket_id = '';
			$lastTicket = $this->mongo_db->select(['ticket_id'])->where('source', 'WebForm')->order_by(['receive_time' => 'desc'])->getOne('2_Ticket');

			if (!isset($lastTicket['ticket_id'])) {
				$ticket_id = '#TK_CWF_1';
			} else {
				$ticket_id_fragments = explode('_', $lastTicket['ticket_id']);
				$ticket_id_fragments[count($ticket_id_fragments) - 1]++;
				$ticket_id = implode("_", $ticket_id_fragments);
			}	

			$contactPersonInfoList = [
				[
					'name' => isset($_POST['name']) ? $_POST['name'] : "",
					'email' => isset($_POST['email']) ? $_POST['email'] : "",
					'phone' => isset($_POST['phone']) ? $_POST['phone'] : "",
				]
			];		

			$data = [				
				'source' => 'WebForm',
				'receive_time' => time(),
				'status' => 'Open',
				'createdAt' => time(),
				'sender_name' => isset($_POST['name']) ? $_POST['name'] : "",
				'title' => isset($_POST['title']) ? $_POST['title'] : "",				
				'content' => isset($_POST['content']) ? $_POST['content'] : "",				
				'service' => isset($_POST['service']) ? $_POST['service'] : "",
				'PNRList' => isset($_POST['pnrList']) ? explode(",", str_replace(' ', '', $_POST['pnrList'])) : "",				
				'contactPersonInfoList' => $contactPersonInfoList,	
				'ticket_id' => $ticket_id,			
				'images' => isset($_POST['images']) ? $_POST['images'] : [],	
			];

			$this->mongo_db->insert('2_Ticket', $data);			

			echo json_encode(array('status' => 1, 'message' => 'Success'));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}

	public function getStats() {
		header('Content-Type: application/json');

		try {
			if ($_SERVER['REQUEST_METHOD'] != 'GET') {
				throw new Exception('Bad Method Request', 403);
			}

			if (!isset($_GET['token'])) {				
				throw new Exception('Unauthorized', 401);
			}

			$user = $this->mongo_db
				->select(['token', 'email'])
				->where([
					'token' => $_GET['token'],				
				])
				->getOne($this->app_users_collection);			

			if (!$user) {
				throw new Exception("Unauthorized", 401);				
			}		
			
			$beginDayOfMonthTS = strtotime(date('Y-m-1'));
			$beginDayOfYearTS = strtotime(date('Y-1-1'));

			$monthsInYearArr = [];
			for ($i = 1; $i <= date('m'); $i++) {
				$monthsInYearArr[$i]['month'] = (int)$i;
				$monthsInYearArr[$i]['from'] = strtotime(date('Y-'.$i.'-1'));
				$monthsInYearArr[$i]['to'] =  strtotime(date('Y-'.(string)($i+1).'-1')) - 1;
			}

			$rawData1 = $this->mongo_db->select('_id', 'createdAt')
				->where('source', 'App Ticket')
				->where('createdBy', $user['email'])
				->where_gte('createdAt', $beginDayOfYearTS)
				->get('2_Ticket');

			$data1 = [];			
			foreach ($monthsInYearArr as $month) {
				$count = 0;

				foreach ($rawData1 as $rawData) {				
					if ($rawData['createdAt'] >= $month['from'] && $rawData['createdAt'] <= $month['to']) {
						$count++;
					}
				}

				$data1[] = [
					'month' => $month['month'],
					'count' => $count
				];
			}

			$pipeline2 = array(
				array(
					'$match' => array(
						'$and' => array(
							array('source' => 'App Ticket'),
							array('createdBy' => $user['email']),
							array('createdAt' => array('$gte' => $beginDayOfMonthTS)),
						)
					)
				),
				array(
					'$group' => array(
						'_id' => '$status',					
						'count' => array('$sum'=> 1)
					)
				)
			);

			$data2 = $this->mongo_db->aggregate_pipeline('2_Ticket', $pipeline2);

			$rawData3 = $this->mongo_db->select('_id', 'createdAt')
				->where('source', 'App Ticket')
				->where('createdBy', $user['email'])
				->where_gte('createdAt', $beginDayOfMonthTS)
				->get('2_Ticket');

			$daysInMonthArr = [];
			for ($i = 1; $i <= date('d'); $i++) {
				$daysInMonthArr[$i]['day'] = $i;
				$daysInMonthArr[$i]['from'] = strtotime('midnight', strtotime(date('Y-m-'.$i)));
				$daysInMonthArr[$i]['to'] =  strtotime('tomorrow', $daysInMonthArr[$i]['from'])  - 1;
			}

			$data3 = [];
			foreach ($daysInMonthArr as $day) {
				$count = 0;

				foreach ($rawData3 as $rawData) {				
					if ($rawData['createdAt'] >= $day['from'] && $rawData['createdAt'] <= $day['to']) {
						$count++;
					}
				}

				$data3[] = [
					'day' => $day['day'],
					'count' => $count
				];
			}

			$data = [
				'fetchedAt' => time(),
				'data1' => $data1,
				'data2' => $data2,
				'data3' => $data3,
			];

			echo json_encode(array('status' => 1, 'message' => 'Success', 'data' => $data));
		} catch (Exception $e) {
			http_response_code($e->getCode());
			echo json_encode(array('status' => 0, 'message' => $e->getMessage()));			
		}
	}
}

?>
