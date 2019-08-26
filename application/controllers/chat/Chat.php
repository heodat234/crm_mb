<?php 
class Chat extends WFF_Controller {
    private $extension = null;
    private $service_type;
    private $work_flow;

    public function __construct()
    {
        parent::__construct();
        $only_main_content = (bool) $this->input->get("omc");
        $this->_build_template($only_main_content);
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        // $this->load->config('worldui');
        $this->extension = $this->session->userdata('extension');
        $this->service_type = $this->session->userdata("service_type");
        $this->agentname = $this->session->userdata('agentname');
        $this->work_flow = $this->session->userdata("work_flow");
        $this->load->library('mongo_db');
    }
    public function index() {
        /*
            <script src="<?=base_url()?>assets/js/vue.js"></script>
            <script src="<?=base_url()?>assets/js/httpVueLoader.js"></script>
            <script src="<?=base_url()?>assets/js/vue-router.js"></script>
            <script src="<?=base_url()?>assets/js/vue-i18n.js"></script>
            <link href="<?=base_url()?>assets/js/select2/select2.min.css" rel="stylesheet" />
            <script src="<?=base_url()?>assets/js/select2/select2.min.js"></script>
            <script src="<?=base_url()?>assets/js/socket/socket.io.js"></script>
        */

        // var_dump(rtrim(base_url(), "/") . ":8001");
        $this->output->data["js_nodefer"][] = CHAT_PATH . "assets/js/vue.min.js";
        $this->output->data["js_nodefer"][] = CHAT_PATH . "assets/js/httpVueLoader.js";
        $this->output->data["js_nodefer"][] = CHAT_PATH . "assets/js/vue-router.js";
        $this->output->data["js_nodefer"][] = CHAT_PATH . "assets/js/vue-i18n.js";
        $this->output->data["js_nodefer"][] = CHAT_PATH . "assets/js/select2/select2.min.js";
        $this->output->data["css"][] = CHAT_PATH . "assets/js/select2/select2.min.css";
        
        $this->output->data["css"][] = STEL_PATH . "css/table.css";
        $this->output->data["js"][] = STEL_PATH . "js/manage/ticket_add_new.js";
        $this->output->data["js"][] = STEL_PATH . "js/tools.js";
        
        
        // var_dump($data['filter_type']); echo 'kakak';
        $this->load->view('chat/chat_view');

    }
}