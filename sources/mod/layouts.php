<?php

require_once('include/identity.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');

function layouts_init(&$a) {

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


function layouts_content(&$a) {

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

	// Get the observer, check their permissions

	$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

	$perms = get_all_perms($owner,$ob_hash);

	if(! $perms['write_pages']) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	//This feature is not exposed in redbasic ui since it is not clear why one would want to
	//download a json encoded pdl file - we dont have a possibility to import it.
	//Use the buildin share/install feature instead.
	if((argc() > 3) && (argv(2) === 'share') && (argv(3))) {
		$r = q("select sid, service, mimetype, title, body  from item_id 
			left join item on item.id = item_id.iid 
			where item_id.uid = %d and item.mid = '%s' and service = 'PDL' order by sid asc",
			intval($owner),
			dbesc(argv(3))
		);
		if($r) {
			header('Content-type: application/x-hubzilla-layout');
			header('Content-disposition: attachment; filename="' . $r[0]['sid'] . '.pdl"');
			echo json_encode($r);
			killme();
		}
	}

	// Create a status editor (for now - we'll need a WYSIWYG eventually) to create pages
	// Nickname is set to the observers xchan, and profile_uid to the owners.  
	// This lets you post pages at other people's channels.

	$x = array(
		'webpage'     => ITEM_TYPE_PDL,
		'is_owner'    => true,
		'nickname'    => $a->profile['channel_address'],
		'bang'        => '',
		'showacl'     => false,
		'visitor'     => false,
		'nopreview'   => 1,
		'ptlabel'     => t('Layout Name'),
		'profile_uid' => intval($owner),
		'expanded'    => true,
		'placeholdertitle' => t('Layout Description (Optional)'),
		'novoting' => true
	);

	if($_REQUEST['title'])
		$x['title'] = $_REQUEST['title'];
	if($_REQUEST['body'])
		$x['body'] = $_REQUEST['body'];
	if($_REQUEST['pagetitle'])
		$x['pagetitle'] = $_REQUEST['pagetitle'];

	$editor = status_editor($a,$x);

	$r = q("select iid, sid, mid, title, body, mimetype, created, edited, item_type from item_id left join item on item_id.iid = item.id
		where item_id.uid = %d and service = 'PDL' and item_type = %d order by item.created desc",
		intval($owner),
		intval(ITEM_TYPE_PDL)
	);

	$pages = null;

	if($r) {
		$pages = array();
		foreach($r as $rr) {
			$element_arr = array(
				'type'      => 'layout',
				'title'     => $rr['title'],
				'body'      => $rr['body'],
				'created'   => $rr['created'],
				'edited'    => $rr['edited'],
				'mimetype'  => $rr['mimetype'],
				'pagetitle' => $rr['sid'],
				'mid'       => $rr['mid']
			);
			$pages[$rr['iid']][] = array(
				'url' => $rr['iid'],
				'title' => $rr['sid'],
				'descr' => $rr['title'],
				'mid' => $rr['mid'],
				'created' => $rr['created'],
				'edited' => $rr['edited'],
				'bb_element' => '[element]' . base64url_encode(json_encode($element_arr)) . '[/element]'
			);
		}
	}

	//Build the base URL for edit links
	$url = z_root() . '/editlayout/' . $which; 

	$o .= replace_macros(get_markup_template('layoutlist.tpl'), array(
		'$title'   => t('Layouts'),
		'$create'  => t('Create'),
		'$help'    => array('text' => t('Help'), 'url' => 'help/comanche', 'title' => t('Comanche page description language help')),
		'$editor'  => $editor,
		'$baseurl' => $url,
		'$name' => t('Layout Name'),
		'$descr' => t('Layout Description'),
		'$created' => t('Created'),
		'$edited' => t('Edited'),
		'$edit'    => t('Edit'),
		'$share'   => t('Share'),
		'$download'   => t('Download PDL file'),
		'$pages'   => $pages,
		'$channel' => $which,
		'$view'    => t('View'),
	));

	return $o;
}
