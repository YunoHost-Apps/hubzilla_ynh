<?php /** @file */



function findpeople_widget() {

	if(get_config('system','invitation_only')) {
		$x = get_pconfig(local_channel(),'system','invites_remaining');
		if($x || is_site_admin()) {
			App::$page['aside'] .= '<div class="side-link" id="side-invite-remain">' 
			. sprintf( tt('%d invitation available','%d invitations available',$x), $x) 
			. '</div>' . $inv;
		}
	}

	$advanced_search = ((local_channel() && feature_enabled(local_channel(),'advanced_dirsearch')) ? t('Advanced') : false);
 
	return replace_macros(get_markup_template('peoplefind.tpl'),array(
		'$findpeople' => t('Find Channels'),
		'$desc' => t('Enter name or interest'),
		'$label' => t('Connect/Follow'),
		'$hint' => t('Examples: Robert Morgenstein, Fishing'),
		'$findthem' => t('Find'),
		'$suggest' => t('Channel Suggestions'),
		'$similar' => '', // FIXME and uncomment when mod/match working // t('Similar Interests'),
		'$random' => t('Random Profile'),
		'$inv' => t('Invite Friends'),
		'$advanced_search' => $advanced_search,
		'$advanced_hint' => "\r\n" . t('Advanced example: name=fred and country=iceland'),
		'$loggedin' => local_channel()
	));

}


function fileas_widget($baseurl,$selected = '') {

	if(! local_channel())
		return '';

	$terms = array();
	$r = q("select distinct(term) from term where uid = %d and ttype = %d order by term asc",
		intval(local_channel()),
		intval(TERM_FILE)
	);
	if(! $r)
		return;

	foreach($r as $rr)
		$terms[] = array('name' => $rr['term'], 'selected' => (($selected == $rr['term']) ? 'selected' : ''));

	return replace_macros(get_markup_template('fileas_widget.tpl'),array(
		'$title' => t('Saved Folders'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('Everything'),
		'$terms' => $terms,
		'$base' => $baseurl,

	));
}

function categories_widget($baseurl,$selected = '') {
	
	if(! feature_enabled(App::$profile['profile_uid'],'categories'))
		return '';

	$item_normal = item_normal();

	$terms = array();
	$r = q("select distinct(term.term)
                from term join item on term.oid = item.id
                where item.uid = %d
                and term.uid = item.uid
                and term.ttype = %d
				and term.otype = %d
                and item.owner_xchan = '%s'
				and item.item_wall = 1
				$item_normal
                order by term.term asc",
		intval(App::$profile['profile_uid']),
	        intval(TERM_CATEGORY),
			intval(TERM_OBJ_POST),
	        dbesc(App::$profile['channel_hash'])
	);
	if($r && count($r)) {
		foreach($r as $rr)
			$terms[] = array('name' => $rr['term'], 'selected' => (($selected == $rr['term']) ? 'selected' : ''));

		return replace_macros(get_markup_template('categories_widget.tpl'),array(
			'$title' => t('Categories'),
			'$desc' => '',
			'$sel_all' => (($selected == '') ? 'selected' : ''),
			'$all' => t('Everything'),
			'$terms' => $terms,
			'$base' => $baseurl,

		));
	}
	return '';
}

function common_friends_visitor_widget($profile_uid) {

	if(local_channel() == $profile_uid)
		return;

	$observer_hash = get_observer_hash();

	if((! $observer_hash) || (! perm_is_allowed($profile_uid,$observer_hash,'view_contacts')))
		return;

	require_once('include/socgraph.php');

	$t = count_common_friends($profile_uid,$observer_hash);
	if(! $t)
		return;

	$r = common_friends($profile_uid,$observer_hash,0,5,true);

	return replace_macros(get_markup_template('remote_friends_common.tpl'), array(
		'$desc' =>  sprintf( tt("%d connection in common", "%d connections in common", $t), $t),
		'$base' => z_root(),
		'$uid' => $profile_uid,
		'$cid' => $observer,
		'$linkmore' => (($t > 5) ? 'true' : ''),
		'$more' => t('show more'),
		'$items' => $r
	)); 

};


