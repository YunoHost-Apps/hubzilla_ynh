<?php
/**
 *
 * Name: Openclipatar
 * Description: Allows you to select a profile photo from openclipart.org easily
 * Version: 1.0
 * Author: Habeas Codice <https://federated.social>
 * Maintainer: Habeas Codice <https://federated.social>
 */

function openclipatar_load() {
	register_hook('profile_photo_content_end', 'addon/openclipatar/openclipatar.php', 'openclipatar_profile_photo_content_end');
}

function openclipatar_unload() {
	unregister_hook('profile_photo_content_end', 'addon/openclipatar/openclipatar.php', 'openclipatar_profile_photo_content_end');
}

function openclipatar_module() { return; }

function openclipatar_plugin_admin_post(&$a) {
	$prefclipids = ((x($_POST, 'prefclipids')) ? notags(trim($_POST['prefclipids'])) : '');
	$defsearch = ((x($_POST, 'defsearch')) ? notags(trim($_POST['defsearch'])) : '');
	$prefclipmsg = ((x($_POST, 'prefclipmsg')) ? notags(trim($_POST['prefclipmsg'])) : '');
	set_config('openclipatar', 'prefclipids', $prefclipids);
	set_config('openclipatar', 'defsearch', $defsearch);
	set_config('openclipatar', 'prefclipmsg', $prefclipmsg);
	if(is_numeric($_POST['returnafter']))
		set_config('openclipatar', 'returnafter', $_POST['returnafter']);
	set_config('openclipatar', 'sortprefids', $_POST['sortprefids']);
	set_config('openclipatar', 'sortids', $_POST['sortids']);
}

function openclipatar_plugin_admin(&$a, &$o) {
	$t = get_markup_template('admin.tpl', 'addon/openclipatar/');
	$prefclipids = get_config('openclipatar', 'prefclipids');
	$defsearch = get_config('openclipatar', 'defsearch');
	$returnafter = get_config('openclipatar', 'returnafter');
	$sortprefids = get_config('openclipatar', 'sortprefids');
	if($sortprefids == "0") $sortprefids = 'date'; // Backwards compatibility
	if($sortprefids == "1") $sortprefids = 'asentered';
	$sortids = get_config('openclipatar', 'sortids');
	$prefclipmsg = get_config('openclipatar', 'prefclipmsg');
	
	if(! $defsearch) 
		$defsearch = 'avatar';
		
	if(! $prefclipmsg)
		$prefclipmsg = t('System defaults:');
	
	$o = replace_macros( $t, array(
		'$submit' => t('Submit'),
		'$prefclipids' => array('prefclipids', t('Preferred Clipart IDs'), $prefclipids, t('List of preferred clipart ids. These will be shown first.')),
		'$defsearch' => array('defsearch', t('Default Search Term'), $defsearch, t('The default search term. These will be shown second.')),
		'$returnafter' => array('returnafter', t('Return After'), $returnafter, t('Page to load after image selection.'), array(
			0 => t('View Profile'),
			1 => t('Edit Profile'),
			2 => t('Profile List'), 
		)),
		'$sortprefids' => array('sortprefids', t('Order of Preferred'), $sortprefids, t('Sort order of preferred clipart ids.'), array(
			'date' => t('Newest first'),
			//'downloads' => t('Most downloaded first'), These don't work yet due to a bug(?) in openclipart.org's search
			//'favorites' => t('Most liked first'),
			'asentered' => t('As entered'),
		)),
		'$sortids' => array('sortids', t('Order of other'), $sortids, t('Sort order of other clipart ids.'), array(
			'date' => t('Newest first'),
			'downloads' => t('Most downloaded first'),
			'favorites' => t('Most liked first'),
		)),
		'$prefclipmsg' => array('prefclipmsg', t('Preferred IDs Message'), $prefclipmsg, t('Message to display above preferred results.')),
		//'$nperpage' => array('nperpage', t('Results pagination'), $nperpage, t('Enter the number of results you wish to pull from the server each page')),
	));
}

function openclipatar_decode_result($arr) {
	$dbt = empty($arr['drawn_by']) ? (t('Uploaded by: ') . $arr['uploader']) : (t('Drawn by: ') . $arr['drawn_by']);
	$r = array(
		'title' => $arr['title'],
		'uploader' => $arr['uploader'],
		'drawn_by' => $arr['drawn_by'],
		'ncomments' => count($arr['comments'], COUNT_NORMAL),
		'nfaves' => $arr['total_favorites'],
		'ndownloads' => $arr['downloaded_by'],
		'desc' => $arr['description'],
		'tags' => $arr['tags'],
		'link' => $arr['detail_link'],
		'thumb' => 'https://openclipart.org/image/80px/svg_to_png/' . $arr['id'] . '/' . $arr['id'] . '.png',
		'id' => $arr['id'],
		'created' => $arr['created'],
		'dbtext' => $dbt,
		'uselink' => '/openclipatar/use/' . $arr['id'],
	);
	return $r;
}

