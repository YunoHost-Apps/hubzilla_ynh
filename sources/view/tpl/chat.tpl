<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			{{if $is_owner}}
			<form id="chat-destroy" method="post" action="chat">
				<input type="hidden" name="room_name" value="{{$room_name}}" />
				<input type="hidden" name="action" value="drop" />
				<button class="btn btn-danger btn-xs" type="submit" name="submit" value="{{$drop}}" onclick="return confirmDelete();"><i class="fa fa-trash-o"></i>&nbsp;{{$drop}}</button>
			</form>
			{{/if}}
			<button id="fullscreen-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen(); adjustFullscreenTopBarHeight();"><i class="fa fa-expand"></i></button>
			<button id="inline-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen(false); adjustInlineTopBarHeight();"><i class="fa fa-compress"></i></button>
		</div>
		<h2>{{$room_name}}</h2>
		<div class="clear"></div>
	</div>
	<div id="chatContainer" class="section-content-wrapper">
		<div id="chatTopBar">
			<div id="chatLineHolder"></div>
		</div>
		<div class="clear"></div>
		<div id="chatBottomBar" >
			<form id="chat-form" method="post" action="#">
				<input type="hidden" name="room_id" value="{{$room_id}}" />
				<div class="form-group">
					<textarea id="chatText" name="chat_text" class="form-control"></textarea>
				</div>
				<div id="chat-submit-wrapper">
					<div id="chat-submit" class="dropup pull-right">
						<button class="btn btn-default btn-sm dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-caret-up"></i></button>
						<button class="btn btn-primary btn-sm" type="submit" id="chat-submit" name="submit" value="{{$submit}}">{{$submit}}</button>
						<ul class="dropdown-menu">
							<li class="nav-item"><a class="nav-link" href="{{$baseurl}}/chatsvc?f=&room_id={{$room_id}}&status=online"><i class="fa fa-circle online"></i>&nbsp;{{$online}}</a></li>
							<li class="nav-item"><a class="nav-link" href="{{$baseurl}}/chatsvc?f=&room_id={{$room_id}}&status=away"><i class="fa fa-circle away"></i>&nbsp;{{$away}}</a></li>
							<li class="nav-item"><a class="nav-link" href="{{$baseurl}}/chat/{{$nickname}}/{{$room_id}}/leave"><i class="fa fa-circle leave"></i>&nbsp;{{$leave}}</a></li>
                            <li class="divider"></li>
                            <li class="nav-item" id="toggle-notifications"><a class="nav-link" href="" onclick="toggleChatNotifications(); return false;"><i id="toggle-notifications-icon" class="fa fa-bell-slash-o"></i>&nbsp;Toggle notifications</a></li>
                            <li class="nav-item disabled" id="toggle-notifications-audio"><a class="nav-link" href="" onclick="toggleChatNotificationAudio(); return false;"><i id="toggle-notifications-audio-icon" class="fa fa-volume-off"></i>&nbsp;Toggle sound</a></li>
							{{if $bookmark_link}}
							<li class="divider"></li>
							<li class="nav-item"><a class="nav-link" href="{{$bookmark_link}}" target="_blank" ><i class="fa fa-bookmark"></i>&nbsp;{{$bookmark}}</a></li>
							{{/if}}
						</ul>
					</div>
					<div id="chat-tools" class="btn-toolbar pull-left">
						<div class="btn-group">
							<button id="main-editor-bold" class="btn btn-default btn-sm" title="{{$bold}}" onclick="inserteditortag('b', 'chatText'); return false;">
								<i class="fa fa-bold jot-icons"></i>
							</button>
							<button id="main-editor-italic" class="btn btn-default btn-sm" title="{{$italic}}" onclick="inserteditortag('i', 'chatText'); return false;">
								<i class="fa fa-italic jot-icons"></i>
							</button>
							<button id="main-editor-underline" class="btn btn-default btn-sm" title="{{$underline}}" onclick="inserteditortag('u', 'chatText'); return false;">
								<i class="fa fa-underline jot-icons"></i>
							</button>
							<button id="main-editor-quote" class="btn btn-default btn-sm" title="{{$quote}}" onclick="inserteditortag('quote', 'chatText'); return false;">
								<i class="fa fa-quote-left jot-icons"></i>
							</button>
							<button id="main-editor-code" class="btn btn-default btn-sm" title="{{$code}}" onclick="inserteditortag('code', 'chatText'); return false;">
								<i class="fa fa-terminal jot-icons"></i>
							</button>
						</div>
						<div class="btn-group hidden-xs">
							<button id="chat-link-wrapper" class="btn btn-default btn-sm" onclick="chatJotGetLink(); return false;" >
								<i id="chat-link" class="fa fa-link jot-icons" title="{{$insert}}" ></i>
							</button>
						</div>
						{{if $feature_encrypt}}
						<div class="btn-group hidden-xs">
							<button id="chat-encrypt-wrapper" class="btn btn-default btn-sm" onclick="red_encrypt('{{$cipher}}', '#chatText', $('#chatText').val()); return false;">
								<i id="chat-encrypt" class="fa fa-key jot-icons" title="{{$encrypt}}" ></i>
							</button>
						</div>
						{{/if}}
						<div class="btn-group dropup visible-xs">
							<button type="button" id="more-tools" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
								<i id="more-tools-icon" class="fa fa-caret-up jot-icons"></i>
							</button>
							<ul class="dropdown-menu dropdown-menu-right" role="menu">
								<li class="visible-xs"><a href="#" onclick="chatJotGetLink(); return false;" ><i class="fa fa-link"></i>&nbsp;{{$insert}}</a></li>
								{{if $feature_encrypt}}
								<li class="divider"></li>
								<li class="visible-xs"><a href="#" onclick="red_encrypt('{{$cipher}}', '#chatText' ,$('#chatText').val()); return false;"><i class="fa fa-key"></i>&nbsp;{{$encrypt}}</a></li>
								{{/if}}
							</ul>
						</div>
					</div>
					<div id="chat-rotator-wrapper" class="pull-left">
						<div id="chat-rotator"></div>
					</div>
					<div class="clear"></div>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
