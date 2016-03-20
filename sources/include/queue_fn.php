<?php /** @file */

function update_queue_item($id, $add_priority = 0) {
	logger('queue: requeue item ' . $id,LOGGER_DEBUG);
	q("UPDATE outq SET outq_updated = '%s', outq_priority = outq_priority + %d WHERE outq_hash = '%s'",
		dbesc(datetime_convert()),
		intval($add_priority),
		dbesc($id)
	);
}

function remove_queue_item($id,$channel_id = 0) {
	logger('queue: remove queue item ' . $id,LOGGER_DEBUG);
	$sql_extra = (($channel_id) ? " and outq_channel = " . intval($channel_id) . " " : '');
		
	q("DELETE FROM outq WHERE outq_hash = '%s' $sql_extra",
		dbesc($id)
	);
}


function remove_queue_by_posturl($posturl) {
	logger('queue: remove queue posturl ' . $posturl,LOGGER_DEBUG);
		
	q("DELETE FROM outq WHERE outq_posturl = '%s' ",
		dbesc($posturl)
	);
}



function queue_set_delivered($id,$channel = 0) {
	logger('queue: set delivered ' . $id,LOGGER_DEBUG);
	$sql_extra = (($channel_id) ? " and outq_channel = " . intval($channel_id) . " " : '');

	q("update outq set outq_delivered = 1, outq_updated = '%s' where outq_hash = '%s' $sql_extra ",
		dbesc(datetime_convert()),
		dbesc($id)
	);
}



function queue_insert($arr) {

	$x = q("insert into outq ( outq_hash, outq_account, outq_channel, outq_driver, outq_posturl, outq_async, outq_priority,
		outq_created, outq_updated, outq_notify, outq_msg ) 
		values ( '%s', %d, %d, '%s', '%s', %d, %d, '%s', '%s', '%s', '%s' )",
		dbesc($arr['hash']),
		intval($arr['account_id']),
		intval($arr['channel_id']),
		dbesc(($arr['driver']) ? $arr['driver'] : 'zot'),
		dbesc($arr['posturl']),
		intval(1),
		intval(($arr['priority']) ? $arr['priority'] : 0),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc($arr['notify']),
		dbesc(($arr['msg']) ? $arr['msg'] : '')
	);
	return $x;

}



function queue_deliver($outq, $immediate = false) {

	$base = null;
	$h = parse_url($outq['outq_posturl']);
	if($h) 
		$base = $h['scheme'] . '://' . $h['host'] . (($h['port']) ? ':' . $h['port'] : '');

	if(($base) && ($base !== z_root()) && ($immediate)) {
		$y = q("select site_update, site_dead from site where site_url = '%s' ",
			dbesc($base)
		);
		if($y) {
			if(intval($y[0]['site_dead'])) {
				remove_queue_by_posturl($outq['outq_posturl']);
				logger('dead site ignored ' . $base);
				return;
			}
			if($y[0]['site_update'] < datetime_convert('UTC','UTC','now - 1 month')) {
				update_queue_item($outq['outq_hash'],10);
				logger('immediate delivery deferred for site ' . $base);
				return;
			}
		}
		else {

			// zot sites should all have a site record, unless they've been dead for as long as
			// your site has existed. Since we don't know for sure what these sites are,
			// call them unknown

			q("insert into site (site_url, site_update, site_dead, site_type) values ('%s','%s',0,%d) ",
				dbesc($base),
				dbesc(datetime_convert()),
				intval(($outq['outq_driver'] === 'post') ? SITE_TYPE_NOTZOT : SITE_TYPE_UNKNOWN)
			);
		}
	}

	$arr = array('outq' => $outq, 'base' => $base, 'handled' => false, 'immediate' => $immediate);
	call_hooks('queue_deliver',$arr);
	if($arr['handled'])
		return;

	// "post" queue driver - used for diaspora and friendica-over-diaspora communications.

	if($outq['outq_driver'] === 'post') {
		$result = z_post_url($outq['outq_posturl'],$outq['outq_msg']);
		if($result['success'] && $result['return_code'] < 300) {
			logger('deliver: queue post success to ' . $outq['outq_posturl'], LOGGER_DEBUG);
			if($base) {
				q("update site set site_update = '%s', site_dead = 0 where site_url = '%s' ",
					dbesc(datetime_convert()),
					dbesc($base)
				);
			}
			q("update dreport set dreport_result = '%s', dreport_time = '%s' where dreport_queue = '%s' limit 1",
				dbesc('accepted for delivery'),
				dbesc(datetime_convert()),
				dbesc($outq['outq_hash'])
			);
			remove_queue_item($outq['outq_hash']);

			// server is responding - see if anything else is going to this destination and is piled up 
			// and try to send some more. We're relying on the fact that do_delivery() results in an 
			// immediate delivery otherwise we could get into a queue loop. 

			if(! $immediate) {
				$x = q("select outq_hash from outq where outq_posturl = '%s' and outq_delivered = 0",
					dbesc($outq['outq_posturl'])
				);

				$piled_up = array();
				if($x) {
					foreach($x as $xx) {
						 $piled_up[] = $xx['outq_hash'];
					}
				}
				if($piled_up) {
					do_delivery($piled_up);
				}
			}
		}
		else {
			logger('deliver: queue post returned ' . $result['return_code'] 
				. ' from ' . $outq['outq_posturl'],LOGGER_DEBUG);
				update_queue_item($outq['outq_posturl']);
		}
		return;
	}

	// normal zot delivery

	logger('deliver: dest: ' . $outq['outq_posturl'], LOGGER_DEBUG);
	$result = zot_zot($outq['outq_posturl'],$outq['outq_notify']);
	if($result['success']) {
		logger('deliver: remote zot delivery succeeded to ' . $outq['outq_posturl']);
		zot_process_response($outq['outq_posturl'],$result, $outq);
	}
	else {
		logger('deliver: remote zot delivery failed to ' . $outq['outq_posturl']);
		logger('deliver: remote zot delivery fail data: ' . print_r($result,true), LOGGER_DATA);
		update_queue_item($outq['outq_hash'],10);
	}
	return;
}

