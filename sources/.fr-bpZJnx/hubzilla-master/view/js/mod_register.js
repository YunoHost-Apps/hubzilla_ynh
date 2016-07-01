$(document).ready(function() {
	$("#id_email").blur(function() {
		var zreg_email = $("#id_email").val();
		$.get("register/email_check.json?f=&email=" + encodeURIComponent(zreg_email), function(data) {
			$("#help_email").html(data.message);
			zFormError("#help_email",data.error);
		});
	});
	$("#id_password").blur(function() {
		if(($("#id_password").val()).length < 6 ) {
			$("#help_password").html(aStr.pwshort);
			zFormError("#help_password", true);
		}
		else {
			$("#help_password").html("");
			zFormError("#help_password", false);
		}
	});
	$("#id_password2").blur(function() {
		if($("#id_password").val() != $("#id_password2").val()) {
			$("#help_password2").html(aStr.pwnomatch);
			zFormError("#help_password2", true);
		}
		else {
			$("#help_password2").html("");
			zFormError("#help_password2", false);
		}
	});

	$("#id_name").blur(function() {
		$("#name-spinner").spin('small');
		var zreg_name = $("#id_name").val();
		$.get("new_channel/autofill.json?f=&name=" + encodeURIComponent(zreg_name),function(data) {
			$("#id_nickname").val(data);
			if(data.error) {
				$("#help_name").html("");
				zFormError("#help_name",data.error);
			}
			$("#name-spinner").spin(false);
		});
	});

	$("#id_nickname").blur(function() {
		$("#nick-spinner").spin('small');
		var zreg_nick = $("#id_nickname").val();
		$.get("new_channel/checkaddr.json?f=&nick=" + encodeURIComponent(zreg_nick),function(data) {
			$("#id_nickname").val(data);
			if(data.error) {
				$("#help_nickname").html("");
				zFormError("#help_nickname",data.error);
			}
			$("#nick-spinner").spin(false);
		});
	});

});
