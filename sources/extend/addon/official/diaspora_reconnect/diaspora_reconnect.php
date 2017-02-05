<?php

/**
 *
 * Name: Diaspora Reconnect
 * Description: Reconnect to Diaspora connections from a different channel clone
 * Requires: diaspora
 * Version: 1.0
 * Author: Mike Macgirvin
 *
 */

require_once('addon/diaspora/diaspora.php');

function diaspora_reconnect_module() {}

function diaspora_reconnect_post(&$a) {

	if(! local_channel())
		return;

	$channel = \App::get_channel();

	$deliveries = array();

	$total = 0;

	if($_POST['reconnect']) {
		$r = q("select * from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d
			and xchan_network like '%%diaspora%%' ",
			intval(local_channel())
		);
		if($r) {
			foreach($r as $rv) {
				if(stripos($rv['abook_instance'],\App::get_hostname()))
					continue;
				$x = diaspora_share($channel,$rv);
				if($x) {
					$deliveries[] = $x;
					$total ++;
				}
			}
		}
		info( sprintf( t('Reconnecting %d connections'), $total));

		if($deliveries)
			do_delivery($deliveries);

	}

}



function diaspora_reconnect_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return;
	}


	$title = t('Diaspora Reconnect');

	$info = t('Use this form to re-establish Diaspora connections which were initially made from a different hub.');

	return replace_macros(get_markup_template('diaspora_reconnect.tpl','addon/diaspora_reconnect'), [ 
		'$title' => $title,
		'$info' => $info,
		'$submit' => t('Reconnect'),
	]);

}