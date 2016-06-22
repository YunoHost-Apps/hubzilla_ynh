<?php /** @file */

/**
 * apps
 *
 */

require_once('include/plugin.php');
require_once('include/identity.php');

function get_system_apps($translate = true) {

	$ret = array();
	if(is_dir('apps'))
		$files = glob('apps/*.apd');
	else
		$files = glob('app/*.apd');
	if($files) {
		foreach($files as $f) {
			$x = parse_app_description($f,$translate);
			if($x) {
				$ret[] = $x;
			}
		}
	}
	$files = glob('addon/*/*.apd');
	if($files) {
		foreach($files as $f) {
			$n = basename($f,'.apd');
			if(plugin_is_installed($n)) {
				$x = parse_app_description($f,$translate);
				if($x) {
					$ret[] = $x;
				}
			}
		}
	}

	return $ret;

}


function import_system_apps() {
	if(! local_channel())
		return;

	// Eventually we want to look at modification dates and update system apps.

	$installed = get_pconfig(local_channel(),'system','apps_installed');
	if($installed)
		return;
	$apps = get_system_apps(false);
	if($apps) {
		foreach($apps as $app) {
			$app['uid'] = local_channel();
			$app['guid'] = hash('whirlpool',$app['name']);
			$app['system'] = 1;
			app_install(local_channel(),$app);			
		}
	}					
	set_pconfig(local_channel(),'system','apps_installed',1);
}




function app_name_compare($a,$b) {
	return strcmp($a['name'],$b['name']);
}


function parse_app_description($f,$translate = true) {
	$ret = array();

	$baseurl = z_root();
	$channel = App::get_channel();
	$address = (($channel) ? $channel['channel_address'] : '');
		
	//future expansion

	$observer = App::get_observer();
	

	$lines = @file($f);
	if($lines) {
		foreach($lines as $x) {
			if(preg_match('/^([a-zA-Z].*?):(.*?)$/ism',$x,$matches)) {
				$ret[$matches[1]] = trim(str_replace(array('$baseurl','$nick'),array($baseurl,$address),$matches[2]));
			}
		}
	}


	if(! $ret['photo'])
		$ret['photo'] = $baseurl . '/' . get_default_profile_photo(80);

	$ret['type'] = 'system';

	foreach($ret as $k => $v) {
		if(strpos($v,'http') === 0)
			$ret[$k] = zid($v);
	}

	if(array_key_exists('desc',$ret))
		$ret['desc'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$ret['desc']);

	if(array_key_exists('target',$ret))
		$ret['target'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$ret['target']);

	if(array_key_exists('requires',$ret)) {
		$requires = explode(',',$ret['requires']);
		foreach($requires as $require) {
			$require = trim(strtolower($require));
			switch($require) {
				case 'nologin':
					if(local_channel())
						unset($ret);
					break;
				case 'admin':
					if(! is_site_admin())
						unset($ret);
					break;
				case 'local_channel':
					if(! local_channel())
						unset($ret);
					break;
				case 'public_profile':
					if(! is_public_profile())
						unset($ret);
					break;
				case 'observer':
					if(! $observer)
						unset($ret);
					break;
				default:
					if(! (local_channel() && feature_enabled(local_channel(),$require)))
						unset($ret);
					break;

			}
		}
	}
	if($ret) {
		if($translate)
			translate_system_apps($ret);
		return $ret;
	}
	return false;
}	


