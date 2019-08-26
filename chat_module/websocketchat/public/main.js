  var socket = io('http://localhost:3000');
  socket.on('news', function (data) {
    // console.log(data);
    // alert(data.hello);
    socket.emit('my other event', { my: 'data' });
  });
  // var appUserId;
  socket.on('appUserId', function (data) {
    console.log(data);
    // if ($('.friend-list').find('.class'+data.appUserId)) {
    
    html = '<li class="active bounceInDown class'+data.appUserId+'" data-app-id="'+data.appUserId+'">';
    html +='              <a href="#" class="clearfix">';
    html +='                <img src="'+data.avatar+'" alt="" class="img-circle">';
    html +='                <div class="friend-name"> ';
    html +='                  <strong>'+data.name+'</strong>';
    html +='                </div>';
    html +='                <div class="last-message text-muted">'+data.messages+'</div>';
    html +='                <small class="time text-muted">Just now</small>';
    html +='               <small class="chat-alert label label-danger">1</small>';
    html +='              </a>';
    html +='            </li>';
    if ($('.class'+data.appUserId)[0]){
    	$('.class'+data.appUserId).remove();
    }else{
    	html_chat = ' <div class="chat-message chat-message'+ data.appUserId +'" style="display:none;"><ul class="chat "> </ul></div>';
    	$('.chat-message-wrap').append(html_chat);
    }
    $('.friend-list').prepend(html);
    html = '<li class="left clearfix">';
    html +='                    <span class="chat-img pull-left">';
    html +='                    <img src="'+data.avatar+'" alt="User Avatar">';
    html +='                  </span>';
    html +='                  <div class="chat-body clearfix">';
    html +='                    <div class="header">';
    html +='                      <strong class="primary-font">'+data.name+'</strong>';
    html +='                      <small class="pull-right text-muted"><i class="fa fa-clock-o"></i> 12 mins ago</small>';
    html +='                    </div>';
    html +='                    <p>'+data.messages+'</p>';
    html +='                  </div>';
    html +='                </li>';
    $('.chat').append(html);
    
  });

  $(document).on('keydown', '.inputMessage', function(e) {
    if (e.keyCode == 13) {
      $('.btn-send-chat').trigger('click');   
    }    
  });
  $(document).on('click', '.btn-send-chat', function(e) {
    if ($(this).closest('.input-group').find('.inputMessage').val()!='') {
    	html = '<li class="right clearfix">';
    	html +='                  <span class="chat-img pull-right">';
    	html +='                    <img src="https://bootdey.com/img/Content/user_1.jpg" alt="User Avatar">';
    	html +='                  </span>';
    	html +='                  <div class="chat-body clearfix">';
    	html +='                    <div class="header">';
    	html +='                      <strong class="primary-font">Hổ trợ</strong>';
    	html +='                      <small class="pull-right text-muted"><i class="fa fa-clock-o"></i> 13 mins ago</small>';
    	html +='                    </div>';
    	html +='                    <p>'+ $(this).closest('.input-group').find('.inputMessage').val() +'</p>';
    	html +='                  </div>';
    	html +='                </li>';
    	$('.chat').append(html);
    	//chat-messageb40bce5aa777c7bbc6863e56
      socket.emit('sendMes', { mes: $(this).closest('.input-group').find('.inputMessage').val(),appUserId: $(this).attr('data-app-id') });
      $(this).closest('.input-group').find('.inputMessage').val('');
    }  
  });
  $(document).on('click', '.friend-list li', function(e) {
  	$('.chat-message').hide();
  	$('.chat-message'+$(this).attr('data-app-id')).show();
  	$('.btn-send-chat').attr('data-app-id', $(this).attr('data-app-id'));
  });
  /*function loadUser(){

  }
  loadUser();*/
  // alert('a');
// });