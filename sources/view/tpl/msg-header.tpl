<script type="text/javascript" src="view/js/ajaxupload.js" ></script>
<script language="javascript" type="text/javascript">

	$("#prvmail-text").editor_autocomplete(baseurl+"/acl");


	$(document).ready(function() {

		var file_uploader = new window.AjaxUpload(
			'prvmail-attach-wrapper',
			{ action: 'wall_attach/{{$nickname}}',
				name: 'userfile',
				onSubmit: function(file,ext) { $('#prvmail-rotator').spin('tiny'); },
				onComplete: function(file,response) {
					addmailtext(response);
					$('#prvmail-rotator').spin(false);
				}
			}
		);

		var file_uploader_sub = new window.AjaxUpload(
			'prvmail-attach-sub',
			{ action: 'wall_attach/{{$nickname}}',
				name: 'userfile',
				onSubmit: function(file,ext) { $('#prvmail-rotator').spin('tiny'); },
				onComplete: function(file,response) {
					addmailtext(response);
					$('#prvmail-rotator').spin(false);
				}
			}
		);


	});

	function prvmailJotGetLink() {
		reply = prompt("{{$linkurl}}");
		if(reply && reply.length) {
			$('#prvmail-rotator').spin('tiny');
			$.get('linkinfo?f=&url=' + reply, function(data) {
				addmailtext(data);
				$('#prvmail-rotator').spin(false);
			});
		}
	}

	function prvmailGetExpiry() {
		reply = prompt("{{$expireswhen}}", $('#inp-prvmail-expires').val());
		if(reply && reply.length) {
			$('#inp-prvmail-expires').val(reply);
		}
	}

	function linkdropper(event) {
		var linkFound = event.dataTransfer.types.contains("text/uri-list");
		if(linkFound)
			event.preventDefault();
	}

	function linkdrop(event) {
		var reply = event.dataTransfer.getData("text/uri-list");
		event.target.textContent = reply;
		event.preventDefault();
		if(reply && reply.length) {
			$('#prvmail-rotator').spin('tiny');
			$.get('linkinfo?f=&url=' + reply, function(data) {
				addmailtext(data);
				$('#prvmail-rotator').spin(false);
			});
		}
	}

	function addmailtext(data) {
		var currentText = $("#prvmail-text").val();
		$("#prvmail-text").val(currentText + data);
	}	



</script>

