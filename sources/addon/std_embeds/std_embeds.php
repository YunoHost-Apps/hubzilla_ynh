<?php


/**
 * Name: Standard Embeds
 * Description: Allow unfiltered access to embeds from top media providers
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 */

function std_embeds_load() {
	Zotlabs\Extend\Hook::register('oembed_action','addon/std_embeds/std_embeds.php','std_embeds_action');
	Zotlabs\Extend\Hook::register('html2bb_video','addon/std_embeds/std_embeds.php','std_embeds_html2bb_video');
	Zotlabs\Extend\Hook::register('bb_translate_video','addon/std_embeds/std_embeds.php','std_embeds_bb_translate_video');
}

function std_embeds_unload() {
	Zotlabs\Extend\Hook::unregister_by_file('addon/std_embeds/std_embeds.php');
}

function std_embeds_action(&$arr) {

	if($arr['action'] === 'block')
		return;

	$m = parse_url($arr['url']);


	$realurl = '';

	// Prevent hostname forgeries to get around host restrictions by providing our own URL replacements.
	// So https://youtube.com.badguy.com/watch/111111111 and https://foobar.com/youtube.com/watch/111111111 and
	// https://foobar.com/?fakeurl=https://youtube.com/watch/111111 will not be allowed unfiltered access.

	$s = array(
		'youtube'    => 'https://youtube.com',
		'youtu.be'   => 'https://youtu.be',
		'vimeo'      => 'https://vimeo.com',
		'soundcloud' => 'https://soundcloud.com'
	);

	foreach($s as $k => $v) {
		if(strpos($m['host'],$k) !== false) {
			logger('found: ' . $k);
			$realurl = $v;
			break;
		}
	}

	if($realurl) {
		$arr['url'] = $realurl . (($m['path']) ? '/' . $m['path'] : '') . (($m['query']) ? '?' . $m['query'] : '') . (($m['fragment']) ? '#' . $m['fragment'] : ''); 
		$arr['action'] = 'allow';
		logger('allowed');
	}

}


function std_embeds_html2bb_video(&$x) {
	$s = $x['string'];

	$s = preg_replace('#<object[^>]+>(.*?)https?://www.youtube.com/((?:v|cp)/[A-Za-z0-9\-_=]+)(.*?)</object>#ism',
			'[embed]https://www.youtube.com/watch?v=$2[/embed]', $s);

	$s = preg_replace('#<iframe[^>](.*?)https?://www.youtube.com/embed/([A-Za-z0-9\-_=]+)(.*?)</iframe>#ism',
			'[embed]https://www.youtube.com/watch?v=$2[/embed]', $s);

	$s = preg_replace('#<iframe[^>](.*?)https?://player.vimeo.com/video/([0-9]+)(.*?)</iframe>#ism',
			'[embed]https://player.vimeo.com/video/$2[/embed]', $s);

	$x['string'] = $s;

}


function std_embeds_bb_translate_video(&$x) {

	$s = $x['string'];

	$matches = null;
	$r = preg_match_all("/\[video\](.*?)\[\/video\]/ism",$s,$matches,PREG_SET_ORDER);
	if($r) {
		foreach($matches as $mtch) {
			if((stristr($mtch[1],'youtube')) || (stristr($mtch[1],'youtu.be')))
				$s = str_replace($mtch[0],'[embed]' . $mtch[1] . '[/embed]',$s);
			elseif(stristr($mtch[1],'vimeo'))
				$s = str_replace($mtch[0],'[embed]' . $mtch[1] . '[/embed]',$s);
		}
	}

	$x['string'] = $s;

}
