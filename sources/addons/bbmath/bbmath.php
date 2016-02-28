<?php
/**
     *
     * Name: bbmath
     * Description: Display math
     * Version: 0.0
     * Author: Stefan Parviainen <pafcu@iki.fi>
     * Maintainer: none
     */

require_once('phplatex.php');
function bbmath_load() {
	register_hook('bbcode','addon/bbmath/bbmath.php','bbmath_bbcode');

}
function bbmath_unload() {
	unregister_hook('bbcode','addon/bbmath/bbmath.php','bbmath_bbcode');
}

function bbmath_bbcode($a,&$text) {
	$text = preg_replace_callback('|\[tex\](.*?)\[/tex\]|',function($m) { return texify($m[1]); },$text);
}
