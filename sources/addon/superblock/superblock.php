<?php


/**
 * Name: superblock
 * Description: block people
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: Mike Macgirvin <mike@macgirvin.com> 
 * MinVersion: 1.1.3
 */

/**
 * This function uses some helper code in include/conversation; which handles filtering item authors. 
 * Those function should ultimately be moved to this plugin.
 */


function superblock_load() {

	register_hook('feature_settings', 'addon/superblock/superblock.php', 'superblock_addon_settings');
	register_hook('feature_settings_post', 'addon/superblock/superblock.php', 'superblock_addon_settings_post');
	register_hook('conversation_start', 'addon/superblock/superblock.php', 'superblock_conversation_start');
	register_hook('item_photo_menu', 'addon/superblock/superblock.php', 'superblock_item_photo_menu');
	register_hook('enotify_store', 'addon/superblock/superblock.php', 'superblock_enotify_store');
	register_hook('directory_item', 'addon/superblock/superblock.php', 'superblock_directory_item');
	register_hook('api_format_items', 'addon/superblock/superblock.php', 'superblock_api_format_items');

}


function superblock_unload() {

	unregister_hook('feature_settings', 'addon/superblock/superblock.php', 'superblock_addon_settings');
	unregister_hook('feature_settings_post', 'addon/superblock/superblock.php', 'superblock_addon_settings_post');
	unregister_hook('conversation_start', 'addon/superblock/superblock.php', 'superblock_conversation_start');
	unregister_hook('item_photo_menu', 'addon/superblock/superblock.php', 'superblock_item_photo_menu');
	unregister_hook('enotify_store', 'addon/superblock/superblock.php', 'superblock_enotify_store');
	unregister_hook('directory_item', 'addon/superblock/superblock.php', 'superblock_directory_item');
	unregister_hook('api_format_items', 'addon/superblock/superblock.php', 'superblock_api_format_items');

}





function superblock_addon_settings(&$a,&$s) {

	if(! local_channel())
		return;

	/* Add our stylesheet to the page so we can make our settings look nice */
	//if(! array_key_exists('htmlhead',App::$page))
		//App::$page['htmlhead'] = '';
	//App::$page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . z_root() . '/addon/superblock/superblock.css' . '" media="all" />' . "\r\n";

	$words = get_pconfig(local_channel(),'system','blocked');
	if(! $words)
		$words = '';

	$sc .= replace_macros(get_markup_template('field_textarea.tpl'), array(
		'$field'	=> array('superblock-words', t('Comma separated profile URLS to block'), htmlspecialchars($words), ''),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('superblock', t('"Superblock" Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

	return;

}

function superblock_addon_settings_post(&$a,&$b) {

	if(! local_channel())
		return;

	if($_POST['superblock-submit']) {
		set_pconfig(local_channel(),'system','blocked',trim($_POST['superblock-words']));
		info( t('SUPERBLOCK Settings saved.') . EOL);
	}
	
	build_sync_packet();


}

function superblock_enotify_store(&$a,&$b) {

	$words = get_pconfig($b['uid'],'system','blocked');
	if($words) {
		$arr = explode(',',$words);
	}
	else {
		return;
	}

	$found = false;
	if(count($arr)) {
		foreach($arr as $word) {
			if(! strlen(trim($word))) {
				continue;
			}

			if(strpos($b['sender_hash'],$word) !== false) {
				$found = true;
				break;
			}
			// also block notifications from any conversations they initiated or own
			if(is_array($b['parent_item'])) {
				if(strpos($b['parent_item']['owner_xchan'],$word) !== false) {
					$found = true;
					break;
				}
				if(strpos($b['parent_item']['author_xchan'],$word) !== false) {
					$found = true;
					break;
				}
			}
		}
	}
	if($found) {
		$b['abort'] = true;
	}
}

function superblock_api_format_items(&$a,&$b) {

	$arr = null;

	$words = get_pconfig($b['api_user'],'system','blocked');
	if($words) {
		$arr = explode(',',$words);
	}

	if($arr)
		return;

	$ret = array();

	for($x = 0; $x < count($b['items']); $x ++) {

		$found = false;
		foreach($arr as $word) {
			if(! strlen(trim($word))) {
				continue;
			}

			if(strpos($b['items'][$x]['owner_xchan'],$word) !== false) {
				$found = true;
				break;
			}
			if(strpos($b['items'][$x]['author_xchan'],$word) !== false) {
				$found = true;
				break;
			}
		}
		if(! $found)
			$ret[] = $b['items'][$x];
	}

	$b['items'] = $ret;

}


function superblock_directory_item(&$a,&$b) {

	if(! local_channel())
		return;

	$words = get_pconfig(local_channel(),'system','blocked');
	if($words) {
		$arr = explode(',',$words);
	}
	else {
		return;
	}


	$found = false;
	if(count($arr)) {
		foreach($arr as $word) {
			if(! strlen(trim($word))) {
				continue;
			}

			if(strpos($b['entry']['hash'],$word) !== false) {
				$found = true;
				break;
			}
		}
	}
	if($found) {
		unset($b['entry']);
	}
}


function superblock_conversation_start(&$a,&$b) {

	if(! local_channel())
		return;

	$words = get_pconfig(local_channel(),'system','blocked');
	if($words) {
		App::$data['superblock'] = explode(',',$words);
	}

	if(! array_key_exists('htmlhead',App::$page))
		App::$page['htmlhead'] = '';

	App::$page['htmlhead'] .= <<< EOT

<script>
function superblockBlock(author) {
	$.get('superblock?block=' +author, function(data) {
		location.reload(true);
	});
}
</script>

EOT;

}

function superblock_item_photo_menu(&$a,&$b) {

	if(! local_channel())
		return;

	$blocked = false;
	$author = $b['item']['author_xchan'];
	if(App::$channel['channel_hash'] == $author)
		return;

	if(is_array(App::$data['superblock'])) {
		foreach(App::$data['superblock'] as $bloke) {
			if(link_compare($bloke,$author)) {
				$blocked = true;
				break;
			}
		}
	}

	$b['author_menu'][ t('Block Completely')] = 'javascript:superblockBlock(\'' . $author . '\'); return false;';
}

function superblock_module() {}


function superblock_init(&$a) {

	if(! local_channel())
		return;

	$words = get_pconfig(local_channel(),'system','blocked');

	if(array_key_exists('block',$_GET) && $_GET['block']) {
		if(strlen($words))
			$words .= ',';
		$words .= trim($_GET['block']);
	}

	set_pconfig(local_channel(),'system','blocked',$words);
	build_sync_packet();

	info( t('superblock settings updated') . EOL );
	killme();
}
