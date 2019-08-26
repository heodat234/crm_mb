// 'use strict';
// Imports
const Hapi          = require('hapi');
const Basic         = require('hapi-auth-basic');
const axios         = require('axios');
const querystring   = require('querystring');
const key_access      = require('./config').keyaccess;
const url_api_mongo   = require('./config').url_api_mongo;
const url_api_webhook = require('./config').url_api_webhook;
const bodyParser    = require('body-parser');
const chatGroupModel     = require('./src/models/chatGroup');

const port_config     = 8001;

const server = new Hapi.Server({ port: port_config});

// var io = require('socket.io')(server.listener);
const io = require('socket.io')(server.listener);//, {path: '/omni/', 'transports': ['websocket', 'polling']});


server.route(require('./src/routes/localapi')(io));

server.route(
  {
    method: 'POST',
    path:'/api/v2/chat',
    handler: (request, reply) => {
      
        var data = request.payload;
        console.log(data);
        var profile_pic = '';
        if (data.sender_info.profile_pic) {
          profile_pic = data.sender_info.profile_pic;
        }else{
          profile_pic = 'https://omnisales.worldfone.vn/portal/assets/images/avatar_default.jpg';
        }
        if (data.type == 'text') {
          var data_emit = {
            id: data.id,
            trigger: data.trigger,
            source:data.source,
            /*metadata: {
              id: data.id
            },*/          
            text: data.text, 
            room_id: data.room_id,
            type: data.type,
            url: '',
            sender_info: data.sender_info,
            sender_id: data.sender_id,
            name: data.sender_info.name,
            profile_pic: profile_pic,
            date: coverStampToTime(data.date_added),
            timestamp: data.date_added
          };
          if (data.source=="facebook") {
            data_emit.details = data.details;
          }

          console.log(data_emit);
          
          io.sockets.in(data.room_id).emit('receiveMes', data_emit);
        }else if (data.type == 'link') {
          var data_emit = {
            id: data.id,
            trigger: data.trigger,
            source:data.source,
            // id: data.id, 
            text: data.text, 
            room_id: data.room_id,
            type: data.type,
            url: '',
            sender_info: data.sender_info,
            sender_id: data.sender_id,
            name: data.sender_info.name,
            profile_pic: profile_pic,
            date: coverStampToTime(data.date_added),
            timestamp: data.date_added
          };
          /*if (data.source=="new_facebook_comment") {
            data_emit.comment_id = data.sender_info.comment_id;
            data_emit.post_url = data.sender_info.post_url;
          }*/
          
          io.sockets.in(data.room_id).emit('receiveMes', data_emit);
          
        } else if (data.type =='image' || data.type =='file' ) {
          var data_emit = {
            id: data.id,
            trigger : data.trigger,
            source:data.source,
            // id: data.id,
            text: data.text, 
            room_id: data.room_id,
            type: data.type,
            url: data.url, 
            sender_info: data.sender_info,
            sender_id: data.sender_id,
            name: data.sender_info.name,
            profile_pic: profile_pic,
            date: coverStampToTime(data.date_added),
            timestamp: data.date_added, 
          };
          /*if (data.source=="new_facebook_comment") {
            data_emit.comment_id = data.sender_info.comment_id;
            data_emit.post_url = data.sender_info.post_url;
          }*/
          io.sockets.in(data.room_id).emit('receiveMesImg', data_emit);
        };
        console.log('sendred');

        
        return reply.response({error:0, message:'success'}).code(200);
      }
});

server.route({
  method: 'POST',
  path:'/api/v2/loadnewroom',
  handler: (request, reply) => {
      /*
      {
        room_id: 23223hj321jg45g3h3gh, 
      }
      */
      // console.log('hoho');
      var data = request.payload;
      // io.sockets.join(data.room_id);
      io.sockets.emit('loadnewroom', {/*data.room_id*/}); 
      return reply.response({error:0, message:'success'}).code(200);
    }
  });


