	$(document).ready(function() {
//		$("#id_permissions_role").sSelect();
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
