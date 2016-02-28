$(document).ready(function() {
	$("#register-email").blur(function() {
		var zreg_email = $("#register-email").val();
		$.get("register/email_check.json?f=&email=" + encodeURIComponent(zreg_email), function(data) {
			$("#register-email-feedback").html(data.message);
			zFormError("#register-email-feedback",data.error);
		});
	});
	$("#register-password").blur(function() {
		if(($("#register-password").val()).length < 6 ) {
			$("#register-password-feedback").html(aStr.pwshort);
			zFormError("#register-password-feedback", true);
		}
		else {
			$("#register-password-feedback").html("");
			zFormError("#register-password-feedback", false);
		}
	});
	$("#register-password2").blur(function() {
		if($("#register-password").val() != $("#register-password2").val()) {
			$("#register-password2-feedback").html(aStr.pwnomatch);
			zFormError("#register-password2-feedback", true);
		}
		else {
			$("#register-password2-feedback").html("");
			zFormError("#register-password2-feedback", false);
		}
	});


	$("#newchannel-name").blur(function() {
		$("#name-spinner").spin('small');
		var zreg_name = $("#newchannel-name").val();
		$.get("new_channel/autofill.json?f=&name=" + encodeURIComponent(zreg_name),function(data) {
			$("#newchannel-nickname").val(data);
			zFormError("#newchannel-name-feedback",data.error);
			$("#name-spinner").spin(false);
		});
	});
	$("#newchannel-nickname").blur(function() {
		$("#nick-spinner").spin('small');
		var zreg_nick = $("#newchannel-nickname").val();
		$.get("new_channel/checkaddr.json?f=&nick=" + encodeURIComponent(zreg_nick),function(data) {
			$("#newchannel-nickname").val(data);
			zFormError("#newchannel-nickname-feedback",data.error);
			$("#nick-spinner").spin(false);
		});
	});

});