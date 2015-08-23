<?php

require_once('include/attach.php');
require_once('include/identity.php');
require_once('include/photos.php');

function wall_attach_post(&$a) {

	if(argc() > 1)
		$channel = get_channel_by_nick(argv(1));
	elseif($_FILES['media']) {
		require_once('include/api.php');
		$user_info = api_get_user($a);
		$nick = $user_info['screen_name'];
		$channel = get_channel_by_nick($user_info['screen_name']);
    }

	if(! $channel)
		killme();

	$observer = $a->get_observer();


//	if($_FILES['userfile']['tmp_name']) {
//		$x = @getimagesize($_FILES['userfile']['tmp_name']);
//		logger('getimagesize: ' . print_r($x,true), LOGGER_DATA); 
//		if(($x) && ($x[2] === IMAGETYPE_GIF || $x[2] === IMAGETYPE_JPEG || $x[2] === IMAGETYPE_PNG)) {
//			$args = array( 'source' => 'editor', 'visible' => 0, 'contact_allow' => array($channel['channel_hash']));
//			$ret = photo_upload($channel,$observer,$args);
//			if($ret['success']) {
//				echo  "\n\n" . $ret['body'] . "\n\n";
//				killme();
//			}
//			if($using_api)
//				return;
//			notice($ret['message']);
//			killme();
//		}
//	}

	$def_album  = get_pconfig($channel['channel_id'],'system','photo_path');
	$def_attach = get_pconfig($channel['channel_id'],'system','attach_path');

	$r = attach_store($channel,(($observer) ? $observer['xchan_hash'] : ''),'', array('source' => 'editor', 'visible' => 0, 'album' => $def_album, 'directory' => $def_attach, 'allow_cid' => '<' . $channel['channel_hash'] . '>'));

	if(! $r['success']) {
		notice( $r['message'] . EOL);
		killme();
	}

	if(intval($r['data']['is_photo'])) {
		echo "\n\n" . $r['body'] . "\n\n";
		if($using_api)
			return;
		killme();
	}
	echo  "\n\n" . '[attachment]' . $r['data']['hash'] . ',' . $r['data']['revision'] . '[/attachment]' . "\n";
	killme();

}
