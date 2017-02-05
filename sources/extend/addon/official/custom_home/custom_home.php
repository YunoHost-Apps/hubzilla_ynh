<?php
/**
 * Name: Custom Home
 * Description: Set a custom home page or display a random channel from this server on the home page.
 * Version: 1.0  
 * Author: Thomas Willingham <zot:beardyunixer@beardyunixer.com>
 * Maintainer: none
 */


function custom_home_load() {
    register_hook('home_mod_content', 'addon/custom_home/custom_home.php', 'custom_home_home');
    logger("loaded custom_home");
}
 
function custom_home_unload() {
    unregister_hook('home_mod_content', 'addon/custom_home/custom_home.php', 'custom_home_home');
    unregister_hook('home_content', 'addon/custom_home/custom_home.php', 'custom_home_home');
    logger("removed custom_home");
}
 
function custom_home_home(&$a, &$o){
    
    $x = get_config('system','custom_home');
    if($x) {
	if ($x == "random") {
		$rand = db_getfunc('rand');
		$r = q("select channel_address from channel left join pconfig on channel_id = pconfig.uid where pconfig.cat = 'perm_limits' and pconfig.k = 'view_stream' and pconfig.v = 1 and channel_address != 'sys' order by $rand limit 1");
		$x = z_root() . '/channel/' . $r[0]['channel_address'];
		}
	else {
		$x = z_root() . '/' . $x;
	}
        
	goaway(zid($x));
	}

//If nothing is set
        return $o;
}