var room_id = {{$room_id}};
var last_chat = 0;
var chat_timer = null;

$(document).ready(function() {
	$('#chatTopBar').spin('small');
	chat_timer = setTimeout(load_chats,300);
	$('#chatroom_bookmarks, #vcard').hide();
	$('#chatroom_list, #chatroom_members').show();
	adjustInlineTopBarHeight();
    chatNotificationInit();
});

$(window).resize(function () {
	if($('main').hasClass('fullscreen')) {
		adjustFullscreenTopBarHeight();
	}
	else {
		adjustInlineTopBarHeight();
	}
});

$('#chat-form').submit(function(ev) {
	$('body').css('cursor','wait');
	$.post("chatsvc", $('#chat-form').serialize(),function(data) {
			if(chat_timer) clearTimeout(chat_timer);
			$('#chatText').val('');
			load_chats();
			$('body').css('cursor','auto');
		},'json');
	ev.preventDefault();
});

function load_chats() {
	$.get("chatsvc?f=&room_id=" + room_id + '&last=' + last_chat + ((stopped) ? '&stopped=1' : ''),function(data) {
		if(data.success && (! stopped)) {
			update_inroom(data.inroom);
			update_chats(data.chats);
			$('#chatTopBar').spin(false);
		}
	});
	
	chat_timer = setTimeout(load_chats,10000);

}

var previousChatRoomMembers = null; // initialize chat room member change register
var currentChatRoomMembers = null; // initialize chat room member change register
function update_inroom(inroom) {
	var html = document.createElement('div');
	var count = inroom.length;
	$.each( inroom, function(index, item) {
		var newNode = document.createElement('div');
		newNode.setAttribute('class', 'member-item');
		$(newNode).html('<img style="height: 32px; width: 32px;" src="' + item.img + '" alt="' + item.name + '" /> ' + '<span class="name">' + item.name + '</span><br /><span class="' + item.status_class + '">' + item.status + '</span>');
		html.appendChild(newNode);
	});
    memberChange = chatRoomMembersChange(inroom); // get list of arrivals and departures
    if(memberChange.membersArriving.length > 0) {
      // Issue pop-up notification if anyone enters the room.
      chat_issue_notification(JSON.stringify(memberChange.membersArriving.pop().name) + ' entered the room', 'Hubzilla Chat');
    }
	$('#chatMembers').html(html);
}

