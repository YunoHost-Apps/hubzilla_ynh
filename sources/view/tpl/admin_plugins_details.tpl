<div class = "generic-content-wrapper-styled" id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
	
	<p><i class='toggleplugin {{if $status==on}}icon-check{{else}}icon-check-empty{{/if}} admin-icons'></i> {{$info.name}} - {{$info.version}} : <a href="{{$baseurl}}/admin/{{$function}}/{{$plugin}}/?a=t&amp;t={{$form_security_token}}">{{$action}}</a></p>
	<p>{{$info.description}}</p>
	
	{{foreach $info.author as $a}}
	<p class="author">{{$str_author}}
		{{$a.name}}{{if $a.link}} {{$a.link}}{{/if}}
	</p>
	{{/foreach}}


	{{foreach $info.maintainer as $a}}
	<p class="maintainer">{{$str_maintainer}}
		{{$a.name}}{{if $a.link}} {{$a.link}}{{/if}}
	</p>
	{{/foreach}}
	
	{{if $screenshot}}
	<a href="{{$screenshot.0}}" class='screenshot'><img src="{{$screenshot.0}}" alt="{{$screenshot.1}}" /></a>
	{{/if}}

	{{if $admin_form}}
	<h3>{{$settings}}</h3>
	<form method="post" action="{{$baseurl}}/admin/{{$function}}/{{$plugin}}/">
		{{$admin_form}}
	</form>
	{{/if}}

	{{if $readme}}
	<h3>Readme</h3>
	<div id="plugin_readme">
		{{$readme}}
	</div>
	{{/if}}
</div>
