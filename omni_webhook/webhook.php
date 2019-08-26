<?php
ini_set('display_errors', 1);
ini_set("error_log", $_SERVER['DOCUMENT_ROOT']."/omni_webhook/PHP_errors_omni_webhook_".date("d_m_Y").".log");

include_once('config.php');
require_once $_SERVER['DOCUMENT_ROOT'].'/omni_webhook/libraries/MyMongoDriver.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/omni_webhook/libraries/CurlWrapper.php';
    class webhook {
        function __construct() {

            $this->mongo_db = new MyMongoDriver(Config::MONGO_DB_NAME, Config::MONGO_DB_HOST, Config::MONGO_DB_PORT);
            $this->omni_webhook_socket_url = Config::OMNI_WEBHOOK_SOCKET_URL() . '/api/v2/chat';
            $this->omni_webhook_noifi_createroom = Config::OMNI_WEBHOOK_SOCKET_URL() . '/api/v2/loadnewroom';
            // $this->customer_secret_key = Config::CUSTOMER_SECRET_KEY;
            $this->customer_type = Config::CUSTOMER_TYPE;          
            $this->chat_mode = 'auto_assign_to_agent';

            //'auto_assign_to_supervisor';//'auto_assign_to_agent', //notification_get_conversation
        }
        public function index() {
            // var_dump(Config::OMNI_WEBHOOK_SOCKET_URL() . '/api/v2/chat');
            $data = json_decode(file_get_contents('php://input'));
            /*$f = fopen($_SERVER['DOCUMENT_ROOT']."/omni_webhook/webhook_in.txt", "a+");
            fwrite($f, print_r($data, true));
            fclose($f);*/

            require_once 'assignmentrules/' . $this->chat_mode . '.php';
            $result = new $this->chat_mode;            

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $json  = json_encode($data);
                $data = json_decode($json, true);
                if ($data['trigger'] == 'comment') {
                    $result->index($data);
                } elseif ($data['trigger'] == 'message') {
                    if (!empty($data['messages']['is_echo'])) {
                        $result->index($data);
                    } else {
                        $result->index($data);
                    }
                }elseif ($data['trigger'] == 'msg_delivered') {
                    $data_return = $this->msg_delivered($data);
                    echo json_encode(array('status' => 0, 'data'    => $data_return,  'errorMessage' => 'Success'));
                }elseif ($data['trigger'] == 'msg_error') {
                    $data_return = $this->msg_error($data);
                    echo json_encode(array('status' => 0, 'data'    => $data_return,  'errorMessage' => 'Success'));
                }elseif ($data['trigger'] == 'get_livechat_remote') {
                    $data_return = $this->get_livechat_remote();
                    echo json_encode(array('status' => 0, 'data'    => $data_return,  'errorMessage' => 'Success'));
                }
            }       

        }

        // public function chat() {
        //     //$chat_mode = $this->chat_mode;
        //     $this->load->library('Chat_mode');
            
        //     $result = $this->chat_mode->init("Notification_get_conversation");


            

            
        //     $data = json_decode(file_get_contents('php://input'));
        //     // var_dump($data);

        //     // $data = $_REQUEST;
        //     // var_dump($data);
        //     //
        //     /*var_dump($data);
        //     var_dump($_POST);*/
        //     $f = fopen("/var/www/html/worldfone4xs/application/controllers/apis/webhook_in.txt", "a+");
        //     fwrite($f, print_r($data, true));
        //     fclose($f);

            
        //     // var_dump($data);

        //     if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                
        //     } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //         $json  = json_encode($data);
        //         $data = json_decode($json, true);
        //         if ($data['trigger'] == 'comment') {
        //             $result->index($data);
        //         } elseif ($data['trigger'] == 'message') {
        //             if (!empty($data['messages']['is_echo'])) {
        //                 $result->index($data);
        //             } else {
        //                 $result->index($data);
        //             }
        //         }elseif ($data['trigger'] == 'get_livechat_remote') {
        //             $data_return = $this->get_livechat_remote();
        //             echo json_encode(array('status' => 0, 'data'    => $data_return,  'errorMessage' => 'Success'));
        //         }
        //     }
            
        // }

        private function msg_delivered($data) {//Tin Nhắn được gởi từ page trên facebook
        // var_dump($data);
            try {

            // $recipient_id = $data['messages']['recipient_id'];
            // $sender_id = $data['messages']['sender_id'];
            // $page_id = $data['page_id'];

                if (isset($data['metadata']['id'])) {
                    $message_id = $data['metadata']['id'];
                    $message_info = $this->mongo_db->where(array('_id' => new mongoId($message_id)))->getOne('chatMessages');                
                    // var_dump($message_info);
                    if (!empty($message_info)) {
                        $this->mongo_db->where(array("_id" => new MongoId($message_id)))->set("msg_delivered", 1)->update('chatMessages');
                        $this->CurlWrapper = new CurlWrapper();
                        $data_send['channel'] = 'global_channel';
                        $data_send['room_id'] = $message_info['room_id'];
                        $data_send['message_id'] = $message_id;
                        $response = $this->CurlWrapper->post(Config::OMNI_WEBHOOK_SOCKET_URL().'/localapiin/v1/msg_delivered', $data_send);     
                        // var_dump($response);
                        // var_dump(Config::OMNI_WEBHOOK_SOCKET_URL().'/localapiin/v1/msg_delivered');
                    }
                }    

            } catch (Exception $ex) {
            }
        }

        private function msg_error($data) {//Tin Nhắn được gởi từ page trên facebook
            try {
                if (isset($data['metadata']['id'])) {
                    $message_id = $data['metadata']['id'];
                    $message_info = $this->mongo_db->where(array('_id' => new mongoId($message_id)))->getOne('chatMessages');                
                    if (!empty($message_info)) {
                        $this->mongo_db->where(array("_id" => new MongoId($message_id)))->set("msg_error", $data['msg_error'])->update('chatMessages');
                        $this->CurlWrapper = new CurlWrapper();
                        $data_send['channel']    = 'global_channel';
                        $data_send['room_id']    = $message_info['room_id'];
                        $data_send['message_id'] = $message_id;
                        $data_send['msg_error']  = $data['msg_error'];
                        $response = $this->CurlWrapper->post(Config::OMNI_WEBHOOK_SOCKET_URL().'/localapiin/v1/msg_error', $data_send);    
                        // var_dump($response); 
                    }
                }    
                
            } catch (Exception $ex) {
            }
        }

        
        public function get_livechat_remote(){
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
        }

    }

    $Webhook = new Webhook();
    $Webhook->index();
