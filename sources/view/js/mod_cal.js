/**
 * JavaScript for mod/cal
 */

$(document).ready( function() {
	$(document).on('click','#fullscreen-btn', on_fullscreen);
	$(document).on('click','#inline-btn', on_inline);
});

function on_fullscreen() {
	var view = $('#events-calendar').fullCalendar('getView');
	if(view.type === 'month') {
		$('#events-calendar').fullCalendar('option', 'height', $(window).height() - $('.section-title-wrapper').outerHeight(true) - 2); // -2 is for border width (top and bottom) of .generic-content-wrapper
	}
}

function on_inline() {
	var view = $('#events-calendar').fullCalendar('getView');
	if(view.type === 'month') {
		$('#events-calendar').fullCalendar('option', 'height', '');
	}
}
