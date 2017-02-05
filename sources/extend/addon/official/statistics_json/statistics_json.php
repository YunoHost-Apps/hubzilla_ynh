<?php

/**
 * Name: Diaspora Statistics
 * Description: Generates some statistics for the-federation.info (formerly http://pods.jasonrobinson.me/)
 * Version: 0.1
 * Author: Michael Vogel <https://pirati.ca/profile/heluecht>
 * Maintainer: none
 * ServerRoles: basic, standard
 */

function statistics_json_load() {
	register_hook('cron_daily', 'addon/statistics_json/statistics_json.php', 'statistics_json_cron');
	register_hook('well_known', 'addon/statistics_json/statistics_json.php', 'statistics_json_well_known');
	register_hook('module_loaded', 'addon/statistics_json/statistics_json.php', 'statistics_json_load_module');
}


function statistics_json_unload() {
	unregister_hook('cron_daily', 'addon/statistics_json/statistics_json.php', 'statistics_json_cron');
	unregister_hook('well_known', 'addon/statistics_json/statistics_json.php', 'statistics_json_well_known');
	unregister_hook('module_loaded', 'addon/statistics_json/statistics_json.php', 'statistics_json_load_module');
}


function statistics_json_well_known() {
	if(argc() > 1 && argv(1) === 'nodeinfo') {
		$arr = array( 'links' => array(
			'rel' => 'http://nodeinfo.diaspora.software/ns/schema/1.0',
			'href' => z_root() . '/nodeinfo/1.0'
		));

		header('Content-type: application/json');
		echo json_encode($arr);
		killme();
	}
}


function statistics_json_load_module(&$a, &$b) {
	if($b['module'] === 'nodeinfo') {
		require_once('addon/statistics_json/nodeinfo.php');
		$b['installed'] = true;
	}
}


function statistics_json_module() {}

function statistics_json_init() {
	global $a;

	if(! get_config('statistics_json','total_users'))
		statistics_json_cron($a,$b);

	$statistics = array(
		"name" => get_config('system','sitename'),
		"network" => Zotlabs\Lib\System::get_platform_name(),
		"version" => Zotlabs\Lib\System::get_project_version(),
		"registrations_open" => (get_config('system','register_policy') != 0),
		"total_users" => get_config('statistics_json','total_users'),
		"active_users_halfyear" => get_config('statistics_json','active_users_halfyear'),
		"active_users_monthly" => get_config('statistics_json','active_users_monthly'),
		"local_posts" => get_config('statistics_json','local_posts'),
		"local_comments" => get_config('statistics_json','local_comments'),
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


	$posts = q("SELECT COUNT(*) AS local_posts FROM item WHERE item_wall != 0 ");
	if (!is_array($posts))
		$local_posts = -1;
	else
		$local_posts = $posts[0]["local_posts"];

	set_config('statistics_json','local_posts', $local_posts);


	$posts = q("SELECT COUNT(*) AS local_posts FROM item WHERE item_wall != 0 and id != parent");
	if (!is_array($posts))
		$local_posts = -1;
	else
		$local_posts = $posts[0]["local_posts"];

	set_config('statistics_json','local_comments', $local_posts);


	$wordpress = false;
	$r = q("select * from addon where hidden = 0 and aname = 'wppost'");
		if($r)
		$wordpress = true;

	set_config('statistics_json','wordpress', intval($wordpress));

	$twitter = false;
	$r = q("select * from addon where hidden = 0 and aname = 'twitter'");
	if($r)
		$twitter = true;

	set_config('statistics_json','twitter', intval($twitter));

	// Now trying to register
	$url = "https://the-federation.info/register/" . App::get_hostname();

	$ret = z_fetch_url($url);
	logger('statistics_json_cron: registering answer: '. print_r($ret,true), LOGGER_DEBUG);
	logger('statistics_json_cron: cron_end');

}
