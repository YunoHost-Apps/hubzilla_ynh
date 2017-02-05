/**
 * JavaScript for mod/events
 */

$(document).ready( function() {
	enableDisableFinishDate();
	$('#comment-edit-text-desc, #comment-edit-text-loc').bbco_autocomplete('bbcode');

	$(document).on('click','#fullscreen-btn', on_fullscreen);
	$(document).on('click','#inline-btn', on_inline);
});


function enableDisableFinishDate() {
	if( $('#id_nofinish').is(':checked'))
		$('#id_finish_text').prop("disabled", true);
	else
		$('#id_finish_text').prop("disabled", false);
}

function on_fullscreen() {
	$('#events-calendar').fullCalendar('option', 'height', $(window).height() - $('.section-title-wrapper').outerHeight(true) - 2); // -2 is for border width (.generic-content-wrapper top and bottom) of .generic-content-wrapper
}

function on_inline() {
	var view = $('#events-calendar').fullCalendar('getView');
	((view.type === 'month') ? $('#events-calendar').fullCalendar('option', 'height', '') : $('#events-calendar').fullCalendar('option', 'height', 'auto'));
}
