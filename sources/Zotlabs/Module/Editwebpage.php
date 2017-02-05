<?php
namespace Zotlabs\Module;

require_once('include/channel.php');
require_once('include/acl_selectors.php');
require_once('include/conversation.php');


class Editwebpage extends \Zotlabs\Web\Controller {

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

		$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

		$perms = get_all_perms($owner,$ob_hash);

		if(! $perms['write_pages']) {
			notice( t('Permission denied.') . EOL);
			return;
		}

		// We've already figured out which item we want and whose copy we need, 
		// so we don't need anything fancy here

		$sql_extra = item_permissions_sql($owner);

		$itm = q("SELECT * FROM item WHERE id = %d and uid = %s $sql_extra LIMIT 1",
			intval($post_id),
			intval($owner)
		);

		if(! $itm) {
			notice( t('Permission denied.') . EOL);
			return;
		}

		if(intval($itm[0]['item_obscured'])) {
			$key = get_config('system','prvkey');
			if($itm[0]['title'])
				$itm[0]['title'] = crypto_unencapsulate(json_decode($itm[0]['title'],true),$key);
			if($itm[0]['body'])
				$itm[0]['body'] = crypto_unencapsulate(json_decode($itm[0]['body'],true),$key);
		}

		$item_id = q("select * from iconfig where cat = 'system' and k = 'WEBPAGE' and iid = %d limit 1",
			intval($itm[0]['id'])
		);
		if($item_id)
			$page_title = $item_id[0]['v'];

		$mimetype = $itm[0]['mimetype'];

		if($mimetype === 'application/x-php') {
			if((! $uid) || ($uid != $itm[0]['uid'])) {
				notice( t('Permission denied.') . EOL);
				return;
			}
		}
	
		$layout = $itm[0]['layout_mid'];
	
		$tpl = get_markup_template("jot.tpl");

		$rp = 'webpages/' . $which;

		$x = array(
			'nickname' => $channel['channel_address'],
			'bbco_autocomplete'=> (($mimetype  == 'text/bbcode') ? 'bbcode' : ''),
			'return_path' => $rp,
			'webpage' => ITEM_TYPE_WEBPAGE,
			'ptlabel' => t('Page link'),
			'pagetitle' => $page_title,
			'writefiles' => (($mimetype  == 'text/bbcode') ? perm_is_allowed($owner, get_observer_hash(), 'write_storage') : false),
			'button' => t('Edit'),
			'weblink' => (($mimetype  == 'text/bbcode') ? t('Insert web link') : false),
			'hide_location' => true,
			'hide_voting' => true,
			'ptyp' => $itm[0]['type'],
			'body' => undo_post_tagging($itm[0]['body']),
			'post_id' => $post_id,
			'visitor' => ($is_owner) ? true : false,
			'acl' => populate_acl($itm[0],false,\Zotlabs\Lib\PermissionDescription::fromGlobalPermission('view_pages')),
			'permissions' => $itm[0],
			'showacl' => ($is_owner) ? true : false,
			'mimetype' => $mimetype,
			'mimeselect' => true,
			'layout' => $layout,
			'layoutselect' => true,
			'title' => htmlspecialchars($itm[0]['title'],ENT_COMPAT,'UTF-8'),
			'lockstate' => (((strlen($itm[0]['allow_cid'])) || (strlen($itm[0]['allow_gid'])) || (strlen($itm[0]['deny_cid'])) || (strlen($itm[0]['deny_gid']))) ? 'lock' : 'unlock'),
			'profile_uid' => (intval($owner)),
			'bbcode' => (($mimetype  == 'text/bbcode') ? true : false)
		);

		$editor = status_editor($a, $x);

		$o .= replace_macros(get_markup_template('edpost_head.tpl'), array(
			'$title' => t('Edit Webpage'),
			'$delete' => ((($itm[0]['author_xchan'] === $ob_hash) || ($itm[0]['owner_xchan'] === $ob_hash)) ? t('Delete') : false),
			'$editor' => $editor,
			'$id' => $itm[0]['id']
		));

		return $o;

	}

}
