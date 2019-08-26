<template>
  <div class="hotline-launcher">
    <div class="h-header">
      <div class="title">
        <div class="ic-back"><i class="fas fa-long-arrow-alt-left"></i></div>
        <h1 class="list-title">Support</h1>
        <p class="list-desc">Currently replying in under 4 minutes</p>
      </div>
      <div class="head-action">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <div class="h-chat">
      <div class="full-chat-middle">
        <div class="chatbox">
          <div class="box-group" style="display: none;"><span class="label"></span></div>
          <div class="chat-content-w scroll1">
            <div class="chat-content">
              <div class="chat-date-separator"><span>8 Tháng 8 2018</span></div>
              <div class="chat-message">
                <div class="user-name">
                  Tiến Duy
                </div>
                <div class="chat-message-content-w">
                  <div class="chat-message-content"><span>5:50</span>
                  </div>
                </div>
                <div class="chat-message-avatar"><img alt="Tiến Duy" title="Tiến Duy" src="https://platform-lookaside.fbsbx.com/platform/profilepic/?psid=1775011219190394&amp;width=1024&amp;ext=1536317425&amp;hash=AeQBa4Q6qSy-0R-p"></div>
                <div data-time="1533725424" class="chat-message-date">17:50</div>
              </div>

              <div class="chat-message self">
                <div class="chat-message-content-w">
                  <div class="chat-message-content"><span>6:51</span>
                  </div>
                </div>
                <div class="chat-message-avatar"><img alt="Trần Admin Tiến" title="Trần Admin Tiến" src="https://omnisalessanbox.worldfone.vn/portal/upload/users/avatar/file1533200802.png"></div>
                <div data-time="1533726962" class="chat-message-date">18:16</div>
              </div>
            </div>
          </div>
          <div class="chat-controls">
            <div class="chat-input"><textarea placeholder="Nhập tin nhắn..."></textarea></div>
              <div class="chat-extra-actions"><a class="btn-upload"><i class="far fa-image"></i></a></div>
          </div>
        </div>
      </div>
    </div><!--h-chat-->
    <div class="h-footer">
      <div id="brand" class="brand" style="display: block;">
      Powered by <a href="https://southtelecom.vn" target="_blank"><strong>South Telecom</strong></a></div>
    </div>
      <!-- <div class="card-body">
          <div class="card-title">
              <h3>Chat Group</h3>
              <hr>
          </div>
          <div class="card-body">
              <div class="messages" v-for="(msg, index) in messages" :key="index">
                  <p><span class="font-weight-bold">{{ msg.user }}: </span>{{ msg.message }}</p>
              </div>
          </div>
      </div>
      <div class="card-footer">
          <form @submit.prevent="sendMessage">
              <div class="gorm-group">
                  <label for="user">User:</label>
                  <input type="text" v-model="user" class="form-control">
              </div>
              <div class="gorm-group pb-3">
                  <label for="message">Message:</label>
                  <input type="text" v-model="message" class="form-control">
              </div>
              <button type="submit" class="btn btn-success">Send</button>
          </form>
      </div> -->
  </div>
</template>

<script>
import io from 'socket.io-client';

export default {
    data() {
        return {
            user: '',
            message: '',
            messages: [],
            socket : io('localhost:3001')
        }
    },
    methods: {
        sendMessage(e) {
            e.preventDefault();
            
            this.socket.emit('SEND_MESSAGE', {
                user: this.user,
                message: this.message
            });
            this.message = ''
        }
    },
    mounted() {
        this.socket.on('MESSAGE', (data) => {
            this.messages = [...this.messages, data];
            // you can also do this.messages.push(data)
        });
    }
}
</script>
