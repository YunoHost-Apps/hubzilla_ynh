<link rel='stylesheet' type='text/css' href='{{$baseurl}}/library/fullcalendar/fullcalendar.css' />
<script language="javascript" type="text/javascript" src="{{$baseurl}}/library/moment/moment.min.js"></script>
<script language="javascript" type="text/javascript" src="{{$baseurl}}/library/fullcalendar/fullcalendar.min.js"></script>
<script language="javascript" type="text/javascript" src="{{$baseurl}}/library/fullcalendar/locale-all.js"></script>

<script>
	function showEvent(eventid) {
		$.get(
			'{{$baseurl}}{{$module_url}}/?id='+eventid,
			function(data){
				$.colorbox({ scrolling: false, html: data, onComplete: function() { $.colorbox.resize(); }});
			}
		);			
	}
	
	function doEventPreview() {
		$('#event-edit-preview').val(1);
		$.post('events',$('#event-edit-form').serialize(), function(data) {
			$.colorbox({ html: data });
		});
		$('#event-edit-preview').val(0);
	}

	function exportDate() {
		var moment = $('#events-calendar').fullCalendar('getDate');
		var sT = 'events/' + moment.year() + '/' + (moment.month() + 1) + '/export';
		window.location.href=sT;
	}
	
	function changeView(action, viewName) {
		$('#events-calendar').fullCalendar(action, viewName);
		var view = $('#events-calendar').fullCalendar('getView');

		if(view.type !== 'month' && !$('main').hasClass('fullscreen')) {
			$('#events-calendar').fullCalendar('option', 'height', 'auto');
		}
		else {
			$('#events-calendar').fullCalendar('option', 'height', '');
		}

		if($('main').hasClass('fullscreen')) {
			$('#events-calendar').fullCalendar('option', 'height', $(window).height() - $('.section-title-wrapper').outerHeight(true) - 2); // -2 is for border width (.generic-content-wrapper top and bottom) of .generic-content-wrapper
		}

		$('#title').text(view.title);
	}


	$(document).ready(function() {
		$('#events-calendar').fullCalendar({
			events: '{{$baseurl}}{{$module_url}}/json',
			header: false,
			lang: '{{$lang}}',
			firstDay: {{$first_day}},

			eventLimit: 3,

			monthNames: aStr['monthNames'],
			monthNamesShort: aStr['monthNamesShort'],
			dayNames: aStr['dayNames'],
			dayNamesShort: aStr['dayNamesShort'],

			allDayText: aStr['allday'],
			timeFormat: 'HH:mm',
			eventClick: function(calEvent, jsEvent, view) {
				showEvent(calEvent.id);
			},
			loading: function(isLoading, view) {
				$('#events-spinner').spin('tiny');
				$('#events-spinner > i').css('color', 'transparent');
				if(!isLoading) {
					$('#events-spinner').spin(false);
					$('#events-spinner > i').css('color', '');
					$('td.fc-day').dblclick(function() {
						openMenu('form');
						//window.location.href='/events/new?start='+$(this).data('date');
					});
				}
			},

			eventRender: function(event, element, view) {

				//console.log(view.name);
				if (event.item['author']['xchan_name']==null) return;

				switch(view.name){
					case "month":
					element.find(".fc-title").html(
						"<img src='{0}' style='height:12px;width:12px;' title='{1}'>&nbsp;<span title='{3}{4}'>{2}</span>".format(
							event.item['author']['xchan_photo_s'],
							event.item['author']['xchan_name'],
							event.title,
							event.item.description ? event.item.description + "\r\n\r\n" : '',
							event.item.location ? aStr['location'] + ': ' + event.item.location.replace(/(<([^>]+)>)/ig,"") : ''
					));
					break;
					case "agendaWeek":
					element.find(".fc-title").html(
						"<img src='{0}' style='height:12px;width:12px;'>&nbsp;{1}: <span title='{3}{4}'>{2}</span>".format(
							event.item['author']['xchan_photo_s'],
							event.item['author']['xchan_name'],
							event.title,
							event.item.description ? event.item.description + "\r\n\r\n" : '',
							event.item.location ? aStr['location'] + ': ' + event.item.location.replace(/(<([^>]+)>)/ig,"") : ''
					));
					break;
					case "agendaDay":
					element.find(".fc-title").html(
						"<img src='{0}' style='height:12px;width:12px;'>&nbsp;{1}: <span title='{3}{4}'>{2}</span>".format(
							event.item['author']['xchan_photo_s'],
							event.item['author']['xchan_name'],
							event.title,
							event.item.description ? event.item.description + "\r\n\r\n" : '',
							event.item.location ? aStr['location'] + ': ' + event.item.location.replace(/(<([^>]+)>)/ig,"") : ''
					));
					break;
				}
			}
			
		});
		
		// center on date
		// @fixme does not work for cal/$nick module_url
		var args=location.href.replace(baseurl,"").split("/");
		{{if $modparams == 2}}
		if (args.length>=5) {
			$("#events-calendar").fullCalendar('gotoDate',args[3] , args[4]-1);
		}
		{{else}}
		if (args.length>=4) {
			$("#events-calendar").fullCalendar('gotoDate',args[2] , args[3]-1);
		}
		{{/if}} 
		
		// show event popup
		var hash = location.hash.split("-")
		if (hash.length==2 && hash[0]=="#link") showEvent(hash[1]);
		
		// echo the title
		var view = $('#events-calendar').fullCalendar('getView');
		$('#title').text(view.title);

		// shift the finish time date on start time date change automagically
		var origsval = $('#id_start_text').val();
		$('#id_start_text').change(function() {
			var origfval = $('#id_finish_text').val();
			if(origfval) {
				var sval = $('#id_start_text').val();
				var diff = moment(sval).diff(origsval);
				var fval = moment(origfval).add(diff, 'millisecond').format("YYYY-MM-DD HH:mm");
				$('#id_finish_text').val(fval);
				origsval = sval;
			}
		});

	});

</script>