function translate_system_apps(&$arr) {
	$apps = array(
		'Site Admin' => t('Site Admin'),
		'Bug Report' => t('Bug Report'),
		'View Bookmarks' => t('View Bookmarks'),
		'My Chatrooms' => t('My Chatrooms'),
		'Connections' => t('Connections'),
		'Firefox Share' => t('Firefox Share'),
		'Remote Diagnostics' => t('Remote Diagnostics'),
		'Suggest Channels' => t('Suggest Channels'),
		'Login' => t('Login'),
		'Channel Manager' => t('Channel Manager'), 
		'Grid' => t('Grid'), 
		'Settings' => t('Settings'),
		'Files' => t('Files'),
		'Webpages' => t('Webpages'),
		'Channel Home' => t('Channel Home'), 
		'View Profile' => t('View Profile'),
		'Photos' => t('Photos'), 
		'Events' => t('Events'), 
		'Directory' => t('Directory'), 
		'Help' => t('Help'),
		'Mail' => t('Mail'),
		'Mood' => t('Mood'),
		'Poke' => t('Poke'),
		'Chat' => t('Chat'),
		'Search' => t('Search'),
		'Probe' => t('Probe'),
		'Suggest' => t('Suggest'),
		'Random Channel' => t('Random Channel'),
		'Invite' => t('Invite'),
		'Features' => t('Features'),
		'Language' => t('Language'),
		'Post' => t('Post'),
		'Profile Photo' => t('Profile Photo')
	);

	if(array_key_exists($arr['name'],$apps))
		$arr['name'] = $apps[$arr['name']];

}


// papp is a portable app

function app_render($papp,$mode = 'view') {

	/**
	 * modes:
	 *    view: normal mode for viewing an app via bbcode from a conversation or page
	 *       provides install/update button if you're logged in locally
	 *    list: normal mode for viewing an app on the app page
	 *       no buttons are shown
	 *    edit: viewing the app page in editing mode provides a delete button
	 */

	$installed = false;

	if(! $papp)
		return;

	if(! $papp['photo'])
		$papp['photo'] = z_root() . '/' . get_default_profile_photo(80);

			

	$papp['papp'] = papp_encode($papp);

	if(! strstr($papp['url'],'://'))
		$papp['url'] = z_root() . ((strpos($papp['url'],'/') === 0) ? '' : '/') . $papp['url'];

	foreach($papp as $k => $v) {
		if(strpos($v,'http') === 0 && $k != 'papp')
			$papp[$k] = zid($v);
		if($k === 'desc')
			$papp['desc'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$papp['desc']);

		if($k === 'requires') {
			$requires = explode(',',$v);
			foreach($requires as $require) {
				$require = trim(strtolower($require));
				switch($require) {
					case 'nologin':
						if(local_channel())
							return '';
						break;
					case 'admin':
						if(! is_site_admin())
							return '';
						break;
					case 'local_channel':
						if(! local_channel())
							return '';
						break;
					case 'public_profile':
						if(! is_public_profile())
							return '';
						break;
					case 'observer':
						$observer = App::get_observer();
						if(! $observer)
							return '';
						break;
					default:
						if(! (local_channel() && feature_enabled(local_channel(),$require)))
							return '';
						break;

				}
			}

		}
	}

	$hosturl = '';

	if(local_channel()) {
		$installed = app_installed(local_channel(),$papp);
		$hosturl = z_root() . '/';
	}
	elseif(remote_channel()) {
		$observer = App::get_observer();
		if($observer && $observer['xchan_network'] === 'zot') {
			// some folks might have xchan_url redirected offsite, use the connurl
			$x = parse_url($observer['xchan_connurl']);
			if($x) {
				$hosturl = $x['scheme'] . '://' . $x['host'] . '/';
			}
		} 
	}

	$install_action = (($installed) ? t('Update') : t('Install'));

	return replace_macros(get_markup_template('app.tpl'),array(
		'$app' => $papp,
		'$hosturl' => $hosturl,
		'$purchase' => (($papp['page'] && (! $installed)) ? t('Purchase') : ''),
		'$install' => (($hosturl && $mode == 'view') ? $install_action : ''),
		'$edit' => ((local_channel() && $installed && $mode == 'edit') ? t('Edit') : ''),
		'$delete' => ((local_channel() && $installed && $mode == 'edit') ? t('Delete') : '')
	));
}


function app_install($uid,$app) {
	$app['uid'] = $uid;

	if(app_installed($uid,$app))
		$x = app_update($app);
	else
		$x = app_store($app);

	if($x['success']) {
		$r = q("select * from app where app_id = '%s' and app_channel = %d limit 1",
			dbesc($x['app_id']),
			intval($uid)
		);
		if($r) {
			if(! $r[0]['app_system']) {
				if($app['categories'] && (! $app['term'])) {
					$r[0]['term'] = q("select * from term where otype = %d and oid = d",
						intval(TERM_OBJ_APP),
						intval($r[0]['id'])
					);
					build_sync_packet($uid,array('app' => $r[0]));
				}
			}
		}
		return $x['app_id'];
	}
	return false;
}

