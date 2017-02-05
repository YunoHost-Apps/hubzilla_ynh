$(document).ready(function() {
	$("#contacts-search").contact_autocomplete(baseurl + '/acl', 'a', true);
	$(".autotime").timeago();
}); 

$("#contacts-search").keyup(function(event){
	if(event.keyCode == 13){
		$("#contacts-search").click();
	}
});
$(".autocomplete-w1 .selected").keyup(function(event){
	if(event.keyCode == 13){
		$("#contacts-search").click();
	}
});

