<?php

require_once('include/cli_startup.php');
require_once('include/attach.php');

cli_startup();

$a = get_app();


$photo_id = $argv[1];
$channel_address = $argv[2];
$fr_server = urldecode($argv[3]);
$fr_username = urldecode($argv[4]);

$rand = random_string(16);

$cookies = 'store/[data]/redphoto_cookie_' . $channel_address;
$photo_tmp = 'store/[data]/redphoto_data_' . $channel_address . $rand;

	$c = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_address = '%s' limit 1",
		dbesc($channel_address)
	);
	if(! $c) {
		logger('redphotohelper: channel not found');
		killme();
	}
	$channel = $c[0];	

	// fake a login session 
	$_SESSION['authenticated'] = 1;
	$_SESSION['uid'] = $channel['channel_id'];

	    $ch = curl_init($fr_server . '/api/red/photo?f=&photo_id=' . $photo_id);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'RedMatrix');

        $output = curl_exec($ch);
        curl_close($ch);

		$j = json_decode($output,true);

		logger('received: ' . print_r($j,true));


		if(! ($j['photo'] && $j['photo']['src'])) {
			logger('redphotohelper: no data');
			killme();
		}

		$filep = fopen($photo_tmp,'w');
		$redirects = 0;
		$x = z_fetch_url($fr_server . '/api/red/getphoto?f=&photo_id=' . $photo_id,true,$redirects,array('filep' => $filep, 'cookiejar' => $cookies, 'cookiefile' => $cookies));
		fclose($filep);
		if(! $x['success']) {
			logger('photo download failed');
			@unlink($photo_tmp);
			killme();
		}

		$args = array();


		$args['src'] = $photo_tmp; 
		$args['nosync'] = true;
		
		$args['filename'] = $j['photo']['filename'];
		if(! $args['filename'])
			$args['filename'] = t('photo');
		$args['hash'] = ((array_key_exists($j['photo']['hash'])) ? $j['photo']['hash'] : $j['photo']['resource_id']);
		$args['imgscale'] = ((array_key_exists('imgscale',$j['photo'])) ? $j['photo']['imgscale'] : $j['photo']['scale']);
		$args['album'] = $j['photo']['album'];
		$args['visible'] = 0;
		$args['created'] = $j['photo']['created'];
		$args['edited'] = $j['photo']['edited'];
		$args['title'] = $j['photo']['title'];
		$args['description'] = $j['photo']['description'];

		$args['allow_cid'] = $j['photo']['allow_cid'];
		$args['allow_gid'] = $j['photo']['allow_gid'];
		$args['deny_cid']  = $j['photo']['deny_cid'];
		$args['deny_gid']  = $j['photo']['deny_gid'];


		if($j['photo']['photo_flags'] & 1)
			$args['photo_usage'] = PHOTO_PROFILE;
		if($j['photo']['profile'])
			$args['photo_usage'] = PHOTO_PROFILE;

		if(array_key_exists('photo_usage',$args))
			$args['photo_usage'] = $j['photo']['photo_usage'];

		$args['type'] = $j['photo']['type']; 

		$args['item'] = (($j['item']) ? $j['item'] : false);


//		logger('redphotohelper: ' . print_r($j,true));

		$r = q("select id from photo where resource_id = '%s' and uid = %d limit 1",
			dbesc($args['hash']),
			intval($channel['channel_id'])
		);
		if($r) {
			killme();
		}


		$ret = attach_store($channel,$channel['channel_hash'],'import',$args);

		$r = q("select * from item where resource_id = '%s' and resource_type = 'photo' and uid = %d limit 1",
			dbesc($args['hash']),
			intval($channel['channel_id'])
		);

		if($r) {
			$item = $r[0];
			item_url_replace($channel,$item,$fr_server,z_root(),$fr_username);

			if(! dbesc_array($item))
				return;
			$item_id = $item['id'];
			unset($item['id']);
			$str = '';
			foreach($item as $k => $v) {
				if($str)
					$str .= ",";
				$str .= " `" . $k . "` = '" . $v . "' ";
			}

			$r = dbq("update `item` set " . $str . " where id = " . $item_id ); 
		}


//		logger('photo_import: ' . print_r($ret,true));

		killme();

