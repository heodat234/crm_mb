<?php

class Appservices extends WFF_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('mongo_db');
    }

    public function pushStatusUpdateNoti() {        
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            throw new Exception('Bad Method Request', 403);
        }   

        if (!isset($_POST['ticket_id'])) {              
            throw new Exception('Bad Request. Invalid data', 400);
        }

        try {
            $app_config = $this->mongo_db->select(['fcm_server_key'])->getOne('app_config');            

            $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
            
            $ticket = $this->mongo_db->select(['ticket_id', 'uuid', 'status', 'createdBy'])->where('ticket_id', $_POST['ticket_id'])->getOne('2_Ticket');
            $user =  $this->mongo_db->where('email', $ticket['createdBy'])->getOne('app_users');

            if (!isset($user['deviceToken'])) {
                throw new Exception("Unavailable device");
            }

            $tokens = $user['deviceToken'];                        

            $notification = [
                'title' => "Ticket ID ".$ticket['ticket_id'],
                'body' => "Ticket has been updated status to: " . $ticket['status'],
                'sound' => true,
                'priority' => 'high'
                //'data' => $ticket
            ];

            $firebaseServerKey = isset($app_config['fcm_server_key']) ? $app_config['fcm_server_key'] : "";
            $headers = [
                'Authorization: key=' . $firebaseServerKey,
                'Content-Type: application/json'
            ];

            foreach ($tokens as $token) {
                $fcmNotification = [
                    'to'        => $token, //single token
                    'notification' => $notification,     
                    'data' => $ticket       
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$fcmUrl);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
                $result = curl_exec($ch);
                curl_close($ch);
            }

            echo json_encode(['status' => 1, 'result' => $result]);
        } catch (Exception $e) {
            echo json_encode(array('status' => 0, 'message' => $e->getMessage()));  
        }
        
    }
}

?>