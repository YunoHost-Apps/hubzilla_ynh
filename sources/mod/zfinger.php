<?php

function zfinger_init(&$a) {

	require_once('include/zot.php');
	require_once('include/crypto.php');


	$x = zotinfo($_REQUEST);
	json_return_and_die($x);

}
