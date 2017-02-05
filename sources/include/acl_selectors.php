<?php
/**
 * @file include/acl_selectors.php
 *
 * @package acl_selectors
 */

/**
 * @brief
 *
 * @param string $selname
 * @param string $selclass
 * @param mixed $preselected
 * @param number $size
 * @return string
 */
function group_select($selname, $selclass, $preselected = false, $size = 4) {

	$o = '';

	$o .= "<select name=\"{$selname}[]\" id=\"$selclass\" class=\"$selclass\" multiple=\"multiple\" size=\"$size\">\r\n";

	$r = q("SELECT * FROM groups WHERE deleted = 0 AND uid = %d ORDER BY gname ASC",
		intval(local_channel())
	);


	$arr = array('group' => $r, 'entry' => $o);

	// e.g. 'network_pre_group_deny', 'profile_pre_group_allow'

	call_hooks(App::$module . '_pre_' . $selname, $arr);

	if($r) {
		foreach($r as $rr) {
			if((is_array($preselected)) && in_array($rr['id'], $preselected))
				$selected = " selected=\"selected\" ";
			else
				$selected = '';
			$trimmed = mb_substr($rr['gname'],0,12);

			$o .= "<option value=\"{$rr['id']}\" $selected title=\"{$rr['name']}\" >$trimmed</option>\r\n";
		}

	}
	$o .= "</select>\r\n";

	call_hooks(App::$module . '_post_' . $selname, $o);

	return $o;
}

function contact_select($selname, $selclass, $preselected = false, $size = 4, $privmail = false, $celeb = false, $privatenet = false, $tabindex = null) {

	$o = '';

	// When used for private messages, we limit correspondence to mutual DFRN/Friendica friends and the selector
	// to one recipient. By default our selector allows multiple selects amongst all contacts.

	$sql_extra = '';

	$tabindex = ($tabindex > 0 ? 'tabindex="$tabindex"' : '');

	if($privmail)
		$o .= "<select name=\"$selname\" id=\"$selclass\" class=\"$selclass\" size=\"$size\" $tabindex >\r\n";
	else
		$o .= "<select name=\"{$selname}[]\" id=\"$selclass\" class=\"$selclass\" multiple=\"multiple\" size=\"$size\" $tabindex>\r\n";

	$r = q("SELECT abook_id, xchan_name, xchan_url, xchan_photo_s from abook left join xchan on abook_xchan = xchan_hash
		where abook_self = 0 and abook_channel = %d
		$sql_extra
		ORDER BY xchan_name ASC",
		intval(local_channel())
	);


	$arr = array('contact' => $r, 'entry' => $o);

	// e.g. 'network_pre_contact_deny', 'profile_pre_contact_allow'

	call_hooks(App::$module . '_pre_' . $selname, $arr);

	if($r) {
		foreach($r as $rr) {
			if((is_array($preselected)) && in_array($rr['id'], $preselected))
				$selected = ' selected="selected" ';
			else
				$selected = '';

			$trimmed = mb_substr($rr['xchan_name'], 0, 20);

			$o .= "<option value=\"{$rr['abook_id']}\" $selected title=\"{$rr['xchan_name']}|{$rr['xchan_url']}\" >$trimmed</option>\r\n";
		}
	}

	$o .= "</select>\r\n";

	call_hooks(App::$module . '_post_' . $selname, $o);

	return $o;
}


function fixacl(&$item) {
	$item = str_replace(array('<', '>'), array('', ''), $item);
}