function app_destroy($uid,$app) {


	if($uid && $app['guid']) {

		$x = q("select * from app where app_id = '%s' and app_channel = %d limit 1",
			dbesc($app['guid']),
			intval($uid)
		);
		if($x) {
			$x[0]['app_deleted'] = 1;
			q("delete from term where otype = %d and oid = %d",
				intval(TERM_OBJ_APP),
				intval($x[0]['id'])
			);
			if($x[0]['app_system']) {
				$r = q("update app set app_deleted = 1 where app_id = '%s' and app_channel = %d",
					dbesc($app['guid']),
					intval($uid)
				);
			}
			else {
				$r = q("delete from app where app_id = '%s' and app_channel = %d",
					dbesc($app['guid']),
					intval($uid)
				);

				// we don't sync system apps - they may be completely different on the other system
				build_sync_packet($uid,array('app' => $x));
			}
		}
	}
}


function app_installed($uid,$app) {

	$r = q("select id from app where app_id = '%s' and app_version = '%s' and app_channel = %d limit 1",
		dbesc((array_key_exists('guid',$app)) ? $app['guid'] : ''), 
		dbesc((array_key_exists('version',$app)) ? $app['version'] : ''), 
		intval($uid)
	);
	return(($r) ? true : false);

}


function app_list($uid, $deleted = false, $cat = '') {
	if($deleted) 
		$sql_extra = " and app_deleted = 1 ";
	else
		$sql_extra = " and app_deleted = 0 ";

	if($cat) {
		$r = q("select oid from term where otype = %d and term = '%s'",
			intval(TERM_OBJ_APP),
			dbesc($cat)
		);
		if(! $r)
			return $r;
		$sql_extra .= " and app.id in ( ";
		$s = '';
		foreach($r as $rr) {
			if($s)
				$s .= ',';
			$s .= intval($rr['oid']);
		}
		$sql_extra .= $s . ') ';
	}

	$r = q("select * from app where app_channel = %d $sql_extra order by app_name asc",
		intval($uid)
	);
	if($r) {
		for($x = 0; $x < count($r); $x ++) {
			if(! $r[$x]['app_system'])
				$r[$x]['type'] = 'personal';
			$r[$x]['term'] = q("select * from term where otype = %d and oid = %d",
				intval(TERM_OBJ_APP),
				intval($r[$x]['id'])
			);
		}
	}
	return($r);
}


function app_decode($s) {
	$x = base64_decode(str_replace(array('<br />',"\r","\n",' '),array('','','',''),$s));
	return json_decode($x,true);
}


