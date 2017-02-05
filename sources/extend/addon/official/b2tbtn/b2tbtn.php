<?php
/**
 * Name: b2tbtn
 * Description: Show link to go back to the top of the page
 * Version: 0.2
 * Author: Davide Pesenti <mrjive@mrjive.eu> 
 * Maintainer: Davide Pesenti <mrjive@mrjive.eu>
 * MinVersion: 0.2
 */


function b2tbtn_load() { register_hook('page_end', 'addon/b2tbtn/b2tbtn.php', 'b2tbtn_active'); }

function b2tbtn_unload() { unregister_hook('page_end', 'addon/b2tbtn/b2tbtn.php', 'b2tbtn_active'); }

function b2tbtn_active(&$a,&$b) { 
    head_add_css('/addon/b2tbtn/view/css/b2tbtn.css');
    head_add_js('/addon/b2tbtn/view/js/b2tbtn.js');
    $b .= "
<script>
$(document).ready(function(){

	// hide #back-top first
	$(\"#back-top\").hide();
	
	// fade in #back-top
	$(function () {
		$(window).scroll(function () {
			if ($(this).scrollTop() > 100) {
				$('#back-top').fadeIn();
			} else {
				$('#back-top').fadeOut();
			}
		});

		// scroll body to 0px on click
		$('#back-top a').click(function () {
			$('body,html').animate({
				scrollTop: 0
			}, 150);
			return false;
		});
	});

});
</script>";
	$b .= '
<p id="back-top">
		<a href="#top"><span></span></a>
	</p>';
} 
