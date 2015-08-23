<?php

/* @file connedit.php
 * @brief In this file the connection-editor form is generated and evaluated.
 *
 *
 */

require_once('include/Contact.php');
require_once('include/socgraph.php');
require_once('include/contact_selectors.php');
require_once('include/group.php');
require_once('include/contact_widgets.php');
require_once('include/zot.php');
require_once('include/widgets.php');

/* @brief Initialize the connection-editor
 *
 *
 */

function connedit_init(&$a) {

	if(! local_channel())
		return;

	if((argc() >= 2) && intval(argv(1))) {
		$r = q("SELECT abook.*, xchan.* 
			FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_channel = %d and abook_id = %d LIMIT 1",
			intval(local_channel()),
			intval(argv(1))
		);
		if($r) {
			$a->poi = $r[0];
		}
	}

	$channel = $a->get_channel();
	if($channel)
		head_set_icon($channel['xchan_photo_s']);

}

/* @brief Evaluate posted values and set changes
 *
 */

function connedit_post(&$a) {

	if(! local_channel())
		return;

	$contact_id = intval(argv(1));
	if(! $contact_id)
		return;

	$channel = $a->get_channel();

	// TODO if configured for hassle-free permissions, we'll post the form with ajax as soon as the
	// connection enable is toggled to a special autopost url and set permissions immediately, leaving 
	// the other form elements alone pending a manual submit of the form. The downside is that there 
	// will be a window of opportunity when the permissions have been set but before you've had a chance
	// to review and possibly restrict them. The upside is we won't have to warn you that your connection
	// can't do anything until you save the bloody form.  

	$autopost = (((argc() > 2) && (argv(2) === 'auto')) ? true : false);
		
	$orig_record = q("SELECT * FROM abook WHERE abook_id = %d AND abook_channel = %d LIMIT 1",
		intval($contact_id),
		intval(local_channel())
	);

	if(! $orig_record) {
		notice( t('Could not access contact record.') . EOL);
		goaway($a->get_baseurl(true) . '/connections');
		return; // NOTREACHED
	}

	call_hooks('contact_edit_post', $_POST);

	if(intval($orig_record[0]['abook_self'])) {
		$autoperms = intval($_POST['autoperms']);
		$is_self = true;
	}
	else {
		$autoperms = null;
		$is_self = false;
	}


	$profile_id = $_POST['profile_assign'];
	if($profile_id) {
		$r = q("SELECT profile_guid FROM profile WHERE profile_guid = '%s' AND `uid` = %d LIMIT 1",
			dbesc($profile_id),
			intval(local_channel())
		);
		if(! count($r)) {
			notice( t('Could not locate selected profile.') . EOL);
			return;
		}
	}

	$abook_incl = escape_tags($_POST['abook_incl']);
	$abook_excl = escape_tags($_POST['abook_excl']);

	$hidden = intval($_POST['hidden']);

	$priority = intval($_POST['poll']);
	if($priority > 5 || $priority < 0)
		$priority = 0;

	$closeness = intval($_POST['closeness']);
	if($closeness < 0)
		$closeness = 99;

	$rating = intval($_POST['rating']);
	if($rating < (-10))
		$rating = (-10);
	if($rating > 10)
		$rating = 10;

	$rating_text = trim(escape_tags($_REQUEST['rating_text']));

	$abook_my_perms = 0;

	foreach($_POST as $k => $v) {
		if(strpos($k,'perms_') === 0) {
			$abook_my_perms += $v;
		}
	}

	$new_friend = false;

	if(! $is_self) {

		$signed = $orig_record[0]['abook_xchan'] . '.' . $rating . '.' . $rating_text;

		$sig = base64url_encode(rsa_sign($signed,$channel['channel_prvkey']));

		$z = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1 limit 1",
			dbesc($channel['channel_hash']),
			dbesc($orig_record[0]['abook_xchan'])
		);

		if($z) {
			$record = $z[0]['xlink_id'];
			$w = q("update xlink set xlink_rating = '%d', xlink_rating_text = '%s', xlink_sig = '%s', xlink_updated = '%s' 
				where xlink_id = %d",
				intval($rating),
				dbesc($rating_text),
				dbesc($sig),
				dbesc(datetime_convert()),
				intval($record)
			);
		}
		else {
			$w = q("insert into xlink ( xlink_xchan, xlink_link, xlink_rating, xlink_rating_text, xlink_sig, xlink_updated, xlink_static ) values ( '%s', '%s', %d, '%s', '%s', '%s', 1 ) ",
				dbesc($channel['channel_hash']),
				dbesc($orig_record[0]['abook_xchan']),
				intval($rating),
				dbesc($rating_text),
				dbesc($sig),
				dbesc(datetime_convert())
			);
			$z = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1 limit 1",
				dbesc($channel['channel_hash']),
				dbesc($orig_record[0]['abook_xchan'])
			);
			if($z)
				$record = $z[0]['xlink_id'];
		}
		if($record) {
			proc_run('php','include/ratenotif.php','rating',$record);
		}	
	}

	if(($_REQUEST['pending']) && intval($orig_record[0]['abook_pending'])) {
		$new_friend = true;
		if(! $abook_my_perms) {

			$abook_my_perms = get_channel_default_perms(local_channel());

			$role = get_pconfig(local_channel(),'system','permissions_role');
			if($role) {
				$x = get_role_perms($role);
				if($x['perms_accept'])
					$abook_my_perms = $x['perms_accept'];
			}
		}
	}

	$abook_pending = $new_friend ? 0 : $orig_record[0]['abook_pending'];

	$r = q("UPDATE abook SET abook_profile = '%s', abook_my_perms = %d , abook_closeness = %d, abook_pending = %d,
		abook_incl = '%s', abook_excl = '%s'
		where abook_id = %d AND abook_channel = %d",
		dbesc($profile_id),
		intval($abook_my_perms),
		intval($closeness),
		intval($abook_pending),
		dbesc($abook_incl),
		dbesc($abook_excl),
		intval($contact_id),
		intval(local_channel())
	);

	if($orig_record[0]['abook_profile'] != $profile_id) { 
		//Update profile photo permissions

		logger('A new profile was assigned - updating profile photos');
		require_once('mod/profile_photo.php');
		profile_photo_set_profile_perms($profile_id);

	}


	if($r)
		info( t('Connection updated.') . EOL);
	else
		notice( t('Failed to update connection record.') . EOL);

	if($a->poi && $a->poi['abook_my_perms'] != $abook_my_perms 
		&& (! intval($a->poi['abook_self']))) {
		proc_run('php', 'include/notifier.php', 'permission_update', $contact_id);
	}

	if($new_friend) {
		$default_group = $channel['channel_default_group'];
		if($default_group) {
			require_once('include/group.php');
			$g = group_rec_byhash(local_channel(),$default_group);
			if($g)
				group_add_member(local_channel(),'',$a->poi['abook_xchan'],$g['id']);
		}

		// Check if settings permit ("post new friend activity" is allowed, and 
		// friends in general or this friend in particular aren't hidden) 
		// and send out a new friend activity

		$pr = q("select * from profile where uid = %d and is_default = 1 and hide_friends = 0",
			intval($channel['channel_id'])
		);
		if(($pr) && (! intval($orig_record[0]['abook_hidden'])) && (intval(get_pconfig($channel['channel_id'],'system','post_newfriend')))) {
			$xarr = array();
			$xarr['verb'] = ACTIVITY_FRIEND;
			$xarr['item_wall'] = 1;
			$xarr['item_origin'] = 1;
			$xarr['item_thread_top'] = 1;
			$xarr['owner_xchan'] = $xarr['author_xchan'] = $channel['channel_hash'];
			$xarr['allow_cid'] = $channel['channel_allow_cid'];
			$xarr['allow_gid'] = $channel['channel_allow_gid'];
			$xarr['deny_cid'] = $channel['channel_deny_cid'];
			$xarr['deny_gid'] = $channel['channel_deny_gid'];
			$xarr['item_private'] = (($xarr['allow_cid']||$xarr['allow_gid']||$xarr['deny_cid']||$xarr['deny_gid']) ? 1 : 0);
			$obj = array(
				'type' => ACTIVITY_OBJ_PERSON,
				'title' => $a->poi['xchan_name'],
				'id' => $a->poi['xchan_hash'],
				'link' => array(
					array('rel' => 'alternate', 'type' => 'text/html', 'href' => $a->poi['xchan_url']),
					array('rel' => 'photo', 'type' => $a->poi['xchan_photo_mimetype'], 'href' => $a->poi['xchan_photo_l'])
       			),
   			);
			$xarr['object'] = json_encode($obj);
			$xarr['obj_type'] = ACTIVITY_OBJ_PERSON;

			$xarr['body'] = '[zrl=' . $channel['xchan_url'] . ']' . $channel['xchan_name'] . '[/zrl]' . ' ' . t('is now connected to') . ' ' . '[zrl=' . $a->poi['xchan_url'] . ']' . $a->poi['xchan_name'] . '[/zrl]';

			$xarr['body'] .= "\n\n\n" . '[zrl=' . $a->poi['xchan_url'] . '][zmg=80x80]' . $a->poi['xchan_photo_m'] . '[/zmg][/zrl]';

			post_activity_item($xarr);

		}


		// pull in a bit of content if there is any to pull in
		proc_run('php','include/onepoll.php',$contact_id);

	}

	// Refresh the structure in memory with the new data

	$r = q("SELECT abook.*, xchan.* 
		FROM abook left join xchan on abook_xchan = xchan_hash
		WHERE abook_channel = %d and abook_id = %d LIMIT 1",
		intval(local_channel()),
		intval($contact_id)
	);
	if($r) {
		$a->poi = $r[0];
	}

	if($new_friend) {
		$arr = array('channel_id' => local_channel(), 'abook' => $a->poi);
		call_hooks('accept_follow', $arr);
	}

	if(! is_null($autoperms)) 
		set_pconfig(local_channel(),'system','autoperms',(($autoperms) ? $abook_my_perms : 0));

	connedit_clone($a);

	return;

}