function openclipatar_sort_result(&$arr, array $prefclipids, $sortprefids) {
	if($sortprefids != 'asentered') // Got them in the right order from openclipart.org
		return;

	usort($arr, function($a, $b) use ($prefclipids) {
		$idxa = array_search($a['id'], $prefclipids);
		$idxb = array_search($b['id'], $prefclipids);
		return ($idxa < $idxb ? -1 : 1);
	});
}

function openclipatar_profile_photo_content_end(&$a, &$o) {
	
	$prefclipids = get_config('openclipatar', 'prefclipids');
	$defsearch = get_config('openclipatar', 'defsearch');
	$returnafter = get_config('openclipatar', 'returnafter');
	$sortprefids = get_config('openclipatar', 'sortprefids');
	if($sortprefids == "0") $sortprefids = 'date'; // Backwards compatibility
	if($sortprefids == "1") $sortprefids = 'asentered';
	$sortids = get_config('openclipatar', 'sortids');
	$prefclipmsg = get_config('openclipatar', 'prefclipmsg');
	
	head_add_css('addon/openclipatar/openclipatar.css');
	
	$t = get_markup_template('avatars.tpl', 'addon/openclipatar/');
	
	if(! $defsearch)
		$defsearch = 'avatar';
		
	if(! $prefclipmsg)
		$prefclipmsg = t('System defaults:');
	
	if(x($_POST,'search'))
		$search = notags(trim($_POST['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');
		
	if(! $search)
		$search = $defsearch;
	
	$entries = array();
	$haveprefclips = false;
	$eidlist = array();
	
	if($prefclipids && preg_match('/[\d,]+/',$prefclipids)) {
		logger('Openclipatar: initial load: '.var_export($_REQUEST,true), LOGGER_DEBUG);
		$sortpref = ($sortprefids == 'asentered') ? 'date' : $sortprefids; // Use user defined sort, unless it's asentered. That's handled later
		$x = z_fetch_url('https://openclipart.org/search/json/?sort='. $sortpref . '&amount=50&byids=' . dbesc($prefclipids));
		if($x['success']) {
			$j = json_decode($x['body'], true);
			if($j && !empty($j['payload'])) {
				$eidlist = explode(',', $prefclipids); // save for later
				if(!$_REQUEST['aj']) {
					foreach($j['payload'] as $rr) {
						$e = openclipatar_decode_result($rr);
						$e['extraclass'] = 'openclipatar-prefids';
						$entries[] = $e;
					}
					if(count($entries)) {
						openclipatar_sort_result($entries, $eidlist, $sortprefids);
						$haveprefclips = true;
					}
				}
			}
		}
	}
	$x =  z_fetch_url('https://openclipart.org/search/json/?sort=' . $sortids . '&amount=20&query=' . urlencode($search) . '&page=' . App::$pager['page']);
	
	if($x['success']) {
		$j = json_decode($x['body'], true);
		if($j && !empty($j['payload'])) {
			foreach($j['payload'] as $rr) {
				$e = openclipatar_decode_result($rr);
				if(!in_array($e['id'], $eidlist)) {
					//logger('openclipatar: id '.$e['id'].' not in '.var_export($eidlist,true), LOGGER_DEBUG);
					$entries[] = $e;
				}
			}
			$o .= "<script> var page_query = 'openclipatar'; var extra_args = '&search=" . urlencode($search) . '&' . extra_query_args() . "' ; </script>";
		}
	}
	if($_REQUEST['aj']) {
		if($entries) {
			$o = replace_macros(get_markup_template('avatar-ajax.tpl', 'addon/openclipatar/'), array(
				'$use' => t('Use'),
				'$entries' => $entries,
			));
		} else {
			$o = '<div id="content-complete"></div>';
		}
		echo $o;
		killme();
	} else {
		$o .= replace_macros( $t, array(
			'$selectmsg' => t('Or select from a free OpenClipart.org image:'),
			'$prefmsg' => $haveprefclips ? ('<div class="openclipatar-prefclipmsg">' . $prefclipmsg . '</div>') : '',
			'$use' => t('Use'),
			'$defsearch' => array('search', t('Search Term'), $search),
			//'$form_security_token' => get_form_security_token('profile_photo'),
			'$entries' => $entries,
		));
	}
}

function openclipatar_content(&$a) {
	if(! local_channel())
		return;
		
	$o = '';
	if(argc() == 3 && argv(1) == 'use') {
		$id = argv(2);
		$chan = App::get_channel();
		
		$x = z_fetch_url('https://openclipart.org/image/250px/svg_to_png/' .$id . '/' . $id . '.png',true);
		if($x['success'])
			$imagedata = $x['body'];
		
		$ph = photo_factory($imagedata, 'image/png');
		if(! $ph->is_valid())
			return t('Unknown error. Please try again later.');
			
		// create a unique resource_id
		$hash = photo_new_resource();
		
		$width  = $ph->getWidth();
		$height = $ph->getHeight();

		// save an original or "scale 0" image
		$p = array('aid' => get_account_id(), 'uid' => local_channel(), 'resource_id' => $hash, 'filename' => $id.'.png', 'album' => t('Profile Photos'), 'imgscale' => 0);
		$r = $ph->save($p);
		if($r) {
			if(($width > 1024 || $height > 1024) && (! $errors))
				$ph->scaleImage(1024);

			$p['imgscale'] = 1;
			$r1 = $ph->save($p);

			if(($width > 640 || $height > 640) && (! $errors))
				$ph->scaleImage(640);

			$p['imgscale'] = 2;
			$r2 = $ph->save($p);

			if(($width > 320 || $height > 320) && (! $errors))
				$ph->scaleImage(320);

			$p['imgscale'] = 3;
			$r3 = $ph->save($p);

			// ensure squareness at first, subsequent scales keep ratio
			$ph->scaleImageSquare(175);
			$p['imgscale'] = 4;
			$r = $ph->save($p);
			if($r === false)
				$photo_failure = true;

			$ph->scaleImage(80);
			$p['imgscale'] = 5;
			$r = $ph->save($p);
			if($r === false)
				$photo_failure = true;

			$ph->scaleImage(48);
			$p['imgscale'] = 6;
			$r = $ph->save($p);
			if($r === false)
				$photo_failure = true;
		}
		
		$is_default_profile = 1;
		if($_REQUEST['profile']) {
			$r = q("select id, is_default from profile where id = %d and uid = %d limit 1",
				intval($_REQUEST['profile']),
				intval(local_channel())
			);
			if(($r) && (! intval($r[0]['is_default'])))
				$is_default_profile = 0;
		} 
		if($is_default_profile) {
			// unset any existing profile photos
			$r = q("UPDATE photo SET photo_usage = %d WHERE photo_usage = %d AND uid = %d",
				intval(PHOTO_NORMAL),
				intval(PHOTO_PROFILE),
				intval(local_channel()));

			// set all sizes of this one as profile photos
			$r = q("UPDATE photo SET photo_usage = %d WHERE uid = %d AND resource_id = '%s'",
				intval(PHOTO_PROFILE),
				intval(local_channel()),
				dbesc($hash)
				);
			
			require_once('include/photos.php');
			profile_photo_set_profile_perms(local_channel()); //Reset default profile photo permissions to public
			
			// only the default needs reload since it uses canonical url -- despite the slightly ambiguous message, left it so as to re-use translations
			info( t('Shift-reload the page or clear browser cache if the new photo does not display immediately.') . EOL);
		}
		else {
			// not the default profile, set the path in the correct entry in the profile DB
			$r = q("update profile set photo = '%s', thumb = '%s' where id = %d and uid = %d",
				dbesc(z_root() . '/photo/' . $hash . '-4'),
				dbesc(z_root() . '/photo/' . $hash . '-5'),
				intval($_REQUEST['profile']),
				intval(local_channel())
			);
			info( t('Profile photo updated successfully.') . EOL);
		}
		// set a new photo_date on our xchan so that we can tell everybody to update their cached copy
		$r = q("UPDATE xchan set xchan_photo_date = '%s' where xchan_hash = '%s'",
			dbesc(datetime_convert()),
			dbesc($chan['xchan_hash'])
		);
		// Similarly, tell the nav bar to bypass the cache and update the avater image.
		$_SESSION['reload_avatar'] = true;

		// tell everybody
		Zotlabs\Daemon\Master::Summon(array('Directory',local_channel()));
		
		$returnafter = get_config('openclipatar', 'returnafter');
		$returnafter_urls = array(
			0 => z_root() . '/profile/' . ($_REQUEST['profile'] ? $_REQUEST['profile'].'/view' : $chan['channel_address']),
			1 => z_root() . '/profiles/' . ($_REQUEST['profile'] ? $_REQUEST['profile'] : App::$profile_uid),
			2 => z_root() . '/profiles'
		);
		
		goaway($returnafter_urls[$returnafter]);
		
	} else {
		//invoked as module, we place in content pane the same as we would for the end of the profile photo page. Also handles json for endless scroll for either invokation.
		openclipatar_profile_photo_content_end($a, $o);
	}
	return $o;
}
