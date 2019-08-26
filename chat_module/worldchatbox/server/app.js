
const express = require('express');
const path    = require("path");
const app     = express();
const axios   = require('axios');
const uuid    = require('uuidv4');
const moment  = require('moment-timezone');
const multer  = require('multer');
require('dotenv').config();
var clients =[];

const url_webhook = 'https://webhook.worldfone.vn/omni/livechat_remote';
const url_omnisales_api = 'http://115.146.126.84/apis';
const url_uploadfile_livechat_remote = 'http://192.168.16.105/upload/livechat';

var bodyParser = require('body-parser')
app.use( bodyParser.json() );       // to support JSON-encoded bodies
app.use(bodyParser.urlencoded({     // to support URL-encoded bodies
  extended: true
}));

const server = app.listen(8006, function() {
    console.log('server running on port 8006');
});

// const io = require('socket.io')(server);
const io = require('socket.io')(server, { wsEngine: 'ws' });

const fileFilter = function (req, file, cb) {
  const allowedTypes = ["image/jpeg", "image/jpg", "image/png"];

  if (!allowedTypes.includes(file.mimetype)) {
    const error = new Error('Wrong file type');
    error.code = "LIMIT_FILE_TYPE";
    return cb(error, false);
  }

  // To accept the file pass `true`, like so:
  cb(null, true)
}


var storage = multer.diskStorage({
  destination: function (req, file, cb) {
    cb(null, '/var/www/worldfone4x_kim_tientran/worldfone4x/upload/livechat')
  },
  filename: function (req, file, cb) {
    var filename = path.basename(file.originalname, path.extname(file.originalname));
    cb(null, filename + '-' + Date.now()+path.extname(file.originalname))
  }
})

const MAX_SIZE = 10 * 1000 * 1000;
const upload = multer({ 
  storage: storage,
  fileFilter,
  limits:{
    fileSize: MAX_SIZE,
  }
})


app.post('/sendmessage/text', function (req, res) {
   data = req.body;
   console.log(req.body);
   if (data.sender.avatar) {
    var avatar = data.sender.avatar;
   }else{
    var avatar = 'http://kim.worldfone.vn/assets/images/avatar_default.svg';
   }
   
    /*sendSockettoStToken('receiveMes', data.recipient.id, {
      user_id: '',
      text: data.messages.text,
      name: data.sender.name,
      type: 'text',
      profile_pic: avatar,
      date: coverStampToTime(data.timestamp),
      timestamp: data.timestamp,
    });*/
    io.in('channel_user_'+data.recipient.id).emit('receiveMes', {
      user_id: '',
      text: data.messages.text,
      name: data.sender.name,
      type: 'text',
      profile_pic: avatar,
      date: coverStampToTime(data.timestamp),
      timestamp: data.timestamp,
    });

    //channel_user_'+st_token
   
   res.status(200).send({status:200, data: 'success'});
});


app.post('/upload/picture', upload.single("file"), function (req, res) {
  // console.log(req.file);
  var data_return = {
    name: "", 
    fileName: req.file.originalname,
    content: process.env.url_uploadfile_livechat_remote+'/'+req.file.filename,
    contentType: req.file.mimetype,
    // height: 960,
    // position: 0,
    // width: 720,
  }
  res.status(200).send({status:200, data: data_return});
  
});

app.post('/sendmessage/image', function (req, res) {
   data = req.body;
   // console.log(req.body);
   if (data.sender.avatar) {
    var avatar = data.sender.avatar;
   }else{
    var avatar = 'http://kim.worldfone.vn/assets/images/avatar_default.svg';
   }
   /*sendSockettoStToken('receiveMes', data.recipient.id, {
    user_id: '',
    text: data.messages.text,
    name: data.sender.name,
    type: 'image',
    url: data.messages.url,
    profile_pic: avatar,
    date: coverStampToTime(data.timestamp),
    timestamp: data.timestamp,
   });*/

  io.in('channel_user_'+data.recipient.id).emit('receiveMes', {
    user_id: '',
    text: data.messages.text,
    name: data.sender.name,
    type: 'image',
    url: data.messages.url,
    profile_pic: avatar,
    date: coverStampToTime(data.timestamp),
    timestamp: data.timestamp,
  });

   
   res.status(200).send({status:200, data: 'success'});
});


app.use(function(err, req, res, next){
 if (err.code==="LIMIT_FILE_TYPE") {
  res.status(400).send({error:"Only images are allowed"});
  return;
}

if (err.code==="LIMIT_FILE_SIZE") {
  res.status(400).send({error:`Too large. Max size is ${MAX_SIZE/1000000}Mb`});
  return;
}
});