function app_store($arr) {

	// logger('app_store: ' . print_r($arr,true));

	$darray = array();
	$ret = array('success' => false);

	$darray['app_url']     = ((x($arr,'url')) ? $arr['url'] : '');
	$darray['app_channel'] = ((x($arr,'uid')) ? $arr['uid'] : 0);

	if((! $darray['app_url']) || (! $darray['app_channel']))
		return $ret;

	if($arr['photo'] && ! strstr($arr['photo'],z_root())) {
		$x = import_xchan_photo($arr['photo'],get_observer_hash(),true);
		$arr['photo'] = $x[1];
	}


	$darray['app_id']       = ((x($arr,'guid'))     ? $arr['guid'] : random_string(). '.' . App::get_hostname());
	$darray['app_sig']      = ((x($arr,'sig'))      ? $arr['sig'] : '');
	$darray['app_author']   = ((x($arr,'author'))   ? $arr['author'] : get_observer_hash());
	$darray['app_name']     = ((x($arr,'name'))     ? escape_tags($arr['name']) : t('Unknown'));
	$darray['app_desc']     = ((x($arr,'desc'))     ? escape_tags($arr['desc']) : '');
	$darray['app_photo']    = ((x($arr,'photo'))    ? $arr['photo'] : z_root() . '/' . get_default_profile_photo(80));
	$darray['app_version']  = ((x($arr,'version'))  ? escape_tags($arr['version']) : '');
	$darray['app_addr']     = ((x($arr,'addr'))     ? escape_tags($arr['addr']) : '');
	$darray['app_price']    = ((x($arr,'price'))    ? escape_tags($arr['price']) : '');
	$darray['app_page']     = ((x($arr,'page'))     ? escape_tags($arr['page']) : '');
	$darray['app_requires'] = ((x($arr,'requires')) ? escape_tags($arr['requires']) : '');
	$darray['app_system']   = ((x($arr,'system'))   ? intval($arr['system']) : 0);
	$darray['app_deleted']  = ((x($arr,'deleted'))  ? intval($arr['deleted']) : 0);

	$created = datetime_convert();

	$r = q("insert into app ( app_id, app_sig, app_author, app_name, app_desc, app_url, app_photo, app_version, app_channel, app_addr, app_price, app_page, app_requires, app_created, app_edited, app_system, app_deleted ) values ( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, %d )",
		dbesc($darray['app_id']),
		dbesc($darray['app_sig']),
		dbesc($darray['app_author']),
		dbesc($darray['app_name']),
		dbesc($darray['app_desc']),
		dbesc($darray['app_url']),
		dbesc($darray['app_photo']),
		dbesc($darray['app_version']),
		intval($darray['app_channel']),
		dbesc($darray['app_addr']),
		dbesc($darray['app_price']),
		dbesc($darray['app_page']),
		dbesc($darray['app_requires']),
		dbesc($created),
		dbesc($created),
		intval($darray['app_system']),
		intval($darray['app_deleted'])
	);
	if($r) {
		$ret['success'] = true;
		$ret['app_id'] = $darray['app_id'];
	}
	if($arr['categories']) {
		$x = q("select id from app where app_id = '%s' and app_channel = %d limit 1",
			dbesc($darray['app_id']),
			intval($darray['app_channel'])
		);
		$y = explode(',',$arr['categories']);
		if($y) {
			foreach($y as $t) {
				$t = trim($t);
				if($t) {
					store_item_tag($darray['app_channel'],$x[0]['id'],TERM_OBJ_APP,TERM_CATEGORY,escape_tags($t),escape_tags(z_root() . '/apps/?f=&cat=' . escape_tags($t)));
				}
			}
		}
	}

	return $ret;
}


