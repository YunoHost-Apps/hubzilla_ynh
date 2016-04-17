<?php

/**
 * Name: PubSubHubBub
 * Description: Add PuSH capability to channel feeds - based loosely on Friendica PuSH module by Mats Sjöberg
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 * MinVersion: 1.2.2
 */

require_once('include/Contact.php');


function pubsubhubbub_install() {
	$r = q("CREATE TABLE IF NOT EXISTS `push_subscriber` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `callback_url` varchar(255) NOT NULL DEFAULT '',
	  `topic` varchar(255) NOT NULL DEFAULT '',
	  `last_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	  `secret` varchar(255) NOT NULL DEFAULT '',
	  PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	if($r) {
		q("alter table push_subscriber add index ( callback_url ) ");
		q("alter table push_subscriber add index ( topic ) ");
	}

}

function pubsubhubbub_uninstall() {
	$r = q("drop table push_subscriber");
}


function pubsubhubbub_load() {
	register_hook('notifier_process','addon/pubsubhubbub/pubsubhubbub.php','push_notifier_process');
	register_hook('queue_deliver','addon/pubsubhubbub/pubsubhubbub.php','push_queue_deliver');
	register_hook('atom_feed','addon/pubsubhubbub/pubsubhubbub.php','push_atom_feed');
	register_hook('module_loaded', 'addon/pubsubhubbub/pubsubhubbub.php','push_module_loaded');

}

function pubsubhubbub_unload() {
	unregister_hook('notifier_process','addon/pubsubhubbub/pubsubhubbub.php','push_notifier_process');
	unregister_hook('queue_deliver','addon/pubsubhubbub/pubsubhubbub.php','push_queue_deliver');
	unregister_hook('atom_feed','addon/pubsubhubbub/pubsubhubbub.php','push_atom_feed');
	unregister_hook('module_loaded', 'addon/pubsubhubbub/pubsubhubbub.php','push_module_loaded');
}


function push_atom_feed(&$a,&$b) {
	$b = str_replace('</generator>','</generator>' . "\r\n" . '  <link href="' . z_root() . '/pubsubhubbub' . '" rel="hub" />',$b);
}


function push_module_loaded(&$a,&$b) {
	if($b['module'] === 'pubsub') {
		require_once('addon/pubsubhubbub/pubsub.php');
		$b['installed'] = true;
	}
}


function push_notifier_process(&$a,&$b) {

	if(! $b['normal_mode'])
		return;

	if($b['private'] || $b['packet_type'] || $b['mail'])
		return;

	if(! $b['top_level_post'])
		return;

	// find push_subscribers following this $owner

	$channel = $b['channel'];

	$r = q("select * from push_subscriber where topic = '%s'",
		dbesc(z_root() . '/feed/' . $channel['channel_address'])
	);
	if(! $r)
		return;


	foreach($r as $rr) {

		$feed = get_feed_for($channel,'',array('begin' => $rr['last_update']));

		$hmac_sig = hash_hmac("sha1", $feed, $rr['secret']);

		$slap = array('sig' => $hmac_sig, 'topic' => $rr['topic'], 'body' => $feed);

		// Check for public post and create atom wrapper and stick in queue	

		// also need queue driver for 'push' since we need to set some extra headers

		$hash = random_string();
		queue_insert(array(
			'hash'       => $hash,
			'account_id' => $channel['channel_account_id'],
			'channel_id' => $channel['channel_id'],
			'driver'     => 'push',
			'posturl'    => $rr['callback_url'],
			'notify'     => '',
			'msg'        => json_encode($slap)
		));
		$b['queued'][] = $hash;
	}
}

function push_queue_deliver(&$a,&$b) {

	$outq = $b['outq'];
	if($outq['outq_driver'] !== 'push')
		return;

	$b['handled'] = true;

	$m = json_decode($outq['outq_msg'],true);

	if($m) {
		$headers = array("Content-type: application/atom+xml",
			sprintf("Link: <%s>;rel=hub,<%s>;rel=self",z_root() . '/pubsubhubbub',$m['topic']),
			"X-Hub-Signature: sha1=" . $m['sig']);

		$counter = 0;
		$result = z_post_url($outq['outq_posturl'], $m['body'], $counter, array('headers' => $headers, 'novalidate' => true));
		if($result['success'] && $result['return_code'] < 300) {
			logger('push_deliver: queue post success to ' . $outq['outq_posturl'], LOGGER_DEBUG);
			if($b['base']) {
				q("update site set site_update = '%s', site_dead = 0 where site_url = '%s' ",
					dbesc(datetime_convert()),
					dbesc($b['base'])
				);
			}
			q("update dreport set dreport_result = '%s', dreport_time = '%s' where dreport_queue = '%s' limit 1",
				dbesc('accepted for delivery'),
				dbesc(datetime_convert()),
				dbesc($outq['outq_hash'])
			);
			q("update push_subscriber set last_update = '%s' where callback_url = '%s' and topic = '%s'",
				dbesc(datetime_convert()),
				dbesc($outq['outq_posturl']),
				dbesc($m['topic'])
			);

			remove_queue_item($outq['outq_hash']);
		}
		else {
			logger('push_deliver: queue post returned ' . $result['return_code']
				. ' from ' . $outq['outq_posturl'],LOGGER_DEBUG);
				update_queue_item($outq['outq_hash']);
		}
		return;
	}
}


function pubsubhubbub_module() {};


function push_post_var($name) {
	return (x($_REQUEST, $name)) ? notags(trim($_REQUEST[$name])) : '';
}

function pubsubhubbub_init(&$a) {
	// PuSH subscription must be considered "public" so just block it
	// if public access isn't enabled.
	if (get_config('system', 'block_public')) {
		http_status_exit(403);
	}

	// Subscription request from subscriber
	// https://pubsubhubbub.googlecode.com/git/pubsubhubbub-core-0.4.html#anchor4
	// Example from GNU Social:
	// [hub_mode] => subscribe
	// [hub_callback] => http://status.local/main/push/callback/1
	// [hub_verify] => sync
	// [hub_verify_token] => af11...
	// [hub_secret] => af11...
	// [hub_topic] => http://friendica.local/dfrn_poll/sazius

	if($_SERVER['REQUEST_METHOD'] === 'POST') {
		$hub_mode = push_post_var('hub_mode');
		$hub_callback = push_post_var('hub_callback');
		$hub_verify = push_post_var('hub_verify');
		$hub_verify_token = push_post_var('hub_verify_token');
		$hub_secret = push_post_var('hub_secret');
		$hub_topic = push_post_var('hub_topic');

		// check for valid hub_mode
		if ($hub_mode === 'subscribe') {
			$subscribe = 1;
		} else if ($hub_mode === 'unsubscribe') {
			$subscribe = 0;
		} else {
			logger("pubsubhubbub: invalid hub_mode=$hub_mode, ignoring.");
			http_status_exit(404);
		}

		logger("pubsubhubbub: $hub_mode request from " . $_SERVER['REMOTE_ADDR']);

		// get the nick name from the topic, a bit hacky but needed
		$nick = substr(strrchr($hub_topic, "/"), 1);

		if (!$nick) {
			logger('pubsubhubbub: bad hub_topic=$hub_topic, ignoring.');
			http_status_exit(404);
		}

		// fetch user from database given the nickname
		$owner = channelx_by_nick($nick);

		if(! $owner) {
			logger('pubsubhubbub: local account not found: ' . $nick);
			http_status_exit(404);
		}

		if(! perm_is_allowed($owner['channel_id'],'','view_stream')) {
			logger('pubsubhubbub: local channel ' . $nick .
				   'has chosen to hide wall, ignoring.');
			http_status_exit(403);
		}

		// sanity check that topic URLs are the same
		if(! link_compare($hub_topic, z_root() . '/feed/' . $nick)) {
			logger('pubsubhubbub: not a valid hub topic ' . $hub_topic );
			http_status_exit(404);
		}

		// do subscriber verification according to the PuSH protocol
		$hub_challenge = random_string(40);
		$params = 'hub.mode=' .
			($subscribe == 1 ? 'subscribe' : 'unsubscribe') .
			'&hub.topic=' . urlencode($hub_topic) .
			'&hub.challenge=' . $hub_challenge .
			'&hub.lease_seconds=604800' .
			'&hub.verify_token=' . $hub_verify_token;

		// lease time is hard coded to one week (in seconds)
		// we don't actually enforce the lease time because GNU
		// Social/StatusNet doesn't honour it (yet)

		$x = z_fetch_url($hub_callback . "?" . $params);
		if(! $x['success']) {
			logger("pubsubhubbub: subscriber verification at $hub_callback ".
				   "returned $ret, ignoring.");
			http_status_exit(404);
		}

		// check that the correct hub_challenge code was echoed back
		if (trim($x['body']) !== $hub_challenge) {
			logger("pubsubhubbub: subscriber did not echo back ".
				   "hub.challenge, ignoring.");
			logger("\"$hub_challenge\" != \"".trim($x['body'])."\"");
			http_status_exit(404);
		}

		// fetch the old subscription if it exists
		$orig = q("SELECT * FROM `push_subscriber` WHERE `callback_url` = '%s'",
		  dbesc($hub_callback));

		// delete old subscription if it exists
		q("DELETE FROM push_subscriber WHERE callback_url = '%s' and topic = '%s'",
			dbesc($hub_callback),
			dbesc($hub_topic)
		);

		if($subscribe) {
			$last_update = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');

			// if we are just updating an old subscription, keep the
			// old values for last_update

			if ($orig) {
				$last_update = $orig[0]['last_update'];
			}

			// subscribe means adding the row to the table
			q("INSERT INTO push_subscriber ( callback_url, topic, last_update, secret) values ('%s', '%s', '%s', '%s') ",
				dbesc($hub_callback),
				dbesc($hub_topic),
				dbesc($last_update),
				dbesc($hub_secret)
			);
			logger("pubsubhubbub: successfully subscribed [$hub_callback].");
		} 
		else {
			logger("pubsubhubbub: successfully unsubscribed [$hub_callback].");
			// we do nothing here, since the row was already deleted
		}
		http_status_exit(202);
	}

	killme();
}


function pubsubhubbub_subscribe($url,$channel,$xchan,$feed,$hubmode = 'subscribe') {

	$push_url = z_root() . '/pubsub/' . $channel['channel_address'] . '/' . $xchan['abook_id'];

	$verify = get_abconfig($channel['channel_hash'],$xchan['xchan_hash'],'pubsubhubbub','verify_token');
	if(! $verify)
		$verify = set_abconfig($channel['channel_hash'],$xchan['xchan_hash'],'pubsubhubbub','verify_token',random_string(16));
	if($feed)
		set_xconfig($xchan['xchan_hash'],'system','feed_url',$feed);
	else
		$feed = get_xconfig($xchan['xchan_hash'],'system','feed_url');


	$params= 'hub.mode=' . $hubmode . '&hub.callback=' . urlencode($push_url) . '&hub.topic=' . urlencode($feed) . '&hub.verify=async&hub.verify_token=' . $verify;

	logger('subscribe_to_hub: ' . $hubmode . ' ' . $xchan['xchan_name'] . ' to hub ' . $url . ' endpoint: '  . $push_url . ' with verifier ' . $verify);


	$x = z_post_url($url,$params);

	logger('subscribe_to_hub: returns: ' . $x['return_code'], LOGGER_DEBUG);

	return;

}