// Determine if the new list of chat room members has any new members or if any have left
function chatRoomMembersChange(inroom) {
    previousChatRoomMembers = currentChatRoomMembers;
    currentChatRoomMembers = inroom;
      var membersArriving = [];
      var membersLeaving = [];
    if(previousChatRoomMembers !== null) {
      var newMember = false;
      $.each( currentChatRoomMembers, function(index, currMember) {
        newMember = true;
        $.each( previousChatRoomMembers, function(index, prevMember) {
          if (prevMember.name === currMember.name) {
              newMember = false;
          }
        });
        if (newMember === true) {
          membersArriving.push(currMember);
        }
      });
    }
    return {membersArriving: membersArriving, membersLeaving: membersLeaving};
}

function update_chats(chats) {
	var count = chats.length;
	$.each( chats, function(index, item) {
		last_chat = item.id;
		var newNode = document.createElement('div');

		if(item.self) {
			newNode.setAttribute('class', 'chat-item-self clear');
			$(newNode).html('<div class="chat-body-self"><div class="chat-item-title-self wall-item-ago"><span class="chat-item-name-self">' + item.name + ' </span><span class="autotime chat-item-time-self" title="' + item.isotime + '">' + item.localtime + '</span></div><div class="chat-item-text-self">' + item.text + '</div></div><img class="chat-item-photo-self" src="' + item.img + '" alt="' + item.name + '" />');
		}
		else {
			newNode.setAttribute('class', 'chat-item clear');
			$(newNode).html('<img class="chat-item-photo" src="' + item.img + '" alt="' + item.name + '" /><div class="chat-body"><div class="chat-item-title wall-item-ago"><span class="chat-item-name">' + item.name + ' </span><span class="autotime chat-item-time" title="' + item.isotime + '">' + item.localtime + '</span></div><div class="chat-item-text">' + item.text + '</div></div>');
            chat_issue_notification(item.name + ':\n' + item.text, 'Hubzilla Chat');
		}
		$('#chatLineHolder').append(newNode);
		$(".autotime").timeago();

		var elem = document.getElementById('chatTopBar');
		elem.scrollTop = elem.scrollHeight;
	});
}

