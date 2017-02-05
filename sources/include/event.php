<?php
use Sabre\VObject;

/**
 * @file include/event.php
 */

/**
 * @brief Returns an event as HTML
 *
 * @param array $ev
 * @return string
 */
function format_event_html($ev) {

	require_once('include/bbcode.php');

	if(! ((is_array($ev)) && count($ev)))
		return '';


	$bd_format = t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8:01 AM

	//todo: move this to template

	$o = '<div class="vevent">' . "\r\n";

	$o .= '<div class="event-title"><h3><i class="fa fa-calendar"></i>&nbsp;' . bbcode($ev['summary']) .  '</h3></div>' . "\r\n";

	$o .= '<div class="event-start"><span class="event-label">' . t('Starts:') . '</span>&nbsp;<span class="dtstart" title="'
		. datetime_convert('UTC', 'UTC', $ev['dtstart'], (($ev['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' ))
		. '" >' 
		. (($ev['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(), 
			$ev['dtstart'] , $bd_format ))
			:  day_translate(datetime_convert('UTC', 'UTC', 
			$ev['dtstart'] , $bd_format)))
		. '</span></div>' . "\r\n";

	if(! $ev['nofinish'])
		$o .= '<div class="event-end" ><span class="event-label">' . t('Finishes:') . '</span>&nbsp;<span class="dtend" title="'
			. datetime_convert('UTC','UTC',$ev['dtend'], (($ev['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' ))
			. '" >' 
			. (($ev['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(), 
				$ev['dtend'] , $bd_format ))
				:  day_translate(datetime_convert('UTC', 'UTC', 
				$ev['dtend'] , $bd_format )))
			. '</span></div>'  . "\r\n";

	$o .= '<div class="event-description">' . bbcode($ev['description']) .  '</div>' . "\r\n";

	if(strlen($ev['location']))
		$o .= '<div class="event-location"><span class="event-label"> ' . t('Location:') . '</span>&nbsp;<span class="location">' 
			. bbcode($ev['location'])
			. '</span></div>' . "\r\n";

	$o .= '</div>' . "\r\n";

	return $o;
}

function format_event_obj($jobject) {
	$event = array();

	$object = json_decode($jobject,true);

	//ensure compatibility with older items - this check can be removed at a later point
	if(array_key_exists('description', $object)) {

		$bd_format = t('l F d, Y \@ g:i A'); // Friday January 18, 2011 @ 8:01 AM

		$event['header'] = replace_macros(get_markup_template('event_item_header.tpl'),array(
			'$title'	 => bbcode($object['title']),
			'$dtstart_label' => t('Starts:'),
			'$dtstart_title' => datetime_convert('UTC', 'UTC', $object['dtstart'], (($object['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' )),
			'$dtstart_dt'	 => (($object['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(), $object['dtstart'] , $bd_format )) : day_translate(datetime_convert('UTC', 'UTC', $object['dtstart'] , $bd_format))),
			'$finish'	 => (($object['nofinish']) ? false : true),
			'$dtend_label'	 => t('Finishes:'),
			'$dtend_title'	 => datetime_convert('UTC','UTC',$object['dtend'], (($object['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' )),
			'$dtend_dt'	 => (($object['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(), $object['dtend'] , $bd_format )) :  day_translate(datetime_convert('UTC', 'UTC', $object['dtend'] , $bd_format )))
		));

		$event['content'] = replace_macros(get_markup_template('event_item_content.tpl'),array(
			'$description'	  => bbcode($object['description']),
			'$location_label' => t('Location:'),
			'$location'	  => bbcode($object['location'])
		));

	}

	return $event;
}

function ical_wrapper($ev) {

	if(! ((is_array($ev)) && count($ev)))
		return '';

	$o .= "BEGIN:VCALENDAR";
	$o .= "\r\nVERSION:2.0";
	$o .= "\r\nMETHOD:PUBLISH";
	$o .= "\r\nPRODID:-//" . get_config('system','sitename') . "//" . Zotlabs\Lib\System::get_platform_name() . "//" . strtoupper(App::$language). "\r\n";
	if(array_key_exists('dtstart', $ev))
		$o .= format_event_ical($ev);
	else {
		foreach($ev as $e) {
			$o .= format_event_ical($e);
		}
	}
	$o .= "\r\nEND:VCALENDAR\r\n";

	return $o;
}

function format_event_ical($ev) {

	if($ev['etype'] === 'task')
		return format_todo_ical($ev);

	$o = '';

	$o .= "\r\nBEGIN:VEVENT";

	$o .= "\r\nCREATED:" . datetime_convert('UTC','UTC', $ev['created'],'Ymd\\THis\\Z');
	$o .= "\r\nLAST-MODIFIED:" . datetime_convert('UTC','UTC', $ev['edited'],'Ymd\\THis\\Z');
	$o .= "\r\nDTSTAMP:" . datetime_convert('UTC','UTC', $ev['edited'],'Ymd\\THis\\Z');
	if($ev['dtstart']) 
		$o .= "\r\nDTSTART:" . datetime_convert('UTC','UTC', $ev['dtstart'],'Ymd\\THis' . (($ev['adjust']) ? '\\Z' : ''));
	if($ev['dtend'] && ! $ev['nofinish']) 
		$o .= "\r\nDTEND:" . datetime_convert('UTC','UTC', $ev['dtend'],'Ymd\\THis' . (($ev['adjust']) ? '\\Z' : ''));
	if($ev['summary']) 
		$o .= "\r\nSUMMARY:" . format_ical_text($ev['summary']);
	if($ev['location'])
		$o .= "\r\nLOCATION:" . format_ical_text($ev['location']);
	if($ev['description']) 
		$o .= "\r\nDESCRIPTION:" . format_ical_text($ev['description']);
	if($ev['event_priority'])
		$o .= "\r\nPRIORITY:" . intval($ev['event_priority']);
	$o .= "\r\nUID:" . $ev['event_hash'] ;
	$o .= "\r\nEND:VEVENT\r\n";
	
	return $o;
}


function format_todo_ical($ev) {

	$o = '';

	$o .= "\r\nBEGIN:VTODO";
	$o .= "\r\nCREATED:" . datetime_convert('UTC','UTC', $ev['created'],'Ymd\\THis\\Z');
	$o .= "\r\nLAST-MODIFIED:" . datetime_convert('UTC','UTC', $ev['edited'],'Ymd\\THis\\Z');
	$o .= "\r\nDTSTAMP:" . datetime_convert('UTC','UTC', $ev['edited'],'Ymd\\THis\\Z');
	if($ev['dtstart']) 
		$o .= "\r\nDTSTART:" . datetime_convert('UTC','UTC', $ev['dtstart'],'Ymd\\THis' . (($ev['adjust']) ? '\\Z' : ''));
	if($ev['dtend'] && ! $ev['nofinish']) 
		$o .= "\r\nDUE:" . datetime_convert('UTC','UTC', $ev['dtend'],'Ymd\\THis' . (($ev['adjust']) ? '\\Z' : ''));
	if($ev['summary']) 
		$o .= "\r\nSUMMARY:" . format_ical_text($ev['summary']);
	if($ev['event_status']) {
		$o .= "\r\nSTATUS:" . $ev['event_status'];
		if($ev['event_status'] === 'COMPLETED')
			$o .= "\r\nCOMPLETED:" . datetime_convert('UTC','UTC', $ev['event_status_date'],'Ymd\\THis\\Z');
	}
	if(intval($ev['event_percent']))
		$o .= "\r\nPERCENT-COMPLETE:" . $ev['event_percent'];		
	if(intval($ev['event_sequence'])) 
		$o .= "\r\nSEQUENCE:" . $ev['event_sequence'];
	if($ev['location'])
		$o .= "\r\nLOCATION:" . format_ical_text($ev['location']);
	if($ev['description']) 
		$o .= "\r\nDESCRIPTION:" . format_ical_text($ev['description']);
	$o .= "\r\nUID:" . $ev['event_hash'] ;
	if($ev['event_priority'])
		$o .= "\r\nPRIORITY:" . intval($ev['event_priority']);
	$o .= "\r\nEND:VTODO\r\n";

	return $o;
}



function format_ical_text($s) {
	require_once('include/bbcode.php');
	require_once('include/html2plain.php');

	$s = html2plain(bbcode($s));
	$s = str_replace(["\r\n","\n"],["",""],$s);
	return(wordwrap(str_replace(['\\',',',';'],['\\\\','\\,','\\;'],$s),72,"\r\n ",true));
}


function format_event_bbcode($ev) {

	$o = '';

	if($ev['summary'])
		$o .= '[event-summary]' . $ev['summary'] . '[/event-summary]';

	if($ev['description'])
		$o .= '[event-description]' . $ev['description'] . '[/event-description]';

	if($ev['dtstart'])
		$o .= '[event-start]' . $ev['dtstart'] . '[/event-start]';

	if(($ev['dtend']) && (! $ev['nofinish']))
		$o .= '[event-finish]' . $ev['dtend'] . '[/event-finish]';
 
	if($ev['location'])
		$o .= '[event-location]' . $ev['location'] . '[/event-location]';

	if($ev['event_hash'])
		$o .= '[event-id]' . $ev['event_hash'] . '[/event-id]';

	if($ev['adjust'])
		$o .= '[event-adjust]' . $ev['adjust'] . '[/event-adjust]';

	return $o;
}


function bbtovcal($s) {
	$o = '';
	$ev = bbtoevent($s);
	if($ev['description'])
		$o = format_event_html($ev);

	return $o;
}


function bbtoevent($s) {

	$ev = array();

	$match = '';
	if(preg_match("/\[event\-summary\](.*?)\[\/event\-summary\]/is",$s,$match))
		$ev['summary'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-description\](.*?)\[\/event\-description\]/is",$s,$match))
		$ev['description'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-start\](.*?)\[\/event\-start\]/is",$s,$match))
		$ev['dtstart'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-finish\](.*?)\[\/event\-finish\]/is",$s,$match))
		$ev['dtend'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-location\](.*?)\[\/event\-location\]/is",$s,$match))
		$ev['location'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-id\](.*?)\[\/event\-id\]/is",$s,$match))
		$ev['event_hash'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-adjust\](.*?)\[\/event\-adjust\]/is",$s,$match))
		$ev['adjust'] = $match[1];
	if(array_key_exists('dtstart',$ev)) {
		if(array_key_exists('dtend',$ev)) {
			if($ev['dtend'] === $ev['dtstart'])
				$ev['nofinish'] = 1;
			elseif($ev['dtend'])
				$ev['nofinish'] = 0;
			else
				$ev['nofinish'] = 1;
		}
		else
			$ev['nofinish'] = 1;
	}

	return $ev;
}

/**
 * @brief Sorts the given array of events by date.
 *
 * @see ev_compare()
 * @param array $arr
 * @return sorted array
 */
function sort_by_date($arr) {
	if (is_array($arr))
		usort($arr, 'ev_compare');

	return $arr;
}

/**
 * @brief Compare function for events.
 *
 * @see sort_by_date()
 * @param array $a
 * @param array $b
 * @return number return values like strcmp()
 */
function ev_compare($a, $b) {

	$date_a = (($a['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$a['dtstart']) : $a['dtstart']);
	$date_b = (($b['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$b['dtstart']) : $b['dtstart']);

	if ($date_a === $date_b)
		return strcasecmp($a['description'], $b['description']);

	return strcmp($date_a, $date_b);
}


function event_store_event($arr) {

	$arr['created']        = (($arr['created'])        ? $arr['created']        : datetime_convert());
	$arr['edited']         = (($arr['edited'])         ? $arr['edited']         : datetime_convert());
	$arr['etype']          = (($arr['etype'])          ? $arr['etype']          : 'event' );
	$arr['event_xchan']    = (($arr['event_xchan'])    ? $arr['event_xchan']    : '');
	$arr['event_priority'] = (($arr['event_priority']) ? $arr['event_priority'] : 0);


	if(array_key_exists('event_status_date',$arr))
		$arr['event_status_date'] = datetime_convert('UTC','UTC', $arr['event_status_date']);
	else
		$arr['event_status_date'] = NULL_DATE;


	$existing_event = null;

	if($arr['event_hash']) {
		$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
			dbesc($arr['event_hash']),
			intval($arr['uid'])
		);
		if($r) {
			$existing_event = $r[0];
		}
	}

	if($arr['id']) {
		$r = q("SELECT * FROM event WHERE id = %d AND uid = %d LIMIT 1",
			intval($arr['id']),
			intval($arr['uid'])
		);
		if($r) {
			$existing_event = $r[0];
		}
		else {
			return false;
		}
	}

	$hook_info = [ 'event' => $arr, 'existing_event' => $existing_event, 'cancel' => false ];
	call_hooks('event_store_event',$hook_info);
	if($hook_info['cancel'])
		return false;

	$arr = $hook_info['event'];
	$existing_event = $hook_info['existing_event'];

	if($existing_event) {

		if($existing_event['edited'] >= $arr['edited']) {
			// Nothing has changed. 
			return $existing_event;
		}

		$hash = $existing_event['event_hash'];

		// The event changed. Update it.

		$r = q("UPDATE event SET
			edited = '%s',
			dtstart = '%s',
			dtend = '%s',
			summary = '%s',
			description = '%s',
			location = '%s',
			etype = '%s',
			adjust = %d,
			nofinish = %d,
			event_status = '%s',
			event_status_date = '%s',
			event_percent = %d,
			event_repeat = '%s',
			event_sequence = %d,
			event_priority = %d,
			event_vdata = '%s',
			allow_cid = '%s',
			allow_gid = '%s',
			deny_cid = '%s',
			deny_gid = '%s'
			WHERE id = %d AND uid = %d",

			dbesc($arr['edited']),
			dbesc($arr['dtstart']),
			dbesc($arr['dtend']),
			dbesc($arr['summary']),
			dbesc($arr['description']),
			dbesc($arr['location']),
			dbesc($arr['etype']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			dbesc($arr['event_status']),
			dbesc($arr['event_status_date']),
			intval($arr['event_percent']),
			dbesc($arr['event_repeat']),
			intval($arr['event_sequence']),
			intval($arr['event_priority']),
			dbesc($arr['event_vdata']),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid']),
			intval($existing_event['id']),
			intval($arr['uid'])
		);
	} else {

		// New event. Store it.


		if(array_key_exists('external_id',$arr))
			$hash = $arr['external_id'];
		elseif(array_key_exists('event_hash',$arr))
			$hash = $arr['event_hash'];
		else
			$hash = random_string() . '@' . App::get_hostname();

		$r = q("INSERT INTO event ( uid,aid,event_xchan,event_hash,created,edited,dtstart,dtend,summary,description,location,etype,
			adjust,nofinish, event_status, event_status_date, event_percent, event_repeat, event_sequence, event_priority, event_vdata, allow_cid,allow_gid,deny_cid,deny_gid)
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', %d, '%s', %d, %d, '%s', '%s', '%s', '%s', '%s' ) ",
			intval($arr['uid']),
			intval($arr['account']),
			dbesc($arr['event_xchan']),
			dbesc($hash),
			dbesc($arr['created']),
			dbesc($arr['edited']),
			dbesc($arr['dtstart']),
			dbesc($arr['dtend']),
			dbesc($arr['summary']),
			dbesc($arr['description']),
			dbesc($arr['location']),
			dbesc($arr['etype']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			dbesc($arr['event_status']),
			dbesc($arr['event_status_date']),
			intval($arr['event_percent']),
			dbesc($arr['event_repeat']),
			intval($arr['event_sequence']),
			intval($arr['event_priority']),
			dbesc($arr['event_vdata']),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid'])
		);
	}

	$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
		dbesc($hash),
		intval($arr['uid'])
	);
	if($r)
		return $r[0];

	return false;
}

function event_addtocal($item_id, $uid) {

	$c = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);

	if(! $c)
		return false;

	$channel = $c[0];

	$r = q("select * from item where id = %d and uid = %d limit 1",
		intval($item_id),
		intval($channel['channel_id'])
	);

	if((! $r) || ($r[0]['obj_type'] !== ACTIVITY_OBJ_EVENT))
		return false;

	$item = $r[0];

	$ev = bbtoevent($r[0]['body']);

	if(x($ev,'summary') && x($ev,'dtstart')) {
		$ev['event_xchan'] = $item['author_xchan'];
		$ev['uid']         = $channel['channel_id'];
		$ev['account']     = $channel['channel_account_id'];
		$ev['edited']      = $item['edited'];
		$ev['mid']         = $item['mid'];
		$ev['private']     = $item['item_private'];

		// is this an edit?

		if($item['resource_type'] === 'event' && (! $ev['event_hash'])) {
			$ev['event_hash'] = $item['resource_id'];
		}

		if($ev->private)
			$ev['allow_cid'] = '<' . $channel['channel_hash'] . '>'; 
		else {
			$acl = new Zotlabs\Access\AccessList($channel);
			$x = $acl->get();
			$ev['allow_cid'] = $x['allow_cid'];
			$ev['allow_gid'] = $x['allow_gid'];
			$ev['deny_cid']  = $x['deny_cid'];
			$ev['deny_gid']  = $x['deny_gid'];
		}

		$event = event_store_event($ev);
		if($event) {
			$r = q("update item set resource_id = '%s', resource_type = 'event' where id = %d and uid = %d",
				dbesc($event['event_hash']),
				intval($item['id']),
				intval($channel['channel_id'])
			);

			$item['resource_id'] = $event['event_hash'];
			$item['resource_type'] = 'event';

			$i = array($item);
			xchan_query($i);
			$sync_item = fetch_post_tags($i);
			$z = q("select * from event where event_hash = '%s' and uid = %d limit 1",
				dbesc($event['event_hash']),
				intval($channel['channel_id'])
			);
			if($z) {
				build_sync_packet($channel['channel_id'],array('event_item' => array(encode_item($sync_item[0],true)),'event' => $z));
			}
			return true;
		}
	}

	return false;
}


function parse_ical_file($f,$uid) {
require_once('vendor/autoload.php');

	$s = @file_get_contents($f);

	// Change the current timezone to something besides UTC.
	// Doesn't matter what it is, as long as it isn't UTC.
	// Save the current timezone so we can reset it when we're done processing.

	$saved_timezone = date_default_timezone_get();
	date_default_timezone_set('Australia/Sydney');

	$ical = VObject\Reader::read($s);

	if($ical) {
		if($ical->VEVENT) {
			foreach($ical->VEVENT as $event) {
				event_import_ical($event,$uid);
			}
		}
		if($ical->VTODO) {
			foreach($ical->VTODO as $event) {
				event_import_ical_task($event,$uid);
			}
		}
	}

	date_default_timezone_set($saved_timezone);

	if($ical)
		return true;
	return false;
}



function event_import_ical($ical, $uid) {

	$c = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);

	if(! $c)
		return false;

	$channel = $c[0];
	$ev = array();


	if(! isset($ical->DTSTART)) {
		logger('no event start');
		return false;
	}

	$dtstart = $ical->DTSTART->getDateTime();
	$ev['adjust'] = (($ical->DTSTART->isFloating()) ? 1 : 0);

//	logger('dtstart: ' . var_export($dtstart,true));

	$ev['dtstart'] = datetime_convert((($ev['adjust']) ? 'UTC' : date_default_timezone_get()),'UTC',
		$dtstart->format(\DateTime::W3C));


	if(isset($ical->DTEND)) {
		$dtend = $ical->DTEND->getDateTime();
		$ev['dtend'] = datetime_convert((($ev['adjust']) ? 'UTC' : date_default_timezone_get()),'UTC',
			$dtend->format(\DateTime::W3C));
	}
	else
		$ev['nofinish'] = 1;


	if($ev['dtstart'] === $ev['dtend'])
		$ev['nofinish'] = 1;

	if(isset($ical->CREATED)) {
		$created = $ical->CREATED->getDateTime();
		$ev['created'] = datetime_convert('UTC','UTC',$created->format(\DateTime::W3C));
	}

	if(isset($ical->{'LAST-MODIFIED'})) {
		$edited = $ical->{'LAST-MODIFIED'}->getDateTime();
		$ev['edited'] = datetime_convert('UTC','UTC',$edited->format(\DateTime::W3C));
	}

	if(isset($ical->LOCATION))
		$ev['location'] = (string) $ical->LOCATION;
	if(isset($ical->DESCRIPTION))
		$ev['description'] = (string) $ical->DESCRIPTION;
	if(isset($ical->SUMMARY))
		$ev['summary'] = (string) $ical->SUMMARY;
	if(isset($ical->PRIORITY))
		$ev['event_priority'] = intval((string) $ical->PRIORITY);

	if(isset($ical->UID)) {
		$evuid = (string) $ical->UID;
		$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
			dbesc($evuid),
			intval($uid)
		);
		if($r)
			$ev['event_hash'] = $evuid;
		else
			$ev['external_id'] = $evuid;
	}
		
	if($ev['summary'] && $ev['dtstart']) {
		$ev['event_xchan'] = $channel['channel_hash'];
		$ev['uid']         = $channel['channel_id'];
		$ev['account']     = $channel['channel_account_id'];
		$ev['private']     = 1;
		$ev['allow_cid']   = '<' . $channel['channel_hash'] . '>';

		logger('storing event: ' . print_r($ev,true), LOGGER_ALL);		
		$event = event_store_event($ev);
		if($event) {
			$item_id = event_store_item($ev,$event);
			return true;
		}
	}

	return false;

}

function event_import_ical_task($ical, $uid) {

	$c = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);

	if(! $c)
		return false;

	$channel = $c[0];
	$ev = array();


	if(! isset($ical->DTSTART)) {
		logger('no event start');
		return false;
	}

	$dtstart = $ical->DTSTART->getDateTime();

	$ev['adjust'] = (($ical->DTSTART->isFloating()) ? 1 : 0);

//	logger('dtstart: ' . var_export($dtstart,true));

	$ev['dtstart'] = datetime_convert((($ev['adjust']) ? 'UTC' : date_default_timezone_get()),'UTC',
		$dtstart->format(\DateTime::W3C));


	if(isset($ical->DUE)) {
		$dtend = $ical->DUE->getDateTime();
		$ev['dtend'] = datetime_convert((($ev['adjust']) ? 'UTC' : date_default_timezone_get()),'UTC',
			$dtend->format(\DateTime::W3C));
	}
	else
		$ev['nofinish'] = 1;


	if($ev['dtstart'] === $ev['dtend'])
		$ev['nofinish'] = 1;

	if(isset($ical->CREATED)) {
		$created = $ical->CREATED->getDateTime();
		$ev['created'] = datetime_convert('UTC','UTC',$created->format(\DateTime::W3C));
	}

	if(isset($ical->{'DTSTAMP'})) {
		$edited = $ical->{'DTSTAMP'}->getDateTime();
		$ev['edited'] = datetime_convert('UTC','UTC',$edited->format(\DateTime::W3C));
	}

	if(isset($ical->{'LAST-MODIFIED'})) {
		$edited = $ical->{'LAST-MODIFIED'}->getDateTime();
		$ev['edited'] = datetime_convert('UTC','UTC',$edited->format(\DateTime::W3C));
	}

	if(isset($ical->LOCATION))
		$ev['location'] = (string) $ical->LOCATION;
	if(isset($ical->DESCRIPTION))
		$ev['description'] = (string) $ical->DESCRIPTION;
	if(isset($ical->SUMMARY))
		$ev['summary'] = (string) $ical->SUMMARY;
	if(isset($ical->PRIORITY))
		$ev['event_priority'] = intval((string) $ical->PRIORITY);

	$stored_event = null;

	if(isset($ical->UID)) {
		$evuid = (string) $ical->UID;
		$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
			dbesc($evuid),
			intval($uid)
		);
		if($r) {
			$ev['event_hash'] = $evuid;
			$stored_event = $r[0];
		}
		else {
			$ev['external_id'] = $evuid;
		}
	}

	if(isset($ical->SEQUENCE)) {
		$ev['event_sequence'] = (string) $ical->SEQUENCE;
		// see if our stored event is more current than the one we're importing
		if((intval($ev['event_sequence']) <= intval($stored_event['event_sequence'])) 
			&& ($ev['edited'] <= $stored_event['edited']))
			return false;
	}

	if(isset($ical->STATUS)) {
		$ev['event_status'] = (string) $ical->STATUS;
	}

	if(isset($ical->{'COMPLETED'})) {
		$completed = $ical->{'COMPLETED'}->getDateTime();
		$ev['event_status_date'] = datetime_convert('UTC','UTC',$completed->format(\DateTime::W3C));
	}

	if(isset($ical->{'PERCENT-COMPLETE'})) {
		$ev['event_percent'] = (string) $ical->{'PERCENT-COMPLETE'} ;
	}

	$ev['etype'] = 'task';

	if($ev['summary'] && $ev['dtstart']) {
		$ev['event_xchan'] = $channel['channel_hash'];
		$ev['uid']         = $channel['channel_id'];
		$ev['account']     = $channel['channel_account_id'];
		$ev['private']     = 1;
		$ev['allow_cid']   = '<' . $channel['channel_hash'] . '>';

		logger('storing event: ' . print_r($ev,true), LOGGER_ALL);		
		$event = event_store_event($ev);
		if($event) {
			$item_id = event_store_item($ev,$event);
			return true;
		}
	}

	return false;

}






function event_store_item($arr, $event) {

	require_once('include/datetime.php');
	require_once('include/items.php');
	require_once('include/bbcode.php');

	$item = null;

	if($arr['mid'] && $arr['uid']) {
		$i = q("select * from item where mid = '%s' and uid = %d limit 1",
			dbesc($arr['mid']),
			intval($arr['uid'])
		);
		if($i) {
			xchan_query($i);
			$item = fetch_post_tags($i,true);
		}
	}



	$item_arr = array();
	$prefix = '';
//	$birthday = false;

	if(($event) && array_key_exists('event_hash',$event) && (! array_key_exists('event_hash',$arr)))
		$arr['event_hash'] = $event['event_hash'];

	if($event['etype'] === 'birthday') {
		if(! is_sys_channel($arr['uid']))
			$prefix =  t('This event has been added to your calendar.');
//		$birthday = true;

		// The event is created on your own site by the system, but appears to belong 
		// to the birthday person. It also isn't propagated - so we need to prevent
		// folks from trying to comment on it. If you're looking at this and trying to 
		// fix it, you'll need to completely change the way birthday events are created
		// and send them out from the source. This has its own issues.

		$item_arr['comment_policy'] = 'none';
	}

	$r = q("SELECT * FROM item left join xchan on author_xchan = xchan_hash WHERE resource_id = '%s' AND resource_type = 'event' and uid = %d LIMIT 1",
		dbesc($event['event_hash']),
		intval($arr['uid'])
	);

	if($r) {
		$object = json_encode(array(
			'type'    => ACTIVITY_OBJ_EVENT,
			'id'      => z_root() . '/event/' . $r[0]['resource_id'],
			'title'   => $arr['summary'],
			'dtstart' => $arr['dtstart'],
			'dtend'  => $arr['dtend'],
			'nofinish'  => $arr['nofinish'],
			'description' => $arr['description'],
			'location'   => $arr['location'],
			'adjust'   => $arr['adjust'],
			'content' => format_event_bbcode($arr),
			'author'  => array(
				'name'     => $r[0]['xchan_name'],
				'address'  => $r[0]['xchan_addr'],
				'guid'     => $r[0]['xchan_guid'],
				'guid_sig' => $r[0]['xchan_guid_sig'],
				'link'     => array(
					array('rel' => 'alternate', 'type' => 'text/html', 'href' => $r[0]['xchan_url']),
					array('rel' => 'photo', 'type' => $r[0]['xchan_photo_mimetype'], 'href' => $r[0]['xchan_photo_m'])
				),
			),
		));

		$private = (($arr['allow_cid'] || $arr['allow_gid'] || $arr['deny_cid'] || $arr['deny_gid']) ? 1 : 0);

		// @FIXME can only update sig if we have the author's channel on this site
		// Until fixed, set it to nothing so it won't give us signature errors

		$sig = '';

		q("UPDATE item SET title = '%s', body = '%s', obj = '%s', allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', edited = '%s', sig = '%s', item_flags = %d, item_private = %d, obj_type = '%s'  WHERE id = %d AND uid = %d",
			dbesc($arr['summary']),
			dbesc($prefix . format_event_bbcode($arr)),
			dbesc($object),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid']),
			dbesc($arr['edited']),
			dbesc($sig),
			intval($r[0]['item_flags']),
			intval($private),
			dbesc(ACTIVITY_OBJ_EVENT),
			intval($r[0]['id']),
			intval($arr['uid'])
		);

		q("delete from term where oid = %d and otype = %d",
			intval($r[0]['id']),
			intval(TERM_OBJ_POST)
		);

		if(($arr['term']) && (is_array($arr['term']))) {
			foreach($arr['term'] as $t) {
				q("insert into term (uid,oid,otype,ttype,term,url)
					values(%d,%d,%d,%d,'%s','%s') ",
					intval($arr['uid']),
					intval($r[0]['id']),
					intval(TERM_OBJ_POST),
					intval($t['ttype']),
					dbesc($t['term']),
					dbesc($t['url'])
				);
			}
		}

		$item_id = $r[0]['id'];
		call_hooks('event_updated', $event['id']);

		return $item_id;
	} else {

		$z = q("select * from channel where channel_id = %d limit 1",
			intval($arr['uid'])
		);

		$private = (($arr['allow_cid'] || $arr['allow_gid'] || $arr['deny_cid'] || $arr['deny_gid']) ? 1 : 0);

		$item_wall = 0;
		$item_origin = 0;
		$item_thread_top = 0;				

		if($item) {
			$item_arr['id'] = $item['id'];
		}
		else {
			$wall = (($z[0]['channel_hash'] == $event['event_xchan']) ? true : false);
			$item_thread_top = 1;
			if($wall) {
				$item_wall = 1;
				$item_origin = 1;
			}
		}

		if(! $arr['mid'])
			$arr['mid'] = item_message_id();

		$item_arr['aid']             = $z[0]['channel_account_id'];
		$item_arr['uid']             = $arr['uid'];
		$item_arr['author_xchan']    = $arr['event_xchan'];
		$item_arr['mid']             = $arr['mid'];
		$item_arr['parent_mid']      = $arr['mid'];
		$item_arr['owner_xchan']     = (($wall) ? $z[0]['channel_hash'] : $arr['event_xchan']);
		$item_arr['author_xchan']    = $arr['event_xchan'];
		$item_arr['title']           = $arr['summary'];
		$item_arr['allow_cid']       = $arr['allow_cid'];
		$item_arr['allow_gid']       = $arr['allow_gid'];
		$item_arr['deny_cid']        = $arr['deny_cid'];
		$item_arr['deny_gid']        = $arr['deny_gid'];
		$item_arr['item_private']    = $private;
		$item_arr['verb']            = ACTIVITY_POST;
		$item_arr['item_wall']       = $item_wall;
		$item_arr['item_origin']     = $item_origin;
		$item_arr['item_thread_top'] = $item_thread_top;

		$attach = array(array(
			'href' => z_root() . '/events/ical/' .  urlencode($event['event_hash']),
			'length' => 0,
			'type' => 'text/calendar',
			'title' => t('event') . '-' . $event['event_hash'],
			'revision' => ''
		));

		$item_arr['attach'] = $attach;


		if(array_key_exists('term', $arr))
			$item_arr['term'] = $arr['term'];

		$item_arr['resource_type']   = 'event';
		$item_arr['resource_id']     = $event['event_hash'];
		$item_arr['obj_type']        = ACTIVITY_OBJ_EVENT;
		$item_arr['body']            = $prefix . format_event_bbcode($arr);

		// if it's local send the permalink to the channel page.
		// otherwise we'll fallback to /display/$message_id

		if($wall)
			$item_arr['plink'] = z_root() . '/channel/' . $z[0]['channel_address'] . '/?f=&mid=' . $item_arr['mid'];
		else
			$item_arr['plink'] = z_root() . '/display/' . $item_arr['mid'];

		$x = q("select * from xchan where xchan_hash = '%s' limit 1",
				dbesc($arr['event_xchan'])
		);
		if($x) {
			$item_arr['obj'] = json_encode(array(
				'type'    => ACTIVITY_OBJ_EVENT,
				'id'      => z_root() . '/event/' . $event['event_hash'],
				'title'   => $arr['summary'],
				'dtstart' => $arr['dtstart'],
				'dtend'  => $arr['dtend'],
				'nofinish'  => $arr['nofinish'],
				'description' => $arr['description'],
				'location'   => $arr['location'],
				'adjust'   => $arr['adjust'],
				'content' => format_event_bbcode($arr),
				'author'  => array(
					'name'     => $x[0]['xchan_name'],
					'address'  => $x[0]['xchan_addr'],
					'guid'     => $x[0]['xchan_guid'],
					'guid_sig' => $x[0]['xchan_guid_sig'],
					'link'     => array(
						array('rel' => 'alternate', 'type' => 'text/html', 'href' => $x[0]['xchan_url']),
						array('rel' => 'photo', 'type' => $x[0]['xchan_photo_mimetype'], 'href' => $x[0]['xchan_photo_m'])),
					),
			));
		}

		$res = item_store($item_arr);

		$item_id = $res['item_id'];

		call_hooks('event_created', $event['id']);

		return $item_id;
	}
}


function todo_stat() {
	return array(
		''             => t('Not specified'),
		'NEEDS-ACTION' => t('Needs Action'),
		'COMPLETED'    => t('Completed'),
		'IN-PROCESS'   => t('In Process'),
		'CANCELLED'    => t('Cancelled')
	);
}


function tasks_fetch($arr) {

   if(! local_channel())
        return;

    $ret = array();
    $sql_extra = " and event_status != 'COMPLETED' ";
    if($arr && $arr['all'] == 1)
        $sql_extra = '';

    $r = q("select * from event where etype = 'task' and uid = %d $sql_extra order by created desc",
        intval(local_channel())
    );

    $ret['success'] = (($r) ? true : false);
    if($r) {
        $ret['tasks'] = $r;
    }

	return $ret;

}
