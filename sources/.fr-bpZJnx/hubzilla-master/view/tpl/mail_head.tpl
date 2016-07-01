<div class="widget">
	<h3>{{$header}}</h3>
	<ul class="nav nav-pills nav-stacked">
	{{foreach $messages as $message}}
		{{include file="mail_list.tpl"}}
	{{/foreach}}
	</ul>
</div>
