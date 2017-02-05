<?php
namespace Zotlabs\Module;


class Hcard extends \Zotlabs\Web\Controller {

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
	
		require_once('include/widgets.php');
		return widget_profile(array());
	
	
	
	}
	
	
	
}
