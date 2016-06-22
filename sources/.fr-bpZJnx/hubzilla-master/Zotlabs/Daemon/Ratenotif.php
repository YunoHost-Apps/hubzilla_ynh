<?php

namespace Zotlabs\Daemon;

require_once('include/zot.php');
require_once('include/queue_fn.php');


class Ratenotif {

	static public function run($argc,$argv) {

		require_once("datetime.php");
		require_once('include/items.php');

		if($argc < 3)
			return;


		logger('ratenotif: invoked: ' . print_r($argv,true), LOGGER_DEBUG);

		$cmd = $argv[1];

		$item_id = $argv[2];


		if($cmd === 'rating') {
			$r = q("select * from xlink where xlink_id = %d and xlink_static = 1 limit 1",
				intval($item_id)
			);
			if(! $r) {
				logger('rating not found');
				return;
			}

			$encoded_item = array(
				'type' => 'rating', 
				'encoding' => 'zot',
				'target' => $r[0]['xlink_link'],
				'rating' => intval($r[0]['xlink_rating']),
				'rating_text' => $r[0]['xlink_rating_text'],
				'signature' => $r[0]['xlink_sig'],
				'edited' => $r[0]['xlink_updated']
			);
		}

		$channel = channelx_by_hash($r[0]['xlink_xchan']);
		if(! $channel) {
			logger('no channel');
			return;
		}


		$primary = get_directory_primary();
	
		if(! $primary)
			return;


		$interval = ((get_config('system','delivery_interval') !== false) 
			? intval(get_config('system','delivery_interval')) : 2 );

		$deliveries_per_process = intval(get_config('system','delivery_batch_count'));

		if($deliveries_per_process <= 0)
			$deliveries_per_process = 1;

		$deliver = array();

		$x = z_fetch_url($primary . '/regdir');
		if($x['success']) {
			$j = json_decode($x['body'],true);
			if($j && $j['success'] && is_array($j['directories'])) {

				foreach($j['directories'] as $h) {
					if($h == z_root())
						continue;

					$hash = random_string();
					$n = zot_build_packet($channel,'notify',null,null,$hash);

					queue_insert(array(
						'hash'       => $hash,
						'account_id' => $channel['channel_account_id'],
						'channel_id' => $channel['channel_id'],
						'posturl'    => $h . '/post',
						'notify'     => $n,
						'msg'        => json_encode($encoded_item)
					));

					$deliver[] = $hash;
	
					if(count($deliver) >= $deliveries_per_process) {
						Master::Summon(array('Deliver',$deliver));
						$deliver = array();
						if($interval)
							@time_sleep_until(microtime(true) + (float) $interval);
					}
				}
	
				// catch any stragglers
	
				if(count($deliver)) {
					Master::Summon(array('Deliver',$deliver));
				}
			}
		}
		
		logger('ratenotif: complete.');
		return;

	}
}
