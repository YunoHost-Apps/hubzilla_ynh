<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			<button id="fullscreen-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen();"><i class="fa fa-expand"></i></button>
			<button id="inline-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen(false);"><i class="fa fa-compress"></i></button>
			{{if $mailbox == 'combined'}}
			<a class="btn btn-xs btn-danger" href="mail/{{$mailbox}}/dropconv/{{$thread_id}}" onclick="return confirmDelete();"><i class="fa fa-trash-o"></i> {{$delete}}</a>
			{{/if}}
		</div>
		<h2>{{$prvmsg_header}}</h2>
		<div class="clear"></div>
	</div>
	<div class="section-content-wrapper">
		{{foreach $mails as $mail}}
			{{include file="mail_conv.tpl"}}
		{{/foreach}}

		{{if $canreply}}
		{{include file="prv_message.tpl"}}
		{{else}}
		{{$unknown_text}}
		{{/if}}
	</div>
</div>
