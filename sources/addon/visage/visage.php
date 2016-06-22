<?php
/**
 * Name: Visage
 * Description: Who viewed my channel/profile
 * Version: 1.0
 * Author: Mike Macgirvin <mike@zothub.com>
 * Maintainer: none
 */


/**
 * Visit $yoursite/visage
 * Lots of room for improvement, turning into a widget, etc.
 * The time of visit can be seen by hovering over the name (not the photo). This way I could re-use an existing template.  
 */


function visage_load() {

	register_hook('magic_auth_success', 'addon/visage/visage.php', 'visage_magic_auth');
	register_hook('feature_settings',      'addon/visage/visage.php', 'visage_settings');
	register_hook('feature_settings_post', 'addon/visage/visage.php', 'visage_settings_post');
}


function visage_unload() {

	unregister_hook('magic_auth_success', 'addon/visage/visage.php', 'visage_magic_auth');
	unregister_hook('feature_settings',      'addon/visage/visage.php', 'visage_settings');
	unregister_hook('feature_settings_post', 'addon/visage/visage.php', 'visage_settings_post');

}



function visage_magic_auth($a, &$b) {

//	logger('visage: ' . print_r($b,true));

	if((! strstr($b['url'],'/channel/')) && (! strstr($b['url'],'/profile/'))) {
//		logger('visage: exiting: ' . $b['url']);
		return;
	}

	$p = preg_match('/http(.*?)(channel|profile)\/(.*?)($|[\/\?\&])/',$b['url'],$matches);
	if(! $p) {
//		logger('visage: no matching pattern');
		return;
	}

//	logger('visage: matches ' . print_r($matches,true));
	
	$nick = $matches[3];

	if($_SERVER['HTTP_DNT'] === '1' || intval($_SESSION['DNT']))
		return;

	$c = q("select channel_id, channel_hash from channel where channel_address = '%s' limit 1",
		dbesc($nick)
	);

	if(! $c)
		return;

	$x = get_pconfig($c[0]['channel_id'],'visage','visitors');
	if(! is_array($x))
		$n = array(array($b['xchan']['xchan_hash'],datetime_convert()));
	else {
		$n = array();

		for($z = ((count($x) > 24) ? count($x) - 24 : 0); $z < count($x); $z ++)
			if($x[$z][0] != $b['xchan']['xchan_hash'])
				$n[] = $x[$z];
		$n[] = array($b['xchan']['xchan_hash'],datetime_convert());
	}

//	logger('visage set: ' . print_r($n,true));

	set_pconfig($c[0]['channel_id'],'visage','visitors',$n);
	return;

}


function visage_module() {}

function visage_content(&$a) {

	if(! local_channel())
		return;


	$o = '<h3>' . t('Recent Channel/Profile Viewers') . '</h3>';

	$enabled = get_pconfig(local_channel(),'visage','enabled');

	if(! $enabled) {
		$o .= t('This plugin/addon has not been configured.') . EOL;
		$o .= sprintf( t('Please visit the Visage settings on %s'), '<a href="settings/featured">' . t('your feature settings page') . '</a>');
		return $o;
	}

	// let's play fair.

	require_once('include/channel.php');

	if(! is_public_profile())
		return $o;

	$x = get_pconfig(local_channel(),'visage','visitors');
	if((! $x) || (! is_array($x))) {
		$o .= t('No entries.');
		return $o;
	}

	$chans = '';
	for($n = 0; $n < count($x); $n ++) {
		if($chans)
			$chans .= ',';
		$chans .= "'" . dbesc($x[$n][0]) . "'";
	}
	if($chans) {
		$r = q("select * from xchan where xchan_hash in ( $chans )");
	}
	if($r) {
        $tpl = get_markup_template('common_friends.tpl');

		for($g = count($x) - 1; $g >= 0; $g --) {
			foreach($r as $rr) {
				if($x[$g][0] == $rr['xchan_hash'])
					break;
			}

            $o .= replace_macros($tpl,array(
                '$url'   => (($rr['xchan_flags'] & XCHAN_FLAGS_HIDDEN) ? z_root() : chanlink_url($rr['xchan_url'])),
                '$name'  => $rr['xchan_name'],
                '$photo' => $rr['xchan_photo_m'],
                '$tags'  => (($rr['xchan_flags'] & XCHAN_FLAGS_HIDDEN) ? z_root() : chanlink_url($rr['xchan_url'])),
				'$note'  => relative_date($x[$g][1])
            ));
        }

        $o .= cleardiv();
    }

    return $o;
}

		
function visage_settings(&$a,&$s) {

	if(! local_channel())
		return;

	/* Add our stylesheet to the page so we can make our settings look nice */

	//head_add_css('/addon/visage/visage.css');

	/* Get the current state of our config variables */

	$enabled = get_pconfig(local_channel(),'visage','enabled');
	$checked = (($enabled) ? 1 : false);
	$css = (($enabled) ? '' : '-disabled');

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('visage', t('Enable Visage Visitor Logging'), $checked, '', array(t('No'),t('Yes'))),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('visage', t('Visage Settings'), '', t('Submit')),
		'$content'	=> $sc
	));
}


function visage_settings_post(&$a,&$b) {

	if(x($_POST,'visage-submit')) {
		set_pconfig(local_channel(),'visage','enabled',intval($_POST['visage']));
	}

}
