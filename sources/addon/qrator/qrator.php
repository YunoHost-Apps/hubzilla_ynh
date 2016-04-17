<?php

/**
 * Name: Qrator
 * Description: QR generator
 * Version: 1.0
 * Author: Macgirvin
 * Maintainer: none
 */


function qrator_load() {
	register_hook('photo_mod_init','addon/qrator/qrator.php','qrator_photo_mod_init');
	register_hook('bbcode','addon/qrator/qrator.php','qrator_bbcode');
}
function qrator_unload() {
	unregister_hook('photo_mod_init','addon/qrator/qrator.php','qrator_photo_mod_init');
	unregister_hook('bbcode','addon/qrator/qrator.php','qrator_bbcode');
}


function qrator_module() {}



function qrator_photo_mod_init(&$a,&$b) {

	if(argc() > 1 && argv(1) === 'qr') {
		$t = $_GET['qr'];
		require_once('addon/qrator/phpqrcode/phpqrcode.php');
		header("Content-type: image/png");
		QRcode::png(($t) ? $t : '.');
		killme();
	}

}




/**
 * @brief Returns an QR-code image from a value given in $match[1].
 *
 * @param array $match
 * @return string HTML img with QR-code of $match[1]
 */
function qrator_bb_qr($match) {
	return '<img class="zrl" src="' . z_root() . '/photo/qr?f=&qr=' . urlencode($match[1]) . '" alt="' . t('QR code') . '" title="' . htmlspecialchars($match[1],ENT_QUOTES,'UTF-8') . '" />';
}


function qrator_bbcode(&$a,&$b) {

	if (strpos($b,'[/qr]') !== false) {
		$b = preg_replace_callback("/\[qr\](.*?)\[\/qr\]/ism", 'qrator_bb_qr', $b);
	}

}


function qrator_content(&$a) {

$header = t('QR Generator');
$prompt = t('Enter some text');

$o .= <<< EOT
<h2>$header</h2>

<div>$prompt</div>
<input type="text" id="qr-input" onkeyup="makeqr();" />
<div id="qr-output"></div>

<script>
function makeqr() {
	var txt = $('#qr-input').val();

	$('#qr-output').html('<img src="/photo/qr/?f=&qr=' + txt + '" /></img>');

}
</script>


EOT;
return $o;

}