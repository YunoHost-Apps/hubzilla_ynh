/**
 * JavaScript used by mod/settings
 */

$(document).ready(function() {
	$('#settings-form').areYouSure({'addRemoveFieldsMarksDirty':true, 'message': aStr['leavethispage'] }); // Warn user about unsaved settings

	$('.token-mirror').html($('#id_token').val());
	$('#id_token').keyup( function() { $('.token-mirror').html($('#id_token').val()); });

	previewTheme($('#id_theme')[0]);

	$("#id_permissions_role").change(function() {
		var role = $("#id_permissions_role").val();
		if(role == 'custom')
			$('#advanced-perm').show();
		else
			$('#advanced-perm').hide();
	});
});


function setTheme(elm) {
	$('#settings-form').submit();
}


function previewTheme(elm) {
	theme = $(elm).val();
	$.getJSON('theme_info/' + theme,function(data) {
		$('#theme-preview').html('<div id="theme-desc">' + data.desc + '</div><div id="theme-version">' + data.version + '</div><div id="theme-credits">' + data.credits + '</div><a href="' + data.img + '"><img src="' + data.img + '" style="max-width:100%; max-height:300px" alt="' + theme + '"></a>');
		$('#id_schema').empty();
		$(data.schemas).each(function(index,item) {
			$('<option/>',{value:item['key'],text:item['val']}).appendTo('#id_schema');
		});
		$('#custom-settings-content .section-content-tools-wrapper').html(data.config);
	});
}



/**
 * 0 nobody
 * 1 perms_specific
 * 2 perms_contacts
 * 3 perms_pending
 * 4 perms_site
 * 5 perms_network
 * 6 perms_authed
 * 7 perms_public
 */


function channel_privacy_macro(n) {
	if(n == 0) {
		$('#id_view_stream option').eq(0).attr('selected','selected');
		$('#id_view_profile option').eq(0).attr('selected','selected');
		$('#id_view_contacts option').eq(0).attr('selected','selected');
		$('#id_view_storage option').eq(0).attr('selected','selected');
		$('#id_view_pages option').eq(0).attr('selected','selected');
		$('#id_send_stream option').eq(0).attr('selected','selected');
		$('#id_post_wall option').eq(0).attr('selected','selected');
		$('#id_post_comments option').eq(0).attr('selected','selected');
		$('#id_post_mail option').eq(0).attr('selected','selected');
		$('#id_tag_deliver option').eq(0).attr('selected','selected');
		$('#id_chat option').eq(0).attr('selected','selected');
		$('#id_write_storage option').eq(0).attr('selected','selected');
		$('#id_write_pages option').eq(0).attr('selected','selected');
		$('#id_delegate option').eq(0).attr('selected','selected');
		$('#id_republish option').eq(0).attr('selected','selected');
		$('#id_post_like option').eq(0).attr('selected','selected');
		$('#id_profile_in_directory_onoff .off').removeClass('hidden');
		$('#id_profile_in_directory_onoff .on').addClass('hidden');
		$('#id_profile_in_directory').val(0);
	}
	if(n == 1) {
		$('#id_view_stream option').eq(1).attr('selected','selected');
		$('#id_view_profile option').eq(1).attr('selected','selected');
		$('#id_view_contacts option').eq(1).attr('selected','selected');
		$('#id_view_storage option').eq(1).attr('selected','selected');
		$('#id_view_pages option').eq(1).attr('selected','selected');
		$('#id_send_stream option').eq(1).attr('selected','selected');
		$('#id_post_wall option').eq(1).attr('selected','selected');
		$('#id_post_comments option').eq(1).attr('selected','selected');
		$('#id_post_mail option').eq(1).attr('selected','selected');
		$('#id_tag_deliver option').eq(1).attr('selected','selected');
		$('#id_chat option').eq(1).attr('selected','selected');
		$('#id_write_storage option').eq(1).attr('selected','selected');
		$('#id_write_pages option').eq(1).attr('selected','selected');
		$('#id_delegate option').eq(0).attr('selected','selected');
		$('#id_republish option').eq(0).attr('selected','selected');
		$('#id_post_like option').eq(1).attr('selected','selected');
		$('#id_profile_in_directory_onoff .off').removeClass('hidden');
		$('#id_profile_in_directory_onoff .on').addClass('hidden');
		$('#id_profile_in_directory').val(0);
	}
	if(n == 2) {
		$('#id_view_stream option').eq(7).attr('selected','selected');
		$('#id_view_profile option').eq(7).attr('selected','selected');
		$('#id_view_contacts option').eq(7).attr('selected','selected');
		$('#id_view_storage option').eq(7).attr('selected','selected');
		$('#id_view_pages option').eq(7).attr('selected','selected');
		$('#id_send_stream option').eq(2).attr('selected','selected');
		$('#id_post_wall option').eq(1).attr('selected','selected');
		$('#id_post_comments option').eq(2).attr('selected','selected');
		$('#id_post_mail option').eq(1).attr('selected','selected');
		$('#id_tag_deliver option').eq(1).attr('selected','selected');
		$('#id_chat option').eq(1).attr('selected','selected');
		$('#id_write_storage option').eq(0).attr('selected','selected');
		$('#id_write_pages option').eq(0).attr('selected','selected');
		$('#id_delegate option').eq(0).attr('selected','selected');
		$('#id_republish option').eq(1).attr('selected','selected');
		$('#id_post_like option').eq(5).attr('selected','selected');
		$('#id_profile_in_directory_onoff .on').removeClass('hidden');
		$('#id_profile_in_directory_onoff .off').addClass('hidden');
		$('#id_profile_in_directory').val(1);
	}
	if(n == 3) {
		$('#id_view_stream option').eq(7).attr('selected','selected');
		$('#id_view_profile option').eq(7).attr('selected','selected');
		$('#id_view_contacts option').eq(7).attr('selected','selected');
		$('#id_view_storage option').eq(7).attr('selected','selected');
		$('#id_view_pages option').eq(7).attr('selected','selected');
		$('#id_send_stream option').eq(5).attr('selected','selected');
		$('#id_post_wall option').eq(5).attr('selected','selected');
		$('#id_post_comments option').eq(5).attr('selected','selected');
		$('#id_post_mail option').eq(5).attr('selected','selected');
		$('#id_tag_deliver option').eq(1).attr('selected','selected');
		$('#id_chat option').eq(5).attr('selected','selected');
		$('#id_write_storage option').eq(2).attr('selected','selected');
		$('#id_write_pages option').eq(2).attr('selected','selected');
		$('#id_delegate option').eq(0).attr('selected','selected');
		$('#id_republish option').eq(5).attr('selected','selected');
		$('#id_post_like option').eq(6).attr('selected','selected');
		$('#id_profile_in_directory_onoff .on').removeClass('hidden');
		$('#id_profile_in_directory_onoff .off').addClass('hidden');
		$('#id_profile_in_directory').val(1);
	}
}