var numUsers = {};
io.on('connection', function (socket) {
  socket.on('load', function (data) {
    console.log(data);
    socket.join('channel_user_'+data.user);
    numUsers[data.user] = socket.id;      
    const responseSocket = io.sockets.connected[socket.id];
    socket.join(data.parent_user);
  });

  socket.on('notification', function (data) {
    console.log(data);
    console.log(numUsers);
    var socket_id = numUsers[data.send_to];
    
    io.in('channel_user_'+data.send_to).emit('notification', data);
  });


  // Nếu 1 room mới thì chạy
  socket.on('joinnewroom', function (room_id) {
    socket.join(room_id);
  });

  //chạy 1 array room
  socket.on('room_join', function (room_data) {
    socket.join(room_data);
    // console.log('join room'+socket.id);
    console.log('room_data');
    console.log(room_data);
    // console.log('___________________ 1 đứa vừa mới join11');
    
    /*for (var i = 0; i < room_data.length; i++) {
     //socket.broadcast.to(room_data[i]).emit('welcom', {data:"welcome to room"+ room_data[i]});
     var room = sockets.adapter.rooms[room_data[i]];
     console.log(room);
    console.log('room_join '+' room_id:'+room_data[i]+' );
     socket.join(room_data[i]);
    }*/
  });

  socket.on('join_room_by_user', function (data) {
    var socket_id = numUsers[data.user_id];
    let socket_user = io.sockets.connected[socket_id];
    socket_user.join(data.room_id);
  });

  socket.on('leave_room_by_user', function (data) {
    // var socket_id = numUsers[data.user_id];
    // let socket_user = io.sockets.connected[socket_id];
    // socket_user.leave(data.room_id);
    io.in('channel_user_'+data.user_id).emit('off_room', data.room_id);

  });

  socket.on('user_onlines', function (room_data) {
    socket.emit('user_onlines', numUsers);
    // socket.broadcast.emit('user_onlines', numUsers);
  });

  // Kiểm tra tồn tại user đó hay chưa
  socket.on('exits_user', function (user_id) {
    count = 0;
    User_Obj = Object.values(numUsers);
    for (var i = 0; i < User_Obj.length; i++) {
      if (user_id==User_Obj[i] ) {
        count++;
      }
    }
    if (count>1) {
      socket.broadcast.emit('exits_user', user_id);
    }
  });

  // Somebody left the chat
  socket.on('disconnect', function() { 
    socket.emit('user_offline', numUsers[socket.id]);
    socket.broadcast.emit('user_offline', numUsers[socket.id]);
    delete numUsers[socket.id];
  });

  socket.on('reconnect', function() { 
    console.log('reconnect');
  });

  socket.on('changeStatus', function (data) {
    socket.broadcast.emit('changeStatus', {user_id : data.user_id, status : data.status});
  });

  socket.on('private_replies', async function (data) {
    console.log(data);
    var axiosOption = {
     method: 'post',
     url: 'https://webhooksanbox.worldfone.vn/omni/me/comments/'+data.comment_id+'/private_replies',
     data: {
      page_id: data.page_id,
    },
    headers: {key: key_access},
  };
  try {
    var response = await axios.request(axiosOption);
    console.log(response.data);
  } catch(error) {
    throw error;
  }
});

    /*socket.on('comment_action', async function (data) {
      console.log(data);
      if (data.action=='like') {
        if (data.action_value==true) {
          var axiosOption = {
           method: 'post',
           url: 'https://webhooksanbox.worldfone.vn/omni/me/comments/'+data.comment_id+'/likes',
           data: {
            page_id: data.page_id,
          },
          headers: {key: key_access},
        };
      }else{
          var axiosOption = {
           method: 'delete',
           url: 'https://webhooksanbox.worldfone.vn/omni/me/comments/'+data.comment_id+'/likes',
           data: {
            page_id: data.page_id,
          },
          headers: {key: key_access},
        };
      }
        
      try {
        var response = await axios.request(axiosOption);
      } catch(error) {
        throw error;
      }
      } else if(data.action=='hide'){
        var axiosOption = {
         method: 'DELETE',
         url: 'https://webhooksanbox.worldfone.vn/omni/me/comments/'+data.comment_id,
         data: {
          is_hidden: data.action_value,
          page_id: data.page_id,
         },
         headers: {key: key_access},
        };
        console.log(axiosOption);console.log('commentHide');
        var response = await axios.request(axiosOption);
        console.log(response.data);
      } else if(data.action=='trash'){        
        var axiosOption = {
         method: 'DELETE',
         url: 'https://webhooksanbox.worldfone.vn/omni/me/comments/'+data.comment_id,
         data: {
          page_id: data.page_id,
         },
         headers: {key: key_access},
        };
        var response = await axios.request(axiosOption);
        console.log(response.data);
      }
      // socket.broadcast.emit('changeStatus', {user_id : data.user_id, status : data.status});
    });*/


    //Phan phoi tin nhan cho nhung ng trong room 
    socket.on('msg', function (data) {
      console.log(data);
      console.log("===================");
      var regex_br = /<br\s*[\/]?>/gi;
      data.text = data.text.replace(regex_br, "\n");    
      
      var data_emit = {
        page_id : data.page_id,
        trigger : data.trigger,
        source:data.source,
        message_id: data.message_id,//id tin nhắn để trả về
        text: data.text, 
        message: data.text, 
        room_id: data.room_id,
        type: 'text',
        url: '',
        metadata: {
          id: data.message_id
        },
        sender_id:data.sender_id,
        receiver_id: data.receiver_id,
        name: data.name, 
        profile_pic: data.profile_pic,
        date: data.date, 
        timestamp: data.timestamp,
      }; 

      console.log(' start data emit');
      console.log(data_emit);     
      console.log(' end data emit');

      if (data.trigger=='message') {
        io.in(data.room_id).emit('receiveMes', {id: data.id, text: data.text, room_id: data.room_id, type: data.type, sender_id:data.sender_id, name: data.name, profile_pic: data.profile_pic, date: data.date, timestamp: data.timestamp, sended:1});
      }else{
        if (data.trigger=='comment') {
          if (data.source=='facebook') {
            console.log(data_emit);     
            console.log("===================");
            console.log('facebook ok ');
            console.log("===================");
            io.in(data.room_id).emit('receiveMes', {id: data.id, text: data.text, room_id: data.room_id, type: data.type, sender_id:data.sender_id, name: data.name, profile_pic: data.profile_pic, date: data.date, timestamp: data.timestamp, sended:1});
          }
        }
      }      
    });

    socket.on('msgImg', function (data) {
      // console.log({text: data.text, room_id: data.room_id, type: data.type, url: data.url, sender_id:data.sender_id, name: data.name, date: data.date, timestamp: data.timestamp});
      /*console.log('dfhdsjfdshdsfdslfjdls____________');*/
      // socket.broadcast.to(data.room_id).emit('receiveMesImg', {text: data.text, room_id: data.room_id, type: data.type, url: data.url, sender_id:data.sender_id, name: data.name, date: data.date, timestamp: data.timestamp});
      var data_emit = {
        page_id : data.page_id,
        trigger : data.trigger,
        source:data.source,
        // message_id: data.message_id,
        metadata: {
          id: data.message_id
        },
        text: data.text,
        room_id: data.room_id,
        type: 'image',
        url: data.url,
        receiver_id: data.receiver_id,
        date: data.date, 
        timestamp: data.timestamp,
      };
      // console.log(data_emit);

      if (data.trigger=='message') {
        io.in(data.room_id).emit('receiveMesImg', {id: data.id, text: data.text, room_id: data.room_id, type: data.type, url: data.url, sender_id:data.sender_id, name: data.name, profile_pic: data.profile_pic, date: data.date, timestamp: data.timestamp});
        // io.in(data.room_id).emit('receiveMes', {text: data.text, room_id: data.room_id, type: data.type, sender_id:data.sender_id, name: data.name, profile_pic: data.profile_pic, date: data.date, timestamp: data.timestamp, sended:1});
        // if (data.source=='messenger') {
        //   io.in(data.room_id).emit('receiveMesImg', {text: data.text, room_id: data.room_id, type: data.type, url: data.url, sender_id:data.sender_id, name: data.name, date: data.date, timestamp: data.timestamp});
        // }else if (data.source=='zalo') {
        //   io.in(data.room_id).emit('receiveMesImg', {text: data.text, room_id: data.room_id, type: data.type, url: data.url, sender_id:data.sender_id, name: data.name, date: data.date, timestamp: data.timestamp});
        // }else if (data.source=='livechat') {
        //   io.in(data.room_id).emit('receiveMes', {text: data.text, room_id: data.room_id, type: data.type, sender_id:data.sender_id, name: data.name, profile_pic: data.profile_pic, date: data.date, timestamp: data.timestamp, sended:1});
        // }else{
        //   io.in(data.room_id).emit('receiveMes', {text: data.text, room_id: data.room_id, type: data.type, sender_id:data.sender_id, name: data.name, profile_pic: data.profile_pic, date: data.date, timestamp: data.timestamp, sended:1});
        // }
      }
      
    });

    //thong báo load lại sortlist để cập nhật tn mới
    /*socket.on('chat-with-me', function (data) {
      // console.log(data);
      socket.broadcast.emit('chat-with-me', {msg: data.msg, room_id: data.room_id, type: data.type, user_id:data.user_id, name: data.name, date: data.date, date_added: data.date_added});
    });*/

});

