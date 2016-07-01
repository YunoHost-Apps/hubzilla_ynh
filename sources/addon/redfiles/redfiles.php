<?php

/**
 * Name: Redmatrix File Migrator
 * Description: Migrate cloud storage from Redmatrix to Hubzilla
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: Mike Macgirvin <mike@macgirvin.com>
 */


function redfiles_install() {}
function redfiles_uninstall() {}
function redfiles_module() {}


function redfiles_init(&$a) {

	if(! local_channel())
		return;

//	if(intval(get_pconfig(local_channel(),'redfiles','complete')))
//		return;

	$channel = App::get_channel();
	
	$fr_server = $_REQUEST['fr_server'];
	$fr_username = $_REQUEST['fr_username'];
	$fr_password = $_REQUEST['fr_password'];


	$cookies = 'store/[data]/redfile_cookie_' . $channel['channel_address'];

	if($fr_server && $fr_username && $fr_password) {

		$ch = curl_init($fr_server . '/api/red/files');

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

		if(count($j['results'])) {
			$todo = count($j['results']);
			logger('redfiles: processing: ' . $todo); 

			foreach($j['results'] as $jj) {

				logger('json data: ' . print_r($jj,true));

				if(in_array($jj['hash'],$arr_done)) {
					$done ++;
					continue;
				}

				$r = q("select uid from attach where hash = '%s' and uid = %d limit 1",
					dbesc($jj['hash']),
					intval($channel['channel_id'])
				);

				if($r) {
					$done ++;
					$arr_done[] = $jj['hash'];
					continue;
				}

				$total ++;

				proc_run('php','addon/redfiles/redfilehelper.php',$jj['hash'], $channel['channel_address'], urlencode($fr_server));
				sleep(3);
				$arr_done[] = $jj['hash'];

			}
		}

		logger('redfiles: already done: ' . $done);
		logger('redfiles: done this run: ' . $total); 

//		set_pconfig(local_channel(),'redfiles','complete','1');

		@unlink($cookies);
		goaway(z_root() . '/cloud/' . $channel['channel_address']);
	}
}


function redfiles_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied') . EOL);
		return;
	}

//	if(intval(get_pconfig(local_channel(),'redfiles','complete'))) {
//		info('Redmatrix files have already been imported into this channel.');
//		return;
//	}

	$o = replace_macros(get_markup_template('redfiles.tpl','addon/redfiles'),array( 
		'$header' => t('Redmatrix File Storage Import'),
		'$desc' => t('This will import all your Redmatrix cloud files to this channel.'),
		'$fr_server' => array('fr_server', t('Redmatrix Server base URL'),'',''),
		'$fr_username' => array('fr_username', t('Redmatrix Login Username'),'',''),
		'$fr_password' => array('fr_password', t('Redmatrix Login Password'),'',''),
		'$submit' => t('Submit'),
	));
	return $o;
}
