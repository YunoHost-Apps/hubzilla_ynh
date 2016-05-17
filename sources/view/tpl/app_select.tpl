{{if $authed}}
<div class="widget">
<h3>{{$title}}</h3>
<ul class="nav nav-pills nav-stacked">
<li><a href="appman"><i class="fa fa-plus"></i>&nbsp;&nbsp;{{$new}}</a></li>
<li><a href="apps/edit"><i class="fa fa-pencil"></i>&nbsp;&nbsp;{{$edit}}</a></li>
</ul>
</div>
{{/if}}

