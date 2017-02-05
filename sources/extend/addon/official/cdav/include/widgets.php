<?php

function widget_cdav() {
	if(!local_channel() || get_pconfig(local_channel(),'cdav','enabled') != 1)
		return;

	$channel = \App::get_channel();
	$principalUri = 'principals/' . $channel['channel_address'];

	if(!cdav_principal($principalUri))
		return;

	if(\DBA::$dba && \DBA::$dba->connected)
		$pdovars = \DBA::$dba->pdo_get();
	else
		killme();

	$pdo = new \PDO($pdovars[0],$pdovars[1],$pdovars[2]);
	$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

	require_once 'vendor/autoload.php';

	$o = '';

	if(argc() == 2 && argv(1) === 'calendar') {

		$caldavBackend = new \Sabre\CalDAV\Backend\PDO($pdo);

		$sabrecals = $caldavBackend->getCalendarsForUser($principalUri);

		//TODO: we should probably also check for permission to send stream here
		$local_channels = q("SELECT * FROM channel LEFT JOIN abook ON abook_xchan = channel_hash WHERE channel_system = 0 AND channel_removed = 0 AND channel_hash != '%s' AND abook_channel = %d",
			dbesc($channel['channel_hash']),
			intval($channel['channel_id'])
		);

		$sharee_options .= '<option value="">' . t('Select Channel') . '</option>' . "\r\n";
		foreach($local_channels as $local_channel) {
			$sharee_options .= '<option value="' . $local_channel['channel_hash'] . '">' . $local_channel['channel_name'] . '</option>' . "\r\n";
		}

		$access_options = '<option value="3">' . t('Read-write') . '</option>' . "\r\n";
		$access_options .= '<option value="2">' . t('Read-only') . '</option>' . "\r\n";

		//list calendars
		foreach($sabrecals as $sabrecal) {
			if($sabrecal['share-access'] == 1)
				$access = '';
			if($sabrecal['share-access'] == 2)
				$access = 'read';
			if($sabrecal['share-access'] == 3)
				$access = 'read-write';

			$invites = $caldavBackend->getInvites($sabrecal['id']);

			$json_source = '/cdav/calendar/json/' . $sabrecal['id'][0] . '/' . $sabrecal['id'][1];

			$switch = get_pconfig(local_channel(), 'cdav_calendar', $sabrecal['id'][0]);

			$color = (($sabrecal['{http://apple.com/ns/ical/}calendar-color']) ? $sabrecal['{http://apple.com/ns/ical/}calendar-color'] : '#3a87ad');

			$editable = (($sabrecal['share-access'] == 2) ? 'false' : 'true'); // false/true must be string since we're passing it to javascript

			$sharees = [];
			$share_displayname = [];
			foreach($invites as $invite) {
				if(strpos($invite->href, 'mailto:') !== false) {
					$sharee = channelx_by_hash(substr($invite->href, 7));
					$sharees[] = [
						'name' => $sharee['channel_name'],
						'access' => (($invite->access == 3) ? ' (RW)' : ' (R)'),
						'hash' => $sharee['channel_hash']
					];
				}
			}

			if(!$access) {
				$my_calendars[] = [
					'ownernick' => $channel['channel_address'],
					'uri' => $sabrecal['uri'],
					'displayname' => $sabrecal['{DAV:}displayname'],
					'calendarid' => $sabrecal['id'][0],
					'instanceid' => $sabrecal['id'][1],
					'json_source' => $json_source,
					'color' => $color,
					'editable' => $editable,
					'switch' => $switch,
					'sharees' => $sharees
				];
			}
			else {
				$shared_calendars[] = [
					'ownernick' => $channel['channel_address'],
					'uri' => $sabrecal['uri'],
					'displayname' => $sabrecal['{DAV:}displayname'],
					'calendarid' => $sabrecal['id'][0],
					'instanceid' => $sabrecal['id'][1],
					'json_source' => $json_source,
					'color' => $color,
					'editable' => $editable,
					'switch' => $switch,
					'sharer' => $sabrecal['{urn:ietf:params:xml:ns:caldav}calendar-description'],
					'access' => $access
				];
			}

			if(!$access || $access === 'read-write') {
				$writable_calendars[] = [
					'displayname' => ((!$access) ? $sabrecal['{DAV:}displayname'] : $share_displayname[0]),
					'id' => $sabrecal['id']
				];
			}
		}

		$o .= replace_macros(get_markup_template('cdav_widget_calendar.tpl', 'addon/cdav'), [
			'$my_calendars_label' => t('My Calendars'),
			'$my_calendars' => $my_calendars,
			'$shared_calendars_label' => t('Shared Calendars'),
			'$shared_calendars' => $shared_calendars,
			'$sharee_options' => $sharee_options,
			'$access_options' => $access_options,
			'$share_label' => t('Share this calendar'),
			'$share' => t('Share'),
			'$edit_label' => t('Calendar name and color'),
			'$edit' => t('Edit'),
			'$create_label' => t('Create new calendar'),
			'$create' => t('Create'),
			'$create_placeholder' => t('Calendar Name'),
			'$tools_label' => t('Calendar Tools'),
			'$import_label' => t('Import calendar'),
			'$import_placeholder' => t('Select a calendar to import to'),
			'$upload' => t('Upload'),
			'$writable_calendars' => $writable_calendars
		]);

		return $o;

	}

	if(argc() >= 2 && argv(1) === 'addressbook') {

		$carddavBackend = new \Sabre\CardDAV\Backend\PDO($pdo);

		$sabreabooks = $carddavBackend->getAddressBooksForUser($principalUri);

		//list addressbooks
		foreach($sabreabooks as $sabreabook) {
			$addressbooks[] = [
				'ownernick' => $channel['channel_address'],
				'uri' => $sabreabook['uri'],
				'displayname' => $sabreabook['{DAV:}displayname'],
				'id' => $sabreabook['id']

			];
		}

		$o .= replace_macros(get_markup_template('cdav_widget_addressbook.tpl', 'addon/cdav'), [
			'$addressbooks_label' => t('Addressbooks'),
			'$addressbooks' => $addressbooks,
			'$edit_label' => t('Addressbook name'),
			'$edit' => t('Edit'),
			'$create_label' => t('Create new addressbook'),
			'$create_placeholder' => t('Addressbook Name'),
			'$create' => t('Create'),
			'$tools_label' => t('Addressbook Tools'),
			'$import_label' => t('Import addressbook'),
			'$import_placeholder' => t('Select an addressbook to import to'),
			'$upload' => t('Upload')
		]);

		return $o;

	}

}
