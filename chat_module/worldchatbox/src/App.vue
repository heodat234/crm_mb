<template>
  <div id="app">
    <div class="hotline-launcher h-open" style="display: none;">
      <app-content v-if="first_component=='chat'" v-bind:show_launcher="show_launcher" v-bind:page="page" v-bind:st_token="st_token" @new_message_number="new_message_number = $event"></app-content>
      <app-survey v-if="first_component=='survey'" v-bind:page="page" v-bind:st_token="st_token"></app-survey>
    </div>
    <div class="d-hotline h-btn animated zoomIn" v-on:click="showLauncher()">
      <div v-if="new_message_number>0" class="new-message-number">{{new_message_number}}</div>
        <i class="icon-ic_chat_icon">
          <img src="https://omnisales.worldfone.vn/portal/assets/images/logo_omni_live.png" alt="">
        </i>
      <div class=""></div>
    </div>
  </div>
</template>

<script>

import Chat from './components/chat.vue'
import Survey from './components/survey.vue'
import Channel from './components/channel.vue'
import io from 'socket.io-client'
export default {
  name: 'app',
  data() {
  	return {
      show_launcher: 0,
      new_message_number: 0,
  		first_component: '',
  		survey: [],
  		page: [],
      st_token: '',
      has_survey: false,
      has_channel: false,


  	}
  },
  components: {
  	'app-content'	: Chat,
  	'app-survey'	: Survey,
  },
  created() {
    if (typeof livechat.getId() === 'undefined') {
      console.log('error_ID');
      this.$destroy();
      return;
    }

    top.postMessage({ready: 1}, document.referrer);
  },
  mounted() {
    socket.on('reconnect', function () {
      socket.emit('reconnect_user', localStorage.getItem('st_token'));
    });
  	var $self = this;
    var st_token = localStorage.getItem('st_token');
    socket.emit('load1',st_token);
    socket.on('disconnect', (reason) => {
      if (reason === 'io server disconnect') {
        // the disconnection was initiated by the server, you need to reconnect manually
        socket.connect();
      }
      // else the socket will automatically try to reconnect
    });

    socket.on('set_token', (st_token) => {
      localStorage.setItem('st_token', st_token);
      $self.st_token = st_token;
    });
    $self.st_token = st_token;
    setInterval(function(){
      localStorage.setItem('st_token', $self.st_token);
    }, 
    50);
    
    socket.on('survey_success', (data) => {
      $self.first_component = 'chat';
    });

  	socket.on('getlivechat', (data) => {
      // console.log(data);
  		$self.page = data;
  		if (data.onoff_surver=='1') {
  			$self.has_survey = true;//'';
        //$self.first_component = 'survey';
  		}else{
  			$self.has_survey = false;        
        //$self.first_component = 'chat';
  		}
  	});

    socket.on('get_people', (data) => {
      if (!$self.has_channel && data!=null) {
        $self.first_component = 'chat';
        window.user.setName(data.name);
        window.user.setPhone(data.phone);
        window.user.setEmail(data.email);
        window.user.setAddress(data.address);
      }else if($self.has_channel){
        $self.first_component = 'channel';
      }else{
        if (window.livechat.hasUserdata) {
          $self.first_component = 'chat';
        }else{
          $self.first_component = 'survey';
          // window.livechat.sethasUserdata(false);
        }
        
      }
      
    });

    function receiveMessage1(event){
      if (typeof(event.data['st_popup_status']) !== 'undefined') {
        $self.showLauncher();
      }
    }
  window.addEventListener("message", receiveMessage1, false);

  },
  methods:{
    showLauncher: function(e){
      $('.h-btn').toggleClass('zoomOut');
      $('.hotline-launcher').toggle();
      this.new_message_number = 0;
      if ($('.hotline-launcher').is(":visible")) {
        top.postMessage({st_popup_status: 1}, document.referrer);
        this.show_launcher = 1;
      }else{
        top.postMessage({st_popup_status: 0}, document.referrer);
        this.show_launcher = 0;
      }
      
     
    },
  },
  watch: {
    /*first_component: function(val){
      if (val=='chat') {
        socket.emit('get_messages', {st_token:this.st_token});
      }      
    },*/
    st_token: function(val){
      var $self = this;
      // alert(val);
      
      if (val!='') {
        /*console.log({
          livechat_id:livechat.getId(),
          st_token: $self.st_token,
        });*/
        // socket.emit('get_people', {st_token:this.st_token});
        socket.emit('getlivechat', {
          livechat_id:livechat.getId(),
          st_token: $self.st_token,
        });
      }
    }
  },

}
</script>

