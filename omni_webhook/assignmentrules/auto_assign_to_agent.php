<?php
    class auto_assign_to_agent {

        private $arraykey;

        function __construct() {            
            $this->mongo_db = new MyMongoDriver(Config::MONGO_DB_NAME, Config::MONGO_DB_HOST, Config::MONGO_DB_PORT);
            $this->omni_webhook_socket_url = Config::OMNI_WEBHOOK_SOCKET_URL() . '/api/v2/chat';
            $this->omni_webhook_noifi_createroom = Config::OMNI_WEBHOOK_SOCKET_URL() . '/api/v2/loadnewroom';
            // $this->customer_secret_key = Config::CUSTOMER_SECRET_KEY;
            $this->customer_type = Config::CUSTOMER_TYPE;

            $this->maxCusAssign = 5;
            /*$this->agents=[
                ['id' =>1,'curCusAssign' =>0,"arrCus"=>[]],
                ['id' =>2,'curCusAssign' =>0,"arrCus"=>[]],
                ['id' =>3,'curCusAssign' =>0,"arrCus"=>[]]
            ];
            $this->customers=[];
            for($i=1;$i<=13;$i++){
                $this->customers[]=["name"=>"test".$i,"phone"=>"123456789".$i];
            }*/

        }

        /*public function index() {
            print_r($this->omni_webhook_noifi_createroom);
        }*/

        public function index($data) {
            header('Content-Type: application/json');
            $data_return  = array();

            // $f = fopen($_SERVER['DOCUMENT_ROOT']."/omni_webhook/webhook_in.txt", "a+");
            // fwrite($f, print_r($data, true));
            // fclose($f);

            try {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if ($data['trigger'] == 'comment') {
                        $this->addComment($data);
                    } elseif ($data['trigger'] == 'message') {
                        if (!empty($data['messages']['is_echo'])) {
                            $this->addMsgEcho($data);
                        } else {
                            $this->addMsg($data);
                        }
                    }
                }
                echo json_encode(array('status' => 0, 'data'    => $data_return,  'message' => 'Success'));
            } catch (Exception $ex) {
                echo json_encode(array('status' => 1,  'message' => $ex->getMessage()));
            }
        }

        private function addMsg($data) {
            $sender_id = $data['messages']['sender_id'];
            $page_id = $data['page_id'];
            $data['messages']['sender_info']['type'] = 'customer';

            $room = $this->mongo_db->where(array('to.user_id' => $sender_id, 'source' => $data['messages']['source']['type']))->getOne('chatGroups');
           
            if (!empty($room)) {
                $room_id = $room['_id']->{'$id'};
                //Lưu lại active mới nhất
                $this->mongo_db->where(array('_id' => new mongoId($room_id)))->set(array('status' => 1, 'date_active' => time()))->update('chatGroups');
            } else {
                $room_id = '';
        
                //Nếu message không nằm ở nhóm chat nào thì kiểm tra page thuộc quản lý của user nào để add notifi
               $pageapps = $this->mongo_db->where(array('id' => $page_id))->getOne('pageapps');
               
                if (!empty($pageapps)) {
                    $group_id = isset($pageapps['group_id']) ? $pageapps['group_id'] : '';
                } else {
                    throw new Exception('pageapps Empty');
                }
                if ($data['messages']['source']['type'] == 'messenger') {
                    $data_type = "new_facebook_chat";
                    $data_line = "facebook";
                } else if ($data['messages']['source']['type'] == 'livechat') {
                    $data_type = "new_livechat_chat";
                    $data_line = "livechat";
                } else if ($data['messages']['source']['type'] == 'zalo') {
                    $data_type = "new_zalo_chat";
                    $data_line = "zalo";
                } else if ($data['messages']['source']['type'] == 'livechat_remote') {
                    $data_type = "new_livechat_remote";
                    $data_line = "livechat_remote";
                }


                $chatGroup_Manager = $this->mongo_db->where(array('_id' => new mongoId($group_id)))->getOne('chatGroup_Manager');
                $group_agents = $chatGroup_Manager['agents'];
                $assign_to_user = '';
                if (empty($chatGroup_Manager)) {
                    throw new Exception('chatGroup_Manager Empty');
                }
                if (empty($chatGroup_Manager['agents'])) {
                    throw new Exception('chatGroup_Manager assigns Empty');
                }

                // lấy tất cả chat với from là user này với status của room = 1, 
                // function tính ra con số total của mổi thành viên list thành 1 array
               /* $group_agent_count_assign = $this->AgentsAssignTotal($group_id, $group_agents);
                usort($group_agent_count_assign, function($a, $b) {
                    return $a['number'] > $b['number'];
                });*/

                /*$f = fopen("/var/www/html/worldfone4xs/omni_webhook/webhook_in.txt", "a+");
                    fwrite($f, print_r($group_agent_count_assign, true));
                    fclose($f);*/

              
                /*if ($group_agent_count_assign[0]['number'] >= $this->maxCusAssign) {
                    // Xử lý cho vào hàng đợi
                }else{
                    //assign cho user đó
                    $assign_to_user = $group_agent_count_assign[0]['id'];
                }*/
                $assign_to_user = $this->AgentsAssignTotal($group_id, $group_agents);
                //var_dump($assign_to_user);
                //Không có agent nào thỏa dk
                if (empty($assign_to_user)) {
                    //xử lý thông báo
                    $notification_data = array(
                        'type'            => $data_type,
                        'trigger'         => 'message',
                        'line'            => $data_line,
                        'source'          => $data['messages']['source']['type'],
                        'page_id'         => $page_id,
                        'group_id'        => $chatGroup_Manager['_id']->{'$id'},
                        'sender_id'       => $sender_id,
                        'title'           => $data['messages']['sender_info']['name'],
                        'text'            => $data['messages']['text'],
                        'assign_to_user'  => $assign_to_user,
                        'sender_info'     => $data['messages']['sender_info'],
                        'supervisor'      => $chatGroup_Manager['supervisor'],
                        'supervisor_name' => $chatGroup_Manager["supervisor"],
                        'date_added'      => $data['messages']['timestamp'],
                    );
                    $room_id = $this->createRoomAndNotify($notification_data);
                }else{
                    $notification_data = array(
                        'type'            => $data_type,
                        'trigger'         => 'message',
                        'line'            => $data_line,
                        'source'          => $data['messages']['source']['type'],
                        'page_id'         => $page_id,
                        'group_id'        => $chatGroup_Manager['_id']->{'$id'},
                        'sender_id'       => $sender_id,
                        'title'           => $data['messages']['sender_info']['name'],
                        'text'            => $data['messages']['text'],
                        'assign_to_user'  => $assign_to_user,
                        'sender_info'     => $data['messages']['sender_info'],
                        'supervisor'      => $chatGroup_Manager['supervisor'],
                        'supervisor_name' => $chatGroup_Manager["supervisor"],
                        'date_added'      => $data['messages']['timestamp'],
                    );

                    $room_id = $this->createRoomAndAssign($notification_data);
                    $notification_data['room_id'] = $room_id;
                    $this->createInteractive($notification_data);
                }

                
                /*$room_id = $this->createRoomAndAssign($notification_data);
                
                $room_group = $this->mongo_db->where(array('to.user_id' => $sender_id, 'source' => $data['messages']['source']['type']))->getOne('chatGroups');
                $room_id = $room_group["_id"]->{'$id'};*/
            }

            $room_update = $this->mongo_db->where(array("_id" => new MongoId($room_id)))->set("read_by", array())->update('chatGroups');

            $message_data = array(
                'trigger' => 'message',
                'source' => $data['messages']['source']['type'],
                'type' => $data['messages']['type'],
                'page_id' => $page_id,
                'sender_id' => $sender_id,
                'sender_info' => $data['messages']['sender_info'],
                'room_id' => $room_id,
                'text' => $data['messages']['text'],
                'url' => $data['messages']['url'],
                'date_added' => $data['messages']['timestamp'],
            );

            $result = $this->mongo_db->insert('chatMessages', $message_data);
            $message_data['id'] = $result->{'$id'};
            // var_dump($message_data);
            // Gởi cho socket giao diện
            $this->sendUrl($this->omni_webhook_socket_url, $message_data);

            

        }

        private function addComment($data) {
            $sender_id = $data['messages']['sender_id'];
            $page_id = $data['page_id'];
            $data['messages']['sender_info']['type'] = 'customer';
            $post_id = $data['messages']['details']['post_id'];
            $room = $this->mongo_db->where(array('details.post_id' => $post_id, 'to.user_id' => $sender_id, 'source' => 'facebook'))->getOne('chatGroups');
            if (!empty($room)) {
                $room_id = $room['_id']->{'$id'};
                //Lưu lại active mới nhất
                $this->mongo_db->where(array('_id' => new mongoId($room_id)))->set(array('status' => 1, 'date_active' => time()))->update('chatGroups');
            } else {
                $room_id = '';
                //Nếu message không nằm ở nhóm chat nào thì kiểm tra page thuộc quản lý của user nào để add notifi
                $pageapps = $this->mongo_db->where(array('id' => $page_id))->getOne('pageapps');
                if (!empty($pageapps)) {
                    $group_id = isset($pageapps['group_id']) ? $pageapps['group_id'] : '';
                    $username = isset($pageapps['username']) ? $pageapps['username'] : '';
                } else {
                    $group_id = '';
                    $username = '';
                }
                if ($data['messages']['source']['type'] == 'facebook') {
                    $data_type = "new_facebook_comment";
                    $data_line = "facebook";
                } else if ($data['messages']['source']['type'] == 'livechat') {
                    $data_type = "new_livechat_comment";
                    $data_line = "livechat";
                } else if ($data['messages']['source']['type'] == 'zalo') {
                    $data_type = "new_zalo_comment";
                    $data_line = "zalo";
                }
                $chatGroup_Manager = $this->mongo_db->where(array('_id' => new mongoId($group_id)))->getOne('chatGroup_Manager');
                $group_agents = $chatGroup_Manager['agents'];
                $assign_to_user = '';
                if (empty($chatGroup_Manager)) {
                    throw new Exception('chatGroup_Manager Empty');
                }
                if (empty($chatGroup_Manager['agents'])) {
                    throw new Exception('chatGroup_Manager assigns Empty');
                }

                // lấy tất cả chat với from là user này với status của room = 1, 
                // function tính ra con số total của mổi thành viên list thành 1 array
                $assign_to_user = $this->AgentsAssignTotal($group_id, $group_agents);
                //Không có agent nào thỏa dk
                if (empty($assign_to_user)) {
                    $notification_data = array(
                        'type' => $data_type,
                        'trigger' => 'comment',
                        'line' => $data_line,
                        'source' => $data['messages']['source']['type'],
                        'page_id' => $page_id,
                        'sender_id' => $sender_id,
                        'assign_to_user' => $assign_to_user,
                        'title' => $data['messages']['sender_info']['name'],
                        'text' => $data['messages']['text'],
                        'sender_info' => $data['messages']['sender_info'],
                        'details' => $data['messages']['details'],
                        'group_id' => $group_id,
                        'username' => $username,
                        'date_added' => $data['messages']['timestamp'],
                    );
                    $room_id = $this->createRoomAndNotify($notification_data);
                }else{
                    $notification_data = array(
                        'type' => $data_type,
                        'trigger' => 'comment',
                        'line' => $data_line,
                        'source' => $data['messages']['source']['type'],
                        'page_id' => $page_id,
                        'sender_id' => $sender_id,
                        'assign_to_user' => $assign_to_user,
                        'title' => $data['messages']['sender_info']['name'],
                        'text' => $data['messages']['text'],
                        'sender_info' => $data['messages']['sender_info'],
                        'details' => $data['messages']['details'],
                        'group_id' => $group_id,
                        'username' => $username,
                        'date_added' => $data['messages']['timestamp'],
                    );
                    $room_id = $this->createRoomAndAssign($notification_data);
                }


                
            }
            $room_update = $this->mongo_db->where(array("_id" => new MongoId($room_id)))->set("read_by", array())->update('chatGroups');

            $message_data = array(
                'trigger' => 'comment',
                'source' => 'facebook',
                'type' => $data['messages']['type'],
                'page_id' => $data['page_id'],
                'sender_id' => $data['messages']['sender_id'],
                'sender_info' => $data['messages']['sender_info'],
                'details' => $data['messages']['details'],
                'room_id' => $room_id,
                'comment_id' => $data['messages']['comment_id'],
                'text' => $data['messages']['text'],
                'date_added' => $data['messages']['timestamp'],
            );

            // Gởi cho socket giao diện
            $result = $this->mongo_db->insert('chatMessages', $message_data);
            $message_data['id'] = $result->{'$id'};
            $this->sendUrl($this->omni_webhook_socket_url, $message_data);

        }
        
        private function addMsgEcho($data) {//Tin Nhắn được gởi từ page trên facebook
            try {

                if (isset($data['messages']['metadata']['id'])) {
                    $message_id = $data['messages']['metadata']['id'];
                    $message_info = $this->mongo_db->where(array('_id' => new mongoId($message_id)))->getOne('chatMessages');
                } else {
                    $message_info = '';
                }

                if (empty($message_info)) {
                    $recipient_id = $data['messages']['recipient_id'];
                    $sender_id = $data['messages']['sender_id'];
                    $page_id = $data['page_id'];

                    $room = $this->mongo_db->where(array('to.user_id' => $recipient_id, 'source' => $data['messages']['source']['type']))->getOne('chatGroups');
                    if (!empty($room)) {
                        $room_id = $room['_id']->{'$id'};
                        //Lưu lại active mới nhất   
                        $this->mongo_db->where(array('_id' => new mongoId($room_id)))->set(array('status' => 1, 'date_active' => time()))->update('chatGroups');
                    } else {
                        $room_id = '';
                    }

                    // Kiểm tra loại là link nhưng bị empty url
                    if ($data['messages']['type'] == 'link' && empty($data['messages']['url'])) {
                        header('Content-Type: application/json');
                        echo json_encode(array('status' => 1, 'errorMessage' => 'Type not support!'));
                        exit();
                    }
                    $data['messages']['sender_info']['type'] = 'page';
                    $message_data = array(
                        'trigger' => 'message',
                        'source' => $data['messages']['source']['type'],
                        'type' => $data['messages']['type'],
                        'page_id' => $page_id,
                        'sender_id' => $sender_id,
                        'recipient_id' => $recipient_id,
                        'sender_info' => $data['messages']['sender_info'],
                        'room_id' => $room_id,
                        'message_app_id' => $data['messages']['message_app_id'],
                        'text' => $data['messages']['text'],
                        'url' => $data['messages']['url'],
                        'date_added' => $data['messages']['timestamp'],
                    );
                    $result = $this->mongo_db->insert('chatMessages', $message_data);
                    $message_data['id'] = $result->{'$id'};
                    // Gởi cho socket giao diện
                    $this->sendUrl($this->omni_webhook_socket_url, $message_data);
                    // $f = fopen("../worldfone4x/application/controllers/apis/webhooktest_in.txt", "a+");
                    // fwrite($f, print_r($message_data, true));
                    // fclose($f);
                    
                }
            } catch (Exception $ex) {
                // $f = fopen("../worldfone4x/application/controllers/apis/webhooktest_err.txt", "a+");
                // fwrite($f, print_r($ex, true));
                // fclose($f);
            }
        }

        

        private function sendUrl($url, $data) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_SSL_VERIFYHOST => 0, // don't verify ssl 
                CURLOPT_SSL_VERIFYPEER => false, //
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'cache-control: no-cache',
                    'content-type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            $response = json_decode($response);
            if (isset($response->error) && $response->error== 0) {
                
            }else{
                if (isset($data["retryCount"])) {
                    $data["retryCount"]++;
                }else{
                    $data["retryCount"] = 1;
                };
                if ($data["retryCount"]<=5) {                    
                    sleep(1);
                    $this->sendUrl($url, $data);           
                }                
            }
            
            /*$f = fopen($_SERVER['DOCUMENT_ROOT']."/omni_webhook/webhook_in.txt", "a+");
            fwrite($f, print_r($response, true));
            fclose($f);*/

          
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);

            curl_close($curl);
        }

        public function createInteractive($data){
            /*
            "title" : "Call",
            "content" : " - ",
            "type" : "call",
            "active" : true,
            "other_id" : null,
            "foreign_key" : "",
            "createdAt" : 1557815668
            */
            //$this->customer_type
            $people_info = $this->mongo_db->where(array('people_id' => $data['sender_id'], 'page_id' => $data['page_id']))->getOne('people');
            if (isset($people_info['customer_4x_id']) && !empty($people_info['customer_4x_id'])) {
                $doc = array(
                "title"         => "Conversation",
                "content"       => $data['sender_info']['name'],
                "type"          => "conversation",
                "active"        => false,
                "other_id"      => $data['room_id'],
                "room_id"      => $data['room_id'],
                "foreign_id"   => new mongoId($people_info['customer_4x_id']),
                "createdAt"     => time(),
            );
            $this->mongo_db->insert($this->customer_type.'_Interactive', $doc);      
            }
                 

        }
        

        public function createRoomAndAssign($notification_data) {
            /*$f = fopen("../worldfone4x/application/controllers/apis/webhooktest_noti.txt", "a+");
            fwrite($f, print_r($notification_data, true));
            fclose($f);*/
            $json = array();
            $line = $notification_data['line'];
            $type = $notification_data['type'];
            $sender_id = $notification_data['sender_id'];
            $source = $notification_data['source'];


            if ($line == "livechat" && $type == "new_livechat_chat") {

                $data_views = $this->mongo_db->where(array("type" => "new_livechat_chat", "sender_id" => $sender_id))->getOne('chatNotifi');

                $room_array = array(
                    'user_id_create' => '',
                    'page_id' => $notification_data['page_id'],
                    'group_id' => $notification_data['group_id'],
                    'type' => "new_livechat_chat", //private/group
                    'from' => array(
                        "id" => $notification_data["assign_to_user"],
                        "username" => $notification_data["assign_to_user"],
                        "name" => $notification_data["assign_to_user"],
                        "type" => "user",
                    ),
                    'to' => array(
                        "id" => $sender_id,
                        "username" => $notification_data['sender_info']['name'],
                        "type" => 'customer',
                        "user_id" => $sender_id,
                    ),
                    'group_user' => array(),
                    'group_name' => $notification_data['sender_info']['name'],
                    'date_active' => time(),
                    'date_added' => time(),
                    'status' => 1,
                    //them 
                    //'room_id' => $room_id,
                    'trigger' => $notification_data['trigger'],
                    'source' => $source
                );

                $result = $this->mongo_db->insert('chatGroups', $room_array);
                
                $room_id = $result->{'$id'};

                $this->NotifiCreateChatGroup($room_id);
                return $room_id;
            }
            if ($line == "livechat_remote" && $type == "new_livechat_remote") {

                $data_views = $this->mongo_db->where(array("type" => "new_livechat_remote", "sender_id" => $sender_id))->getOne('chatNotifi');

                $room_array = array(
                    'user_id_create' => '',
                    'page_id' => $notification_data['page_id'],
                    'group_id' => $notification_data['group_id'],
                    'type' => "new_livechat_remote", //private/group
                    'from' => array(
                        "id" => $notification_data["assign_to_user"],
                        "username" => $notification_data["assign_to_user"],
                        "name" => $notification_data["assign_to_user"],
                        "type" => "user",
                    ),
                    'to' => array(
                        "id" => $sender_id,
                        "username" => $notification_data['sender_info']['name'],
                        "type" => "customer",
                        "user_id" => $sender_id,
                    ),
                    'group_user' => array(),
                    'group_name' => $notification_data['sender_info']['name'],
                    'date_active' => time(),
                    'date_added' => time(),
                    'status' => 1,
                    //them 
                    //'room_id' => $room_id,
                    'trigger' => $notification_data['trigger'],
                    'source' => $source
                );

                $result = $this->mongo_db->insert('chatGroups', $room_array);
                
                $room_id = $result->{'$id'};

                $this->NotifiCreateChatGroup($room_id);
                return $room_id;
            }

            if ($line == "facebook" && $type == "new_facebook_chat") {

                $data_views = $this->mongo_db->where(array("type" => "new_facebook_chat", "sender_id" => $sender_id))->getOne('chatNotifi');

                $room_array = array(
                    'user_id_create' => '',
                    'page_id' => $notification_data['page_id'],
                    'group_id' => $notification_data['group_id'],
                    'type' => "new_facebook_chat", //private/group
                    'from' => array(
                        "id" => $notification_data["assign_to_user"],
                        "username" => $notification_data["assign_to_user"],
                        "name" => $notification_data["assign_to_user"],
                        "type" => "user",
                    ),
                    'to' => array(
                        "id" => $sender_id,
                        "username" => $notification_data['sender_info']['name'],
                        "type" => 'customer',
                        "user_id" => $sender_id,
                    ),
                    'group_user' => array(),
                    'group_name' => $notification_data['sender_info']['name'],
                    'date_active' => time(),
                    'date_added' => time(),
                    'status' => 1,
                    //them 
                    //'room_id' => $room_id,
                    'trigger' => $notification_data['trigger'],
                    'source' => $source
                );

                $result = $this->mongo_db->insert('chatGroups', $room_array);
                
                $room_id = $result->{'$id'};

                $this->NotifiCreateChatGroup($room_id);
                return $room_id;
            }
            if ($type == "new_facebook_comment") {

                $data_views = $this->mongo_db->where(array("type" => "new_facebook_comment", "sender_id" => $sender_id))->getOne('chatNotifi');
            
                $room_array = array(
                    'trigger' => $notification_data['trigger'],
                    'user_id_create' => '',
                    'page_id' => $notification_data['page_id'],
                    'group_id' => $notification_data['group_id'],
                    'type' => "new_facebook_comment", //private/group
                    'from' => array(
                        "id" => $notification_data["assign_to_user"],
                        "username" => $notification_data["assign_to_user"],
                        "name" => $notification_data["assign_to_user"],
                        "type" => "user",
                    ),
                    'to' => array(
                        "user_id" => $sender_id,
                        "name" => $notification_data['sender_info']['name'],
                        "id" => $sender_id,
                        "username" => $notification_data['sender_info']['name'],
                        "type" => 'customer',
                        "comment_id" => $notification_data['details']['comment_id'],
                        // "parent_id" => $notification_data["supervisor"],
                        "post_id" => $notification_data['details']['post_id'],
                        // "verb" => $notification_data["supervisor"],
                        "post_url" => $notification_data['details']['post_url'],
                    ),
                    'details' => array(
                        "comment_id" => $notification_data['details']['comment_id'],
                        "post_id" => $notification_data['details']['post_id'],
                        "post_url" => $notification_data['details']['post_url'],
                    ),
                    'source' => $source,
                    'group_user' => array(),
                    'group_name' => $notification_data['sender_info']['name'],
                    'date_active' => time(),
                    'date_added' => time(),
                    'status' => 1,
                );
                $result = $this->mongo_db->insert('chatGroups', $room_array);
                
                $room_id = $result->{'$id'};

                $this->NotifiCreateChatGroup($room_id);
                return $room_id;
            }
            if ($type == "new_zalo_chat") {

                $data_views = $this->mongo_db->where(array("type" => "new_zalo_chat", "sender_id" => $sender_id))->getOne('chatNotifi');

                $room_array = array(
                    'user_id_create' => '',
                    'page_id' => $notification_data['page_id'],
                    'group_id' => $notification_data['group_id'],
                    'type' => "new_zalo_chat", //private/group
                    'from' => array(
                        "id" => $notification_data["assign_to_user"],
                        "username" => $notification_data["assign_to_user"],
                        "name" => $notification_data["assign_to_user"],
                        "type" => "user",
                    ),
                    'to' => array(
                        "id" => $sender_id,
                        "username" => $notification_data['sender_info']['name'],
                        "type" => 'customer',
                        "user_id" => $sender_id,
                    ),
                    'group_user' => array(),
                    'group_name' => $notification_data['sender_info']['name'],
                    'date_active' => time(),
                    'date_added' => time(),
                    'status' => 1,
                    //them 
                    //'room_id' => $room_id,
                    'trigger' => $notification_data['trigger'],
                    'source' => $source
                );

                $result = $this->mongo_db->insert('chatGroups', $room_array);
                
                $room_id = $result->{'$id'};

                $this->NotifiCreateChatGroup($room_id);
                return $room_id;
            }
        }

        public function createRoomAndNotify($notification_data) {
            $json = array();
            $line = $notification_data['line'];
            $type = $notification_data['type'];
            $sender_id = $notification_data['sender_id'];
            $source = $notification_data['source'];


            if ($line == "livechat" && $type == "new_livechat_chat") {

                $data_views = $this->mongo_db->where(array("type" => "new_livechat_chat", "sender_id" => $sender_id))->getOne('chatNotifi');

                $room_array = array(
                    'page_id' => $notification_data['page_id'],
                    'group_id' => $notification_data['group_id'],
                    'type' => "new_livechat_chat", //private/group
                    'to' => array(
                        "id" => $sender_id,
                        "username" => $notification_data['sender_info']['name'],
                        "type" => 'customer',
                        "user_id" => $sender_id,
                    ),

                    'group_user' => array(),
                    'group_name' => $notification_data['sender_info']['name'],
                    'date_active' => time(),
                    'date_added' => time(),
                    'status' => 1,
                    'trigger' => $notification_data['trigger'],
                    'source' => $source
                );

                $result = $this->mongo_db->insert('chatGroups', $room_array);
                

                $room_id = $result->{'$id'};
                $notification_data['room_id'] = $room_id;
                $this->mongo_db->insert('chatNotifi', $notification_data);
                $this->NotifiCreateChatGroup($room_id);
                $this->UpdateMesChuaPhanphoiBySenderId($room_id, $sender_id, $source, $notification_data['page_id']);
                return $room_id;
                
            }
            if ($line == "livechat_remote" && $type == "new_livechat_remote") {

                $data_views = $this->mongo_db->where(array("type" => "new_livechat_remote", "sender_id" => $sender_id))->getOne('chatNotifi');

                $room_array = array(
                    'user_id_create' => $notification_data["supervisor"],
                    'page_id' => $notification_data['page_id'],
                    'group_id' => $notification_data['group_id'],
                    'type' => "new_livechat_remote", //private/group
                 
                    'to' => array(
                        "id" => $sender_id,
                        "username" => $notification_data['sender_info']['name'],
                        "type" => 'customer',
                        "user_id" => $sender_id,
                    ),
                    'group_user' => array(),
                    'group_name' => $notification_data['sender_info']['name'],
                    'date_active' => time(),
                    'date_added' => time(),
                    'status' => 1,
                    //them 
                    //'room_id' => $room_id,
                    'trigger' => $notification_data['trigger'],
                    'source' => $source
                );

                $result = $this->mongo_db->insert('chatGroups', $room_array);
                

                $room_id = $result->{'$id'};
                $notification_data['room_id'] = $room_id;
                $this->mongo_db->insert('chatNotifi', $notification_data);
                $this->NotifiCreateChatGroup($room_id);
                $this->UpdateMesChuaPhanphoiBySenderId($room_id, $sender_id, $source, $notification_data['page_id']);
                return $room_id;
                
            }

            if ($line == "facebook" && $type == "new_facebook_chat") {

                $data_views = $this->mongo_db->where(array("type" => "new_facebook_chat", "sender_id" => $sender_id))->getOne('chatNotifi');

                $room_array = array(
                    'user_id_create' => $notification_data["supervisor"],
                    'page_id' => $notification_data['page_id'],
                    'group_id' => $notification_data['group_id'],
                    'type' => "new_facebook_chat", //private/group
                    'to' => array(
                        "id" => $sender_id,
                        "username" => $notification_data['sender_info']['name'],
                        "type" => 'customer',
                        "user_id" => $sender_id,
                    ),
                    'group_user' => array(),
                    'group_name' => $notification_data['sender_info']['name'],
                    'date_active' => time(),
                    'date_added' => time(),
                    'status' => 1,
                    //them 
                    //'room_id' => $room_id,
                    'trigger' => $notification_data['trigger'],
                    'source' => $source
                );

                $result = $this->mongo_db->insert('chatGroups', $room_array);
                
                $room_id = $result->{'$id'};
                $notification_data['room_id'] = $room_id;
                $this->mongo_db->insert('chatNotifi', $notification_data);
                $this->NotifiCreateChatGroup($room_id);
                $this->UpdateMesChuaPhanphoiBySenderId($room_id, $sender_id, $source, $notification_data['page_id']);
                return $room_id;


            }
            if ($type == "new_facebook_comment") {

                $data_views = $this->mongo_db->where(array("type" => "new_facebook_comment", "sender_id" => $sender_id))->getOne('chatNotifi');
                
                $room_array = array(
                    'trigger' => $notification_data['trigger'],
                    'user_id_create' => $notification_data["supervisor"],
                    'page_id' => $notification_data['page_id'],
                    'group_id' => $notification_data['group_id'],
                    'type' => "new_facebook_comment", //private/group
                    'to' => array(
                        "user_id" => $sender_id,
                        "name" => $notification_data['sender_info']['name'],
                        "id" => $sender_id,
                        "username" => $notification_data['sender_info']['name'],
                        "type" => 'customer',
                        "comment_id" => $notification_data['details']['comment_id'],
                        "parent_id" => $notification_data["supervisor"],
                        "post_id" => $notification_data['details']['post_id'],
                        "verb" => $notification_data["supervisor"],
                        "post_url" => $notification_data['details']['post_url'],
                    ),
                    'details' => array(
                        "comment_id" => $notification_data['details']['comment_id'],
                        "post_id" => $notification_data['details']['post_id'],
                        "post_url" => $notification_data['details']['post_url'],
                    ),
                    'source' => $source,
                    'group_user' => array(),
                    'group_name' => $notification_data['sender_info']['name'],
                    'date_active' => time(),
                    'date_added' => time(),
                    'status' => 1,
                );
                $result = $this->mongo_db->insert('chatGroups', $room_array);
                
                $room_id = $result->{'$id'};
                $notification_data['room_id'] = $room_id;
                $this->mongo_db->insert('chatNotifi', $notification_data);
                $this->NotifiCreateChatGroup($room_id);
                $this->UpdateMesChuaPhanphoiBySenderId($room_id, $sender_id, $source, $notification_data['page_id']);
                return $room_id;
                
            }
            if ($type == "new_zalo_chat") {

                $data_views = $this->mongo_db->where(array("type" => "new_zalo_chat", "sender_id" => $sender_id))->getOne('chatNotifi');

                $room_array = array(
                    'user_id_create' => $notification_data["supervisor"],
                    'page_id' => $notification_data['page_id'],
                    'group_id' => $notification_data['group_id'],
                    'type' => "new_zalo_chat", //private/group
                    'to' => array(
                        "id" => $sender_id,
                        "username" => $notification_data['sender_info']['name'],
                        "type" => 'customer',
                        "user_id" => $sender_id,
                    ),
                    'group_user' => array(),
                    'group_name' => $notification_data['sender_info']['name'],
                    'date_active' => time(),
                    'date_added' => time(),
                    'status' => 1,
                    //them 
                    //'room_id' => $room_id,
                    'trigger' => $notification_data['trigger'],
                    'source' => $source
                );

                $result = $this->mongo_db->insert('chatGroups', $room_array);
                
                $room_id = $result->{'$id'};
                $notification_data['room_id'] = $room_id;
                $this->mongo_db->insert('chatNotifi', $notification_data);
                $this->NotifiCreateChatGroup($room_id);
                return $room_id;
            }
            
            header('Content-Type: application/json');
            echo json_encode($json);
        }

        public function UpdateMesChuaPhanphoiBySenderId($room_id, $sender_id, $source) {
            $this->mongo_db->where(array("sender_id" => $sender_id, "room_id" => ''))->set(array('room_id' => $room_id, 'source' => $source))->update_all('chatMessages');
        }

        public function NotifiCreateChatGroup($room_id) {

            $chat_room = array(
                "room_id" => $room_id
            );
            $response = $this->sendUrl($this->omni_webhook_noifi_createroom, $chat_room);
            /*$f = fopen("../worldfone4x/application/controllers/apis/webhooktest_noti.txt", "a+");
            fwrite($f, print_r($response, true));
            fclose($f);*/
            // return ;
        }
        /*public function get_livechat_remote(){
            try{
                $pageapps = $this->mongo_db->get('livechat_remote_pageapps');
                
                $data_return = array();
                foreach ($pageapps as $pageapp) {
                    $picture = '';

                    $data_return[] = array(
                        'id'        => $pageapp['_id']->{'$id'},
                        'source'    => $pageapp['source'],
                        'name'      => $pageapp['page_info']['name'],
                        'picture'   => $picture,
                        'status'    => $pageapp['status'],
                    );
                }
                // var_dump($data_return);
                return $data_return;
            }catch (Exception $ex) {
                return false;
            }


            // var_dump($pageapps);
        }*/


        /*public function assign($customers=[]){
            foreach ($customers as $key => $value) {
                $idx=$this->getMinCusAgent($this->agents);
                if($idx==$this->maxCusAssign){
                    break;
                }
                $this->agents[$idx]['curCusAssign']++;
                $this->agents[$idx]['arrCus'][]=$value;
                unset($this->customers[$key]);
            }
            print_r($this->agents[0]);
            print_r($this->agents[1]);
            print_r($this->agents[2]);
            print_r("overflow:");
            print_r($this->customers);
        }*/
        /*public function getMinCusAgent($agents=[]){
            $idx=0;
            $minCusAssign=$this->maxCusAssign;
            foreach ($agents as $key => $agent) {
                if($agent['curCusAssign']<$minCusAssign){
                    $idx=$key;
                    $minCusAssign=$agent['curCusAssign'];
                }
            }
            return ($minCusAssign<$this->maxCusAssign) ? $idx : $minCusAssign;
        }*/

        public function AgentsAssignTotal($group_id, $agents){
            $pipeline = array();
            $agents_count_conversation = array();

            // query lấy agent và tổng conversation của agent đó
            foreach ($agents as $agent) {
                $pipeline = [];
                $pipeline[] = array(
                    '$match' => array(
                        '$and' => array(
                            array('group_id' => (string)$group_id),
                            array('from.id' => (string)$agent),
                            array('status' => 1),
                        )
                    )
                );

                

                $results = $this->mongo_db->aggregate_pipeline("chatGroups", $pipeline);

                $agents_count_conversation[] = array(
                    'id'        => $agent,
                    'number'    => count($results),
                );
                
            }

            // agent nào có conversation thấp nhất sẽ nằm đầu tiên
            usort($agents_count_conversation, function($a, $b) {
                return $a['number'] > $b['number'];
            });
            // lặp agent, xem các agent có online và sẳn sàng hay chưa, sẳn sàng rồi thì assign, chưa thì lặp tới agent khác
            // return $data_return;
            foreach ($agents_count_conversation as $agent) {
                $agent_status = $this->getOneByExtension($agent['id']);
                if (isset($agent_status['statuscode']) && !empty($agent_status['statuscode'])) {
                    return $agent['id'];
                }
            }
            // Nếu ko thỏa điều kiện thì return null và xử lý thông báo
            return null;
        }

        public function getOneByExtension($extension) {
            $time = time();
            $data = $this->mongo_db->where(array("extension" => $extension, "endtime" => 0))
            ->where_gt("lastupdate", $time - 10)
            //->order_by(array('starttime' => -1))
            ->getOne('2_Chat_status');
            return $data;
        }   

    }