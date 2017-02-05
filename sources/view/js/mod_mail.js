$(document).ready(function() { 
	$("#recip").name_autocomplete(baseurl + '/acl', 'm', false, function(data) {
		$("#recip-complete").val(data.xid);
	});
	$(".autotime").timeago()
	$('#prvmail-text').bbco_autocomplete('bbcode');
}); 
