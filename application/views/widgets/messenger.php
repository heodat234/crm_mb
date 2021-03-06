<li class="noti-box">
	<a href="javascript:void(0)" class="btn btn-alt btn-sm btn-default dropdown-toggle"  data-toggle="dropdown">
		<i class="fa fa-comments" data-toggle="tooltip" title="@Chat notification@" data-placement="bottom"></i>
	</a>
	<ul id="newmess_content_box" class="dropdown-menu dropdown-custom dropdown-options" style="left: -50px; width: 200px; max-height: 500px; overflow-y: scroll;">
	</ul>
</li>

<script type="text/javascript" >
	var base_url = ENV.baseUrl;
    function chatNotifications(e){
        var response = JSON.parse(e.data);
        if(response.total > 0){ 
            $( ".noti-box a.dropdown-toggle" ).append('<span class="label label-danger label-indicator animation-pulse ">'+response.total+'</span> ');
        } 

        $("#newmess_content_box").html('');
        var strhtml = '';
        if (response.total) {
            for (var i = 0; i < response.total; i++) {
                /*strhtml += '<a href="javascript:void(0)" ><strong>'+data[i].name+'</strong></a><br/>';
                 strhtml += data[i].text+'<br/>';
                 strhtml += '<a data-name="'+ data[i].name +'" data-id="'+ data[i].user_id +'" data-line="'+ data[i].line +'" data-type="'+ data[i].type +'" class="btn btn-xs btn-primary btn-create-room-invite">Chat</a><br/><br/>';         */

                // strhtml += '<a data-name="'+ data[i].name +'" data-id="'+ data[i].id +'" data-line="'+ data[i].line +'" data-type="'+ data[i].type +'" class="btn btn-xs btn-primary btn-create-room-invite">Chat</a><br/><br/>';   
                strhtml += '<li><div class="noti-item">';
                strhtml += '   <a data-id="' + response.data[i].id + '" class="btn-create-room-invite" href="javascript:void(0)"><strong>' + response.data[i].icon + ' ' + response.data[i].name + '</strong></a> ' + response.data[i].text;
                strhtml += '</div></li>';
            }
            $("#newmess_content_box").html(strhtml);
        }
    }
    $(document).on('click', '.btn-create-room-invite', function (e) {
        e.preventDefault();
        id = $(this).attr('data-id');
        $.ajax({
            type: 'POST',
            url: base_url + 'app/chat/redirectNotify',
            data: {id: id},
            dataType: "json",
            success: function (json) {
                
                if (json['data_emit']) {
                 
                    if (json['data_emit']['source']=='transfer_success') {
                        //       console.log("aaaaaaaaaaaf");
                        // alert('okok');
                        // console.log({room_id: json['data_emit']['room_id'], user_id: json['data_emit']['send_to'] });
                        //console.log({room_id: json['data_emit']['room_id'], user_id: json['data_emit']['from'] });
                        socket.emit('join_room', json['data_emit']['room_id']);
                        // socket.emit('join_room_by_user', {room_id: json['room_id'], user_id: json['send_to'] });
                        socket.emit('notification', json['data_emit']);
                        socket.emit('leave_room_by_user', {room_id: json['data_emit']['room_id'], user_id: json['data_emit']['from']});
                        //console.log("json['data_emit']");
                    }
                }   
            
            self.$emit('notification_message', {source:json['source'], trigger:json['trigger'], title:json['title'] });                    
            /*if (json['redirect']) {
              window.location.href = json['redirect'];
            }*/

            }
        });


    });

    function chatWindow() {
        $rightForm = $("#right-form");
        $rightForm.html(`<iframe src="${ENV.baseUrl + 'chat/chat?omc=1'}" style="width: 100%; min-height: 90vh; border: 0"></iframe>`);
    }
</script>