<?php
namespace Zotlabs\Module;

require_once('include/conversation.php');
require_once('include/bbcode.php');
require_once('include/datetime.php');
require_once('include/event.php');
require_once('include/items.php');


class Cal extends \Zotlabs\Web\Controller {

	function init() {
		if(observer_prohibited()) {
			return;
		}
	
		$o = '';
	
		if(argc() > 1) {
			$nick = argv(1);
	
			profile_load($nick);
	
			$channelx = channelx_by_nick($nick);
	
			if(! $channelx)
				return;
	
			\App::$data['channel'] = $channelx;
	
			$observer = \App::get_observer();
			\App::$data['observer'] = $observer;
	
			$observer_xchan = (($observer) ? $observer['xchan_hash'] : '');
	
			head_set_icon(\App::$data['channel']['xchan_photo_s']);
	
			\App::$page['htmlhead'] .= "<script> var profile_uid = " . ((\App::$data['channel']) ? \App::$data['channel']['channel_id'] : 0) . "; </script>" ;
	
		}
	
		return;
	}
	
	
	
	function get() {
	
		if(observer_prohibited()) {
			return;
		}
		
		$channel = null;
	
		if(argc() > 1) {
			$channel = channelx_by_nick(argv(1));
		}
	
	
		if(! $channel) {
			notice( t('Channel not found.') . EOL);
			return;
		}
	
		// since we don't currently have an event permission - use the stream permission
	
		if(! perm_is_allowed($channel['channel_id'], get_observer_hash(), 'view_stream')) {
			notice( t('Permissions denied.') . EOL);
			return;
		}
	
		$sql_extra = permissions_sql($channel['channel_id'],get_observer_hash(),'event');
	
		$first_day = get_pconfig(local_channel(),'system','cal_first_day');
		$first_day = (($first_day) ? $first_day : 0);
	
		$htpl = get_markup_template('event_head.tpl');
		\App::$page['htmlhead'] .= replace_macros($htpl,array(
			'$baseurl' => z_root(),
			'$module_url' => '/cal/' . $channel['channel_address'],
			'$modparams' => 2,
			'$lang' => \App::$language,
			'$first_day' => $first_day
		));
	
		$o = '';
	
		$tabs = profile_tabs($a, True, $channel['channel_address']);
	
		$mode = 'view';
		$y = 0;
		$m = 0;
		$ignored = ((x($_REQUEST,'ignored')) ? " and dismissed = " . intval($_REQUEST['ignored']) . " "  : '');
	
		// logger('args: ' . print_r(\App::$argv,true));
	
		if(argc() > 3 && intval(argv(2)) && intval(argv(3))) {
			$mode = 'view';
			$y = intval(argv(2));
			$m = intval(argv(3));
		}
		if(argc() <= 3) {
			$mode = 'view';
			$event_id = argv(2);
		}
	
		if($mode == 'view') {
	
			/* edit/create form */
			if($event_id) {
				$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
					dbesc($event_id),
					intval($channel['channel_id'])
				);
				if(count($r))
					$orig_event = $r[0];
			}
	
	
			// Passed parameters overrides anything found in the DB
			if(!x($orig_event))
				$orig_event = array();
	
	
	
			$tz = date_default_timezone_get();
			if(x($orig_event))
				$tz = (($orig_event['adjust']) ? date_default_timezone_get() : 'UTC');
	
			$syear = datetime_convert('UTC', $tz, $sdt, 'Y');
			$smonth = datetime_convert('UTC', $tz, $sdt, 'm');
			$sday = datetime_convert('UTC', $tz, $sdt, 'd');
			$shour = datetime_convert('UTC', $tz, $sdt, 'H');
			$sminute = datetime_convert('UTC', $tz, $sdt, 'i');
	
			$stext = datetime_convert('UTC',$tz,$sdt);
			$stext = substr($stext,0,14) . "00:00";
	
			$fyear = datetime_convert('UTC', $tz, $fdt, 'Y');
			$fmonth = datetime_convert('UTC', $tz, $fdt, 'm');
			$fday = datetime_convert('UTC', $tz, $fdt, 'd');
			$fhour = datetime_convert('UTC', $tz, $fdt, 'H');
			$fminute = datetime_convert('UTC', $tz, $fdt, 'i');
	
			$ftext = datetime_convert('UTC',$tz,$fdt);
			$ftext = substr($ftext,0,14) . "00:00";
	
			$type = ((x($orig_event)) ? $orig_event['etype'] : 'event');
	
			$f = get_config('system','event_input_format');
			if(! $f)
				$f = 'ymd';
	
			$catsenabled = feature_enabled($channel['channel_id'],'categories');
	
	
			$show_bd = perm_is_allowed($channel['channel_id'], get_observer_hash(), 'view_contacts');
			if(! $show_bd) {
				$sql_extra .= " and event.etype != 'birthday' ";
			}
	
	
			$category = '';
	
			$thisyear = datetime_convert('UTC',date_default_timezone_get(),'now','Y');
			$thismonth = datetime_convert('UTC',date_default_timezone_get(),'now','m');
			if(! $y)
				$y = intval($thisyear);
			if(! $m)
				$m = intval($thismonth);
	
			// Put some limits on dates. The PHP date functions don't seem to do so well before 1900.
			// An upper limit was chosen to keep search engines from exploring links millions of years in the future. 
	
			if($y < 1901)
				$y = 1900;
			if($y > 2099)
				$y = 2100;
	
			$nextyear = $y;
			$nextmonth = $m + 1;
			if($nextmonth > 12) {
					$nextmonth = 1;
				$nextyear ++;
			}
	
			$prevyear = $y;
			if($m > 1)
				$prevmonth = $m - 1;
			else {
				$prevmonth = 12;
				$prevyear --;
			}
				
			$dim    = get_dim($y,$m);
			$start  = sprintf('%d-%d-%d %d:%d:%d',$y,$m,1,0,0,0);
			$finish = sprintf('%d-%d-%d %d:%d:%d',$y,$m,$dim,23,59,59);
	
	
			if (argv(2) === 'json'){
				if (x($_GET,'start'))	$start = $_GET['start'];
				if (x($_GET,'end'))	$finish = $_GET['end'];
			}
	
			$start  = datetime_convert('UTC','UTC',$start);
			$finish = datetime_convert('UTC','UTC',$finish);
	
			$adjust_start = datetime_convert('UTC', date_default_timezone_get(), $start);
			$adjust_finish = datetime_convert('UTC', date_default_timezone_get(), $finish);
	

			if(! perm_is_allowed(\App::$profile['uid'],get_observer_hash(),'view_contacts'))
				$sql_extra .= " and etype != 'birthday' ";

			if (x($_GET,'id')){
			  	$r = q("SELECT event.*, item.plink, item.item_flags, item.author_xchan, item.owner_xchan
	                                from event left join item on resource_id = event_hash where resource_type = 'event' and event.uid = %d and event.id = %d $sql_extra limit 1",
					intval($channel['channel_id']),
					intval($_GET['id'])
				);
			} 
			else {
				// fixed an issue with "nofinish" events not showing up in the calendar.
				// There's still an issue if the finish date crosses the end of month.
				// Noting this for now - it will need to be fixed here and in Friendica.
				// Ultimately the finish date shouldn't be involved in the query. 
	
				$r = q("SELECT event.*, item.plink, item.item_flags, item.author_xchan, item.owner_xchan
	                              from event left join item on event_hash = resource_id 
					where resource_type = 'event' and event.uid = %d and event.uid = item.uid $ignored 
					AND (( adjust = 0 AND ( dtend >= '%s' or nofinish = 1 ) AND dtstart <= '%s' ) 
					OR  (  adjust = 1 AND ( dtend >= '%s' or nofinish = 1 ) AND dtstart <= '%s' )) $sql_extra ",
					intval($channel['channel_id']),
					dbesc($start),
					dbesc($finish),
					dbesc($adjust_start),
					dbesc($adjust_finish)
				);
	
			}
	
			$links = array();
	
			if($r) {
				xchan_query($r);
				$r = fetch_post_tags($r,true);
	
				$r = sort_by_date($r);
			}
	
			if($r) {
				foreach($r as $rr) {
					$j = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['dtstart'], 'j') : datetime_convert('UTC','UTC',$rr['dtstart'],'j'));
					if(! x($links,$j)) 
						$links[$j] = z_root() . '/' . \App::$cmd . '#link-' . $j;
				}
			}
	
			$events=array();
	
			$last_date = '';
			$fmt = t('l, F j');
	
			if($r) {
	
				foreach($r as $rr) {
					
					$j = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['dtstart'], 'j') : datetime_convert('UTC','UTC',$rr['dtstart'],'j'));
					$d = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['dtstart'], $fmt) : datetime_convert('UTC','UTC',$rr['dtstart'],$fmt));
					$d = day_translate($d);
					
					$start = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['dtstart'], 'c') : datetime_convert('UTC','UTC',$rr['dtstart'],'c'));
					if ($rr['nofinish']){
						$end = null;
					} else {
						$end = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['dtend'], 'c') : datetime_convert('UTC','UTC',$rr['dtend'],'c'));
					}
					
					
					$is_first = ($d !== $last_date);
						
					$last_date = $d;
	
					$edit = false;
	
					$drop = false;
	
					$title = strip_tags(html_entity_decode(bbcode($rr['summary']),ENT_QUOTES,'UTF-8'));
					if(! $title) {
						list($title, $_trash) = explode("<br",bbcode($rr['desc']),2);
						$title = strip_tags(html_entity_decode($title,ENT_QUOTES,'UTF-8'));
					}
					$html = format_event_html($rr);
					$rr['desc'] = bbcode($rr['desc']);
					$rr['location'] = bbcode($rr['location']);
					$events[] = array(
						'id'=>$rr['id'],
						'hash' => $rr['event_hash'],
						'start'=> $start,
						'end' => $end,
						'drop' => $drop,
						'allDay' => false,
						'title' => $title,
						
						'j' => $j,
						'd' => $d,
						'edit' => $edit,
						'is_first'=>$is_first,
						'item'=>$rr,
						'html'=>$html,
						'plink' => array($rr['plink'],t('Link to Source'),'',''),
					);
	
	
				}
			}
			
			if (argv(2) === 'json'){
				echo json_encode($events); killme();
			}
			
			// links: array('href', 'text', 'extra css classes', 'title')
			if (x($_GET,'id')){
				$tpl =  get_markup_template("event_cal.tpl");
			} 
			else {
				$tpl = get_markup_template("events_cal-js.tpl");
			}
	
			$nick = $channel['channel_address'];
	
			$o = replace_macros($tpl, array(
				'$baseurl'	=> z_root(),
				'$new_event'	=> array(z_root().'/cal',(($event_id) ? t('Edit Event') : t('Create Event')),'',''),
				'$previus'	=> array(z_root()."/cal/$nick/$prevyear/$prevmonth",t('Previous'),'',''),
				'$next'		=> array(z_root()."/cal/$nick/$nextyear/$nextmonth",t('Next'),'',''),
				'$export'	=> array(z_root()."/cal/$nick/$y/$m/export",t('Export'),'',''),
				'$calendar'	=> cal($y,$m,$links, ' eventcal'),
				'$events'	=> $events,
				'$upload'	=> t('Import'),
				'$submit'	=> t('Submit'),
				'$prev'		=> t('Previous'),
				'$next'		=> t('Next'),
				'$today'	=> t('Today'),
				'$form'		=> $form,
				'$expandform'	=> ((x($_GET,'expandform')) ? true : false),
				'$tabs'		=> $tabs
			));
			
			if (x($_GET,'id')){ echo $o; killme(); }
			
			return $o;
		}
	
	}
	
}
