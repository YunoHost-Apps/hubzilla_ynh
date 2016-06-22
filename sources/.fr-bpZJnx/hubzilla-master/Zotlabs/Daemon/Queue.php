<?php /** @file */

namespace Zotlabs\Daemon;

require_once('include/queue_fn.php');
require_once('include/zot.php');

class Queue {

	static public function run($argc,$argv) {

		require_once('include/items.php');
		require_once('include/bbcode.php');

		if(argc() > 1)
			$queue_id = argv(1);
		else
			$queue_id = 0;

		logger('queue: start');

		// delete all queue items more than 3 days old
		// but first mark these sites dead if we haven't heard from them in a month

		$r = q("select outq_posturl from outq where outq_created < %s - INTERVAL %s",
			db_utcnow(), db_quoteinterval('3 DAY')
		);
		if($r) {
			foreach($r as $rr) {
				$site_url = '';
				$h = parse_url($rr['outq_posturl']);
				$desturl = $h['scheme'] . '://' . $h['host'] . (($h['port']) ? ':' . $h['port'] : '');
				q("update site set site_dead = 1 where site_dead = 0 and site_url = '%s' and site_update < %s - INTERVAL %s",
					dbesc($desturl),
					db_utcnow(), db_quoteinterval('1 MONTH')
				);
			}
		}

		$r = q("DELETE FROM outq WHERE outq_created < %s - INTERVAL %s",
			db_utcnow(), db_quoteinterval('3 DAY')
		);

		if($queue_id) {
			$r = q("SELECT * FROM outq WHERE outq_hash = '%s' LIMIT 1",
				dbesc($queue_id)
			);
		}
		else {

			// For the first 12 hours we'll try to deliver every 15 minutes
			// After that, we'll only attempt delivery once per hour. 
			// This currently only handles the default queue drivers ('zot' or '') which we will group by posturl 
			// so that we don't start off a thousand deliveries for a couple of dead hubs.
			// The zot driver will deliver everything destined for a single hub once contact is made (*if* contact is made).
			// Other drivers will have to do something different here and may need their own query.
	
			// Note: this requires some tweaking as new posts to long dead hubs once a day will keep them in the 
			// "every 15 minutes" category. We probably need to prioritise them when inserted into the queue
			// or just prior to this query based on recent and long-term delivery history. If we have good reason to believe
			// the site is permanently down, there's no reason to attempt delivery at all, or at most not more than once 
			// or twice a day. 
	
			// FIXME: can we sort postgres on outq_priority and maintain the 'distinct' ?
			// The order by max(outq_priority) might be a dodgy query because of the group by.
			// The desired result is to return a sequence in the order most likely to be delivered in this run.
			// If a hub has already been sitting in the queue for a few days, they should be delivered last;
			// hence every failure should drop them further down the priority list.
 
			if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
				$prefix = 'DISTINCT ON (outq_posturl)';
				$suffix = 'ORDER BY outq_posturl';
			} else {
				$prefix = '';
				$suffix = 'GROUP BY outq_posturl ORDER BY max(outq_priority)';
			}
			$r = q("SELECT $prefix * FROM outq WHERE outq_delivered = 0 and (( outq_created > %s - INTERVAL %s and outq_updated < %s - INTERVAL %s ) OR ( outq_updated < %s - INTERVAL %s )) $suffix",
				db_utcnow(), db_quoteinterval('12 HOUR'),
				db_utcnow(), db_quoteinterval('15 MINUTE'),
				db_utcnow(), db_quoteinterval('1 HOUR')
			);
		}
		if(! $r)
			return;

		foreach($r as $rr) {
			queue_deliver($rr);
		}
	}
}