/* @brief Clone connection
 *
 *
 */

function connedit_clone(&$a) {

		if(! $a->poi)
			return;
		$clone = $a->poi;

		unset($clone['abook_id']);
		unset($clone['abook_account']);
		unset($clone['abook_channel']);

		build_sync_packet(0 /* use the current local_channel */, array('abook' => array($clone)));
}

/* @brief Generate content of connection edit page
 *
 *
 */

function connedit_content(&$a) {

	$sort_type = 0;
	$o = '';

	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return login();
	}

	$channel = $a->get_channel();
	$my_perms = get_channel_default_perms(local_channel());
	$role = get_pconfig(local_channel(),'system','permissions_role');
	if($role) {
		$x = get_role_perms($role);
		if($x['perms_accept'])
			$my_perms = $x['perms_accept'];
	}

	if($my_perms) {
		$o .= "<script>function connectDefaultShare() {
		\$('.abook-edit-me').each(function() {
			if(! $(this).is(':disabled'))
				$(this).prop('checked', false);
		});\n\n";
		$perms = get_perms();
		foreach($perms as $p => $v) {
			if($my_perms & $v[1]) {
				$o .= "\$('#me_id_perms_" . $p . "').prop('checked', true); \n";
			}
		}
		$o .= " }\n</script>\n";
	}

	if(argc() == 3) {

		$contact_id = intval(argv(1));
		if(! $contact_id)
			return;

		$cmd = argv(2);

		$orig_record = q("SELECT abook.*, xchan.* FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_id = %d AND abook_channel = %d AND abook_self = 0 LIMIT 1",
			intval($contact_id),
			intval(local_channel())
		);

		if(! count($orig_record)) {
			notice( t('Could not access address book record.') . EOL);
			goaway($a->get_baseurl(true) . '/connections');
		}
		
		if($cmd === 'update') {

			// pull feed and consume it, which should subscribe to the hub.
			proc_run('php',"include/poller.php","$contact_id");
			goaway($a->get_baseurl(true) . '/connedit/' . $contact_id);

		}

		if($cmd === 'refresh') {
			if(! zot_refresh($orig_record[0],get_app()->get_channel())) 
				notice( t('Refresh failed - channel is currently unavailable.') );
			goaway($a->get_baseurl(true) . '/connedit/' . $contact_id);
		}

		if($cmd === 'block') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_BLOCKED)) {
				connedit_clone($a);
			}
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connedit/' . $contact_id);
		}

		if($cmd === 'ignore') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_IGNORED)) {
				connedit_clone($a);
			}
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connedit/' . $contact_id);
		}

		if($cmd === 'archive') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_ARCHIVED)) {
				connedit_clone($a);
			}
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connedit/' . $contact_id);
		}

		if($cmd === 'hide') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_HIDDEN)) {
				connedit_clone($a);
			}
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connedit/' . $contact_id);
		}

		// We'll prevent somebody from unapproving an already approved contact.
		// Though maybe somebody will want this eventually (??)

		if($cmd === 'approve') {
			if(intval($orig_record[0]['abook_pending'])) {
				if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_PENDING)) {
					connedit_clone($a);
				}
				else
					notice(t('Unable to set address book parameters.') . EOL);
			}
			goaway($a->get_baseurl(true) . '/connedit/' . $contact_id);
		}


		if($cmd === 'drop') {

			require_once('include/Contact.php');

// FIXME
// We need to send either a purge or a refresh packet to the other side (the channel being unfriended). 
// The issue is that the abook DB record _may_ get destroyed when we call contact_remove. As the notifier runs
// in the background there could be a race condition preventing this packet from being sent in all cases.
// PLACEHOLDER

			contact_remove(local_channel(), $orig_record[0]['abook_id']);
			build_sync_packet(0 /* use the current local_channel */, 
				array('abook' => array(array(
					'abook_xchan' => $orig_record[0]['abook_xchan'],
					'entry_deleted' => true))
				)
			);

			info( t('Connection has been removed.') . EOL );
			if(x($_SESSION,'return_url'))
				goaway($a->get_baseurl(true) . '/' . $_SESSION['return_url']);
			goaway($a->get_baseurl(true) . '/contacts');

		}
	}

	if($a->poi) {

		$contact_id = $a->poi['abook_id'];
		$contact = $a->poi;

		$buttons = array(

			'view' => array(
				'label' => t('View Profile'),
				'url'   => chanlink_cid($contact['abook_id']), 
				'sel'   => '',
				'title' => sprintf( t('View %s\'s profile'), $contact['xchan_name']),
			),

			'refresh' => array(
				'label' => t('Refresh Permissions'),
				'url'   => $a->get_baseurl(true) . '/connedit/' . $contact['abook_id'] . '/refresh', 
				'sel'   => '',
				'title' => t('Fetch updated permissions'),
			),

			'recent' => array(
				'label' => t('Recent Activity'),
				'url'   => $a->get_baseurl(true) . '/network/?f=&cid=' . $contact['abook_id'], 
				'sel'   => '',
				'title' => t('View recent posts and comments'),
			),

			'block' => array(
				'label' => (intval($contact['abook_blocked']) ? t('Unblock') : t('Block')),
				'url'   => $a->get_baseurl(true) . '/connedit/' . $contact['abook_id'] . '/block', 
				'sel'   => (intval($contact['abook_blocked']) ? 'active' : ''),
				'title' => t('Block (or Unblock) all communications with this connection'),
				'info'   => (intval($contact['abook_blocked']) ? t('This connection is blocked!') : ''),
			),

			'ignore' => array(
				'label' => (intval($contact['abook_ignored']) ? t('Unignore') : t('Ignore')),
				'url'   => $a->get_baseurl(true) . '/connedit/' . $contact['abook_id'] . '/ignore', 
				'sel'   => (intval($contact['abook_ignored']) ? 'active' : ''),
				'title' => t('Ignore (or Unignore) all inbound communications from this connection'),
				'info'   => (intval($contact['abook_ignored']) ? t('This connection is ignored!') : ''),
			),

			'archive' => array(
				'label' => (intval($contact['abook_archived']) ? t('Unarchive') : t('Archive')),
				'url'   => $a->get_baseurl(true) . '/connedit/' . $contact['abook_id'] . '/archive', 
				'sel'   => (intval($contact['abook_archived']) ? 'active' : ''),
				'title' => t('Archive (or Unarchive) this connection - mark channel dead but keep content'),
				'info'   => (intval($contact['abook_archived']) ? t('This connection is archived!') : ''),
			),

			'hide' => array(
				'label' => (intval($contact['abook_hidden']) ? t('Unhide') : t('Hide')),
				'url'   => $a->get_baseurl(true) . '/connedit/' . $contact['abook_id'] . '/hide', 
				'sel'   => (intval($contact['abook_hidden']) ? 'active' : ''),
				'title' => t('Hide or Unhide this connection from your other connections'),
				'info'   => (intval($contact['abook_hidden']) ? t('This connection is hidden!') : ''),
			),

			'delete' => array(
				'label' => t('Delete'),
				'url'   => $a->get_baseurl(true) . '/connedit/' . $contact['abook_id'] . '/drop', 
				'sel'   => '',
				'title' => t('Delete this connection'),
			),

		);

		$self = false;

		if(intval($contact['abook_self']))
			$self = true;

		require_once('include/contact_selectors.php');

		$tpl = get_markup_template("abook_edit.tpl");

		if(feature_enabled(local_channel(),'affinity')) {

			$labels = array(
				t('Me'),
				t('Family'),
				t('Friends'),
				t('Acquaintances'),
				t('All')
			);
			call_hooks('affinity_labels',$labels);
			$label_str = '';

			if($labels) {
				foreach($labels as $l) {
					if($label_str) {
						$label_str .= ", '|'";
						$label_str .= ", '" . $l . "'";
					}
					else
						$label_str .= "'" . $l . "'";
				}
			}

			$slider_tpl = get_markup_template('contact_slider.tpl');
			$slide = replace_macros($slider_tpl,array(
				'$min' => 1,
				'$val' => (($contact['abook_closeness']) ? $contact['abook_closeness'] : 99),
				'$labels' => $label_str,
			));
		}

		$rating_val = 0;
		$rating_text = '';

		$xl = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1",
			dbesc($channel['channel_hash']),
			dbesc($contact['xchan_hash'])
		);

		if($xl) {
			$rating_val = intval($xl[0]['xlink_rating']);
			$rating_text = $xl[0]['xlink_rating_text'];
		}

		$poco_rating = get_config('system','poco_rating_enable');

		// if unset default to enabled
		if($poco_rating === false)
			$poco_rating = true;

		if($poco_rating) {
			$rating = replace_macros(get_markup_template('rating_slider.tpl'),array(
				'$min' => -10,
				'$val' => $rating_val
			));
		}
		else {
			$rating = false;
		}


		$perms = array();
		$channel = $a->get_channel();

		$global_perms = get_perms();
		$existing = get_all_perms(local_channel(),$contact['abook_xchan']); 

		$unapproved = array('pending', t('Approve this connection'), '', t('Accept connection to allow communication'), array(t('No'),('Yes')));

		$multiprofs = ((feature_enabled(local_channel(),'multi_profiles')) ? true : false);

		if($slide && !$multiprofs)
			$affinity = t('Set Affinity');

		if(!$slide && $multiprofs)
			$affinity = t('Set Profile');

		if($slide && $multiprofs)
			$affinity = t('Set Affinity & Profile');

		foreach($global_perms as $k => $v) {
			$thisperm = (($contact['abook_my_perms'] & $v[1]) ? "1" : '');
			$checkinherited = ((($channel[$v[0]]) && ($channel[$v[0]] != PERMS_SPECIFIC)) ? "1" : '');

			// For auto permissions (when $self is true) we don't want to look at existing
			// permissions because they are enabled for the channel owner
			if((! $self) && ($existing[$k]))
				$thisperm = "1";

			$perms[] = array('perms_' . $k, $v[3], (($contact['abook_their_perms'] & $v[1]) ? "1" : ""),$thisperm, $v[1], (($channel[$v[0]] == PERMS_SPECIFIC) ? '' : '1'), $v[4], $checkinherited);
		}

		$o .= replace_macros($tpl,array(

			'$header'         => (($self) ? t('Connection Default Permissions') : sprintf( t('Connection: %s'),$contact['xchan_name'])),
			'$autoperms'      => array('autoperms',t('Apply these permissions automatically'), ((get_pconfig(local_channel(),'system','autoperms')) ? 1 : 0), 'Connection requests will be approved without your interaction', array(t('No'),('Yes'))),
			'$addr'           => $contact['xchan_addr'],
			'$addr_text'      => t('This connection\'s address is'),
			'$notself'        => (($self) ? '' : '1'),
			'$self'           => (($self) ? '1' : ''),
			'$autolbl'        => t('The permissions indicated on this page will be applied to all new connections.'),
			'$buttons'        => (($self) ? '' : $buttons),
			'$lbl_slider'     => t('Slide to adjust your degree of friendship'),
			'$lbl_rating'     => t('Rating'),
			'$lbl_rating_label' => t('Slide to adjust your rating'),
			'$lbl_rating_txt' => t('Optionally explain your rating'),
			'$connfilter'     => feature_enabled(local_channel(),'connfilter'),
			'$connfilter_label' => t('Custom Filter'),
			'$incl'           => array('abook_incl',t('Only import posts with this text'), $contact['abook_incl'],t('words one per line or #tags or /patterns/, leave blank to import all posts')), 
			'$excl'           => array('abook_excl',t('Do not import posts with this text'), $contact['abook_excl'],t('words one per line or #tags or /patterns/, leave blank to import all posts')), 
			'$rating_text'    => array('rating_text', t('Optionally explain your rating'),$rating_text,''),
			'$rating_info'    => t('This information is public!'),
			'$rating'         => $rating,
			'$rating_val'     => $rating_val,
			'$slide'          => $slide,
			'$affinity'       => $affinity,
			'$pending_label'  => t('Connection Pending Approval'),
			'$pending_modal_title' => t('Connection Request'),
			'$pending_modal_body' => sprintf(t('(%s) would like to connect with you. Please approve this connection to allow communication.'), $contact['xchan_addr']),
			'$pending_modal_approve' => t('Approve'),
			'$pending_modal_dismiss' => t('Approve Later'),
			'$is_pending'     => (intval($contact['abook_pending']) ? 1 : ''),
			'$unapproved'     => $unapproved,
			'$inherited'      => t('inherited'),
			'$submit'         => t('Submit'),
			'$lbl_vis2'       => sprintf( t('Please choose the profile you would like to display to %s when viewing your profile securely.'), $contact['xchan_name']),
			'$close'          => $contact['abook_closeness'],
			'$them'           => t('Their Settings'),
			'$me'             => t('My Settings'),
			'$perms'          => $perms,
			'$permlbl'        => t('Individual Permissions'),
			'$permnote'       => t('Some permissions may be inherited from your channel\'s <a href="settings"><strong>privacy settings</strong></a>, which have higher priority than individual settings. You can <strong>not</strong> change those settings here.'),
			'$permnote_self'  => t('Some permissions may be inherited from your channel\'s <a href="settings"><strong>privacy settings</strong></a>, which have higher priority than individual settings. You can change those settings here but they wont have any impact unless the inherited setting changes.'),
			'$lastupdtext'    => t('Last update:'),
			'$last_update'    => relative_date($contact['abook_connected']),
			'$profile_select' => contact_profile_assign($contact['abook_profile']),
			'$multiprofs'     => $multiprofs,
			'$contact_id'     => $contact['abook_id'],
			'$name'           => $contact['xchan_name'],

		));

		$arr = array('contact' => $contact,'output' => $o);

		call_hooks('contact_edit', $arr);

		return $arr['output'];

	}


}
