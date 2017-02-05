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
 *
 */


function superblock_load() {

	register_hook('feature_settings', 'addon/superblock/superblock.php', 'superblock_addon_settings');
	register_hook('feature_settings_post', 'addon/superblock/superblock.php', 'superblock_addon_settings_post');
	register_hook('conversation_start', 'addon/superblock/superblock.php', 'superblock_conversation_start');
	register_hook('item_photo_menu', 'addon/superblock/superblock.php', 'superblock_item_photo_menu');
	register_hook('enotify_store', 'addon/superblock/superblock.php', 'superblock_enotify_store');
	register_hook('item_store', 'addon/superblock/superblock.php', 'superblock_item_store');
	register_hook('directory_item', 'addon/superblock/superblock.php', 'superblock_directory_item');
	register_hook('api_format_items', 'addon/superblock/superblock.php', 'superblock_api_format_items');
	register_hook('stream_item', 'addon/superblock/superblock.php', 'superblock_stream_item');
	register_hook('post_mail', 'addon/superblock/superblock.php', 'superblock_post_mail');

}


function superblock_unload() {

	unregister_hook('feature_settings', 'addon/superblock/superblock.php', 'superblock_addon_settings');
	unregister_hook('feature_settings_post', 'addon/superblock/superblock.php', 'superblock_addon_settings_post');
	unregister_hook('conversation_start', 'addon/superblock/superblock.php', 'superblock_conversation_start');
	unregister_hook('item_photo_menu', 'addon/superblock/superblock.php', 'superblock_item_photo_menu');
	unregister_hook('enotify_store', 'addon/superblock/superblock.php', 'superblock_enotify_store');
	unregister_hook('item_store', 'addon/superblock/superblock.php', 'superblock_item_store');
	unregister_hook('directory_item', 'addon/superblock/superblock.php', 'superblock_directory_item');
	unregister_hook('api_format_items', 'addon/superblock/superblock.php', 'superblock_api_format_items');
	unregister_hook('stream_item', 'addon/superblock/superblock.php', 'superblock_stream_item');
	unregister_hook('post_mail', 'addon/superblock/superblock.php', 'superblock_post_mail');

}



class Superblock {

	private $list = [];

	function __construct($channel_id) {
		$cnf = get_pconfig($channel_id,'system','blocked');
		if(! $cnf)
			return;
		$this->list = explode(',',$cnf);
	}

	function get_list() {
		return $this->list;
	}

	function match($n) {
		if(! $this->list)
			return false;
		foreach($this->list as $l) {
			if(trim($n) === trim($l)) {
				return true;
			}
		}
		return false;
	}

}





