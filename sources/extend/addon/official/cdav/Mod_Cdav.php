<?php

namespace Zotlabs\Module;

class Cdav extends \Zotlabs\Web\Controller {

	function init() {

		if((argv(1) !== 'calendar') && (argv(1) !== 'addressbook')) {

			// workaround for HTTP-auth in CGI mode
			if (x($_SERVER, 'REDIRECT_REMOTE_USER')) {
				$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"], 6)) ;
				if(strlen($userpass)) {
					list($name, $password) = explode(':', $userpass);
					$_SERVER['PHP_AUTH_USER'] = $name;
					$_SERVER['PHP_AUTH_PW'] = $password;
				}
			}

			if (x($_SERVER, 'HTTP_AUTHORIZATION')) {
				$userpass = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"], 6)) ;
				if(strlen($userpass)) {
					list($name, $password) = explode(':', $userpass);
					$_SERVER['PHP_AUTH_USER'] = $name;
					$_SERVER['PHP_AUTH_PW'] = $password;
				}
			}

			/**
			 * This server combines both CardDAV and CalDAV functionality into a single
			 * server. It is assumed that the server runs at the root of a HTTP domain (be
			 * that a domainname-based vhost or a specific TCP port.
			 *
			 * This example also assumes that you're using SQLite and the database has
			 * already been setup (along with the database tables).
			 *
			 * You may choose to use MySQL instead, just change the PDO connection
			 * statement.
			 */

			/**
			 * UTC or GMT is easy to work with, and usually recommended for any
			 * application.
			 */
			date_default_timezone_set('UTC');

			/**
			 * Make sure this setting is turned on and reflect the root url for your WebDAV
			 * server.
			 *
			 * This can be for example the root / or a complete path to your server script.
			 */

			$baseUri = '/cdav/';

			/**
			 * Database
			 *
			 */

			$pdo = \DBA::$dba->db;

//			$pdo = new \PDO($pdovars[0],$pdovars[1],$pdovars[2]);
//			$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);


			// Autoloader
			require_once 'vendor/autoload.php';

			/**
			 * The backends. Yes we do really need all of them.
			 *
			 * This allows any developer to subclass just any of them and hook into their
			 * own backend systems.
			 */

			$auth = new \Zotlabs\Storage\BasicAuth();
			$auth->setRealm(ucfirst(\Zotlabs\Lib\System::get_platform_name()) . 'CalDAV/CardDAV');

		//	$ob_hash = get_observer_hash();

		//	if ($ob_hash) {
				if (local_channel()) {
					logger('loggedin');
					$channel = \App::get_channel();
					$auth->setCurrentUser($channel['channel_address']);
					$auth->channel_id = $channel['channel_id'];
					$auth->channel_hash = $channel['channel_hash'];
					$auth->channel_account_id = $channel['channel_account_id'];
					if($channel['channel_timezone'])
						$auth->setTimezone($channel['channel_timezone']);
					$auth->observer = $channel['channel_hash'];
				}
		//		$auth->observer = $ob_hash;
		//	}

			//$authBackend      = new \Sabre\DAV\Auth\Backend\PDO($pdo);
			$principalBackend = new \Sabre\DAVACL\PrincipalBackend\PDO($pdo);
			$carddavBackend   = new \Sabre\CardDAV\Backend\PDO($pdo);
			$caldavBackend    = new \Sabre\CalDAV\Backend\PDO($pdo);

			/**
			 * The directory tree
			 *
			 * Basically this is an array which contains the 'top-level' directories in the
			 * WebDAV server.
			 */

			$nodes = [
				// /principals
				new \Sabre\CalDAV\Principal\Collection($principalBackend),
				// /calendars
				new \Sabre\CalDAV\CalendarRoot($principalBackend, $caldavBackend),
				// /addressbook
				new \Sabre\CardDAV\AddressBookRoot($principalBackend, $carddavBackend),
			];

			// The object tree needs in turn to be passed to the server class

			$server = new \Sabre\DAV\Server($nodes);

			if(isset($baseUri))
				$server->setBaseUri($baseUri);

			// Plugins
			$server->addPlugin(new \Sabre\DAV\Auth\Plugin($auth));
			//$server->addPlugin(new \Sabre\DAV\Browser\Plugin());
			$server->addPlugin(new \Sabre\DAV\Sync\Plugin());
			$server->addPlugin(new \Sabre\DAV\Sharing\Plugin());
			$server->addPlugin(new \Sabre\DAVACL\Plugin());

			// CalDAV plugins
			$server->addPlugin(new \Sabre\CalDAV\Plugin());
			$server->addPlugin(new \Sabre\CalDAV\SharingPlugin());
			//$server->addPlugin(new \Sabre\CalDAV\Schedule\Plugin());
			$server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());

			// CardDAV plugins
			$server->addPlugin(new \Sabre\CardDAV\Plugin());
			$server->addPlugin(new \Sabre\CardDAV\VCFExportPlugin());

			// And off we go!
			$server->exec();

			killme();

		}

	}

	function post() {
		if(!local_channel() || get_pconfig(local_channel(),'cdav','enabled') != 1)
			return;

		$channel = \App::get_channel();
		$principalUri = 'principals/' . $channel['channel_address'];

		if(!cdav_principal($principalUri))
			return;

		$pdo = \DBA::$dba->db;

/*
		if(\DBA::$dba && \DBA::$dba->connected)
			$pdovars = \DBA::$dba->pdo_get();
		else
			killme();

		$pdo = new \PDO($pdovars[0],$pdovars[1],$pdovars[2]);
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
*/

		require_once 'vendor/autoload.php';

		if(argc() == 2 && argv(1) === 'calendar') {

			$caldavBackend = new \Sabre\CalDAV\Backend\PDO($pdo);
			$calendars = $caldavBackend->getCalendarsForUser($principalUri);

			//create new calendar
			if($_REQUEST['{DAV:}displayname'] && $_REQUEST['create']) {
				do {
					$duplicate = false;
					$calendarUri = random_string(40);

					$r = q("SELECT uri FROM calendarinstances WHERE principaluri = '%s' AND uri = '%s' LIMIT 1",
						dbesc($principalUri),
						dbesc($calendarUri)
					);

					if (count($r))
						$duplicate = true;
				} while ($duplicate == true);

				$properties = [
					'{DAV:}displayname' => $_REQUEST['{DAV:}displayname'],
					'{http://apple.com/ns/ical/}calendar-color' => $_REQUEST['color'],
					'{urn:ietf:params:xml:ns:caldav}calendar-description' => $channel['channel_name']
				];

				$id = $caldavBackend->createCalendar($principalUri, $calendarUri, $properties);

				// set new calendar to be visible
				set_pconfig(local_channel(), 'cdav_calendar' , $id[0], 1);
			}

			//create new calendar object via ajax request
			if($_REQUEST['submit'] === 'create_event' && $_REQUEST['title'] && $_REQUEST['target'] && $_REQUEST['dtstart']) {

				$id = explode(':', $_REQUEST['target']);

				if(!cdav_perms($id[0],$calendars,true))
					return;

				$title = $_REQUEST['title'];
				$dtstart = new \DateTime($_REQUEST['dtstart']);
				if($_REQUEST['dtend'])
					$dtend = new \DateTime($_REQUEST['dtend']);
				$description = $_REQUEST['description'];
				$location = $_REQUEST['location'];

				do {
					$duplicate = false;
					$objectUri = random_string(40) . '.ics';

					$r = q("SELECT uri FROM calendarobjects WHERE calendarid = %s AND uri = '%s' LIMIT 1",
						intval($id[0]),
						dbesc($objectUri)
					);

					if (count($r))
						$duplicate = true;
				} while ($duplicate == true);


				$vcalendar = new \Sabre\VObject\Component\VCalendar([
				    'VEVENT' => [
					'SUMMARY' => $title,
					'DTSTART' => $dtstart
				    ]
				]);
				if($dtend)
					$vcalendar->VEVENT->add('DTEND', $dtend);
				if($description)
					$vcalendar->VEVENT->add('DESCRIPTION', $description);
				if($location)
					$vcalendar->VEVENT->add('LOCATION', $location);

				$calendarData = $vcalendar->serialize();

				$caldavBackend->createCalendarObject($id, $objectUri, $calendarData);

				killme();
			}

			//edit calendar name and color
			if($_REQUEST['{DAV:}displayname'] && $_REQUEST['edit'] && $_REQUEST['id']) {

				$id = explode(':', $_REQUEST['id']);

				if(! cdav_perms($id[0],$calendars))
					return;

				$mutations = [
					'{DAV:}displayname' => $_REQUEST['{DAV:}displayname'],
					'{http://apple.com/ns/ical/}calendar-color' => $_REQUEST['color']
				];

				$patch = new \Sabre\DAV\PropPatch($mutations);

				$caldavBackend->updateCalendar($id, $patch);

				$patch->commit();

			}

			//edit calendar object via ajax request
			if($_REQUEST['submit'] === 'update_event' && $_REQUEST['uri'] && $_REQUEST['title'] && $_REQUEST['target'] && $_REQUEST['dtstart']) {

				$id = explode(':', $_REQUEST['target']);

				if(!cdav_perms($id[0],$calendars,true))
					return;

				$uri = $_REQUEST['uri'];
				$title = $_REQUEST['title'];
				$dtstart = new \DateTime($_REQUEST['dtstart']);
				$dtend = $_REQUEST['dtend'] ? new \DateTime($_REQUEST['dtend']) : '';
				$description = $_REQUEST['description'];
				$location = $_REQUEST['location'];

				$object = $caldavBackend->getCalendarObject($id, $uri);

				$vcalendar = \Sabre\VObject\Reader::read($object['calendardata']);

				if($title)
					$vcalendar->VEVENT->SUMMARY = $title;
				if($dtstart)
					$vcalendar->VEVENT->DTSTART = $dtstart;
				if($dtend)
					$vcalendar->VEVENT->DTEND = $dtend;
				else
					unset($vcalendar->VEVENT->DTEND);
				if($description)
					$vcalendar->VEVENT->DESCRIPTION = $description;
				if($location)
					$vcalendar->VEVENT->LOCATION = $location;

				$calendarData = $vcalendar->serialize();

				$caldavBackend->updateCalendarObject($id, $uri, $calendarData);

				killme();
			}

			//delete calendar object via ajax request
			if($_REQUEST['delete'] && $_REQUEST['uri'] && $_REQUEST['target']) {

				$id = explode(':', $_REQUEST['target']);

				if(!cdav_perms($id[0],$calendars,true))
					return;

				$uri = $_REQUEST['uri'];

				$caldavBackend->deleteCalendarObject($id, $uri);

				killme();
			}

			//edit calendar object date/timeme via ajax request (drag and drop)
			if($_REQUEST['update'] && $_REQUEST['id'] && $_REQUEST['uri']) {

				$id = [$_REQUEST['id'][0], $_REQUEST['id'][1]];

				if(!cdav_perms($id[0],$calendars,true))
					return;

				$uri = $_REQUEST['uri'];
				$dtstart = new \DateTime($_REQUEST['dtstart']);
				$dtend = $_REQUEST['dtend'] ? new \DateTime($_REQUEST['dtend']) : '';

				$object = $caldavBackend->getCalendarObject($id, $uri);

				$vcalendar = \Sabre\VObject\Reader::read($object['calendardata']);

				if($dtstart) {
					$vcalendar->VEVENT->DTSTART = $dtstart;
				}
				if($dtend) {
					$vcalendar->VEVENT->DTEND = $dtend;
				}
				else {
					unset($vcalendar->VEVENT->DTEND);
				}

				$calendarData = $vcalendar->serialize();

				$caldavBackend->updateCalendarObject($id, $uri, $calendarData);

				killme();
			}

			//share a calendar - this only works on local system (with channels on the same server)
			if($_REQUEST['sharee'] && $_REQUEST['share']) {

				$id = [intval($_REQUEST['calendarid']), intval($_REQUEST['instanceid'])];

				if(! cdav_perms($id[0],$calendars))
					return;

				$hash = $_REQUEST['sharee'];

				$sharee_arr = channelx_by_hash($hash);

				$sharee = new \Sabre\DAV\Xml\Element\Sharee();

				$sharee->href = 'mailto:' . $sharee_arr['channel_hash'];
				$sharee->principal = 'principals/' . $sharee_arr['channel_address'];
				$sharee->access = intval($_REQUEST['access']);
				$sharee->properties = ['{DAV:}displayname' => $channel['channel_name']];

				$caldavBackend->updateInvites($id, [$sharee]);
			}
		}

		if(argc() >= 2 && argv(1) === 'addressbook') {

			$carddavBackend = new \Sabre\CardDAV\Backend\PDO($pdo);
			$addressbooks = $carddavBackend->getAddressBooksForUser($principalUri);

			//create new addressbook
			if($_REQUEST['{DAV:}displayname'] && $_REQUEST['create']) {
				do {
					$duplicate = false;
					$addressbookUri = random_string(20);

					$r = q("SELECT uri FROM addressbooks WHERE principaluri = '%s' AND uri = '%s' LIMIT 1",
						dbesc($principalUri),
						dbesc($addressbookUri)
					);

					if (count($r))
						$duplicate = true;
				} while ($duplicate == true);

				$properties = ['{DAV:}displayname' => $_REQUEST['{DAV:}displayname']];

				$carddavBackend->createAddressBook($principalUri, $addressbookUri, $properties);
			}

			//edit addressbook
			if($_REQUEST['{DAV:}displayname'] && $_REQUEST['edit'] && intval($_REQUEST['id'])) {

				$id = $_REQUEST['id'];

				if(! cdav_perms($id,$addressbooks))
					return;

				$mutations = [
					'{DAV:}displayname' => $_REQUEST['{DAV:}displayname']
				];

				$patch = new \Sabre\DAV\PropPatch($mutations);

				$carddavBackend->updateAddressBook($id, $patch);

				$patch->commit();
			}

			//create addressbook card
			if($_REQUEST['create'] && $_REQUEST['target'] && $_REQUEST['fn']) {
				$id = $_REQUEST['target'];

				do {
					$duplicate = false;
					$uri = random_string(40) . '.vcf';

					$r = q("SELECT uri FROM cards WHERE addressbookid = %s AND uri = '%s' LIMIT 1",
						intval($id),
						dbesc($uri)
					);

					if (count($r))
						$duplicate = true;
				} while ($duplicate == true);

				//TODO: this mostly duplictes the procedure in update addressbook card. should move this part to a function to avoid duplication
				$fn = $_REQUEST['fn'];

				$vcard = new \Sabre\VObject\Component\VCard([
					'FN' => $fn,
					'N' => array_reverse(explode(' ', $fn))
				]);

				$org = $_REQUEST['org'];
				if($org) {
					$vcard->ORG = $org;
				}

				$title = $_REQUEST['title'];
				if($title) {
					$vcard->TITLE = $title;
				}

				$tel = $_REQUEST['tel'];
				$tel_type = $_REQUEST['tel_type'];
				if($tel) {
					$i = 0;
					foreach($tel as $item) {
						if($item) {
							$vcard->add('TEL', $item, ['type' => $tel_type[$i]]);
						}
						$i++;
					}
				}

				$email = $_REQUEST['email'];
				$email_type = $_REQUEST['email_type'];
				if($email) {
					$i = 0;
					foreach($email as $item) {
						if($item) {
							$vcard->add('EMAIL', $item, ['type' => $email_type[$i]]);
						}
						$i++;
					}
				}

				$impp = $_REQUEST['impp'];
				$impp_type = $_REQUEST['impp_type'];
				if($impp) {
					$i = 0;
					foreach($impp as $item) {
						if($item) {
							$vcard->add('IMPP', $item, ['type' => $impp_type[$i]]);
						}
						$i++;
					}
				}

				$url = $_REQUEST['url'];
				$url_type = $_REQUEST['url_type'];
				if($url) {
					$i = 0;
					foreach($url as $item) {
						if($item) {
							$vcard->add('URL', $item, ['type' => $url_type[$i]]);
						}
						$i++;
					}
				}

				$adr = $_REQUEST['adr'];
				$adr_type = $_REQUEST['adr_type'];

				if($adr) {
					$i = 0;
					foreach($adr as $item) {
						if($item) {
							$vcard->add('ADR', $item, ['type' => $adr_type[$i]]);
						}
						$i++;
					}
				}

				$note = $_REQUEST['note'];
				if($note) {
					$vcard->NOTE = $note;
				}

				$cardData = $vcard->serialize();

				$carddavBackend->createCard($id, $uri, $cardData);

			}

			//edit addressbook card
			if($_REQUEST['update'] && $_REQUEST['uri'] && $_REQUEST['target']) {

				$id = $_REQUEST['target'];

				if(!cdav_perms($id,$addressbooks))
					return;

				$uri = $_REQUEST['uri'];

				$object = $carddavBackend->getCard($id, $uri);
				$vcard = \Sabre\VObject\Reader::read($object['carddata']);

				$fn = $_REQUEST['fn'];
				if($fn) {
					$vcard->FN = $fn;
					$vcard->N = array_reverse(explode(' ', $fn));
				}

				$org = $_REQUEST['org'];
				if($org) {
					$vcard->ORG = $org;
				}
				else {
					unset($vcard->ORG);
				}

				$title = $_REQUEST['title'];
				if($title) {
					$vcard->TITLE = $title;
				}
				else {
					unset($vcard->TITLE);
				}

				$tel = $_REQUEST['tel'];
				$tel_type = $_REQUEST['tel_type'];
				if($tel) {
					$i = 0;
					unset($vcard->TEL);
					foreach($tel as $item) {
						if($item) {
							$vcard->add('TEL', $item, ['type' => $tel_type[$i]]);
						}
						$i++;
					}
				}
				else {
					unset($vcard->TEL);
				}

				$email = $_REQUEST['email'];
				$email_type = $_REQUEST['email_type'];
				if($email) {
					$i = 0;
					unset($vcard->EMAIL);
					foreach($email as $item) {
						if($item) {
							$vcard->add('EMAIL', $item, ['type' => $email_type[$i]]);
						}
						$i++;
					}
				}
				else {
					unset($vcard->EMAIL);
				}

				$impp = $_REQUEST['impp'];
				$impp_type = $_REQUEST['impp_type'];
				if($impp) {
					$i = 0;
					unset($vcard->IMPP);
					foreach($impp as $item) {
						if($item) {
							$vcard->add('IMPP', $item, ['type' => $impp_type[$i]]);
						}
						$i++;
					}
				}
				else {
					unset($vcard->IMPP);
				}

				$url = $_REQUEST['url'];
				$url_type = $_REQUEST['url_type'];
				if($url) {
					$i = 0;
					unset($vcard->URL);
					foreach($url as $item) {
						if($item) {
							$vcard->add('URL', $item, ['type' => $url_type[$i]]);
						}
						$i++;
					}
				}
				else {
					unset($vcard->URL);
				}

				$adr = $_REQUEST['adr'];
				$adr_type = $_REQUEST['adr_type'];
				if($adr) {
					$i = 0;
					unset($vcard->ADR);
					foreach($adr as $item) {
						if($item) {
							$vcard->add('ADR', $item, ['type' => $adr_type[$i]]);
						}
						$i++;
					}
				}
				else {
					unset($vcard->ADR);
				}

				$note = $_REQUEST['note'];
				if($note) {
					$vcard->NOTE = $note;
				}
				else {
					unset($vcard->NOTE);
				}

				$cardData = $vcard->serialize();

				$carddavBackend->updateCard($id, $uri, $cardData);
			}

			//delete addressbook card
			if($_REQUEST['delete'] && $_REQUEST['uri'] && $_REQUEST['target']) {

				$id = $_REQUEST['target'];

				if(!cdav_perms($id,$addressbooks))
					return;

				$uri = $_REQUEST['uri'];

				$carddavBackend->deleteCard($id, $uri);
			}
		}

		//Import calendar or addressbook
		if(($_FILES) && array_key_exists('userfile',$_FILES) && intval($_FILES['userfile']['size']) && $_REQUEST['target']) {

			$src = @file_get_contents($_FILES['userfile']['tmp_name']);

			if($src) {

				if($_REQUEST['c_upload']) {
					$id = explode(':', $_REQUEST['target']);
					$ext = 'ics';
					$table = 'calendarobjects';
					$column = 'calendarid';
					$objects = new \Sabre\VObject\Splitter\ICalendar($src);
					$profile = \Sabre\VObject\Node::PROFILE_CALDAV;
					$backend = new \Sabre\CalDAV\Backend\PDO($pdo);
				}

				if($_REQUEST['a_upload']) {
					$id[] = intval($_REQUEST['target']);
					$ext = 'vcf';
					$table = 'cards';
					$column = 'addressbookid';
					$objects = new \Sabre\VObject\Splitter\VCard($src);
					$profile = \Sabre\VObject\Node::PROFILE_CARDDAV;
					$backend = new \Sabre\CardDAV\Backend\PDO($pdo);
				}

				while ($object = $objects->getNext()) {

					if($_REQUEST['a_upload']) {
						$object = $object->convert(\Sabre\VObject\Document::VCARD40);
					}

					$ret = $object->validate($profile & \Sabre\VObject\Node::REPAIR);

					//level 3 Means that the document is invalid,
					//level 2 means a warning. A warning means it's valid but it could cause interopability issues,
					//level 1 means that there was a problem earlier, but the problem was automatically repaired.

					if($ret[0]['level'] < 3) {
						do {
							$duplicate = false;
							$objectUri = random_string(40) . '.' . $ext;

							$r = q("SELECT uri FROM $table WHERE $column = %d AND uri = '%s' LIMIT 1",
								dbesc($id[0]),
								dbesc($objectUri)
							);

							if (count($r))
								$duplicate = true;
						} while ($duplicate == true);

						if($_REQUEST['c_upload']) {
							$backend->createCalendarObject($id, $objectUri, $object->serialize());
						}

						if($_REQUEST['a_upload']) {
							$backend->createCard($id[0], $objectUri, $object->serialize());
						}
					}
					else {
						if($_REQUEST['c_upload']) {
							notice( '<strong>' . t('INVALID EVENT DISMISSED!') . '</strong>' . EOL .
								'<strong>' . t('Summary: ') . '</strong>' . (($object->VEVENT->SUMMARY) ? $object->VEVENT->SUMMARY : t('Unknown')) . EOL .
								'<strong>' . t('Date: ') . '</strong>' . (($object->VEVENT->DTSTART) ? $object->VEVENT->DTSTART : t('Unknown')) . EOL .
								'<strong>' . t('Reason: ') . '</strong>' . $ret[0]['message'] . EOL
							);
						}

						if($_REQUEST['a_upload']) {
							notice( '<strong>' . t('INVALID CARD DISMISSED!') . '</strong>' . EOL .
								'<strong>' . t('Name: ') . '</strong>' . (($object->FN) ? $object->FN : t('Unknown')) . EOL .
								'<strong>' . t('Reason: ') . '</strong>' . $ret[0]['message'] . EOL
							);
						}
					}
				}
			}
			@unlink($src);
		}
	}

	function get() {

		if(!local_channel())
			return;

		if(get_pconfig(local_channel(),'cdav','enabled') != 1)
			return t('You have to enable this plugin in Feature/Addon Settings > CalDAV/CardDAV Settings before you can use it.');
			//TODO: add a possibility to enable this plugin here.

		$channel = \App::get_channel();
		$principalUri = 'principals/' . $channel['channel_address'];

		if(!cdav_principal($principalUri))
			return;

		$pdo = \DBA::$dba->db;

/*
		if(\DBA::$dba && \DBA::$dba->connected)
			$pdovars = \DBA::$dba->pdo_get();
		else
			killme();

		$pdo = new \PDO($pdovars[0],$pdovars[1],$pdovars[2]);
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
*/
		require_once 'vendor/autoload.php';

		head_add_css('addon/cdav/view/css/cdav.css');

		if(argv(1) === 'calendar') {
			$caldavBackend = new \Sabre\CalDAV\Backend\PDO($pdo);
			$calendars = $caldavBackend->getCalendarsForUser($principalUri);
		}

		//Display calendar(s) here
		if(argc() == 2 && argv(1) === 'calendar') {

			head_add_css('library/fullcalendar/fullcalendar.css');
			head_add_css('addon/cdav/view/css/cdav_calendar.css');

			head_add_js('library/moment/moment.min.js', 1);
			head_add_js('library/fullcalendar/fullcalendar.min.js', 1);
			head_add_js('library/fullcalendar/locale-all.js', 1);

			foreach($calendars as $calendar) {
				$editable = (($calendar['share-access'] == 2) ? 'false' : 'true');  // false/true must be string since we're passing it to javascript
				$color = (($calendar['{http://apple.com/ns/ical/}calendar-color']) ? $calendar['{http://apple.com/ns/ical/}calendar-color'] : '#3a87ad');
				$sharer = (($calendar['share-access'] == 3) ? $calendar['{urn:ietf:params:xml:ns:caldav}calendar-description'] : '');
				$switch = get_pconfig(local_channel(), 'cdav_calendar', $calendar['id'][0]);
				if($switch) {
					$sources .= '{
						url: \'/cdav/calendar/json/' . $calendar['id'][0] . '/' . $calendar['id'][1] . '\',
						color: \'' . $color . '\'
					 }, ';
				}

				if($calendar['share-access'] != 2) {
					$writable_calendars[] = [
						'displayname' => $calendar['{DAV:}displayname'],
						'sharer' => $sharer,
						'id' => $calendar['id']
					];
				}
			}

			$sources = rtrim($sources, ', ');

			$first_day = get_pconfig(local_channel(),'system','cal_first_day');
			$first_day = (($first_day) ? $first_day : 0);

			$title = ['title', t('Event title')];
			$dtstart = ['dtstart', t('Start date and time'), '', t('Example: YYYY-MM-DD HH:mm')];
			$dtend = ['dtend', t('End date and time'), '', t('Example: YYYY-MM-DD HH:mm')];
			$description = ['description', t('Description')];
			$location = ['location', t('Location')];

			$o .= replace_macros(get_markup_template('cdav_calendar.tpl', 'addon/cdav'), [
				'$sources' => $sources,
				'$color' => $color,
				'$lang' => \App::$language,
				'$first_day' => $first_day,
				'$prev'	=> t('Previous'),
				'$next'	=> t('Next'),
				'$today' => t('Today'),
				'$month' => t('Month'),
				'$week' => t('Week'),
				'$day' => t('Day'),
				'$list_month' => t('List month'),
				'$list_week' => t('List week'),
				'$list_day' => t('List day'),
				'$title' => $title,
				'$writable_calendars' => $writable_calendars,
				'$dtstart' => $dtstart,
				'$dtend' => $dtend,
				'$description' => $description,
				'$location' => $location,
				'$more' => t('More'),
				'$less' => t('Less'),
				'$calendar_select_label' => t('Select calendar'),
				'$delete' => t('Delete'),
				'$delete_all' => t('Delete all'),
				'$cancel' => t('Cancel'),
				'$recurrence_warning' => t('Sorry! Editing of recurrent events is not yet implemented.')
			]);

			return $o;

		}

		//Provide json data for calendar
		if(argc() == 5 && argv(1) === 'calendar' && argv(2) === 'json'  && intval(argv(3)) && intval(argv(4))) {

			$id = [argv(3), argv(4)];

			if(! cdav_perms($id[0],$calendars))
				killme();

			if (x($_GET,'start'))
				$start = new \DateTime($_GET['start']);
			if (x($_GET,'end'))
				$end = new \DateTime($_GET['end']);

			$filters['name'] = 'VCALENDAR';
			$filters['prop-filters'][0]['name'] = 'VEVENT';
			$filters['comp-filters'][0]['name'] = 'VEVENT';
			$filters['comp-filters'][0]['time-range']['start'] = $start;
			$filters['comp-filters'][0]['time-range']['end'] = $end;

			$uris = $caldavBackend->calendarQuery($id, $filters);
			if($uris) {

				$objects = $caldavBackend->getMultipleCalendarObjects($id, $uris);

				foreach($objects as $object) {

					$vcalendar = \Sabre\VObject\Reader::read($object['calendardata']);

					if(isset($vcalendar->VEVENT->RRULE))
						$vcalendar = $vcalendar->expand($start, $end);

					foreach($vcalendar->VEVENT as $vevent) {
						$title = (string)$vevent->SUMMARY;
						$dtstart = (string)$vevent->DTSTART;
						$dtend = (string)$vevent->DTEND;
						$description = (string)$vevent->DESCRIPTION;
						$location = (string)$vevent->LOCATION;

						$rw = ((cdav_perms($id[0],$calendars,true)) ? true : false);
						$recurrent = ((isset($vevent->{'RECURRENCE-ID'})) ? true : false);

						$editable = $rw ? true : false;

						if($recurrent)
							$editable = false;

						$allDay = false;

						// allDay event rules
						if(!strpos($dtstart, 'T') && !strpos($dtend, 'T'))
							$allDay = true;
						if(strpos($dtstart, 'T000000') && strpos($dtend, 'T000000'))
							$allDay = true;

						$events[] = [
							'calendar_id' => $id,
							'uri' => $object['uri'],
							'title' => $title,
							'start' => $dtstart,
							'end' => $dtend,
							'description' => $description,
							'location' => $location,
							'allDay' => $allDay,
							'editable' => $editable,
							'recurrent' => $recurrent,
							'rw' => $rw
						];
					}
				}
				json_return_and_die($events);
			}
			else {
				killme();
			}
		}

		//enable/disable calendars
		if(argc() == 5 && argv(1) === 'calendar' && argv(2) === 'switch'  && intval(argv(3)) && (argv(4) == 1 || argv(4) == 0)) {
			$id = argv(3);

			if(! cdav_perms($id,$calendars))
				killme();

			set_pconfig(local_channel(), 'cdav_calendar' , argv(3), argv(4));
			killme();
		}

		//drop calendar
		if(argc() == 5 && argv(1) === 'calendar' && argv(2) === 'drop' && intval(argv(3)) && intval(argv(4))) {
			$id = [argv(3), argv(4)];

			if(! cdav_perms($id[0],$calendars))
				killme();

			$caldavBackend->deleteCalendar($id);
			killme();
		}

		//drop sharee
		if(argc() == 6 && argv(1) === 'calendar' && argv(2) === 'dropsharee'  && intval(argv(3)) && intval(argv(4))) {

			$id = [argv(3), argv(4)];
			$hash = argv(5);

			if(! cdav_perms($id[0],$calendars))
				killme();

			$sharee_arr = channelx_by_hash($hash);

			$sharee = new \Sabre\DAV\Xml\Element\Sharee();

			$sharee->href = 'mailto:' . $sharee_arr['channel_hash'];
			$sharee->principal = 'principals/' . $sharee_arr['channel_address'];
			$sharee->access = 4;
			$caldavBackend->updateInvites($id, [$sharee]);

			killme();
		}


		if(argv(1) === 'addressbook') {
			$carddavBackend = new \Sabre\CardDAV\Backend\PDO($pdo);
			$addressbooks = $carddavBackend->getAddressBooksForUser($principalUri);
		}

		//Display Adressbook here
		if(argc() == 3 && argv(1) === 'addressbook' && intval(argv(2))) {

			$id = argv(2);

			$displayname = cdav_perms($id,$addressbooks);

			if(!$displayname)
				return;

			head_add_css('addon/cdav/view/css/cdav_addressbook.css');

			$o = '';

			$sabrecards = $carddavBackend->getCards($id);
			foreach($sabrecards as $sabrecard) {
				$uris[] = $sabrecard['uri'];
			}

			if($uris) {
				$objects = $carddavBackend->getMultipleCards($id, $uris);

				foreach($objects as $object) {
					$vcard = \Sabre\VObject\Reader::read($object['carddata']);

					$photo = '';
					if($vcard->PHOTO) {
						$photo_value = strtolower($vcard->PHOTO->getValueType()); // binary or uri
						if($photo_value === 'binary') {
							$photo_type = strtolower($vcard->PHOTO['TYPE']); // mime jpeg, png or gif
							$photo = 'data:image/' . $photo_type . ';base64,' . base64_encode((string)$vcard->PHOTO);
						}
						else {
							$url = parse_url((string)$vcard->PHOTO);
							$photo = 'data:' . $url['path'];
						}
					}

					$fn = '';
					if($vcard->FN) {
						$fn = (string)$vcard->FN;
					}

					$org = '';
					if($vcard->ORG) {
						$org = (string)$vcard->ORG;
					}

					$title = '';
					if($vcard->TITLE) {
						$title = (string)$vcard->TITLE;
					}

					$tels = [];
					if($vcard->TEL) {
						foreach($vcard->TEL as $tel) {
							$type = (($tel['TYPE']) ? translate_type((string)$tel['TYPE']) : '');
							$tels[] = [
								'type' => $type,
								'nr' => (string)$tel
							];
						}
					}

					$emails = [];
					if($vcard->EMAIL) {
						foreach($vcard->EMAIL as $email) {
							$type = (($email['TYPE']) ? translate_type((string)$email['TYPE']) : '');
							$emails[] = [
								'type' => $type,
								'address' => (string)$email
							];
						}
					}

					$impps = [];
					if($vcard->IMPP) {
						foreach($vcard->IMPP as $impp) {
							$type = (($impp['TYPE']) ? translate_type((string)$impp['TYPE']) : '');
							$impps[] = [
								'type' => $type,
								'address' => (string)$impp
							];
						}
					}

					$urls = [];
					if($vcard->URL) {
						foreach($vcard->URL as $url) {
							$type = (($url['TYPE']) ? translate_type((string)$url['TYPE']) : '');
							$urls[] = [
								'type' => $type,
								'address' => (string)$url
							];
						}
					}

					$adrs = [];
					if($vcard->ADR) {
						foreach($vcard->ADR as $adr) {
							$type = (($adr['TYPE']) ? translate_type((string)$adr['TYPE']) : '');
							$adrs[] = [
								'type' => $type,
								'address' => $adr->getParts()
							];
						}
					}

					$note = '';
					if($vcard->NOTE) {
						$note = (string)$vcard->NOTE;
					}

					$cards[] = [
						'id' => $object['id'],
						'uri' => $object['uri'],

						'photo' => $photo,
						'fn' => $fn,
						'org' => $org,
						'title' => $title,
						'tels' => $tels,
						'emails' => $emails,
						'impps' => $impps,
						'urls' => $urls,
						'adrs' => $adrs,
						'note' => $note
					];
				}

				usort($cards, function($a, $b) { return strcasecmp($a['fn'], $b['fn']); });
			}

			$o .= replace_macros(get_markup_template('cdav_addressbook.tpl', 'addon/cdav'), [
				'$id' => $id,
				'$cards' => $cards,
				'$displayname' => $displayname,
				'$name_label' => t('Name'),
				'$org_label' => t('Organisation'),
				'$title_label' => t('Title'),
				'$tel_label' => t('Phone'),
				'$email_label' => t('Email'),
				'$impp_label' => t('Instant messenger'),
				'$url_label' => t('Website'),
				'$adr_label' => t('Address'),
				'$note_label' => t('Note'),
				'$mobile' => t('Mobile'),
				'$home' => t('Home'),
				'$work' => t('Work'),
				'$other' => t('Other'),
				'$add_card' => t('Add Contact'),
				'$add_field' => t('Add Field'),
				'$create' => t('Create'),
				'$update' => t('Update'),
				'$delete' => t('Delete'),
				'$cancel' => t('Cancel'),
				'$po_box' => t('P.O. Box'),
				'$extra' => t('Additional'),
				'$street' => t('Street'),
				'$locality' => t('Locality'),
				'$region' => t('Region'),
				'$zip_code' => t('ZIP Code'),
				'$country' => t('Country')
			]);

			return $o;
		}

		//delete addressbook
		if(argc() > 3 && argv(1) === 'addressbook' && argv(2) === 'drop' && intval(argv(3))) {
			$id = argv(3);

			if(! cdav_perms($id,$addressbooks))
				return;

			$carddavBackend->deleteAddressBook($id);
			killme();
		}

	}
}
