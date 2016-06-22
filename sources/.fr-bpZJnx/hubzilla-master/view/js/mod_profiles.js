$(document).ready(function() {
	$('form').areYouSure(); // Warn user about unsaved settings
	$('textarea').bbco_autocomplete('bbcode');
});
