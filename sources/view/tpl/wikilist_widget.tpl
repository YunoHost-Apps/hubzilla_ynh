<div id="wiki_list" class="widget">
	<h3>{{$header}}</h3>
	<ul class="nav nav-pills nav-stacked">
		{{foreach $wikis as $wiki}}
		<li><a href="/wiki/{{$channel}}/{{$wiki.urlName}}/Home" title="{{$view}}">{{$wiki.title}}</a></li> 
		{{/foreach}}
	</ul>
</div>