var chat_notify_granted = false; // Initialize notification permission to denied
var chat_notify_enabled = false;
var chat_notify_audio_enabled = false;
var chat_notify_audio = {};
// Request notification access from the user
// TODO: Check Hubzilla member config setting before requesting permission
function chatNotificationInit() {
  
    if (!("Notification" in window)) {
        window.console.log("This browser does not support system notifications");
    }
    // Let's check whether notification permissions have already been granted
    else if (Notification.permission === "granted") {
        // If it's okay let's create a notification
        chat_notify_granted = true; //var notification = new Notification("Hi there!");
    }

    // Otherwise, we need to ask the user for permission
    else if (Notification.permission !== 'denied') {
        Notification.requestPermission(function (permission) {
            // If the user accepts, let's create a notification
            if (permission === "granted") {
                chat_notify_granted = true; //var notification = new Notification("Hi there!");
            }
        });
    }
    // Encode a wav audio file in base64 and create the audio object for game alerts
    //var base64string = 'UklGRr4VAABXQVZFZm10IBAAAAABAAEAIlYAACJWAAABAAgAZGF0YZkVAACAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBxcnJycoGNjY2NjYyMjIyMg3FxcXFxcXJycnJ0jY2NjY2NjIyMjIx1cXFxcXFxcnJycoKNjY2NjY2MjIyMgXFxcXFxcXJycnJ2jY2NjY2NjIyMjIxzcXFxcXFxcnJycoSNjY2NjY2MjIyMgHFxcXFxcXJycnJ4jY2NjY2NjIyMjIxycXFxcXFycnJycoWNjY2NjY2MjIyMf3FxcXFxcXJycnJ5jY2NjY2NjIyMjIxxcXFxcXFycnJycoeNjY2NjY2MjIyMfnFxcXFxcXJycnJ7jY2NjY2NjIyMjIpxcXFxcXFycnJycomNjY2NjYyMjIyMfHFxcXFxcXJycnJ8jY2NjY2NjIyMjIhycnJycoyLi4uLi4uLioqKfHFxcXFxcnJycnJyc4yMjIuLi4uLi4uKin5xcXFxcnJycnJyc3OMjIyMi4uLi4uLi4p/cXFxcnJycnJyc3Nzi4yMjIyLi4uLi4uLgHBwcXFxcXFxcnJycouNjY2NjYyMjIyMjIBwcHFxcXFxcXJycnKLjY2NjY2MjIyMjIyBcHFxcXFxcXJycnJyio2NjY2NjIyMjIyMgnBxcXFxcXFycnJycomNjY2NjY2MjIyMjINwcXFxcXFxcnJycnKIjY2NjY2NjIyMjIyEcHFxcXFxcXJycnJyh42NjY2NjYyMjIyMhXBxcXFxcXFycnJycoaNjY2NjY2MjIyMjIZwcXFxcXFxcnJycnKFjY2NjY2NjIyMjIyHcHFxcXFxcXJycnJyhI2NjY2NjYyMjIyMiHBxcXFxcXFycnJycoONjY2NjY2MjIyMjIlwcXFxcXFxcnJycnKCjY2NjY2NjIyMjIyKcHFxcXFxcXJycnJygY2NjYyMjIyLi4uLi4uLioqKioqKdnBxcXFxcXFycnKAi4uLi4uLioqKioJxcXFxcXFycnJydIyMi4uLi4uLi4qKdXFxcXFycnJycnKBjIyLi4uLi4uLioFxcXFycnJycXFydY2NjYyMjIyMjIyLc3BwcXFxcXFxcnKDjY2NjIyMjIyMjIBwcHFxcXFxcXJyd42NjY2MjIyMjIyMcnBxcXFxcXFycnKFjY2NjYyMjIyMjH9wcXFxcXFxcnJyeY2NjY2NjIyMjIyMcHFxcXFxcXJycnKHjY2NjY2MjIyMjH5xcXFxcXFycnJyeo2NjY2NjIyMjIyKcHFxcXFxcXJycnKIjY2NjY2MjIyMjHxxcXFxcXFycnJyfI2NjY2NjYyMjIyJcXFxcXFxcXJycnKKjY2NjY2MjIyMjHtxcXFxcXFycnJyfo2NjY2NjYyMjIyHcXFxcXFxcnJycnKMjY2NjY2MjIuLi3lycnJycnJzc3Nzf4yMjIyMjIuLi4uFcnJycnJycnNzc3OMjIyMjIyMi4uLi3hycnJycnJzc3NzgIyMjIyMjIuLi4uDcnJycnJycnNzc3SMjIyMjIyMi4uLi3dycnJycnJzc3NzgYyMjIyMjIuLi4uCcnJycnJyc3Nzc3aMjIyMjIyMi4uLi3VycnJycnJzc3Nzg4yMjIyMjIuLi4uAcnJycnJyc3Nzc3eMjIyMjIyMi4uLi3RycnJycnJzc3NzhIyMjIyMjIuLi4uAcnJycnJyc3Nzc3mMjIyMjIyMi4uLi3JycnJycnJzc3NzhoyMjIyMjIuLi4t+cnJycnJyc3Nzc3qMjIyMjIyLi4uLinJycnNzc3N0dHR0h4uLi4uLi4qKiop9c3Nzc3Nzc3R0dHyLi4uLi4uLioqKiHNzc3Nzc3N0dHR0iIuLi4uLi4qKiop8c3Nzc3Nzc3R0dH2Li4uLi4uLioqKhnNzc3Nzc3N0dHR0iYuLi4uLi4qKiop6c3Nzc3Nzc3R0dH+Li4uLi4uLioqKhXNzc3Nzc3N0dHR0i4uLi4uLi4qKiop5c3Nzc3Nzc3R0dICLi4uLi4uLioqKhHNzc3Nzc3N0dHR1i4uLi4uLi4qKiop4c3Nzc3NzdHR0dIGLi4uLi4uLioqKgnNzc3Nzc3N0dHR2i4uLi4uLi4qKiop2c3Nzc3NzdHR0dIKLi4uLi4uLioqKgXNzc3Nzc3N0dHR3i4uLi4uLi4qKiol2dHR0dHR0dHV1dYOKioqKioqKiYmJgHR0dHR0dHR0dXV5ioqKioqKiomJiYl0dHR0dHR0dHV1dYSKioqKioqKiYmJf3R0dHR0dHR0dXV7ioqKioqKiomJiYl0dHR0dHR0dHV1dYaKioqKioqKiYmJfnR0dHR0dHR1dXV8ioqKioqKioqJiYd0dHR0dHR0dHV1dYeKioqKioqKiYmJfHR0dHR0dHR1dXV9ioqKioqKiomJiYZ0dHR0dHR0dHV1dYiKioqKioqKiYmJe3R0dHR0dHR1dXV+ioqKioqKiomJiYV0dHR0dHR0dHV1dYmKioqKioqKiYmJenR0dHR0dHR1dXWAioqKiYmJiYmIiIN1dXV1dXV1dXZ2domJiYmJiYmJiYiIeXV1dXV1dXV1dnaAiYmJiYmJiYmIiIJ1dXV1dXV1dXZ2d4mJiYmJiYmJiYiIeHV1dXV1dXV1dnaBiYmJiYmJiYmIiIF1dXV1dXV1dXZ2eImJiYmJiYmJiYiId3V1dXV1dXV1dnaCiYmJiYmJiYmIiIB1dXV1dXV1dXZ2eYmJiYmJiYmJiIiIdnV1dXV1dXV1dnaDiYmJiYmJiYmIiH91dXV1dXV1dXZ2e4mJiYmJiYmJiIiIdXV1dXV1dXV1dnaFiYmJiYmJiYmIiH51dXV1dXV1dXZ2fImJiYmJiYmJiIiHdXV1dXV1dXV1dnaGiYmJiYmJiYmIiH11dXV1dXV1dXZ2fYiIiIiIiIiIiIiFdnZ2dnZ2dnZ2d3eGiIiIiIiIiIiIh3x2dnZ2dnZ2dnd3foiIiIiIiIiIiIeEdnZ2dnZ2dnZ2d3eHiIiIiIiIiIiIh3t2dnZ2dnZ2dnZ3f4iIiIiIiIiIiIeDdnZ2dnZ2dnZ2d3eIiIiIiIiIiIiIh3p2dnZ2dnZ2dnZ3gIiIiIiIiIiIiIeCdnZ2dnZ2dnZ2d3iIiIiIiIiIiIiIh3l2dnZ2dnZ2dnZ3gIiIiIiIiIiIiIeBdnZ2dnZ2dnZ2d3mIiIiIiIiIiIiHh3h2dnZ2dnZ2dnZ3goiIiIiIiIiIiIeAdnZ2dnZ2dnZ2d3qIiIiIiIiIiIiHh3d2dnZ2dnZ2dnZ3g4iIiIiIh4eHh4eAd3d3d3d3d3d3d3uHh4eHh4eHh4eHhnd3d3d3d3d3d3d3g4eHh4eHh4eHh4d/d3d3d3d3d3d3d3yHh4eHh4eHh4eHhnd3d3d3d3d3d3d4hIeHh4eHh4eHh4d+d3d3d3d3d3d3d32Hh4eHh4eHh4eHhXd3d3d3d3d3d3d4hYeHh4eHh4eHh4d9d3d3d3d3d3d3d36Hh4eHh4eHh4eHhHd3d3d3d3d3d3d4hoeHh4eHh4eHh4d8d3d3d3d3d3d3d3+Hh4eHh4eHh4eHg3d3d3d3d3d3d3d4h4eHh4eHh4eHh4d7d3d3d3d3d3d3d4CHh4eHh4eHh4eHgnd3d3d3d3d3d3d5h4aGhoaGhoaGhoZ7eHh4eHh4eHh4eICGhoaGhoaGhoaGgXh4eHh4eHh4eHh6h4aGhoaGhoaGhoZ6eHh4eHh4eHh4eIGGhoaGhoaGhoaGgHh4eHh4eHh4eHh7hoaGhoaGhoaGhoZ5eHh4eHh4eHh4eIKGhoaGhoaGhoaGgHh4eHh4eHh4eHh7hoaGhoaGhoaGhoZ4eHh4eHh4eHh4eIKGhoaGhoaGhoaGf3h4eHh4eHh4eHh8hoaGhoaGhoaGhoV4eHh4eHh4eHh4eIOGhoaGhoaGhoaGfnh4eHh4eHh4eHh9hoaGhoaGhoaGhoR4eHh4eHh4eHh4eISGhoaGhoaGhoaGfXh4eHh4eHh4eHh+hoaGhoaGhoaGhoR4eHh4eHh5eXl5eYSFhYWFhYWFhYWFfXl5eXl5eXl5eXl/hYWFhYWFhYWFhYJ5eXl5eXl5eXl5eYWFhYWFhYWFhYWFfHl5eXl5eXl5eXmAhYWFhYWFhYWFhYF5eXl5eXl5eXl5eYWFhYWFhYWFhYWFe3l5eXl5eXl5eXmAhYWFhYWFhYWFhYF5eXl5eXl5eXl5eoWFhYWFhYWFhYWFe3l5eXl5eXl5eXmAhYWFhYWFhYWFhYB5eXl5eXl5eXl5e4WFhYWFhYWFhYWFenl5eXl5eXl5eXmBhYWFhYWFhYWFhYB5eXl5eXl5eXl5fIWFhYWFhYWFhYWFeXl5eXl5eXl5eXmChYWFhYWFhYWFhX95enp6f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f4CAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAA';
    var base64string = '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';
    chat_notify_audio = new Audio("data:audio/wav;base64," + base64string);

}

