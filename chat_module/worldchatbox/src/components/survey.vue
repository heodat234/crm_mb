<template>
  <div class="h-survey">
    <div class="h-header">
      <div class="title">
        <!-- <div class="ic-back"><i class="fas fa-long-arrow-alt-left"></i></div> -->
        <span class="ic-chat" ><img style="width: 24px;" src="https://omnisales.worldfone.vn/portal/assets/images/livechat/icon-white-64x64.png" alt=""></span>
        <h1 class="list-title">{{page.title_survey_heading}}</h1>
        <p class="list-desc">{{page.title_instruction_text}}</p>
      </div>
      <div class="head-action">
        <i class="fas fa-times" v-on:click="minimize"></i>
      </div>
    </div>
    <div class="body">
        <form class="form-suvey" id="form-suvey" @submit.prevent="submit_survey" ref="submit_survey">
          <div v-for="(item, key) in page.data_field"  class="form-control">
            <p>{{item.field_name}} <span v-if="item.require=='1'">(*)</span></p>
            <input v-model="field[item.id]" type="text" :ref="'v_'+item.id">
          </div>
          <button type="submit" class="btn_start_chat" :style="{'background-color':page.color}">{{page.title_begin_chat}}</button>
        </form><!--/.form-suvey-->
    </div>
    <div class="footer-note">
      <span>⚡ by </span><a class="product" target="_blank" href="https://omnisales.worldfone.vn/portal/">Omnisales</a>
    </div>
  </div>
</template>

<script>
// import io from 'socket.io-client';

export default {
  props: ['page'],
  data() {
    return {
      user: '',
      field: [],            
    }
  },
  methods: {
    submit_survey: function(e) {
      var $self = this;
      var main_field = ["name", "email", "phone", "address"];
      var survey_data = [];
      var properties = [];
      //console.log($self.$refs.submit_survey);
      $($self.$refs.submit_survey).find('.require').removeClass('require');
      var check_ok = true;
      for ( var i in $self.page.data_field) {
        var item = $self.page.data_field[i];          
          // survey_data[item.id] = $self.field[item.id];
        survey_data.push({id: item.id, value: $self.field[item.id] });
        // console.log(survey_data);
        if ((typeof $self.field[item.id] ==='undefined' || $self.field[item.id]=='') && item.require==1) {
          $($self.$refs['v_'+item.id]).parent().addClass('require');
          check_ok = false;
        }else{
          if (main_field.indexOf(item.id)=='-1') {
            properties.push({ name :`${item.field_name}`, value: $self.field[item.id] });
            //properties.push({ /*$$item.field_name*/ [`${item.field_name}`]: $self.field[item.id] });
          }
        }

        if (item.id=='email' && !livechat.validateEmail($self.field[item.id])) {
          $($self.$refs['v_'+item.id]).parent().addClass('require');
          check_ok = false;
        }

      }
      console.log(survey_data);
      console.log(properties);
      // return;
      if (check_ok) {
        window.user.setProperties(properties);
        for (var i in survey_data) {
          if (survey_data[i].id=='name') {
            window.user.setName(survey_data[i].value);
          }else if (survey_data[i].id=='phone') {
            window.user.setPhone(survey_data[i].value);
          }else if (survey_data[i].id=='email') {
            window.user.setEmail(survey_data[i].value);
          }else if (survey_data[i].id=='address') {
            window.user.setAddress(survey_data[i].value);
          }
        }
        
        if (localStorage.getItem('st_token')) {
          socket.emit('submit_survey', {survey_data: {name:window.user.name, phone:window.user.phone, email:window.user.email, address:window.user.address}, properties:window.user.properties, livechat_id: livechat.getId(), st_token: localStorage.getItem('st_token')});
        }
        
      }

    },
    minimize: function(){
      $('.h-btn').removeClass('zoomOut');
      $('.hotline-launcher').hide();
      top.postMessage({st_popup_status: 0}, document.referrer);
    },
      /*sendMessage(e) {
        e.preventDefault();

        this.socket.emit('SEND_MESSAGE', {
          user: this.user,
          message: this.message
        });
        this.message = ''
      }*/
    },
    created() {
      
    },
    mounted() {

      // tin nhắn gởi từ admin về phải được nhận
      // hiển thị được tin nhắn lên trên giao diện
      // kiểm tra chặn people khi có khả nghi
      // chat lại cho 
      // 
/*      room.setRoomId('húhú');
      console.log(room.getRoomId('húhú'));
      console.log(user.setName('duytien'));
      console.log(user.name);*/
        /*this.socket.on('MESSAGE', (data) => {
            this.messages = [...this.messages, data];
            // you can also do this.messages.push(data)
        });*/
    },
    computed: {
      count () {
        return '1';
      }
    }
}
</script>
