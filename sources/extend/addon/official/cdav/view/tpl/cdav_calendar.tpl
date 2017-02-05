<script>

var new_event = [];
var new_event_id = Math.random().toString(36).substring(7);
var views = {'month' : '{{$month}}', 'agendaWeek' : '{{$week}}', 'agendaDay' : '{{$day}}', 'listMonth' : '{{$list_month}}', 'listWeek' : '{{$list_week}}', 'listDay' : '{{$list_day}}'};

$(document).ready(function() {
	$('#calendar').fullCalendar({
		eventSources: [ {{$sources}} ],

		header: false,

		lang: '{{$lang}}',
		firstDay: {{$first_day}},

		monthNames: aStr['monthNames'],
		monthNamesShort: aStr['monthNamesShort'],
		dayNames: aStr['dayNames'],
		dayNamesShort: aStr['dayNamesShort'],
		allDayText: aStr['allday'],

		timeFormat: 'HH:mm',
		timezone: 'local',

		defaultTimedEventDuration: '01:00:00',
		snapDuration: '00:15:00',

		dayClick: function(date, jsEvent, view) {

			if(new_event.length)
				$('#calendar').fullCalendar( 'removeEventSource', new_event);

			$('#event_uri').val('');
			$('#id_title').val('New event');
			$('#calendar_select').val($("#calendar_select option:first").val()).attr('disabled', false);
			$('#id_dtstart').val(date.format());
			$('#id_dtend').val(date.hasTime() ? date.add(1, 'hours').format() : date.add(1, 'days').format());
			$('#id_description').val('');
			$('#id_location').val('');
			$('#event_submit').val('create_event').html('Create');
			$('#event_delete').hide();

			new_event = [{ id: new_event_id, title  : 'New event', start: $('#id_dtstart').val(), end: $('#id_dtend').val(), editable: true, color: '#bbb' }]
			$('#calendar').fullCalendar( 'addEventSource', new_event);
		},

		eventClick: function(event, jsEvent, view) {

			if(event.id == new_event_id) {
				$(window).scrollTop(0);
				$('.section-content-tools-wrapper, #event_form_wrapper').show();
				$('#recurrence_warning').hide();
				$('#id_title').focus().val('');
				return false;
			}

			if($('main').hasClass('fullscreen') && view.type !== 'month' && event.rw)
				$('#calendar').fullCalendar('option', 'height', 'auto');

			if(new_event.length && event.rw) {
				$('#calendar').fullCalendar( 'removeEventSource', new_event);
			}

			if(!event.recurrent && event.rw) {
				var start_clone = moment(event.start);
				var noend_allday = start_clone.add(1, 'day').format('YYYY-MM-DD');

				$(window).scrollTop(0);
				$('.section-content-tools-wrapper, #event_form_wrapper').show();
				$('#recurrence_warning').hide();
				$('#id_title').focus();

				$('#event_uri').val(event.uri);
				$('#id_title').val(event.title);
				$('#calendar_select').val(event.calendar_id[0] + ':' + event.calendar_id[1]).attr('disabled', true);
				$('#id_dtstart').val(event.start.format());
				$('#id_dtend').val(event.end ? event.end.format() : event.start.hasTime() ? '' : noend_allday);
				$('#id_description').val(event.description);
				$('#id_location').val(event.location);
				$('#event_submit').val('update_event').html('Update');
				$('#event_delete').show();
			}
			else if(event.recurrent && event.rw) {
				$('.section-content-tools-wrapper, #recurrence_warning').show();
				$('#event_form_wrapper').hide();
				$('#event_uri').val(event.uri);
				$('#calendar_select').val(event.calendar_id[0] + ':' + event.calendar_id[1]).attr('disabled', true);
			}
		},

		eventResize: function(event, delta, revertFunc) {

			$('#id_title').val(event.title);
			$('#id_dtstart').val(event.start.format());
			$('#id_dtend').val(event.end.format());

			$.post( 'cdav/calendar', {
				'update': 'resize',
				'id[]': event.calendar_id,
				'uri': event.uri,
				'dtstart': event.start ? event.start.format() : '',
				'dtend': event.end ? event.end.format() : ''
			})
			.fail(function() {
				revertFunc();
			});
		},

		eventDrop: function(event, delta, revertFunc) {

			var start_clone = moment(event.start);
			var noend_allday = start_clone.add(1, 'day').format('YYYY-MM-DD');

			$('#id_title').val(event.title);
			$('#id_dtstart').val(event.start.format());
			$('#id_dtend').val(event.end ? event.end.format() : event.start.hasTime() ? '' : noend_allday);

			$.post( 'cdav/calendar', {
				'update': 'drop',
				'id[]': event.calendar_id,
				'uri': event.uri,
				'dtstart': event.start ? event.start.format() : '',
				'dtend': event.end ? event.end.format() : event.start.hasTime() ? '' : noend_allday
			})
			.fail(function() {
				revertFunc();
			});
		},

		loading: function(isLoading, view) {
			$('#events-spinner').spin('tiny');
			$('#events-spinner > i').css('color', 'transparent');
			if(!isLoading) {
				$('#events-spinner').spin(false);
				$('#events-spinner > i').css('color', '');
			}
		}
	});

	// echo the title
	var view = $('#calendar').fullCalendar('getView');

	$('#title').text(view.title);

	$('#view_selector').html('<i class="fa fa-caret-down"></i> ' + views[view.name]);

	$('.color-edit').colorpicker({ input: '.color-edit-input' });

	$(document).on('click','#fullscreen-btn', on_fullscreen);
	$(document).on('click','#inline-btn', on_inline);

	$(document).on('click','#event_submit', on_submit);
	$(document).on('click','#event_more', on_more);
	$(document).on('click','#event_cancel, #event_cancel_recurrent', reset_form);
	$(document).on('click','#event_delete, #event_delete_recurrent', on_delete);

});

