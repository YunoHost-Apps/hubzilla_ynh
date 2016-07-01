<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="btn-group pull-right">
			<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{$sort}}">
				<i class="fa fa-sort"></i>
			</button>
			<ul class="dropdown-menu">
				<li><a href="directory?f=&order=date{{$suggest}}">{{$date}}</a></li>
				<li><a href="directory?f=&order=normal{{$suggest}}">{{$normal}}</a></li>
				<li><a href="directory?f=&order=reversedate{{$suggest}}">{{$reversedate}}</a></li>
				<li><a href="directory?f=&order=reverse{{$suggest}}">{{$reverse}}</a></li>
			</ul>
		</div>
		<h2>{{$dirlbl}}{{if $search}}:&nbsp;{{$safetxt}}{{/if}}</h2>
		<div class="clear"></div>
	</div>
	{{foreach $entries as $entry}}
		{{include file="direntry.tpl"}}
	{{/foreach}}
	<div id="page-end"></div>
	<div class="clear"></div>
</div>
<script>$(document).ready(function() { loadingPage = false;});</script>
<div id="page-spinner"></div>