async function getTokenFacebook(page_id){
  var axiosOptionGetPage = {
    method: 'post',
    url: url_api_mongo+"getTokenPagebyID",
    data: {
      page_id:page_id,
    },
    headers: {key: key_access},
    //headers: {Authorization: 'bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MSwibmFtZSI6IlBvc1RlYW0iLCJpYXQiOjE1MTYzNDk2NjZ9.2C8aVVOImsbMO9DcLx4EJv0xleh6SJnSTrPf2LrvJQ4'},
  };
  try {
    var responsePageToken = await axios.request(axiosOptionGetPage);
    return responsePageToken.data;
  } catch(error) {
    // throw error;
    return false;
  }
}

async function getTokenViber(public_account_id){
  var axiosOptionGetPage = {
    method: 'post',
    url: url_api_mongo+"getTokenViber",
    data: {
      public_account_id:public_account_id,
    },
    headers: {key: key_access},
    //headers: {Authorization: 'bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MSwibmFtZSI6IlBvc1RlYW0iLCJpYXQiOjE1MTYzNDk2NjZ9.2C8aVVOImsbMO9DcLx4EJv0xleh6SJnSTrPf2LrvJQ4'},
  };
  try {
    var responsePageToken = await axios.request(axiosOptionGetPage);
    return responsePageToken.data;
  } catch(error) {
    // throw error;
    return false;
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


var validate = (decoded, request, callback) => {
    console.log(" - - - - - - - decoded token:");
    console.log(decoded);
   
};


server.start((err) => {
console.log(`Server running at: ${server.info.uri}`);
    if (err) {
        throw err;
    }
    console.log(`Server running at: ${server.info.uri}`);
});
console.log('App listening on port'+port_config);


