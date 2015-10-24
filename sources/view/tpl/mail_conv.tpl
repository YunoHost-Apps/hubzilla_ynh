<div id="mail-{{$mail.id}}" class="mail-conv-outside-wrapper">
	<div class="mail-conv-sender" >
		<a href="{{$mail.from_url}}"><img class="mail-conv-sender-photo" src="{{$mail.from_photo}}" alt="{{$mail.from_name}}" /></a>
	</div>
	<div class="mail-conv-detail">
		{{if $mail.is_recalled}}<strong>{{$mail.is_recalled}}</strong>{{/if}}
		<div class="mail-conv-sender-name"><a href="{{$mail.from_url}}">{{$mail.from_name}}</a></div>
		<div class="mail-conv-date autotime wall-item-ago" title="{{$mail.date}}">{{$mail.date}}</div>
	</div>
	<div class="clear"></div>
	<div class="mail-conv-content">
		<div class="mail-conv-body">
			{{$mail.body}}
			<div class="clear"></div>
		</div>
		<div class="pull-right dropdown">
			<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" id="mail-item-menu-{{$mail.id}}">
				<i class="icon-caret-down"></i>
			</button>
			<ul class="dropdown-menu" role="menu" aria-labelledby="mail-item-menu-{{$mail.id}}">
				{{if $mail.can_recall}}
				<li>
					<a href="mail/{{$mail.mailbox}}/recall/{{$mail.id}}" title="{{$mail.recall}}" id="mail-conv-recall-icon-{{$mail.id}}"><i class="icon-undo mail-icons"></i>&nbsp;{{$mail.recall}}</a>
				</li>
				{{/if}}
				<li>
					<a href="#" onclick="dropItem('mail/{{$mail.mailbox}}/drop/{{$mail.id}}', '#mail-{{$mail.id}}'); return false;" title="{{$mail.delete}}" id="mail-conv-delete-icon-{{$mail.id}}"><i class="icon-trash mail-icons"></i>&nbsp;{{$mail.delete}}</a>
				</li>
				{{if $mail.can_recall}}
				<li class="divider"></li>
				<li>
					<a href="dreport/mail/{{$mail.mid}}" title="{{$mail.dreport}}" id="mail-conv-dreport-icon-{{$mail.id}}">{{$mail.dreport}}</a>
				</li>
				{{/if}}
			</ul>

		</div>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
</div>
