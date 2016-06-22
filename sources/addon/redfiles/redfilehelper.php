<?php

require_once('include/cli_startup.php');
require_once('include/attach.php');

cli_startup();

$a = get_app();


$attach_id = $argv[1];
$channel_address = $argv[2];
$fr_server = urldecode($argv[3]);

$cookies = 'store/[data]/redfile_cookie_' . $channel_address;
$attach_tmp = 'store/[data]/redfile_data_' . $channel_address;

	$c = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_address = '%s' limit 1",
		dbesc($channel_address)
	);
	if(! $c) {
		logger('redfilehelper: channel not found');
		killme();
	}
	$channel = $c[0];	

	// fake a login session 
	$_SESSION['authenticated'] = 1;
	$_SESSION['uid'] = $channel['channel_id'];

	    $ch = curl_init($fr_server . '/api/red/file?f=&file_id=' . $attach_id);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'RedMatrix');

        $output = curl_exec($ch);
        curl_close($ch);

		$j = json_decode($output,true);

// logger('redfilehelper: ' . print_r($j,true));

		if(! ($j['attach'])) {
			logger('redfilehelper: no data');
			killme();
		}

		if(array_key_exists('content',$j['attach']) && $j['attach']['content']) {
			file_put_contents($attach_tmp,base64_decode($j['attach']['content']));
			unset($j['attach']['content']);
		}
		else {
			file_put_contents($attach_tmp,(($j['attach']['data']) ? base64_decode($j['attach']['data']) : ''));
			unset($j['attach']['data']);
		}
		$args = array();


		$args['src'] = $attach_tmp; 
		
		$args['filename'] = $j['attach']['filename'];
		if(! $args['filename'])
			$args['filename'] = t('file');
		$args['resource_id'] = $j['attach']['hash'];
		$args['hash'] = $j['attach']['hash'];
		$args['created'] = $j['attach']['created'];
		$args['edited'] = $j['attach']['edited'];
		$args['allow_cid'] = $j['attach']['allow_cid'];
		$args['allow_gid'] = $j['attach']['allow_gid'];
		$args['deny_cid']  = $j['attach']['deny_cid'];
		$args['deny_gid']  = $j['attach']['deny_gid'];
		$args['type'] = $j['attach']['mimetype']; 
		$args['creator'] = $j['attach']['creator'];
		$args['folder'] = $j['attach']['folder'];
		$args['revision'] = $j['attach']['revision'];
		$args['is_dir'] = $j['attach']['is_dir'];

		logger('redfilehelper: ' . print_r($j,true));

		$r = q("select id from attach where hash = '%s' and uid = %d limit 1",
			dbesc($args['resource_id']),
			intval($channel['channel_id'])
		);
		if($r) {
			killme();
		}


		$ret = attach_store($channel,$channel['channel_hash'],'import',$args);
//		logger('file_import: ' . print_r($ret,true));

		killme();

