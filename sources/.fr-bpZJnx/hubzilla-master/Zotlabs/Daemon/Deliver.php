<?php /** @file */

namespace Zotlabs\Daemon;

require_once('include/zot.php');
require_once('include/queue_fn.php');


class Deliver {
	
	static public function run($argc,$argv) {

		if($argc < 2)
			return;

		logger('deliver: invoked: ' . print_r($argv,true), LOGGER_DATA);

		for($x = 1; $x < $argc; $x ++) {

			if(! $argv[$x])
				continue;

			$dresult = null;
			$r = q("select * from outq where outq_hash = '%s' limit 1",
				dbesc($argv[$x])
			);
			if($r) {

				$notify = json_decode($r[0]['outq_notify'],true);

				// Messages without an outq_msg will need to go via the web, even if it's a
				// local delivery. This includes conversation requests and refresh packets.

				if(($r[0]['outq_posturl'] === z_root() . '/post') && ($r[0]['outq_msg'])) {
					logger('deliver: local delivery', LOGGER_DEBUG);

					// local delivery
					// we should probably batch these and save a few delivery processes

					if($r[0]['outq_msg']) {
						$m = json_decode($r[0]['outq_msg'],true);
						if(array_key_exists('message_list',$m)) {
							foreach($m['message_list'] as $mm) {
								$msg = array('body' => json_encode(array('success' => true, 'pickup' => array(array('notify' => $notify,'message' => $mm)))));
								zot_import($msg,z_root());
							}
						}	
						else {	
							$msg = array('body' => json_encode(array('success' => true, 'pickup' => array(array('notify' => $notify,'message' => $m)))));
							$dresult = zot_import($msg,z_root());
						}

						remove_queue_item($r[0]['outq_hash']);

						if($dresult && is_array($dresult)) {
							foreach($dresult as $xx) {
								if(is_array($xx) && array_key_exists('message_id',$xx)) {
									if(delivery_report_is_storable($xx)) {
										q("insert into dreport ( dreport_mid, dreport_site, dreport_recip, dreport_result, dreport_time, dreport_xchan ) values ( '%s', '%s','%s','%s','%s','%s' ) ",
											dbesc($xx['message_id']),
											dbesc($xx['location']),
											dbesc($xx['recipient']),
											dbesc($xx['status']),
											dbesc(datetime_convert($xx['date'])),
											dbesc($xx['sender'])
										);
									}
								}
							}
						}

						q("delete from dreport where dreport_queue = '%s'",
							dbesc($argv[$x])
						);
					}
				}

				// otherwise it's a remote delivery - call queue_deliver() with the $immediate flag

				queue_deliver($r[0],true);

			}
		}
	}
}