// Issue a pop-up notification using the web standard Notification API
// https://developer.mozilla.org/docs/Web/API/notification
var chat_issue_notification = function (theBody,theTitle) {
    if ( !chat_notify_granted || !chat_notify_enabled) {
        return;
    }
    var nIcon = "/images/icons/48/group.png";
    var options = {
        body: theBody,
        icon: nIcon,
        silent: false
    }
    var n = new Notification(theTitle,options);
    n.onclick = function (event) {
        setTimeout(n.close.bind(n), 300); 
    } 
    if(chat_notify_audio_enabled) {
      chat_notify_audio.play();
    }
}


function toggleChatNotificationAudio() {
	if(!chat_notify_audio_enabled) {
		chat_notify_audio_enabled = true;
        $('#toggle-notifications-audio-icon').removeClass('fa-volume-off');
        $('#toggle-notifications-audio-icon').addClass('fa-volume-up');
	}
	else {
		chat_notify_audio_enabled = false;
        $('#toggle-notifications-audio-icon').removeClass('fa-volume-up');
        $('#toggle-notifications-audio-icon').addClass('fa-volume-off');
	}
}

function toggleChatNotifications() {
	if(!chat_notify_enabled) {
		chat_notify_enabled = true;
        $('#toggle-notifications-icon').addClass('fa-bell');
        $('#toggle-notifications-icon').removeClass('fa-bell-slash-o');
        $('#toggle-notifications-audio').removeClass('disabled');
	}
	else {
		chat_notify_enabled = false;
        $('#toggle-notifications-icon').addClass('fa-bell-slash-o');
        $('#toggle-notifications-icon').removeClass('fa-bell');
        $('#toggle-notifications-audio').addClass('disabled');
	}
}

