<?php

function nojs_init(&$a) {

	setcookie('jsdisabled', 1, 0);
	$p = $_GET['query'];
	$hasq = strpos($p,'?');
	goaway(z_root() . (($p) ? '/' . $p : '') . (($hasq) ? '' : '?f=' ) . '&jsdisabled=1');

}