function superblock_addon_settings(&$a,&$s) {

	if(! local_channel())
		return;

	$cnf = get_pconfig(local_channel(),'system','blocked');
	if(! $cnf)
		$cnf = '';

	$list = explode(',',$cnf);
	stringify_array_elms($list,true);
	$query_str = implode(',',$list);
	if($query_str) {
		$r = q("select * from xchan where xchan_hash in ( " . $query_str . " ) ");
	}
	else
		$r = [];

	if($r) {
		for($x = 0; $x < count($r); $x ++) {
			$r[$x]['encoded_hash'] = urlencode($r[$x]['xchan_hash']);
		}
	}

	$sc = replace_macros(get_markup_template('superblock_list.tpl','addon/superblock'), [
		'$blocked' => t('Currently blocked'),
		'$entries' => $r,
		'$nothing' => (($r) ? '' : t('No channels currently blocked')),
		'$token' => get_form_security_token('superblock'),
		'$remove' => t('Remove')
	]);

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('superblock', t('"Superblock" Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

	return;

}

function superblock_addon_settings_post(&$a,&$b) {

	if(! local_channel())
		return;

}

function superblock_stream_item(&$a,&$b) {
	if(! local_channel())
		return;

	$sb = new Superblock(local_channel());

	$found = false;

	if(is_array($b['item']) && (! $found)) {
		if($sb->match($b['item']['author_xchan']))
			$found = true;
		elseif($sb->match($b['item']['owner_xchan']))
			$found = true;
	}

	if($b['item']['children']) {
		for($d = 0; $d < count($b['item']['children']); $d ++) {
			if($sb->match($b['item']['children'][$d]['owner_xchan']))
				$b['item']['children'][$d]['blocked'] = true;
			elseif($sb->match($b['item']['children'][$d]['author_xchan']))
				$b['item']['children'][$d]['blocked'] = true;
		}
	}

	if($found) {
		$b['item']['blocked'] = true;
	}

}


function superblock_item_store(&$a,&$b) {

	if(! $b['item_wall'])
		return;

	$sb = new Superblock($b['uid']);

	$found = false;

	if($sb->match($b['owner_xchan']))
		$found = true;
	elseif($sb->match($b['author_xchan']))
		$found = true;

	if($found) {
		$b['cancel'] = true;
	}
	return;
}

function superblock_post_mail(&$a,&$b) {

	$sb = new Superblock($b['channel_id']);

	$found = false;

	if($sb->match($b['from_xchan']))
		$found = true;

	if($found) {
		$b['cancel'] = true;
	}
	return;
}






function superblock_enotify_store(&$a,&$b) {

	$sb = new Superblock($b['uid']);

	$found = false;

	if($sb->match($b['sender_hash']))
		$found = true;

	if(is_array($b['parent_item']) && (! $found)) {
		if($sb->match($b['parent_item']['owner_xchan']))
			$found = true;
		elseif($sb->match($b['parent_item']['author_xchan']))
			$found = true;
	}

	if($found) {
		$b['abort'] = true;
	}
}

function superblock_api_format_items(&$a,&$b) {


	$sb = new Superblock($b['api_user']);
	$ret = [];

	for($x = 0; $x < count($b['items']); $x ++) {

		$found = false;

		if($sb->match($b['items'][$x]['owner_xchan']))
			$found = true;
		elseif($sb->match($b['items'][$x]['author_xchan']))
			$found = true;

		if(! $found)
			$ret[] = $b['items'][$x];
	}

	$b['items'] = $ret;

}


function superblock_directory_item(&$a,&$b) {

	if(! local_channel())
		return;


	$sb = new Superblock(local_channel());

	$found = false;

	if($sb->match($b['entry']['hash'])) {
		$found = true;
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
function superblockBlock(author,item) {
	$.get('superblock?f=&item=' + item + '&block=' +author, function(data) {
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
	$item = $b['item']['id'];
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

	$b['author_menu'][ t('Block Completely')] = 'javascript:superblockBlock(\'' . $author . '\',' . $item . '); return false;';
}

function superblock_module() {}


function superblock_init(&$a) {

	if(! local_channel())
		return;

	$words = get_pconfig(local_channel(),'system','blocked');

	if(array_key_exists('block',$_GET) && $_GET['block']) {
		$r = q("select id from item where id = %d and author_xchan = '%s' limit 1",
			intval($_GET['item']),
			dbesc($_GET['block'])
		);
		if($r) {
			if(strlen($words))
				$words .= ',';
			$words .= trim($_GET['block']);
		}
	}

	if(array_key_exists('unblock',$_GET) && $_GET['unblock']) {
		if(check_form_security_token('superblock','sectok')) {
			$newlist = [];
			$list = explode(',',$words);
			if($list) {
				foreach($list as $li) {
					if($li !== $_GET['unblock']) {
						$newlist[] = $li;
					}
				}
			}

			$words = implode(',',$newlist);
		}
	}


	set_pconfig(local_channel(),'system','blocked',$words);
	build_sync_packet();

	info( t('superblock settings updated') . EOL );

	if($_GET['unblock'])
		goaway(z_root() . '/settings/featured');


	killme();
}
