<?php


require_once('include/Contact.php');

function pubsub_init(&$a) {

	$nick = ((argc() > 1) ? escape_tags(trim(argv(1))) : '');
	$contact_id = ((argc() > 2) ? intval(argv(2)) : 0 );

	if($_SERVER['REQUEST_METHOD'] === 'GET') {

		$hub_mode      = ((x($_GET,'hub_mode'))          ? notags(trim($_GET['hub_mode']))          : '');
		$hub_topic     = ((x($_GET,'hub_topic'))         ? notags(trim($_GET['hub_topic']))         : '');
		$hub_challenge = ((x($_GET,'hub_challenge'))     ? notags(trim($_GET['hub_challenge']))     : '');
		$hub_lease     = ((x($_GET,'hub_lease_seconds')) ? notags(trim($_GET['hub_lease_seconds'])) : '');
		$hub_verify    = ((x($_GET,'hub_verify_token'))  ? notags(trim($_GET['hub_verify_token']))  : '');

		logger('pubsub: Subscription from ' . $_SERVER['REMOTE_ADDR']);
		logger('pubsub: data: ' . print_r($_GET,true), LOGGER_DATA);

		$subscribe = (($hub_mode === 'subscribe') ? 1 : 0);

		$channel = channelx_by_nick($nick);
		if(! $channel)
			http_status_exit(404,'not found.');

		$connections = abook_connections($channel['channel_id'], ' and abook_id = ' . $contact_id);
		if($connections)
			$xchan = $connections[0];
		else {
			logger('connection ' . $contact_id . ' not found.');
			http_status_exit(404,'not found.');
		}

		if($hub_verify) {
			$verify = get_abconfig($channel['channel_hash'],$xchan['xchan_hash'],'pubsubhubbub','verify_token');
			if($verify != $hub_verify) {
				logger('hub verification failed.');
				http_status_exit(404,'not found.');
			}
		} 

		$feed_url = z_root() . '/feed/' . $channel['channel_address'];

		if($hub_topic) {
			if(! link_compare($hub_topic,$feed_url)) {
				logger('hub topic ' . $hub_topic . ' != ' . $feed_url);
				// should abort but let's humour them.
			}
		}

		$contact = $r[0];

		// We must initiate an unsubscribe request with a verify_token. 
		// Don't allow outsiders to unsubscribe us.

		if($hub_mode === 'unsubscribe') {
			if(! strlen($hub_verify)) {
				logger('pubsub: bogus unsubscribe');
				http_status_exit(403,'permission denied.');
			}
			logger('pubsub: unsubscribe success');
		}

		if($hub_mode) {
			set_abconfig($channel['channel_hash'],$xchan['xchan_hash'],'pubsubhubbub','subscribed',intval($subscribe));
		}

		header($_SERVER["SERVER_PROTOCOL"] . ' 200 ' . 'OK');
		echo $hub_challenge;
		killme();
	}
}

function pubsub_post(&$a) {

	$xml = file_get_contents('php://input');

	logger('pubsub: feed arrived from ' . $_SERVER['REMOTE_ADDR'] . ' for ' .  $a->cmd );
	logger('pubsub: user-agent: ' . $_SERVER['HTTP_USER_AGENT'] );
	logger('pubsub: data: ' . $xml, LOGGER_DATA);


	$nick = ((argc() > 1) ? escape_tags(trim(argv(1))) : '');
	$contact_id = ((argc() > 2) ? intval(argv(2)) : 0 );

	$channel = channelx_by_nick($nick);
	if(! $channel)
		http_status_exit(200,'OK');

	$connections = abook_connections($channel['channel_id'], ' and abook_id = ' . $contact_id);
	if($connections)
		$xchan = $connections[0];
	else {
		logger('connection ' . $contact_id . ' not found.');
		http_status_exit(200,'OK');
	}

	if(! perm_is_allowed($channel['channel_id'],$xchan['xchan_hash'],'send_stream')) {
		logger('permission denied.');
		http_status_exit(200,'OK');
	}

	consume_feed($xml,$channel,$xchan,1);
	consume_feed($xml,$channel,$xchan,2);

	http_status_exit(200,'OK');

}