/**
* Builds a modal dialog for editing permissions, using acl_selector.tpl as the template.
*
* @param array   $defaults Optional access control list for the initial state of the dialog.
* @param boolean $show_jotnets Whether plugins for federated networks should be included in the permissions dialog
* @param PermissionDescription $emptyACL_description - An optional description for the permission implied by selecting an empty ACL. Preferably an instance of PermissionDescription.
* @param string  $dialog_description Optional message to include at the top of the dialog. E.g. "Warning: Post permissions cannot be changed once sent".
* @param string  $context_help Allows the dialog to present a help icon. E.g. "acl_dialog_post"
* @param boolean $readonly Not implemented yet. When implemented, the dialog will use acl_readonly.tpl instead, so that permissions may be viewed for posts that can no longer have their permissions changed.
*
* @return string html modal dialog built from acl_selector.tpl
*/
function populate_acl($defaults = null,$show_jotnets = true, $emptyACL_description = '', $dialog_description = '', $context_help = '', $readonly = false) {

	$allow_cid = $allow_gid = $deny_cid = $deny_gid = false;
	$showall_origin = '';
	$showall_icon   = 'fa-globe';
	$role = get_pconfig(local_channel(), 'system', 'permissions_role');

	if(! $emptyACL_description) {
		$showall_caption = t('Visible to your default audience');

	} else if(is_a($emptyACL_description, '\\Zotlabs\\Lib\\PermissionDescription')) {
		$showall_caption = $emptyACL_description->get_permission_description();
		$showall_origin  = (($role === 'custom') ? $emptyACL_description->get_permission_origin_description() : '');
		$showall_icon    = $emptyACL_description->get_permission_icon();
	} else {
		// For backwards compatibility we still accept a string... for now!
		$showall_caption = $emptyACL_description;
	}


	if(is_array($defaults)) {
		$allow_cid = ((strlen($defaults['allow_cid']))
			? explode('><', $defaults['allow_cid']) : array() );
		$allow_gid = ((strlen($defaults['allow_gid']))
			? explode('><', $defaults['allow_gid']) : array() );
		$deny_cid  = ((strlen($defaults['deny_cid']))
			? explode('><', $defaults['deny_cid']) : array() );
		$deny_gid  = ((strlen($defaults['deny_gid']))
			? explode('><', $defaults['deny_gid']) : array() );
		array_walk($allow_cid,'fixacl');
		array_walk($allow_gid,'fixacl');
		array_walk($deny_cid,'fixacl');
		array_walk($deny_gid,'fixacl');
	}


	$channel = ((local_channel()) ? \App::get_channel() : '');
	$has_acl = false;
	$single_group = false;
	$just_me = false;
	$custom = false;

	if($allow_cid || $allow_gid || $deny_gid || $deny_cid) {
		$has_acl = true;
		$custom = true;
	}

	if(count($allow_gid) === 1 && (! $allow_cid) && (! $deny_gid) && (! $deny_cid)) {
		$single_group = true;
		$custom = false;
	}

	if(count($allow_cid) === 1 && $channel && $allow_cid[0] = $channel['channel_hash'] && (! $allow_gid) && (! $deny_gid) && (! $deny_cid)) {
		$just_me = true;
		$custom = false;
	}	

	$r = q("SELECT id, hash, gname FROM groups WHERE deleted = 0 AND uid = %d ORDER BY gname ASC",
		intval(local_channel())
	);

	if($r) {
		foreach($r as $rr) {
			$selected = (($single_group && $rr['hash'] === $allow_gid[0]) ? ' selected = "selected" ' : '');
			$groups .= '<option id="' . $rr['id'] . '" value="' . $rr['hash'] . '"' . $selected . '>' . $rr['gname'] . '</option>' . "\r\n";
		}
	}

	$tpl = get_markup_template("acl_selector.tpl");
	$o = replace_macros($tpl, array(
		'$showall'         => $showall_caption,
		'$onlyme'          => t('Only me'),
		'$groups'	       => $groups,
		'$public_selected' => (($has_acl) ? false : true),
		'$justme_selected' => $just_me,
		'$custom_selected' => $custom,
		'$showallOrigin'   => $showall_origin,
		'$showallIcon'     => $showall_icon,
		'$select_label'    => t('Who can see this?'),
		'$custom'          => t('Custom selection'),
		'$showlimitedDesc' => t('Select "Show" to allow viewing. "Don\'t show" lets you override and limit the scope of "Show".'),
		'$show'	           => t('Show'),
		'$hide'	           => t("Don't show"),
		'$search'          => t('Search'),
		'$allowcid'        => json_encode($allow_cid),
		'$allowgid'        => json_encode($allow_gid),
		'$denycid'         => json_encode($deny_cid),
		'$denygid'         => json_encode($deny_gid),
		'$aclModalTitle'   => t('Permissions'),
		'$aclModalDesc'    => $dialog_description,
		'$aclModalDismiss' => t('Close'),
		'$helpUrl'         => (($context_help == '') ? '' : (z_root() . '/help/' . $context_help))
	));

	return $o;
}

/**
 * Returns a string that's suitable for passing as the $dialog_description argument to a
 * populate_acl() call for wall posts or network posts.
 *
 * This string is needed in 3 different files, and our .po translation system currently
 * cannot be used as a string table (because the value is always the key in english) so
 * I've centralized the value here (making this function name the "key") until we have a
 * better way.
 *
 * @return string Description to present to user in modal permissions dialog
 */
function get_post_aclDialogDescription() {

	// I'm trying to make two points in this description text - warn about finality of wall
	// post permissions, and try to clear up confusion that these permissions set who is
	// *shown* the post, istead of who is able to see the post, i.e. make it clear that clicking
	// the "Show"  button on a group does not post it to the feed of people in that group, it
	// mearly allows those people to view the post if they are viewing/following this channel.
	$description = t('Post permissions %s cannot be changed %s after a post is shared.</br />These permissions set who is allowed to view the post.');

	// Lets keep the emphasis styling seperate from the translation. It may change.
	$emphasisOpen  = '<b><a href="' . z_root() . '/help/acl_dialog_post" target="hubzilla-help">';
	$emphasisClose = '</a></b>';

	return sprintf($description, $emphasisOpen, $emphasisClose);
}