function app_update($arr) {

	$darray = array();
	$ret = array('success' => false);

	$darray['app_url']     = ((x($arr,'url')) ? $arr['url'] : '');
	$darray['app_channel'] = ((x($arr,'uid')) ? $arr['uid'] : 0);
	$darray['app_id']      = ((x($arr,'guid')) ? $arr['guid'] : 0);

	if((! $darray['app_url']) || (! $darray['app_channel']) || (! $darray['app_id']))
		return $ret;

	if($arr['photo'] && ! strstr($arr['photo'],z_root())) {
		$x = import_xchan_photo($arr['photo'],get_observer_hash(),true);
		$arr['photo'] = $x[1];
	}

	$darray['app_sig']      = ((x($arr,'sig')) ? $arr['sig'] : '');
	$darray['app_author']   = ((x($arr,'author')) ? $arr['author'] : get_observer_hash());
	$darray['app_name']     = ((x($arr,'name')) ? escape_tags($arr['name']) : t('Unknown'));
	$darray['app_desc']     = ((x($arr,'desc')) ? escape_tags($arr['desc']) : '');
	$darray['app_photo']    = ((x($arr,'photo')) ? $arr['photo'] : z_root() . '/' . get_default_profile_photo(80));
	$darray['app_version']  = ((x($arr,'version')) ? escape_tags($arr['version']) : '');
	$darray['app_addr']     = ((x($arr,'addr')) ? escape_tags($arr['addr']) : '');
	$darray['app_price']    = ((x($arr,'price')) ? escape_tags($arr['price']) : '');
	$darray['app_page']     = ((x($arr,'page')) ? escape_tags($arr['page']) : '');
	$darray['app_requires'] = ((x($arr,'requires')) ? escape_tags($arr['requires']) : '');
	$darray['app_system']   = ((x($arr,'system'))   ? intval($arr['system']) : 0);
	$darray['app_deleted']  = ((x($arr,'deleted'))  ? intval($arr['deleted']) : 0);

	$edited = datetime_convert();

	$r = q("update app set app_sig = '%s', app_author = '%s', app_name = '%s', app_desc = '%s', app_url = '%s', app_photo = '%s', app_version = '%s', app_addr = '%s', app_price = '%s', app_page = '%s', app_requires = '%s', app_edited = '%s', app_system = %d, app_deleted = %d where app_id = '%s' and app_channel = %d",
		dbesc($darray['app_sig']),
		dbesc($darray['app_author']),
		dbesc($darray['app_name']),
		dbesc($darray['app_desc']),
		dbesc($darray['app_url']),
		dbesc($darray['app_photo']),
		dbesc($darray['app_version']),
		dbesc($darray['app_addr']),
		dbesc($darray['app_price']),
		dbesc($darray['app_page']),
		dbesc($darray['app_requires']),
		dbesc($edited),
		intval($darray['app_system']),
		intval($darray['app_deleted']),
		dbesc($darray['app_id']),
		intval($darray['app_channel'])
	);
	if($r) {
		$ret['success'] = true;
		$ret['app_id'] = $darray['app_id'];
	}

	$x = q("select id from app where app_id = '%s' and app_channel = %d limit 1",
		dbesc($darray['app_id']),
		intval($darray['app_channel'])
	);
	if($x) {
		q("delete from term where otype = %d and oid = %d",
			intval(TERM_OBJ_APP),
			intval($x[0]['id'])
		);
		if($arr['categories']) {
			$y = explode(',',$arr['categories']);
			if($y) {
				foreach($y as $t) {
					$t = trim($t);
					if($t) {
						store_item_tag($darray['app_channel'],$x[0]['id'],TERM_OBJ_APP,TERM_CATEGORY,escape_tags($t),escape_tags(z_root() . '/apps/?f=&cat=' . escape_tags($t)));
					}
				}
			}
		}
	}

	return $ret;

}


function app_encode($app,$embed = false) {

	$ret = array();

	$ret['type'] = 'personal';

	if($app['app_id'])
		$ret['guid'] = $app['app_id'];

	if($app['app_id'])
		$ret['guid'] = $app['app_id'];

	if($app['app_sig'])
		$ret['sig'] = $app['app_sig'];

	if($app['app_author'])
		$ret['author'] = $app['app_author'];

	if($app['app_name'])
		$ret['name'] = $app['app_name'];

	if($app['app_desc'])
		$ret['desc'] = $app['app_desc'];

	if($app['app_url'])
		$ret['url'] = $app['app_url'];

	if($app['app_photo'])
		$ret['photo'] = $app['app_photo'];

	if($app['app_version'])
		$ret['version'] = $app['app_version'];

	if($app['app_addr'])
		$ret['addr'] = $app['app_addr'];

	if($app['app_price'])
		$ret['price'] = $app['app_price'];

	if($app['app_page'])
		$ret['page'] = $app['app_page'];

	if($app['app_requires'])
		$ret['requires'] = $app['app_requires'];

	if($app['app_system'])
		$ret['system'] = $app['app_system'];

	if($app['app_deleted'])
		$ret['deleted'] = $app['app_deleted'];

	if($app['term']) {
		$s = '';
		foreach($app['term'] as $t) {
			if($s)
				$s .= ',';
			$s .= $t['term'];
		}
		$ret['categories'] = $s;
	}


	if(! $embed)
		return $ret;

	if(array_key_exists('categories',$ret))
		unset($ret['categories']);
	
	$j = json_encode($ret);
	return '[app]' . chunk_split(base64_encode($j),72,"\n") . '[/app]';

}


function papp_encode($papp) {
	return chunk_split(base64_encode(json_encode($papp)),72,"\n");

}


