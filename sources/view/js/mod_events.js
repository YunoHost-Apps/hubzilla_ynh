/**
 * JavaScript for mod/events
 */

$(document).ready( function() {
	enableDisableFinishDate();
	$('#comment-edit-text-desc, #comment-edit-text-loc').bbco_autocomplete('bbcode');
});

function enableDisableFinishDate() {
	if( $('#id_nofinish').is(':checked'))
		$('#id_finish_text').prop("disabled", true);
	else
		$('#id_finish_text').prop("disabled", false);
}
