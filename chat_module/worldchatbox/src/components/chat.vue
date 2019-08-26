<template>
    <div class="h-chat">
        <div class="h-header">
          <div class="title">
            <!-- <div class="ic-back"><i class="fas fa-long-arrow-alt-left"></i></div> -->
            <!-- <div class="ic-icon">
              <embed src="https://omnisalessanbox.worldfone.vn/portal/assets/images/icon-livechat-white-30x30.svg" alt="">
              </div> -->
              <span class="ic-chat" ><img style="width: 24px;" src="https://omnisales.worldfone.vn/portal/assets/images/livechat/icon-white-64x64.png" alt=""></span>
              <h1 class="list-title">{{page.title_ready_text}}</h1>
              <p class="list-desc">{{page.title_agentname_text}}</p>
            </div>
            <div class="head-action">
              <i class="fas fa-cog" v-on:click="toggle_widget_setting"></i>
              <i class="fas fa-times" v-on:click="minimize"></i>
              <ul class="widget-setting-dropdown">
                <li><i class="fas fa-volume-down widget-setting-icon"></i> Âm thanh <label class="switch"><input type="checkbox" v-model="setting_sound"> <div class="circle round"></div></label></li> 
                <li><i class="fas fa-bell widget-setting-icon"></i> Nhận thông báo <label class="switch"><input type="checkbox" v-model="setting_noti"> <div class="circle round"></div></label></li> 
                  <!-- <li><i class="fas fa-info-circle widget-setting-icon"></i>Thông tin liên hệ</li>  -->
                  <li v-on:click="reload_iframe"><i class="fas fa-power-off widget-setting-icon"></i> Kết thúc hội thoại</li>
                </ul>
            </div>
        </div>
        <div class="body">
          <div class="chatbox">
            <div class="chat-content-w scroll1">
              <div class="chat-content">
                <div  v-for="message in messages" class="chat-message" :class="{self:message.user_id==st_token}">
                  <div class="user-name" v-if="message.user_id!=st_token">
                    {{message.name}} 
                  </div>
                  <div class="chat-message-content-w">
                    <div class="chat-message-content" v-if="message.type=='text'"><span>{{message.text}}</span>
                    </div>
                    <div class="chat-message-content" v-else-if="message.type=='image'"><a :href="message.url" target="_blank"><img style="width: 200px;" :src="message.url" :alt="message.text" :title="message.text"></a></div>
                    <div class="chat-message-content" v-else-if="message.type=='link'"><a :href="message.url" target="_blank">{{message.text ? message.text : message.url}}</a></div>
                  </div>
                  <div v-if="message.user_id==st_token" class="chat-message-avatar"><!-- <img :alt="message.name" :title="message.name" :src="agent.profile_pic"> --></div>
                  <div v-else class="chat-message-avatar"><img :alt="message.name" :title="message.name" :src="message.profile_pic"></div>
                  <div :data-time="message.timestamp" class="chat-message-date">{{message.date}}</div>
                </div>
              </div>
            </div>
            
          </div>
          <div class="chat-controls">
            <div class="chat-input"><textarea v-on:keypress="enter_chat($event)" :placeholder="page.title_ready_enter_text"></textarea></div>
            <!-- <div class="chat-extra-actions"> -->
              <a class="btn-upload" @click="upload_file"><i class="far fa-image"></i></a>
              <div>
                <input style="display: block; visibility: hidden;" ref="fileImage" type="file" accept="image/*" @change="onFileSelected" name="" value="" placeholder="">
              <!-- <button type="button" @click="onUpload">Upload</button> -->
              </div>
              
            <!-- </div> -->
          </div>
        </div><!--body-->

        <div class="footer-note">
          <span>⚡ by </span><a class="product" target="_blank" href="https://omnisales.worldfone.vn/portal/">Omnisales</a>
          <!-- {{messages}} -->
        </div>
    </div>

</template>