function changeView(action, viewName) {
	$('#calendar').fullCalendar(action, viewName);
	var view = $('#calendar').fullCalendar('getView');

	if($('main').hasClass('fullscreen'))
		if(view.name !== 'month')
			$('.section-content-tools-wrapper').css('display') === 'none' ? on_fullscreen() : on_inline() ;
		else
			on_fullscreen();
	else
		on_inline();

	$('#title').text(view.title);
	$('#view_selector').html('<i class="fa fa-caret-down"></i> ' + views[view.name]);
}

function add_remove_json_source(source, color, editable, status) {

	if(status === undefined)
		status = 'fa-calendar-check-o';

	if(status === 'drop') {
		reset_form();
		$('#calendar').fullCalendar( 'removeEventSource', source );
		return;
	}

	var parts = source.split('/');
	var id = parts[4];

	var selector = '#calendar-btn-' + id;

	if($(selector).hasClass('fa-calendar-o')) {
		$('#calendar').fullCalendar( 'addEventSource', { url: source, color: color, editable: editable });
		$(selector).removeClass('fa-calendar-o');
		$(selector).addClass(status);
		$.get('/cdav/calendar/switch/' + id + '/1');
	}
	else {
		$('#calendar').fullCalendar( 'removeEventSource', source );
		$(selector).removeClass(status);
		$(selector).addClass('fa-calendar-o');
		$.get('/cdav/calendar/switch/' + id + '/0');
	}
}

function on_fullscreen() {
	var view = $('#calendar').fullCalendar('getView');
	if(($('.section-content-tools-wrapper').css('display') === 'none') || ($('.section-content-tools-wrapper').css('display') !== 'none' && view.type === 'month'))
		$('#calendar').fullCalendar('option', 'height', $(window).height() - $('.section-title-wrapper').outerHeight(true) - 2); // -2 is for border width (.generic-content-wrapper top and bottom) of .generic-content-wrapper
}

function on_inline() {
	var view = $('#calendar').fullCalendar('getView');
	((view.type === 'month') ? $('#calendar').fullCalendar('option', 'height', '') : $('#calendar').fullCalendar('option', 'height', 'auto'));
}

function on_submit() {
	$.post( 'cdav/calendar', {
		'submit': $('#event_submit').val(),
		'target': $('#calendar_select').val(),
		'uri': $('#event_uri').val(),
		'title': $('#id_title').val(),
		'dtstart': $('#id_dtstart').val(),
		'dtend': $('#id_dtend').val(),
		'description': $('#id_description').val(),
		'location': $('#id_location').val()
	})
	.done(function() {
		$('#calendar').fullCalendar( 'refetchEventSources', [ {{$sources}} ] );
		reset_form();
	});
}

