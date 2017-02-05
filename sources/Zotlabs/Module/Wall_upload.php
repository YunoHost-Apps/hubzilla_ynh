<?php
namespace Zotlabs\Module;

require_once('include/photo/photo_driver.php');
require_once('include/channel.php');
require_once('include/photos.php');




class Wall_upload extends \Zotlabs\Web\Controller {

	function post() {
	
	
		$using_api = ((x($_FILES,'media')) ? true : false); 
	
		if($using_api) {
			require_once('include/api.php');
			if(api_user())
				$channel = channelx_by_n(api_user());
		}
		else {
			if(argc() > 1)
				$channel = channelx_by_nick(argv(1));
		}
	
		if(! $channel) {
			if($using_api)
				return;
			notice( t('Channel not found.') . EOL);
			killme();
		}
	
		$observer = \App::get_observer();
	
		$args = array( 'source' => 'editor', 'visible' => 0, 'contact_allow' => array($channel['channel_hash']));
	
	 	$ret = photo_upload($channel,$observer,$args);
	
		if(! $ret['success']) {
			if($using_api)
				return;
			notice($ret['message']);
			killme();
		}
	
		if($using_api)
			return("\n\n" . $ret['body'] . "\n\n");
		else
			echo  "\n\n" . $ret['body'] . "\n\n";
		killme();
	}
	
}
