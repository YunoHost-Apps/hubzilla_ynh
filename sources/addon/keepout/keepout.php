<?php

/**
 * Name: Keep Out
 * Description: Block public completely, IMPORTANT: off grid use ONLY
 * Version: 1.0
 * Author: Macgirvin
 * Maintainer: none
 * MinVersion: 1.1.4
 *
 */


function keepout_urls() {
	return array(
		'blocks','bookmarks','channel','chat','cloud','connections','connedit','cover_photo','directory','dirsearch','display','editblock','editlayout','editpost','editwebpage','events','feed','filestorage','hcard','hostxrd','layouts','mail','manage','menu','mitem','network','online','page','pconfig','pdledit','photos','poco','profile','public','search','siteinfo','siteinfo_json','thing','viewsrc','webpages','wfinger','xchan','xpoco','xrd','zcard','zotfeed');
}

function keepout_load() {
	foreach(keepout_urls() as $x) {
		register_hook($x . '_mod_init', 'addon/keepout/keepout.php', 'keepout_mod_init');
		register_hook($x . '_mod_content', 'addon/keepout/keepout.php', 'keepout_mod_content');
	}
}

function keepout_unload() {
	foreach(keepout_urls() as $x) {
		unregister_hook($x . '_mod_init', 'addon/keepout/keepout.php', 'keepout_mod_init');
		unregister_hook($x . '_mod_content', 'addon/keepout/keepout.php', 'keepout_mod_content');
	}
}


function keepout_mod_init(&$a,&$b) {
	if((get_config('system','block_public')) && (! get_account_id()) && (! remote_channel())) {
		notice( t('Permission denied.') . EOL);
		$b['replace'] = true;
	}
}


function keepout_mod_content(&$a,&$b) {
	if((get_config('system','block_public')) && (! get_account_id()) && (! remote_channel()))
		$b['replace'] = true;
}