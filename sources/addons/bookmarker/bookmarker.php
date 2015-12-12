<?php


/**
 * Name: bookmarker
 * Description: replace #^ with bookmark icon
 * Version: 1.0
 * Author: Mike Macgirvin <mike@zothub.com>
 * 
 */

function bookmarker_install() {
	register_hook('prepare_body', 'addon/bookmarker/bookmarker.php', 'bookmarker_prepare_body', 10);
}


function bookmarker_uninstall() {
	unregister_hook('prepare_body', 'addon/bookmarker/bookmarker.php', 'bookmarker_prepare_body');
}

function bookmarker_prepare_body(&$a,&$b) {


	if(get_pconfig(local_channel(),'bookmarker','disable'))
		return;

	if(! strpos($b['html'],'bookmark-identifier'))
		return;

	if(! function_exists('redbasic_init'))
		return;

	$id = $b['item']['id'];
	if(local_channel())
		$link = '<a class="fakelink" onclick="itemBookmark(' . $id . '); return false;" title="' . t('Save Bookmarks') . '" href="#"><i class="icon-bookmark"></i></a> ';
	else
		$link = '<i class="icon-bookmark"></i></a> ';

	$b['html'] = str_replace('<span class="bookmark-identifier">#^</span>',$link,$b['html']);

}
