<?php

/**
 * Name: Statistics
 * Description: Generates some statistics for the-federation.info (formerly http://pods.jasonrobinson.me/)
 * Version: 0.1
 * Author: Michael Vogel <https://pirati.ca/profile/heluecht>
 */

function statistics_json_load() {
	register_hook('cron_daily', 'addon/statistics_json/statistics_json.php', 'statistics_json_cron');
}


function statistics_json_unload() {
	unregister_hook('cron_daily', 'addon/statistics_json/statistics_json.php', 'statistics_json_cron');
}

function statistics_json_module() {}

function statistics_json_init() {
	global $a;

	if(! get_config('statistics_json','total_users'))
		statistics_json_cron($a,$b);

	$statistics = array(
		"name" => get_config('system','sitename'),
		"network" => PLATFORM_NAME,
		"version" => RED_VERSION,
		"registrations_open" => (get_config('system','register_policy') != 0),
		"total_users" => get_config('statistics_json','total_users'),
		"active_users_halfyear" => get_config('statistics_json','active_users_halfyear'),
		"active_users_monthly" => get_config('statistics_json','active_users_monthly'),
		"local_posts" => get_config('statistics_json','local_posts'),
		"twitter" => (bool) get_config('statistics_json','twitter'),
		"wordpress" => (bool) get_config('statistics_json','wordpress')
	);

	header("Content-Type: application/json");
	echo json_encode($statistics);
	logger("statistics_init: printed ".print_r($statistics, true));
	killme();
}

function statistics_json_cron($a,$b) {

	logger('statistics_json_cron: cron_start');


	$r = q("select count(channel_id) as total_users from channel left join account on account_id = channel_account_id
		where account_flags = 0 ");
	if($r)
		$total_users = $r[0]['total_users'];

	$r = q("select channel_id from channel left join account on account_id = channel_account_id
		where account_flags = 0 and account_lastlog > %s - INTERVAL %s",
		db_utcnow(), db_quoteinterval('6 MONTH')
	);
	if($r) {
		$s = '';
		foreach($r as $rr) {
			if($s)
				$s .= ',';
			$s .= intval($rr['channel_id']);
		}
		$x = q("select uid from item where uid in ( $s ) and item_wall !=  0 and created > %s - INTERVAL %s group by uid",
			db_utcnow(), db_quoteinterval('6 MONTH')
		);
		if($x)
			$active_users_halfyear = count($x);
	}

	$r = q("select channel_id from channel left join account on account_id = channel_account_id
		where account_flags = 0 and account_lastlog > %s - INTERVAL %s",
		db_utcnow(), db_quoteinterval('1 MONTH')
	);
	if($r) {
		$s = '';
		foreach($r as $rr) {
			if($s)
				$s .= ',';
			$s .= intval($rr['channel_id']);
		}
		$x = q("select uid from item where uid in ( $s ) and item_wall != 0 and created > %s - INTERVAL %s group by uid",
			db_utcnow(), db_quoteinterval('1 MONTH')
		);
		if($x)
			$active_users_monthly = count($x);
	}


	set_config('statistics_json','total_users', $total_users);

	set_config('statistics_json','active_users_halfyear', $active_users_halfyear);
	set_config('statistics_json','active_users_monthly', $active_users_monthly);


	$posts = q("SELECT COUNT(*) AS local_posts FROM `item` WHERE item_wall != 0 ");
	if (!is_array($posts))
		$local_posts = -1;
	else
		$local_posts = $posts[0]["local_posts"];

	set_config('statistics_json','local_posts', $local_posts);


	$wordpress = false;
	$r = q("select * from addon where hidden = 0 and name = 'wppost'");
		if($r)
		$wordpress = true;

	set_config('statistics_json','wordpress', intval($wordpress));

	$twitter = false;
	$r = q("select * from addon where hidden = 0 and name = 'twitter'");
	if($r)
		$twitter = true;

	set_config('statistics_json','twitter', intval($twitter));

	// Now trying to register
	$url = "http://the-federation.info/register/" . $a->get_hostname();

	$ret = z_fetch_url($url);
	logger('statistics_json_cron: registering answer: '. print_r($ret,true), LOGGER_DEBUG);
	logger('statistics_json_cron: cron_end');

}
