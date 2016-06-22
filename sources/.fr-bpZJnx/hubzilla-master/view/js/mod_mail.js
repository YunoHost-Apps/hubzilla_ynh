$(document).ready(function() { 
	$("#recip").name_autocomplete(baseurl + '/acl', '', false, function(data) {
		$("#recip-complete").val(data.xid);
	});
	$(".autotime").timeago()
	$('#prvmail-text').bbco_autocomplete('bbcode');
}); 
