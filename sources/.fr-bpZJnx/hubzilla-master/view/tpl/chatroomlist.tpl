<div id="chatroom_list" class="widget">
	<h3>{{$header}}</h3>
	<ul class="nav nav-pills nav-stacked">
		<li><a href="{{$baseurl}}/chat/{{$nickname}}">{{$overview}}</a></li>
		{{foreach $items as $item}}
		<li><a href="{{$baseurl}}/chat/{{$nickname}}/{{$item.cr_id}}"><span class="badge pull-right">{{$item.cr_inroom}}</span>{{$item.cr_name}}</a></li>
		{{/foreach}}
	</ul>
</div>

