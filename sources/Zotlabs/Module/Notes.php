<?php
namespace Zotlabs\Module; /** @file */


class Notes extends \Zotlabs\Web\Controller {

	function init() {
	
		if(! local_channel())
			return;
	
		$ret = array('success' => true);
		if(array_key_exists('note_text',$_REQUEST)) {
			$body = escape_tags($_REQUEST['note_text']);
	
			// I've had my notes vanish into thin air twice in four years.
			// Provide a backup copy if there were contents previously 
			// and there are none being saved now.
	
			if(! $body) {
				$old_text = get_pconfig(local_channel(),'notes','text');
				if($old_text)
					set_pconfig(local_channel(),'notes','text.bak',$old_text);
			}
			set_pconfig(local_channel(),'notes','text',$body);
		}
	
		// push updates to channel clones
	
		if((argc() > 1) && (argv(1) === 'sync')) {
			require_once('include/zot.php');
			build_sync_packet();
		}
	
		logger('notes saved.', LOGGER_DEBUG);
		json_return_and_die($ret);
		
	}
	
}
