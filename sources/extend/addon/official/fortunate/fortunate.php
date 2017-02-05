<?php
/**
 * Name: Fortunate
 * Description: Add a random fortune cookie at the bottom of every pages. [Requires manual confguration.]
 * Version: 1.0
 * Author: Mike Macgirvin <http://macgirvin.com/profile/mike>
 * Maintainer: none
 */


function fortunate_load() {
	register_hook('page_end', 'addon/fortunate/fortunate.php', 'fortunate_fetch');

}

function fortunate_unload() {
	unregister_hook('page_end', 'addon/fortunate/fortunate.php', 'fortunate_fetch');
}

function fortunate_module(){}


function fortunate_fetch(&$a,&$b) {

	$fort_server = get_config('fortunate','server');
	if(! $fort_server)
		return;

	App::$page['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="' 
		. z_root() . '/addon/fortunate/fortunate.css' . '" media="all" />' . "\r\n";

	$s = z_fetch_url('http://' . $fort_server . '/cookie.php?numlines=4&equal=1&rand=' . mt_rand());
	if($s['success'])
		$b .= '<div class="fortunate">' . $s['body'] . '</div>';

}

function fortunate_content(&$a) {

//	$o = '';
//	fortunate_fetch($a,$o);
//	return $o;

}