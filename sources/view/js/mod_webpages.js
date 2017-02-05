$(document).ready(function() {	
	$("input[type=\"checkbox\"]").hide();
});

window.isChecked = true;

function checkedAll(isChecked) {
		window.isChecked = !window.isChecked ;
		var c = document.getElementsByTagName('input');
		for (var i = 0; i < c.length; i++){
				if (c[i].type == 'checkbox'){
								c[i].checked = isChecked;
				}
		}
}