function on_delete() {
	$.post( 'cdav/calendar', {
		'delete': 'delete',
		'target': $('#calendar_select').val(),
		'uri': $('#event_uri').val(),
	})
	.done(function() {
		$('#calendar').fullCalendar( 'refetchEventSources', [ {{$sources}} ] );
		reset_form();
	});
}

function reset_form() {
	$('.section-content-tools-wrapper, #event_form_wrapper, #recurrence_warning').hide();

	$('#event_submit').val('');
	$('#calendar_select').val('');
	$('#event_uri').val('');
	$('#id_title').val('');
	$('#id_dtstart').val('');
	$('#id_dtend').val('');

	if(new_event.length)
		$('#calendar').fullCalendar( 'removeEventSource', new_event);

	if($('#more_block').hasClass('open'))
		on_more();

	if($('main').hasClass('fullscreen'))
		on_fullscreen();
}

function on_more() {
	if($('#more_block').hasClass('open')) {
		$('#event_more').html('<i class="fa fa-caret-down"></i> {{$more}}');
		$('#more_block').removeClass('open').hide();
	}
	else {
		$('#event_more').html('<i class="fa fa-caret-up"></i> {{$less}}');
		$('#more_block').addClass('open').show();
	}
}

</script>

<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			<div class="dropdown">
				<button id="view_selector" type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown"><i class="fa fa-caret-down"></i>&nbsp;</button>
				<ul class="dropdown-menu">
					<li><a href="#" onclick="changeView('changeView', 'month'); return false;">{{$month}}</a></li>
					<li><a href="#" onclick="changeView('changeView', 'agendaWeek'); return false;">{{$week}}</a></li>
					<li><a href="#" onclick="changeView('changeView', 'agendaDay'); return false;">{{$day}}</a></li>
					<li class="divider"></li>
					<li><a href="#" onclick="changeView('changeView', 'listMonth'); return false;">{{$list_month}}</a></li>
					<li><a href="#" onclick="changeView('changeView', 'listWeek'); return false;">{{$list_week}}</a></li>
					<li><a href="#" onclick="changeView('changeView', 'listDay'); return false;">{{$list_day}}</a></li>
				</ul>
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
	<div class="section-content-tools-wrapper" style="display: none">
		<div id="recurrence_warning" style="display: none">
			<div class="section-content-warning-wrapper">
				{{$recurrence_warning}}
			</div>
			<div>
				<button id="event_delete_recurrent" type="button" class="btn btn-danger btn-sm">{{$delete_all}}</button>
				<button id="event_cancel_recurrent" type="button" class="btn btn-default btn-sm">{{$cancel}}</button>
			</div>
		</div>
		<div id="event_form_wrapper" style="display: none">
			<form id="event_form" method="post" action="">
				<input id="event_uri" type="hidden" name="uri" value="">
				{{include file="field_input.tpl" field=$title}}
				<label for="calendar_select">{{$calendar_select_label}}</label>
				<select id="calendar_select" name="target" class="form-control form-group">
					{{foreach $writable_calendars as $writable_calendar}}
					<option value="{{$writable_calendar.id.0}}:{{$writable_calendar.id.1}}">{{$writable_calendar.displayname}}{{if $writable_calendar.sharer}} ({{$writable_calendar.sharer}}){{/if}}</option>
					{{/foreach}}
				</select>
				<div id="more_block" style="display: none;">
					{{include file="field_input.tpl" field=$dtstart}}
					{{include file="field_input.tpl" field=$dtend}}
					{{include file="field_textarea.tpl" field=$description}}
					{{include file="field_textarea.tpl" field=$location}}
				</div>
				<div class="form-group">
					<div class="pull-right">
						<button id="event_more" type="button" class="btn btn-default btn-sm"><i class="fa fa-caret-down"></i> {{$more}}</button>
						<button id="event_submit" type="button" value="" class="btn btn-primary btn-sm"></button>

					</div>
					<div>
						<button id="event_delete" type="button" class="btn btn-danger btn-sm">{{$delete}}</button>
						<button id="event_cancel" type="button" class="btn btn-default btn-sm">{{$cancel}}</button>
					</div>
					<div class="clear"></div>
				</div>
			</form>
		</div>
	</div>
	<div class="section-content-wrapper-np">
		<div id="calendar"></div>
	</div>
</div>
