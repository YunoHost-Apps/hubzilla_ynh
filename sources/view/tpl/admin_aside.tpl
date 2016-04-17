<script>
	// update pending count //
	$(function(){

		$("nav").bind('nav-update',  function(e,data){
			var elm = $('#pending-update');
			var register = $(data).find('register').text();
			if (register=="0") { register=""; elm.hide();} else { elm.show(); }
			elm.html(register);
		});
	});
</script>
<div class="widget">
<h3>{{$admtxt}}</h3>
<ul class="nav nav-pills nav-stacked">
	{{foreach $admin as $link}}
	<li><a href='{{$link.0}}'>{{$link.1}}{{if $link.3}}<span id='{{$link.3}}' title='{{$link.4}}'></span>{{/if}}</a></li>
	{{/foreach}}
</ul>
</div>

{{if $admin.update}}
<ul class="nav nav-pills nav-stacked">
	<li><a href='{{$admin.update.0}}'>{{$admin.update.1}}</a></li>
	<li><a href=''>Important Changes</a></li>
</ul>
{{/if}}


{{if $plugins}}
<div class="widget">
<h3>{{$plugadmtxt}}</h3>
<ul class="nav nav-pills nav-stacked">
	{{foreach $plugins as $l}}
	<li><a href='{{$l.0}}'>{{$l.1}}</a></li>
	{{/foreach}}
</ul>
</div>
{{/if}}
	
<div class="widget">	
<h3>{{$logtxt}}</h3>
<ul class="nav nav-pills nav-stacked">
	<li><a href='{{$logs.0}}'>{{$logs.1}}</a></li>
</ul>
</div>
