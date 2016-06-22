<?php
namespace Zotlabs\Module;


require_once('include/follow.php');


class Follow extends \Zotlabs\Web\Controller {

	function init() {
	
		if(! local_channel()) {
			return;
		}
	
		$uid = local_channel();
		$url = notags(trim($_REQUEST['url']));
		$return_url = $_SESSION['return_url'];
		$confirm = intval($_REQUEST['confirm']);
	
		$channel = \App::get_channel();

		// Warning: Do not edit the following line. The first symbol is UTF-8 &#65312; 
		$url = str_replace('@','@',$url);	

		$result = new_contact($uid,$url,$channel,true,$confirm);
		
		if($result['success'] == false) {
			if($result['message'])
				notice($result['message']);
			goaway($return_url);
		}
	
		info( t('Channel added.') . EOL);
	
		$clone = array();
		foreach($result['abook'] as $k => $v) {
			if(strpos($k,'abook_') === 0) {
				$clone[$k] = $v;
			}
		}
		unset($clone['abook_id']);
		unset($clone['abook_account']);
		unset($clone['abook_channel']);
	
		$abconfig = load_abconfig($channel['channel_hash'],$clone['abook_xchan']);
		if($abconfig)
			$clone['abconfig'] = $abconfig;
	
		build_sync_packet(0 /* use the current local_channel */, array('abook' => array($clone)));
	
	
		// If we can view their stream, pull in some posts
	
		if(($result['abook']['abook_their_perms'] & PERMS_R_STREAM) || ($result['abook']['xchan_network'] === 'rss'))
			proc_run('php','include/onepoll.php',$result['abook']['abook_id']);
	
		goaway(z_root() . '/connedit/' . $result['abook']['abook_id'] . '?f=&follow=1');
	
	}
	
		function get() {
	
		if(! local_channel()) {
			return login();
		}
	}
}
