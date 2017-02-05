<?php
namespace Zotlabs\Module;

require_once('include/channel.php');
require_once('include/acl_selectors.php');
require_once('include/conversation.php');

class Editblock extends \Zotlabs\Web\Controller {

	function init() {

		if(argc() > 1 && argv(1) === 'sys' && is_site_admin()) {
			$sys = get_sys_channel();
			if($sys && intval($sys['channel_id'])) {
				\App::$is_sys = true;
			}
		}

		if(argc() > 1)
			$which = argv(1);
		else
			return;

		profile_load($which);

	}

	function get() {

		if(! \App::$profile) {
			notice( t('Requested profile is not available.') . EOL );
			\App::$error = 404;
			return;
		}

		$which = argv(1);

		$uid = local_channel();
		$owner = 0;
		$channel = null;
		$observer = \App::get_observer();

		$channel = \App::get_channel();

		if(\App::$is_sys && is_site_admin()) {
			$sys = get_sys_channel();
			if($sys && intval($sys['channel_id'])) {
				$uid = $owner = intval($sys['channel_id']);
				$channel = $sys;
				$observer = $sys;
			}
		}

		if(! $owner) {
			// Figure out who the page owner is.
			$r = q("select channel_id from channel where channel_address = '%s'",
				dbesc($which)
			);
			if($r) {
				$owner = intval($r[0]['channel_id']);
			}
		}

		$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

		if(! perm_is_allowed($owner,$ob_hash,'write_pages')) {
			notice( t('Permission denied.') . EOL);
			return;
		}

		$is_owner = (($uid && $uid == $owner) ? true : false);

		$o = '';

		// Figure out which post we're editing
		$post_id = ((argc() > 2) ? intval(argv(2)) : 0);

		if(! ($post_id && $owner)) {
			notice( t('Item not found') . EOL);
			return;
		}

		$itm = q("SELECT * FROM item WHERE id = %d and uid = %s LIMIT 1",
			intval($post_id),
			intval($owner)
		);
		if($itm) {
			$item_id = q("select * from iconfig where cat = 'system' and k = 'BUILDBLOCK' and iid = %d limit 1",
				intval($itm[0]['id'])
			);
			if($item_id)
				$block_title = $item_id[0]['v'];
		}
		else {
			notice( t('Item not found') . EOL);
			return;
		}

		$mimetype = $itm[0]['mimetype'];

		$rp = 'blocks/' . $channel['channel_address'];

		$x = array(
			'nickname' => $channel['channel_address'],
			'bbco_autocomplete'=> (($mimetype  == 'text/bbcode') ? 'bbcode' : 'comanche-block'),
			'return_path' => $rp,
			'webpage' => ITEM_TYPE_BLOCK,
			'ptlabel' => t('Block Name'),
			'button' => t('Edit'),
			'writefiles' => (($mimetype  == 'text/bbcode') ? perm_is_allowed($owner, get_observer_hash(), 'write_storage') : false),
			'weblink' => (($mimetype  == 'text/bbcode') ? t('Insert web link') : false),
			'hide_voting' => true,
			'hide_future' => true,
			'hide_location' => true,
			'hide_expire' => true,
			'showacl' => false,
			'ptyp' => $itm[0]['type'],
			'mimeselect' => true,
			'mimetype' => $itm[0]['mimetype'],
			'body' => undo_post_tagging($itm[0]['body']),
			'post_id' => $post_id,
			'visitor' => true,
			'title' => htmlspecialchars($itm[0]['title'],ENT_COMPAT,'UTF-8'),
			'placeholdertitle' => t('Title (optional)'),
			'pagetitle' => $block_title,
			'profile_uid' => (intval($channel['channel_id'])),
			'bbcode' => (($mimetype  == 'text/bbcode') ? true : false)
		);

		$editor = status_editor($a, $x);

		$o .= replace_macros(get_markup_template('edpost_head.tpl'), array(
			'$title' => t('Edit Block'),
			'$delete' => ((($itm[0]['author_xchan'] === $ob_hash) || ($itm[0]['owner_xchan'] === $ob_hash)) ? t('Delete') : false),
			'$id' => $itm[0]['id'],
			'$editor' => $editor
		));

		return $o;

	}

}