<script>
// import io from 'socket.io-client';
import sound from '../assets/media/notif.mp3';
import axios from 'axios';
export default {
    props: ['st_token', 'page', 'show_launcher', 'sidebar'],
    data() {
        return {
            setting_sound: window.livechat.setting_sound,
            setting_noti: window.livechat.setting_noti,
            new_message_number: 0,
            message: '',
            messages: [],
            agent_id: '',
            agent: null,
            user_has_created: false,
            selectedFile: null,

        }
    },
    methods: {
      onFileSelected(event){
        var $self = this;
        this.selectedFile = event.target.files[0];
        const formdata = new FormData();
        formdata.append('file', this.selectedFile,this.selectedFile.name);
        axios.post(/*'/upload/picture'*/'http://115.146.126.84/apis/upload',formdata)
        .then(function (response) {
          console.log(response);
          socket.emit('SEND_IMG' ,{
            page_id: livechat.getId(),
            text: $self.selectedFile.name,
            url: response.data.content,
            sender_id: $self.st_token,
            name: window.user.name,
            survey_data: {
              name:window.user.name,
              phone:window.user.phone,
              email:window.user.email,
              address:window.user.address
            }, 
            properties:window.user.properties
          });
        })
        .catch(function (error) {
          console.log(error.data);
          console.log(error);
        })
      },
      enter_chat(e){
        var $self = this;
        var $this = $(e.target);
        var text = $this.val();
        if ( e.which == 13 && e.shiftKey) {
          $(this).val(function(i,val){
            return text + "\n";
          })
        }else if ((e.which == 13)) {
          //gởi tin nhắn
            socket.emit('SEND_MESSAGE' ,{
              page_id: livechat.getId(),
              text: $this.val(),
              sender_id: $self.st_token,
              name: window.user.name,
              survey_data: {
                name:window.user.name,
                phone:window.user.phone,
                email:window.user.email,
                address:window.user.address
              }, 
              properties:window.user.properties
            });      
        // this.message = '';
          $this.val('');
          $self.scrollToEnd();
          e.preventDefault();
          return false;
        }
      },

      upload_file: function ($event) {
        var $self = this;
        var elem = this.$refs.fileImage
            elem.click()

        
        /*$('#form-upload').remove();
        $('body').prepend('<form enctype="multipart/form-data" id="form-upload" style="display: none;"><input type="file" name="file" value="" accept="image/*" /></form>');

        $('#form-upload input[name=\'file\']').trigger('click');
        if (typeof timer != 'undefined') {
          clearInterval(timer);
        }*/

        /*var timer = setInterval(function () {
          if ($('#form-upload input[name=\'file\']').val() != '') {
            clearInterval(timer);*/
            const formdata = new FormData();
            formdata.append('file', this.selectedFile);

            // var file = new FormData($('#form-upload')[0]);
            // console.log(file);
            /*socket.emit('SEND_IMG' ,{
              page_id: livechat.getId(),
              text: '',
              file: new FormData($('#form-upload')[0]),
              sender_id: $self.st_token,
              name: window.user.name,
              survey_data: {
                name:window.user.name,
                phone:window.user.phone,
                email:window.user.email,
                address:window.user.address
              }, 
              properties:window.user.properties
            });*/
            // axios.post('/upload/picture',formdata);
            /*$.ajax({
              url: '/upload/picture',
              type: 'post',
              dataType: 'json',
              // data: new FormData($('#form-upload')[0]),
              cache: false,
              contentType: false,
              processData: false,
              success: function (json) {
                
              },
              error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
              }
            });*/
          // }
        //}, 500);
      },

      minimize: function(){
        $('.h-btn').removeClass('zoomOut');
        $('.hotline-launcher').hide();
        this.new_message_number = 0;
        this.$emit('new_message_number', 0);
        top.postMessage({st_popup_status: 0}, document.referrer);
        this.show_launcher = 0;
      },
      reload_iframe: function(){
        localStorage.clear();
        top.postMessage({reload_iframe: true}, document.referrer);
        socket.emit('load1',null);
      },
      playSound (sound) {
        if(sound) {
          var audio = new Audio(sound);
          audio.volume = 0.4;
          audio.play();
        }
      },
      toggle_widget_setting: function(){
        $('.widget-setting-dropdown').toggleClass('visible');
      },      
      scrollToEnd: function () {
        var $self = this;
        setTimeout(function(){
          $self.insertDateLine();
        }, 150);
        setTimeout(function(){
          var $chat_content = $(document).find('.chat-content');
          var chatbox = $(document).find('.chatbox');
          chatbox.scrollTop($chat_content.height()+99999);
        }, 200);
      },
      insertDateLine: function(){
        var date_array = [];
        $(document).find('.chatbox .chat-date-separator').remove();
        $(document).find('.chatbox .chat-message').each(function (index, value) { 
          var timestamp = $(this).find('.chat-message-date').attr('data-time');
          var d = new Date(timestamp*1000);
          var curr_date = d.getDate();
          var curr_month = d.getMonth()+1;
          var curr_year = d.getFullYear();
          var date = curr_date+'/'+curr_month+'/'+curr_year;
          if ($.inArray( date, date_array )>=0) {

          } else {
            date_array.push(date);
            date = MDFormat(curr_year+'-'+curr_month+'-'+curr_date);
            $('<div class="chat-date-separator"><span>'+date+'</span></div>').insertBefore($(this));
          }

        });
      },
      showNotification: function(title, text, icon = '') {
        //if (localStorage.getItem("noti_browser") > 0) {
        if (window.Notification) {
            Notification.requestPermission(function (status) {
                text = text.replace('<br>', '');
                text = text.substring(0, 20);
                var n = new Notification(title, {body: text, icon: icon});
                setTimeout(n.close.bind(n), 5000);
            });
        } else {
            alert('Your browser doesn\'t support notifications.');
        }
    }
    },
    mounted() {
      var $self = this;
      socket.on('receiveMes', (data) => {
        // console.log(data);        
        $self.messages.push(data);
        this.new_message_number++;          
        this.$emit('new_message_number', this.new_message_number);
        if (data.sender_id!= localStorage.getItem('st_token') && this.setting_sound==true) {          
          this.playSound(sound);
        }
        if (data.sender_id!= localStorage.getItem('st_token') && this.setting_noti==true) {
          this.showNotification(data.name, data.text, data.profile_pic);
        }
        $self.scrollToEnd();
        
      });
      socket.emit('get_messages', {st_token:localStorage.getItem('st_token')});
      socket.on('get_messages', (data) => {
        // console.log(data);
        $self.messages = data;        
        setTimeout(function(){
          $self.insertDateLine();
        }, 150);
        $self.scrollToEnd();
      });

      socket.on('get_agent', (data) => {
        $self.agent = data;
      });

     /* setTimeout(function(){
        $self.scrollToEnd();
        alert('scroll1');
      }, 2500);*/
    },
    watch: {
      show_launcher: function(val){
        var $self = this;
        if (val==1) {
          setTimeout(function(){
            $self.scrollToEnd();
            $self.insertDateLine();
          }, 150);
        }        
      },
      setting_sound: function(val){
        window.livechat.setSettingSound(val);
      },
      setting_noti: function(val){
        window.livechat.setSettingNoti(val);
      },
      agent_id: function(val){
        var $self = this;
        if (val!='') {
          // socket.emit('get_agent', {st_token:$self.st_token, agent_id: val});
        }
      },
      messages: function(val){
        var $self = this;
        /*if (val.length != 0 && $self.agent_id=='') {
          for (var i = val.length - 1; i >= 0; i--) {
            console.log(val[i].user_id);
            if (val[i].user_id != $self.st_token) {
              $self.agent_id = val[i].user_id;
              break;
            }

          }
        }*/
      },//
    },
}
function MDFormat(MMDD) {
    MMDD = new Date(MMDD+' 00:00:00');
    var months = ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6", "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"];
    var strDate = "";

    var today = new Date();
    today.setHours(0, 0, 0, 0);

    var yesterday = new Date();
    yesterday.setHours(0, 0, 0, 0);
    yesterday.setDate(yesterday.getDate() - 1);

    var tomorrow = new Date();
    tomorrow.setHours(0, 0, 0, 0);
    tomorrow.setDate(tomorrow.getDate() + 1);

    if (today.getTime() == MMDD.getTime()) {
        strDate = "Hôm nay";
    } else if (yesterday.getTime() == MMDD.getTime()) {
        strDate = "Hôm qua";
    } else if (tomorrow.getTime() == MMDD.getTime()) {
        strDate = "Ngày mai";
    } else {
        strDate = MMDD.getDate() + " " + months[MMDD.getMonth()]+" " + MMDD.getFullYear();
    }
    return strDate;
}
</script>
