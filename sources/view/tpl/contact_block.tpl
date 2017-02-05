<div id="contact-block" class="widget">
	<h3>{{$contacts}}</h3>
	{{if $micropro}}
	{{if $viewconnections}}
	<a class="allcontact-link" href="viewconnections/{{$nickname}}">{{$viewconnections}}</a>
	{{/if}}
	<div class='contact-block-content'>
	{{foreach $micropro as $m}}
		{{$m}}
	{{/foreach}}
	</div>
	{{/if}}
</div>
<div class="clear"></div>
