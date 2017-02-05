<?php
namespace Zotlabs\Module; /** @file */

require_once('include/contact_widgets.php');
require_once('include/items.php');
require_once("include/bbcode.php");
require_once('include/security.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');



class Profile extends \Zotlabs\Web\Controller {

	function init() {
	
		if(argc() > 1)
			$which = argv(1);
		else {
			notice( t('Requested profile is not available.') . EOL );
			\App::$error = 404;
			return;
		}
	
		$profile = '';
		$channel = \App::get_channel();
	
		if((local_channel()) && (argc() > 2) && (argv(2) === 'view')) {
			$which = $channel['channel_address'];
			$profile = argv(1);		
			$r = q("select profile_guid from profile where id = %d and uid = %d limit 1",
				intval($profile),
				intval(local_channel())
			);
			if(! $r)
				$profile = '';
			$profile = $r[0]['profile_guid'];
		}
	
		\App::$page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . z_root() . '/feed/' . $which .'" />' . "\r\n" ;
	
		if(! $profile) {
			$x = q("select channel_id as profile_uid from channel where channel_address = '%s' limit 1",
				dbesc(argv(1))
			);
			if($x) {
				\App::$profile = $x[0];
			}
		}
	
		profile_load($which,$profile);
	
	
	}
	
	function get() {
	
		if(observer_prohibited(true)) {
			return login();
		}
	
		$groups = array();
	
		$tab = 'profile';
		$o = '';
	
		if(! (perm_is_allowed(\App::$profile['profile_uid'],get_observer_hash(), 'view_profile'))) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
	
		$is_owner = ((local_channel()) && (local_channel() == \App::$profile['profile_uid']) ? true : false);
	
		if(\App::$profile['hidewall'] && (! $is_owner) && (! remote_channel())) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		$o .= profile_tabs($a, $is_owner, \App::$profile['channel_address']);
	
		\App::$page['htmlhead'] .= "\r\n" . '<link rel="alternate" type="application/json+oembed" href="' . z_root() . '/oep?f=&url=' . urlencode(z_root() . '/' . \App::$query_string) . '" title="oembed" />' . "\r\n";
	
		$o .= advanced_profile($a);
		call_hooks('profile_advanced',$o);
		return $o;
	
	}
	
}
