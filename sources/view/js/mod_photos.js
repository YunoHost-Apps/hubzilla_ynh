/**
 * JavaScript used by mod/photos
 */
$(document).ready(function() {

	$("#photo-edit-newtag").contact_autocomplete(baseurl + '/acl', 'a', false, function(data) {
		$("#photo-edit-newtag").val('@' + data.name);
	});

	$('textarea').bbco_autocomplete('bbcode');
	showHideBodyTextarea();

});

function showHideBodyTextarea() {
	if( $('#id_visible').is(':checked'))
		$('#body-textarea').slideDown();
	else
		$('#body-textarea').slideUp();
}