function chatJotGetLink() {
	reply = prompt("{{$linkurl}}");
	if(reply && reply.length) {
		$('#chat-rotator').spin('tiny');
		$.get('linkinfo?f=&url=' + reply, function(data) {
			addmailtext(data);
			$('#chat-rotator').spin(false);
		});
	}
}

function addmailtext(data) {
	var currentText = $("#chatText").val();
	$("#chatText").val(currentText + data);
}

function adjustFullscreenTopBarHeight() {
	$('#chatTopBar').height($(window).height() - $('#chatBottomBar').outerHeight(true) - $('.section-title-wrapper').outerHeight(true) - 16);
	$('#chatTopBar').scrollTop($('#chatTopBar').prop('scrollHeight'));
}

function adjustInlineTopBarHeight() {
	$('#chatTopBar').height($(window).height() - $('#chatBottomBar').outerHeight(true) - $('.section-title-wrapper').outerHeight(true) - $('nav').outerHeight(true) - 23);
	$('#chatTopBar').scrollTop($('#chatTopBar').prop('scrollHeight'));
}

function isMobile() {
	if( navigator.userAgent.match(/Android/i)
		 || navigator.userAgent.match(/webOS/i)
		 || navigator.userAgent.match(/iPhone/i)
		 || navigator.userAgent.match(/iPad/i)
		 || navigator.userAgent.match(/iPod/i)
		 || navigator.userAgent.match(/BlackBerry/i)
		 || navigator.userAgent.match(/Windows Phone/i)
	 ){
		return true;
	}
	else {
		 return false;
	}
}

$(function(){
	$('#chatText').keypress(function(e){
		if (e.keyCode == 13 && e.shiftKey||isMobile()) {
			//do nothing
		}
		else if (e.keyCode == 13) {
			e.preventDefault();
			$('#chat-form').trigger('submit');
		}
	});
});
</script>
