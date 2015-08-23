<?php

require_once('include/identity.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');

function blocks_init(&$a) {

	if(argc() > 1 && argv(1) === 'sys' && is_site_admin()) {
		$sys = get_sys_channel();
		if($sys && intval($sys['channel_id'])) {
			$a->is_sys = true;
		}
	}

	if(argc() > 1)
		$which = argv(1);
	else
		return;

	profile_load($a,$which);

}


function blocks_content(&$a) {

	if(! $a->profile) {
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

	$which = argv(1);

	$_SESSION['return_url'] = $a->query_string;

	$uid = local_channel();
	$owner = 0;
	$channel = null;
	$observer = $a->get_observer();

	$channel = $a->get_channel();

	if($a->is_sys && is_site_admin()) {
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

	$perms = get_all_perms($owner,$ob_hash);

	if(! $perms['write_pages']) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	// Block design features from visitors 

	if((! $uid) || ($uid != $owner)) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$mimetype = (($_REQUEST['mimetype']) ? $_REQUEST['mimetype'] : get_pconfig($owner,'system','page_mimetype'));

	if(! $mimetype) {
		$mimetype = 'choose';
	}

	$x = array(
		'webpage' => ITEM_TYPE_BLOCK,
		'is_owner' => true,
		'nickname' => $a->profile['channel_address'],
		'lockstate' => (($channel['channel_allow_cid'] || $channel['channel_allow_gid'] || $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
		'bang' => '',
		'showacl' => false,
		'visitor' => true,
		'mimetype' => $mimetype,
		'ptlabel' => t('Block Name'),
		'profile_uid' => intval($owner),
		'expanded' => true,
		'novoting' => true
	);

	if($_REQUEST['title'])
		$x['title'] = $_REQUEST['title'];
	if($_REQUEST['body'])
		$x['body'] = $_REQUEST['body'];
	if($_REQUEST['pagetitle'])
		$x['pagetitle'] = $_REQUEST['pagetitle'];

	$editor = status_editor($a,$x);

	$r = q("select iid, sid, mid, title, body, mimetype, created, edited from item_id left join item on item_id.iid = item.id
		where item_id.uid = %d and service = 'BUILDBLOCK' and item_type = %d order by item.created desc",
		intval($owner),
		intval(ITEM_TYPE_BLOCK)
	);

	$pages = null;

	if($r) {
		$pages = array();
		foreach($r as $rr) {
			$element_arr = array(
				'type'      => 'block',
				'title'	    => $rr['title'],
				'body'      => $rr['body'],
				'created'   => $rr['created'],
				'edited'    => $rr['edited'],
				'mimetype'  => $rr['mimetype'],
				'pagetitle' => $rr['sid'],
				'mid'       => $rr['mid']
			);
			$pages[$rr['iid']][] = array(
				'url' => $rr['iid'],
				'name' => $rr['sid'],
				'title' => $rr['title'],
				'created' => $rr['created'],
				'edited' => $rr['edited'],
				'bb_element' => '[element]' . base64url_encode(json_encode($element_arr)) . '[/element]'
			);
		} 
	}

	//Build the base URL for edit links
	$url = z_root() . '/editblock/' . $which; 

	$o .= replace_macros(get_markup_template('blocklist.tpl'), array(
		'$baseurl'    => $url,
		'$title'      => t('Blocks'),
		'$name'       => t('Block Name'),
		'$blocktitle' => t('Block Title'),
		'$created'    => t('Created'),
		'$edited'     => t('Edited'),
		'$create'     => t('Create'),
		'$edit'       => t('Edit'),
		'$share'      => t('Share'),
		'$delete'     => t('Delete'),
		'$editor'     => $editor,
		'$pages'      => $pages,
		'$channel'    => $which,
		'$view'       => t('View'),
		'$preview'    => '1',
	));
    
	return $o;
}
