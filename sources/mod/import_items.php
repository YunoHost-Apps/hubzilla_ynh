<?php

require_once('include/import.php');

function import_items_post(&$a) {

	if(! local_channel())
		return;

	$data     = null;

	$src      = $_FILES['filename']['tmp_name'];
	$filename = basename($_FILES['filename']['name']);
	$filesize = intval($_FILES['filename']['size']);
	$filetype = $_FILES['filename']['type'];

	if($src) {
		// This is OS specific and could also fail if your tmpdir isn't very large
		// mostly used for Diaspora which exports gzipped files.

		if(strpos($filename,'.gz')){
			@rename($src,$src . '.gz');
			@system('gunzip ' . escapeshellarg($src . '.gz'));
		}

		if($filesize) {
			$data = @file_get_contents($src);
		}
		unlink($src);
	}

	if(! $src) {

		$old_address = ((x($_REQUEST,'old_address')) ? $_REQUEST['old_address'] : '');

		if(! $old_address) {
			logger('mod_import: nothing to import.');
			notice( t('Nothing to import.') . EOL);
			return;
		}

		$email    = ((x($_REQUEST,'email'))    ? $_REQUEST['email']    : '');
		$password = ((x($_REQUEST,'password')) ? $_REQUEST['password'] : '');

		$year = ((x($_REQUEST,'year'))    ? $_REQUEST['year']    : '');

		$channelname = substr($old_address,0,strpos($old_address,'@'));
		$servername  = substr($old_address,strpos($old_address,'@')+1);

		$scheme = 'https://';
		$api_path = '/api/red/channel/export/items?f=&channel=' . $channelname . '&year=' . intval($year);
		$binary = false;
		$redirects = 0;
		$opts = array('http_auth' => $email . ':' . $password);
		$url = $scheme . $servername . $api_path;
		$ret = z_fetch_url($url, $binary, $redirects, $opts);
		if(! $ret['success'])
			$ret = z_fetch_url('http://' . $servername . $api_path, $binary, $redirects, $opts);
		if($ret['success'])
			$data = $ret['body'];
		else
			notice( t('Unable to download data from old server') . EOL);

	}

	if(! $data) {
		logger('mod_import: empty file.');
		notice( t('Imported file is empty.') . EOL);
		return;
	}

	$data = json_decode($data,true);

//	logger('import: data: ' . print_r($data,true));
//	print_r($data);


	if(array_key_exists('compatibility',$data) && array_key_exists('database',$data['compatibility'])) {
		$v1 = substr($data['compatibility']['database'],-4);
		$v2 = substr(DB_UPDATE_VERSION,-4);
		if($v2 > $v1) {
			$t = sprintf( t('Warning: Database versions differ by %1$d updates.'), $v2 - $v1 ); 
			notice($t);
		}
	}

	$channel = $a->get_channel();

	$saved_notification_flags = notifications_off($channel['channel_id']);

	if(array_key_exists('item',$data) && $data['item']) {
		import_items($channel,$data['item']);
	}

	notifications_on($channel['channel_id'],$saved_notification_flags);

	if(array_key_exists('item_id',$data) && $data['item_id']) {
		import_item_ids($channel,$data['item_id']);
	}

	info( t('Import completed') . EOL);
	return;
}




function import_items_content(&$a) {




	if(! local_channel()) {
		notice( t('Permission denied') . EOL);
		return login();
	}

	$o = replace_macros(get_markup_template('item_import.tpl'),array(
		'$title' => t('Import Items'),
		'$desc' => t('Use this form to import existing posts and content from an export file.'),
		'$label_filename' => t('File to Upload'),
		'$submit' => t('Submit')
	));

	return $o;

}


