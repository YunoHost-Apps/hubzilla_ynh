<div class = "generic-content-wrapper-styled" id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

	<p>{{if ! $info.disabled}}<i class='toggleplugin fa {{if $status==on}}fa-check-square-o{{else}}fa-square-o{{/if}} admin-icons'></i>{{else}}<i class='fa fa-stop admin-icons'></i>{{/if}} {{$info.name}} - {{$info.version}}{{if ! $info.disabled}} : <a href="{{$baseurl}}/admin/{{$function}}/{{$plugin}}/?a=t&amp;t={{$form_security_token}}">{{$action}}</a>{{/if}}</p>

	{{if $info.disabled}}
	<p>{{$disabled}}</p>
	{{/if}}

	<p>{{$info.description}}</p>
	
	{{foreach $info.author as $a}}
	<p class="author">{{$str_author}}
		{{$a.name}}{{if $a.link}} {{$a.link}}{{/if}}
	</p>
	{{/foreach}}

	{{if $info.minversion}}
	<p class="versionlimit">{{$str_minversion}}{{$info.minversion}}</p>
	{{/if}}
	{{if $info.maxversion}}
	<p class="versionlimit">{{$str_maxversion}}{{$info.maxversion}}</p>
	{{/if}}
	{{if $info.minphpversion}}
	<p class="versionlimit">{{$str_minphpversion}}{{$info.minphpversion}}</p>
	{{/if}}
	{{if $info.serverroles}}
	<p class="versionlimit">{{$str_serverroles}}{{$info.serverroles}}</p>
	{{/if}}
	{{if $info.requires}}
	<p class="versionlimit">{{$str_requires}}{{$info.requires}}</p>
	{{/if}}


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
