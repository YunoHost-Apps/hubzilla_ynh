<li>
	<a href="mail/{{$message.mailbox}}/{{$message.id}}" class="{{if $message.selected}}active{{/if}}">
		<span class="{{if $message.seen}}seen{{else}}unseen{{/if}}">{{$message.subject}}</span><br>
		<span class="conv-participants">{{$message.from_name}} > {{$message.to_name}}</span><br>
		<span class="wall-item-ago autotime" title="{{$message.date}}">{{$message.date}}</span>
	</a>
</li>
