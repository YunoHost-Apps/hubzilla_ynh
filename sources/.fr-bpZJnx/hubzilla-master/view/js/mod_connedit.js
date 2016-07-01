$(document).ready(function() {

	$('form').areYouSure({'addRemoveFieldsMarksDirty':true, 'message': aStr['leavethispage'] }); // Warn user about unsaved settings

	if(typeof(after_following) !== 'undefined' && after_following) {
		if(typeof(connectDefaultShare) !== 'undefined')
			connectDefaultShare();
		else
			connectFullShare();
	}

	$('#id_pending').click(function() {
		if(typeof(connectDefaultShare) !== 'undefined')
			connectDefaultShare();
		else
			connectFullShare();
	});

});

function connectFullShare() {
	$('.abook-edit-me').each(function() {
		if(! $(this).is(':disabled'))
			$(this).removeAttr('checked');
	});
	$('#me_id_perms_view_stream').attr('checked','checked');
	$('#me_id_perms_view_profile').attr('checked','checked');
	$('#me_id_perms_view_contacts').attr('checked','checked');
	$('#me_id_perms_view_storage').attr('checked','checked');
	$('#me_id_perms_view_pages').attr('checked','checked');
	$('#me_id_perms_send_stream').attr('checked','checked');
	$('#me_id_perms_post_wall').attr('checked','checked');
	$('#me_id_perms_post_comments').attr('checked','checked');
	$('#me_id_perms_post_mail').attr('checked','checked');
	$('#me_id_perms_chat').attr('checked','checked');
	$('#me_id_perms_view_storage').attr('checked','checked');
	$('#me_id_perms_republish').attr('checked','checked');
	$('#me_id_perms_post_like').attr('checked','checked');
}
