<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			<div class="dropdown">
				<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown"><i class="fa fa-caret-down"></i>&nbsp;{{$view_label}}</button>
				<ul class="dropdown-menu">
					<li><a href="#" onclick="changeView('changeView', 'month'); return false;">{{$month}}</a></li>
					<li><a href="#" onclick="changeView('changeView', 'agendaWeek'); return false;">{{$week}}</a></li>
					<li><a href="#" onclick="changeView('changeView', 'agendaDay'); return false;">{{$day}}</a></li>
				</ul>
				<button class="btn btn-success btn-xs" onclick="openClose('form');">{{$new_event.1}}</button>
				<div class="btn-group">
					<button class="btn btn-default btn-xs" onclick="changeView('prev', false);" title="{{$prev}}"><i class="fa fa-backward"></i></button>
					<button id="events-spinner" class="btn btn-default btn-xs" onclick="changeView('today', false);" title="{{$today}}"><i class="fa fa-bullseye"></i></button>
					<button class="btn btn-default btn-xs" onclick="changeView('next', false);" title="{{$next}}"><i class="fa fa-forward"></i></button>
				</div>
				<button id="fullscreen-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen();"><i class="fa fa-expand"></i></button>
				<button id="inline-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen(false);"><i class="fa fa-compress"></i></button>
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
