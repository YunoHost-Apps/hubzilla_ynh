<?php

/**
 * Name: Redmatrix Photo Migrator
 * Description: Migrate photo albums from Redmatrix to Hubzilla
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: Mike Macgirvin <mike@macgirvin.com>
 */


function redphotos_install() {}
function redphotos_uninstall() {}
function redphotos_module() {}


function redphotos_init(&$a) {

	if(! local_channel())
		return;

//	if(intval(get_pconfig(local_channel(),'redphotos','complete')))
//		return;

	$channel = App::get_channel();
	
	$fr_server = $_REQUEST['fr_server'];
	$fr_username = $_REQUEST['fr_username'];
	$fr_password = $_REQUEST['fr_password'];
	$fr_album = $_REQUEST['fr_album'];
	
	$max = intval($_REQUEST['fr_max']);

	$cookies = 'store/[data]/redphoto_cookie_' . $channel['channel_address'];

	if($fr_server && $fr_username && $fr_password) {

		$ch = curl_init($fr_server . '/api/red/photos' . '?f=&scale=0' . (($fr_album) ? '&album=' . $fr_album : ''));

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       	curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookies);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $fr_username . ':' . $fr_password); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                          
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                           
		curl_setopt($ch, CURLOPT_USERAGENT, 'RedMatrix');
 
		$output = curl_exec($ch);
		curl_close($ch);

		$j = json_decode($output,true);

//		echo print_r($j,true);

		if(! $j['success']) 
			return;

		$total = 0;
		$done = 0;
		$todo = 0;
		$arr_done = array();

		if(count($j['photos'])) {
			$todo = count($j['photos']);
			logger('redphotos: processing: ' . $todo); 

			foreach($j['photos'] as $jj) {

//				logger('json data: ' . print_r($jj,true));

				if(in_array($jj['resource_id'],$arr_done)) {
					$done ++;
					continue;
				}

				if(intval($jj['scale']))
					continue;

				$r = q("select uid from photo where resource_id = '%s' and uid = %d limit 1",
					dbesc($jj['resource_id']),
					intval($channel['channel_id'])
				);

				if($r) {
					$done ++;
					$arr_done[] = $jj['resource_id'];
					continue;
				}

				$total ++;

				if($max && $total > $max)
					break;

				proc_run('php','addon/redphotos/redphotohelper.php',$jj['resource_id'], $channel['channel_address'], urlencode($fr_server), urlencode($fr_username));
				sleep(5);
				$arr_done[] = $jj['resource_id'];

			}
		}

		logger('redphotos: already done: ' . $done);
		logger('redphotos: done this run: ' . $total); 

		info(t('Photos imported') . ' ' . $done . '/' . $total); 

//		set_pconfig(local_channel(),'redphotos','complete','1');

		@unlink($cookies);
		goaway(z_root() . '/photos/' . $channel['channel_address']);
	}
}


function redphotos_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied') . EOL);
		return;
	}

//	if(intval(get_pconfig(local_channel(),'redphotos','complete'))) {
//		info('Redmatrix photos have already been imported into this channel.');
//		return;
//	}

	$o = replace_macros(get_markup_template('redphotos.tpl','addon/redphotos'),array( 
		'$header' => t('Redmatrix Photo Album Import'),
		'$desc' => t('This will import all your Redmatrix photo albums to this channel.'),
		'$fr_server' => array('fr_server', t('Redmatrix Server base URL'),'',''),
		'$fr_username' => array('fr_username', t('Redmatrix Login Username'),'',''),
		'$fr_password' => array('fr_password', t('Redmatrix Login Password'),'',''),
		'$fr_album' => array('fr_album', t('Import just this album'),'', t('Leave blank to import all albums')),
		'$fr_max' => array('fr_max', t('Maximum count to import'), '0', t('0 or blank to import all available')),
		'$submit' => t('Submit'),
	));
	return $o;
}