io.on('connection', function(socket) {
    socket.on('load1', function(st_token) {
      // console.log('token day'+st_token);
      if (st_token=='null' || st_token==null) {
        var uuid1 = uuid();
        var clientInfo = new Object();
        clientInfo.st_token     = uuid1;
        clientInfo.clientId     = socket.id;
        clients.push(clientInfo);
        io.to(socket.id).emit('set_token', uuid1);
        // console.log(clients);
        
        socket.join('channel_user_'+uuid1);
        // console.log('channel_user_'+st_token);
        // console.log(io.sockets.adapter.rooms);

      }else{
        var clientInfo = new Object();
        clientInfo.st_token     = st_token;
        clientInfo.clientId     = socket.id;
        clients.push(clientInfo);
        console.log(clients);

        socket.join('channel_user_'+st_token);
        // console.log('channel_user_'+st_token);
        // console.log(io.sockets.adapter.rooms);
      }   
      // console.log()  
  });
    socket.on('reconnect_user', function(st_token) {
      // console.log('reconnect_user'+st_token);
      socket.join('channel_user_'+st_token);
    });

    socket.on('disconnect', function() {
      for( var i=0, len=clients.length; i<len; ++i ){
        var c = clients[i];
        if(c.clientId == socket.id){
          clients.splice(i,1);
          break;
        }
      }
      console.log(clients);
  });

    socket.on('SEND_MESSAGE', async function(data) {
      console.log('SEND_MESSAGE');
      // console.log(data);
      var timestamp = moment()./*tz("Asia/Ho_Chi_Minh").*/format("X");
      var webhook_data = {
        trigger: 'message',
        page_id: data.page_id,
        timestamp: timestamp,
        surveys : data.survey_data,
        properties : data.properties,
        messages:{
          text: data.text,
          sender_id: data.sender_id,
          sender_info:{
            name: data.name,
            user_id: data.sender_id,
            profile_pic:'',
            locale: '',
            timezone: '',
            // gender: profile.userGender == 1 ? 'male' : 'female',
          },
          type: 'text',
          url: '',
          source: {
            "type": "livechat_remote",
          }
        },
      };
      console.log(webhook_data);
      var response = await axios({
      method: 'post',
      // url: process.env.url_webhook,
      url: process.env.url_omnisales_api+"/livechat_webhook_hander_clients",
      data: webhook_data,
      headers: {key: "key_access"},
    })
    .then(response => {
      console.log(response.data);
      io.in('channel_user_'+data.sender_id).emit('receiveMes',{
        user_id: data.sender_id,
        sender_id: data.sender_id,
        text: data.text,
        type: 'text',
        date: coverStampToTime(timestamp),
        timestamp: timestamp,
      });
      /*sendSockettoStToken('receiveMes', data.sender_id, {
        user_id: data.sender_id,
        sender_id: data.sender_id,
        text: data.text,
        type: 'text',
        date: coverStampToTime(timestamp),
        timestamp: timestamp,
      });*/
    })
    .catch(error => {
      // console.log(error.response);
    });
        // io.emit('MESSAGE', data)
    });

    socket.on('SEND_IMG', async function(data) {
      console.log('SEND_IMG');
      console.log(data);
      var timestamp = moment()./*tz("Asia/Ho_Chi_Minh").*/format("X");
      var webhook_data = {
        trigger: 'message',
        page_id: data.page_id,
        timestamp: timestamp,
        surveys : data.survey_data,
       properties : data.properties,
       messages:{
         text: '',
         sender_id: data.sender_id,
        sender_info:{
          name: data.name,
          user_id: data.sender_id,
          profile_pic:'',
          locale: '',
          timezone: '',
        },
          type: 'image',
          url: data.url,
          source: {
            "type": "livechat_remote",
          }
        },
      };
      console.log(webhook_data);
      
      var response = await axios({
      method: 'post',
      // url: process.env.url_webhook,
      url: url_omnisales_api+"/livechat_webhook_hander_clients",
      data: webhook_data,
      headers: {key: "key_access"},
    })
    .then(response => {
      /*sendSockettoStToken('receiveMes', data.sender_id, {
        user_id: data.sender_id,
        sender_id: data.sender_id,
        text: data.text,
        url: data.url,
        type: 'image',
        date: coverStampToTime(timestamp),
        timestamp: timestamp,
      });*/
      console.log(response);
      io.in('channel_user_'+data.sender_id).emit('receiveMes',{
        user_id: data.sender_id,
        sender_id: data.sender_id,
        text: data.text,
        url: data.url,
        type: 'image',
        date: coverStampToTime(timestamp),
        timestamp: timestamp,
      });

    })
    .catch(error => {
      console.log(error.response);
    });

        // io.emit('MESSAGE', data)
    });

    socket.on('getlivechat',async function(data) {
        var webhook_data = {
      livechat_id : data.livechat_id,
      st_token : data.st_token,
    };
    console.log(webhook_data);
    var response = await axios({
      method: 'get',
      url: url_omnisales_api+"/livechat_webhook_hander_clients/getlivechat",
      params: webhook_data,
      headers: {key: "key_access"},
    })
    .then(response => {
      // console.log(response);
      sendSockettoStToken('getlivechat', data.st_token, response.data.data);
      if (response.data.data.people_info) {
        sendSockettoStToken('get_people', data.st_token, response.data.data.people_info);
      }else{
        sendSockettoStToken('get_people', data.st_token, null);
      }
      
    }).catch(error => {
      console.log(error);
      // console.log(error.response);
    });
    });

    socket.on('submit_survey',async function(data) {
      // console.log(data);
        var webhook_data = {
      trigger : 'message',
      page_id : data.livechat_id,
      surveys : data.survey_data,
      properties : data.properties,
      messages: {
        type:'text',
        text: '',       
        sender_id: data.st_token,
        source: {
          "type": "livechat_remote",
        }
      },
      timestamp: (new Date()).getTime() / 1000,
    }

    var response = await axios({
      method: 'post',
      // url: process.env.url_webhook,
      url: url_omnisales_api+"/livechat_webhook_hander_clients",
      data: webhook_data,
      headers: {key: "key_access"},
    })
    .then(response => {
      console.log(response.data);
      if (response.data.status==0) {
        console.log('survey_success');
        console.log(data.st_token);
        sendSockettoStToken('survey_success', data.st_token, {});
      }
      
    })
    .catch(error => {
      console.log(error.response);
    }); 
    });

    socket.on('get_messages',async function(data) {
      /*console.log('get_messages start');
      console.log(data);
      console.log('get_messages end');*/
    var response = await axios({
      method: 'get',
      url: process.env.url_omnisales_api+"/livechat_webhook_hander_clients/getmessages",
      params: {
        st_token: data.st_token,
      },
      headers: {key: "key_access"},
    })
    .then(response => {
      var messages = [];
      var data_messages = response.data.data;
      // console.log(response.data);
      for (var i = 0; i < data_messages.length; i++) {      
        messages.push({
          type: data_messages[i].type,
          text: data_messages[i].text,
          url: data_messages[i].url,
          user_id: data_messages[i].user_id,
          profile_pic: data_messages[i].profile_pic,
          name: data_messages[i].name,
          date: coverStampToTime(data_messages[i].timestamp),
          timestamp: data_messages[i].timestamp,
        });
      }
      // console.log(response.data[0].type);
      // console.log(messages);
      sendSockettoStToken('get_messages', data.st_token, messages);
    })
    .catch(error => {
      console.log(error.response);
    }); 
    });

    socket.on('get_agent',async function(data) {
      // console.log(data);
      console.log('get agent!');
        /*var webhook_data = {
          trigger : 'message',
          page_id : data.livechat_id,
      surveys : data.survey_data,
      messages: {
        text: '',
        timestamp: (new Date()).getTime() / 1000,
        sender_id: data.st_token,
        source: {
          "type": "livechat",
        }
      }
    };*/
    /*var response = await axios({
      method: 'get',
      url: process.env.url_webhook+"webhookLive/getagent",
      params: {
        st_token: data.st_token,
        agent_id: data.agent_id,
      },
      headers: {key: "key_access"},
    })
    .then(response => {
      sendSockettoStToken('get_agent', data.st_token, response.data);
    })
    .catch(error => {
      console.log(error.response);
    });*/ 
    });
});

function sendSockettoStToken(emit_name, st_token, data){
console.log(clients);
  for( var i=0, len=clients.length; i<len; ++i ){
    var c = clients[i];
    if(c.st_token == st_token){
      io.to(c.clientId).emit(emit_name, data);
      /*console.log('___ok');
      console.log(data);
      console.log('___');*/
    }
  }
}

function coverStampToTime(unix_timestamp){
  var date = new Date(unix_timestamp*1000);
// Hours part from the timestamp
var hours = date.getHours();
// Minutes part from the timestamp
var minutes = "0" + date.getMinutes();
// Seconds part from the timestamp
var seconds = "0" + date.getSeconds();

// Will display time in 10:30:23 format
var formattedTime = hours + ':' + minutes.substr(-2); //+ ':' + seconds.substr(-2);
return formattedTime;
}