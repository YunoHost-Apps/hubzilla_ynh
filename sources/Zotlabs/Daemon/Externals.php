<?php /** @file */

namespace Zotlabs\Daemon;

require_once('include/zot.php');
require_once('include/channel.php');


class Externals {

	static public function run($argc,$argv){

		$total = 0;
		$attempts = 0;

		logger('externals: startup', LOGGER_DEBUG);

		// pull in some public posts


		while($total == 0 && $attempts < 3) {
			$arr = array('url' => '');
			call_hooks('externals_url_select',$arr);

			if($arr['url']) {
				$url = $arr['url'];
			} 
			else {
				$randfunc = db_getfunc('RAND');

				// fixme this query does not deal with directory realms. 

				$r = q("select site_url, site_pull from site where site_url != '%s' and site_flags != %d and site_type = %d and site_dead = 0 order by $randfunc limit 1",
					dbesc(z_root()),
					intval(DIRECTORY_MODE_STANDALONE),
					intval(SITE_TYPE_ZOT)
				);
				if($r)
					$url = $r[0]['site_url'];
			}

			$blacklisted = false;

			if(! check_siteallowed($url)) {
				logger('blacklisted site: ' . $url);
				$blacklisted = true;
			}

			$attempts ++;

			// make sure we can eventually break out if somebody blacklists all known sites

			if($blacklisted) {
				if($attempts > 20)
					break;
				$attempts --;
				continue;
			}

			if($url) {
				if($r[0]['site_pull'] > NULL_DATE)
					$mindate = urlencode(datetime_convert('','',$r[0]['site_pull'] . ' - 1 day'));
				else {
					$days = get_config('externals','since_days');
					if($days === false)
						$days = 15;
					$mindate = urlencode(datetime_convert('','','now - ' . intval($days) . ' days'));
				}

				$feedurl = $url . '/zotfeed?f=&mindate=' . $mindate;

				logger('externals: pulling public content from ' . $feedurl, LOGGER_DEBUG);

				$x = z_fetch_url($feedurl);
				if(($x) && ($x['success'])) {

					q("update site set site_pull = '%s' where site_url = '%s'",
						dbesc(datetime_convert()),
						dbesc($url)
					);

					$j = json_decode($x['body'],true);
					if($j['success'] && $j['messages']) {
						$sys = get_sys_channel();
						foreach($j['messages'] as $message) {
							// on these posts, clear any route info. 
							$message['route'] = '';
							$results = process_delivery(array('hash' => 'undefined'), get_item_elements($message),
								array(array('hash' => $sys['xchan_hash'])), false, true);
							$total ++;
						}
						logger('externals: import_public_posts: ' . $total . ' messages imported', LOGGER_DEBUG);
					}
				}
			}
		}
	}
}
