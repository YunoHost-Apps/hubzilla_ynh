<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			<button class="btn btn-success btn-xs" onclick="openClose('form'); closeMenu('event-tools');">{{$new_event.1}}</button>
			<div class="btn-group">
				<button class="btn btn-default btn-xs" onclick="changeView('prev', false);" title="{{$prev}}"><i class="icon-backward"></i></button>
				<button id="events-spinner" class="btn btn-default btn-xs" onclick="changeView('today', false);" title="{{$today}}"><i class="icon-bullseye"></i></button>
				<button class="btn btn-default btn-xs" onclick="changeView('next', false);" title="{{$next}}"><i class="icon-forward"></i></button>
			</div>
		</div>
		<h2 id="title"></h2>
		<div class="clear"></div>
	</div>
	<div id="form" class="section-content-tools-wrapper"{{if !$expandform}} style="display:none;"{{/if}}>
		{{$form}}
	</div>
	<div class="clear"></div>
	<div class="section-content-wrapper-np">
		<div id="events-calendar"></div>
	</div>
</div>
