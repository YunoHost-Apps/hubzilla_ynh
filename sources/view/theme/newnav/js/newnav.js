/**
 * NewNav theme specific JavaScript
 */
$(document).ready(function() {
	
	
	$('#avatar').click(function() {
		if($('#navbar-collapse-1').hasClass('in')){
			$('#navbar-collapse-1').removeClass('in');
		}
	});
	
	if($('#tabs-collapse-1').length === 0) {
		$('#expand-tabs').hide();
	}
	
//	$('.dropdown-menu').click(function(event){
//		event.stopPropagation();
//	});
});