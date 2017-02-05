<?php

/**
 * Name: CalDAV,CardDAV server
 * Description: CalDAV and CardDAV sync server (experimental, unsupported)
 * Version: 1.1
 * MinVersion 1.15.2
 * Author: Mike Macgirvin <mike@macgirvin.com>
 * 
 */

require_once('addon/cdav/Mod_Cdav.php');
require_once('addon/cdav/include/widgets.php');

function cdav_install() {

	if(ACTIVE_DBTYPE === DBTYPE_POSTGRES) {
		$type='postgres';
	}
	else {
		$type = 'mysql';
	}

	$r = q('SELECT * FROM principals LIMIT 1');

	if(!is_array($r)) {
		$str = file_get_contents('addon/cdav/' . $type . '.sql');
		$arr = explode(';',$str);

		$errors = '';

		foreach($arr as $a) {
			if(strlen(trim($a))) {
				$r = q(trim($a));
				if(! $r) {
					$errors .=  t('Errors encountered creating database table: ') . $a . EOL;
				}
			}
		}
		if($errors) {
			notice(t('Errors encountered creating database tables.') . EOL);
		}
	}
}

function cdav_uninstall() {

	/**
	 * MYSQL DB Migration Code
	 * This section can be removed after a while (when everybody did the migration)
	 *
	 */

	if(ACTIVE_DBTYPE === DBTYPE_MYSQL) {

		//check if calendarinstances table exist
		$r = q('SELECT * FROM calendarinstances LIMIT 1');

		if(!is_array($r)) {

			//create calendarinstances table
			q("
				CREATE TABLE calendarinstances (
				    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
				    calendarid INTEGER UNSIGNED NOT NULL,
				    principaluri VARBINARY(100),
				    access TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 = owner, 2 = read, 3 = readwrite',
				    displayname VARCHAR(100),
				    uri VARBINARY(200),
				    description TEXT,
				    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
				    calendarcolor VARBINARY(10),
				    timezone TEXT,
				    transparent TINYINT(1) NOT NULL DEFAULT '0',
				    share_href VARBINARY(100),
				    share_displayname VARCHAR(100),
				    share_invitestatus TINYINT(1) NOT NULL DEFAULT '2' COMMENT '1 = noresponse, 2 = accepted, 3 = declined, 4 = invalid',
				    UNIQUE(principaluri, uri),
				    UNIQUE(calendarid, principaluri),
				    UNIQUE(calendarid, share_href)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			");
	
			// Populate calendarinstances
			q("
				INSERT INTO calendarinstances
				    (
					calendarid,
					principaluri,
					access,
					displayname,
					uri,
					description,
					calendarorder,
					calendarcolor,
					transparent
				    )
				SELECT
				    id,
				    principaluri,
				    1,
				    displayname,
				    uri,
				    description,
				    calendarorder,
				    calendarcolor,
				    transparent
				FROM calendars
			");

			//backup calendars table
			q('RENAME TABLE calendars TO calendars_bak');

			//create new calendars table
			q("
				CREATE TABLE calendars (
				    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
				    synctoken INTEGER UNSIGNED NOT NULL DEFAULT '1',
				    components VARBINARY(21)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			");

			//migrating data from old to new table
			q("
				INSERT INTO calendars (id, synctoken, components) SELECT id, synctoken, COALESCE(components,'VEVENT,VTODO,VJOURNAL') as components FROM calendars_bak
			");

			//get rid of the backup table
			q('DROP TABLE calendars_bak');
		}
	}

	/**
	 * End DB Migration
	 *
	 */

	// Currently we do nothing here as it could destroy a lot of data, when
	// often you only want to reset the plugin state. 
	// We need a way to specify that you really really want to destroy
	// everything before we let you do it.
}

function cdav_load() {
	Zotlabs\Extend\Hook::register('well_known', 'addon/cdav/cdav.php', 'cdav_well_known');
	Zotlabs\Extend\Hook::register('feature_settings', 'addon/cdav/cdav.php', 'cdav_feature_settings');
	Zotlabs\Extend\Hook::register('feature_settings_post', 'addon/cdav/cdav.php','cdav_feature_settings_post');
	Zotlabs\Extend\Hook::register('load_pdl', 'addon/cdav/cdav.php', 'cdav_load_pdl');
}

function cdav_unload() {
	Zotlabs\Extend\Hook::unregister_by_file('addon/cdav/cdav.php');
}

function cdav_well_known(&$x) {
	if(argv(1) === 'caldav' || argv(1) === 'carddav') {
		goaway(z_root() . '/cdav');
	}
}

function cdav_feature_settings_post(&$b) {

	if(! local_channel())
		return;

 	if($_POST['cdav-submit']) {

		$channel = \App::get_channel();
		$uri = 'principals/' . $channel['channel_address'];

		set_pconfig(local_channel(),'cdav','enabled',intval($_POST['cdav_enabled']));
		if(intval($_POST['cdav_enabled'])) {
			$r = q("select * from principals where uri = '%s' limit 1",
				dbesc($uri)
			);
			if($r) {
				$r = q("update principals set email = '%s', displayname = '%s' where uri = '%s' ",
					dbesc($channel['xchan_addr']),
					dbesc($channel['channel_name']),
					dbesc($uri)
				);
			}
			else {
				$r = q("insert into principals ( uri, email, displayname ) values('%s','%s','%s') ",
					dbesc($uri),
					dbesc($channel['xchan_addr']),
					dbesc($channel['channel_name'])
				);

				//create default calendar
				$r = q("insert into calendars (components) values('%s') ",
					dbesc('VEVENT,VTODO')
				);

				$r = q("insert into calendarinstances (calendarid, principaluri, displayname, uri, description, calendarcolor) values(LAST_INSERT_ID(), '%s', '%s', '%s', '%s', '%s') ",
					dbesc($uri),
					dbesc(t('Default Calendar')),
					dbesc('default'),
					dbesc($channel['channel_name']),
					dbesc('#3a87ad')
				);

				//create default addressbook
				$r = q("insert into addressbooks (principaluri, displayname, uri) values('%s', '%s', '%s') ",
					dbesc($uri),
					dbesc(t('Default Addressbook')),
					dbesc('default')
				);
			}
		}
		else {
			// figure out how to safely disable
		}

		info( t('CalDAV/CardDAV Settings saved.') . EOL);
	}
}

function cdav_feature_settings(&$b) {
	
	if(! local_channel())
		return;

	$channel = App::get_channel();
	$enabled = get_pconfig(local_channel(),'cdav','enabled');

		
	$sc .= '<div class="settings-block">';
	$sc .= '<div id="cdav-wrapper">';

	//$sc .= '<div class="section-content-warning-wrapper">' . t('<strong>WARNING:</strong> Please note that this plugin is in early alpha state and highly experimental. You will likely loose your data at some point!') . '</div>';

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'    => array('cdav_enabled', t('Enable CalDAV/CardDAV Server for this channel'), $enabled, '', array(t('No'),t('Yes'))),
	));

	$sc .= '<div class="descriptive-text">' . sprintf( t('Your CalDAV resources are located at %s '), 
		z_root() . '/cdav/calendars/' . $channel['channel_address']) . '</div>';

	$sc .= '<div class="descriptive-text">' . sprintf( t('Your CardDAV resources are located at %s '), 
		z_root() . '/cdav/addressbooks/' . $channel['channel_address']) . '</div>';

	$sc .= '</div>';

	$b .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon'    => array('cdav', t('CalDAV/CardDAV Settings'), '', t('Submit')),
		'$content'  => $sc
	));
}

function cdav_load_pdl(&$b) {
	if ($b['module'] === 'cdav') {
		$b['layout'] = '
			[region=aside]
			[widget=cdav][/widget]
			[/region]
		';
	}
}


function translate_type($type) {

	if(!$type)
		return;

	$type = strtoupper($type);

	$map = [
		'CELL' => t('Mobile'),
		'HOME' => t('Home'),
		'HOME,VOICE' => t('Home, Voice'),
		'HOME,FAX' => t('Home, Fax'),
		'WORK' => t('Work'),
		'WORK,VOICE' => t('Work, Voice'),
		'WORK,FAX' => t('Work, Fax'),
		'OTHER' => t('Other')
	];

	if (array_key_exists($type, $map)) {
		return [$type, $map[$type]];
	}
	else {
		return [$type, t('Other') . ' (' . $type . ')'];
	}
}

function cdav_principal($uri) {
	$r = q("SELECT uri FROM principals WHERE uri = '%s' LIMIT 1",
		dbesc($uri)
	);

	if($r[0]['uri'] === $uri)
		return true;
	else
		return false;
}

function cdav_perms($needle, $haystack, $check_rw = false) {
	foreach ($haystack as $item) {
		if($check_rw) {
			if(is_array($item['id'])) {
				if ($item['id'][0] == $needle && $item['share-access'] != 2) {
					return $item['{DAV:}displayname'];
				}
			}
			else {
				if ($item['id'] == $needle && $item['share-access'] != 2) {
					return $item['{DAV:}displayname'];
				}
			}
		}
		else {
			if(is_array($item['id'])) {
				if ($item['id'][0] == $needle) {
					return $item['{DAV:}displayname'];
				}
			}
			else {
				if ($item['id'] == $needle) {
					return $item['{DAV:}displayname'];
				}
			}
		}
	}
	return false;
}
