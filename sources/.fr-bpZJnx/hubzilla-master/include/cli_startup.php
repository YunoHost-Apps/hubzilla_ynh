<?php /** @file */

require_once('boot.php');

// Everything we need to boot standalone 'background' processes

function cli_startup() {

	sys_boot();
	App::set_baseurl(get_config('system','baseurl'));

}