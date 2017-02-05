<?php
namespace Zotlabs\Module;

require_once('include/channel.php');
require_once('include/acl_selectors.php');
require_once('include/conversation.php');

class Editlayout extends \Zotlabs\Web\Controller {

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

		if(! $post_id) {
			notice( t('Item not found') . EOL);
			return;
		}

		// Now we've got a post and an owner, let's find out if we're allowed to edit it

		$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

		$perms = get_all_perms($owner,$ob_hash);

		if(! $perms['write_pages']) {
			notice( t('Permission denied.') . EOL);
			return;
		}

		$itm = q("SELECT * FROM item WHERE id = %d and uid = %s LIMIT 1",
			intval($post_id),
			intval($owner)
		);

		$item_id = q("select * from iconfig where cat = 'system' and k = 'PDL' and iid = %d limit 1",
			intval($itm[0]['id'])
		);
		if($item_id)
			$layout_title = $item_id[0]['v'];


		$rp = 'layouts/' . $which;

		$x = array(
			'webpage' => ITEM_TYPE_PDL,
			'nickname' => $channel['channel_address'],
			'editor_autocomplete'=> true,
			'bbco_autocomplete'=> 'comanche',
			'return_path' => $rp,
			'button' => t('Edit'),
			'hide_voting' => true,
			'hide_future' => true,
			'hide_expire' => true,
			'hide_location' => true,
			'hide_weblink' => true,
			'hide_attach' => true,
			'hide_preview' => true,
			'ptyp' => $itm[0]['obj_type'],
			'body' => undo_post_tagging($itm[0]['body']),
			'post_id' => $post_id,
			'title' => htmlspecialchars($itm[0]['title'],ENT_COMPAT,'UTF-8'),
			'pagetitle' => $layout_title,
			'ptlabel' => t('Layout Name'),
			'placeholdertitle' => t('Layout Description (Optional)'),
			'showacl' => false,
			'profile_uid' => intval($owner),
		);

		$editor = status_editor($a, $x);

		$o .= replace_macros(get_markup_template('edpost_head.tpl'), array(
			'$title' => t('Edit Layout'),
			'$delete' => ((($itm[0]['author_xchan'] === $ob_hash) || ($itm[0]['owner_xchan'] === $ob_hash)) ? t('Delete') : false),
			'$id' => $itm[0]['id'],
			'$editor' => $editor
		));

		return $o;

	}

}
