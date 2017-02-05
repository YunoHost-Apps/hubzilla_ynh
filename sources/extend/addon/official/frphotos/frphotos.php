<?php

/**
 * Name: Friendica Photo Migrator
 * Description: Migrate photo albums from Friendica to a Redmatrix channel (not yet ported to Hubzilla)
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 */


function frphotos_install() {}
function frphotos_uninstall() {}
function frphotos_module() {}


function frphotos_init(&$a) {

	if(! local_channel())
		return;

	if(intval(get_pconfig(local_channel(),'frphotos','complete')))
		return;

	$channel = App::get_channel();
	
	$fr_server = $_REQUEST['fr_server'];
	$fr_username = $_REQUEST['fr_username'];
	$fr_password = $_REQUEST['fr_password'];

	$cookies = 'store/[data]/frphoto_cookie_' . $channel['channel_address'];

	if($fr_server && $fr_username && $fr_password) {

		$ch = curl_init($fr_server . '/api/friendica/photos/list');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       	curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookies);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $fr_username . ':' . $fr_password); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                          
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                           
		curl_setopt($ch, CURLOPT_USERAGENT, 'Hubzilla');
 
		$output = curl_exec($ch);
		curl_close($ch);

		$j = json_decode($output,true);

//		echo print_r($j,true);

		$total = 0;
		if(count($j)) {
			foreach($j as $jj) {

				$r = q("select uid from photo where resource_id = '%s' and uid = %d limit 1",
					dbesc($jj),
					intval($channel['channel_id'])
				);
				if($r) 
					continue;

				$total ++;
				proc_run('php','addon/frphotos/frphotohelper.php',$jj, $channel['channel_address'], urlencode($fr_server));
				sleep(3);
			}
		}
		if($total) {
			set_pconfig(local_channel(),'frphotos','complete','1');
		}
		@unlink($cookies);
		goaway(z_root() . '/photos/' . $channel['channel_address']);
	}
}


function frphotos_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied') . EOL);
		return;
	}

	if(intval(get_pconfig(local_channel(),'frphotos','complete'))) {
		info('Friendica photos have already been imported into this channel.');
		return;
	}

	$o = replace_macros(get_markup_template('frphotos.tpl','addon/frphotos'),array( 
		'$header' => t('Friendica Photo Album Import'),
		'$desc' => t('This will import all your Friendica photo albums to this Red channel.'),
		'$fr_server' => array('fr_server', t('Friendica Server base URL'),'',''),
		'$fr_username' => array('fr_username', t('Friendica Login Username'),'',''),
		'$fr_password' => array('fr_password', t('Friendica Login Password'),'',''),
		'$submit' => t('Submit'),
	));
	return $o;
}
