<?php

/**
 * Name: Like Banner
 * Description: Creates a "like us on hubzilla" banner 
 * Version: 1.1
 * Author: Mike Macgirvin
 * Maintainer: none
 */



function likebanner_load() {}
function likebanner_unload() {}
function likebanner_module() {}

function likebanner_init(&$a) {
	if(argc() > 1 && argv(1) == 'show' && $_REQUEST['addr']) {
		header("Content-Type: image/png");
		$im = ImageCreateFromPng('addon/likebanner/like_banner.png');
		$black = ImageColorAllocate($im, 0,0,0);
		$start_x = 24;
		$start_y = 70;
		$fontsize=(($_REQUEST['size'])? intval($_REQUEST['size']) : 28);
		imagettftext($im,$fontsize,0,$start_x,$start_y,$black, 'addon/likebanner/FreeSansBold.ttf',$_REQUEST['addr']);
		imagepng($im);
		ImageDestroy($im);
		killme();
	}
}




function likebanner_content(&$a) {

	if(local_channel()) {
		$channel = App::get_channel();
	}
	else 
		$channel = null;

	$o = '<h1>Like Banner</h1>';

	$def = $_REQUEST['addr'];
	if($channel && (! $def)) {
		$def = $channel['xchan_addr'];
	}

	$o .= '<form action="likebanner" method="get" >';
	$o .= t('Your Webbie:');
	$o .= '<br /><br />';
	$o .= '<input type="text" name="addr" size="32" value="' . $def . '" />';
	$o .= '<br /><br />' . t('Fontsize (px):');
	$o .= '<br /><br />';
	$o .= '<input type="text" name="size" size="32" value="' . (($_REQUEST['size']) ? $_REQUEST['size'] : 12) . '" /><br /><br />';
	$o .= '<input type="submit" name="submit" value="' . t('Submit'). '" /></form><br /><br/>';

	if($_REQUEST['addr']) {
		$o .= '<img style="border: 1px solid #000;" src="likebanner/show/?f=&addr=' . urlencode($_REQUEST['addr']) . '&size=' . $_REQUEST['size'] . '" alt="banner" />';

		if($channel) {
			$p = q("select profile_guid from profile where uid = %d and is_default = 1 limit 1",
				intval($channel['channel_id'])
			);
			if($p) {
				$link = z_root() . '/like/profile/' . $p[0]['profile_guid'] . '?f=&verb=like&interactive=1';
				$o .= EOL . EOL . t('Link:') . EOL . '<input type="text" size="64" onclick="this.select();" value="' . $link . '" />';

				$html = '<a href="' . $link . '" ><img src="' . z_root() . '/likebanner?f=&addr=' . $def . '&size=' . $_REQUEST['size'] . '" alt="' . t('Like us on Hubzilla') . '" /></a>';

				$o .= EOL . EOL . t('Embed:') . EOL . '<input type="text" size="64" onclick="this.select();" value="' . htmlspecialchars($html,ENT_QUOTES,'UTF-8') . '" />'; 


			}
		}
	}
	
	return $o;

}
