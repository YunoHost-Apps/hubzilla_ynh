/**
 * JavaScript for mod/events
 */

$(document).ready( function() {

	enableDisableFinishDate();

});

function enableDisableFinishDate() {
	if( $('#id_nofinish').is(':checked'))
		$('#id_finish_text').prop("disabled", true);
	else
		$('#id_finish_text').prop("disabled", false);
}
