<?php
/**
 * Name: Dfedfix
 * Description: Fix Diaspora federation until the proper fix is deployed
 * Version: 1.0
 * Author: Mike Macgirvin
 */


function dfedfix_load() {
	register_hook('personal_xrd', 'addon/dfedfix/dfedfix.php', 'dfedfix_personal_xrd');
}

function dfedfix_unload() {
	unregister_hook('personal_xrd', 'addon/dfedfix/dfedfix.php', 'dfedfix_personal_xrd');
}


function dfedfix_personal_xrd(&$a,&$b) {

logger('dfedfix: ' . print_r($b,true));
	$x = $b['xml'];
	$x = str_replace('</Subject>','</Subject>
<Alias>' . z_root() . '/channel/' . $b['user']['channel_address'] . '</Alias>',$x);
	$x = str_replace('.AQAB" />','.AQAB "/>
<Link rel="salmon" href="' . z_root() . '/receive/users/' . $b['user']['channel_guid'] . str_replace('.','',$a->get_hostname()) . '"/>',$x);
	$b['xml'] = $x;

}
		