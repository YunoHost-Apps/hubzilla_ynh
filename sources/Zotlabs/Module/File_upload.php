<?php
namespace Zotlabs\Module;

require_once('include/attach.php');
require_once('include/channel.php');
require_once('include/photos.php');


class File_upload extends \Zotlabs\Web\Controller {

	function post() {

		// logger('file upload: ' . print_r($_REQUEST,true));
	
		$channel = (($_REQUEST['channick']) ? channelx_by_nick($_REQUEST['channick']) : null);
	
		if(! $channel) {
			logger('channel not found');
			killme();
		}
	
		$_REQUEST['source'] = 'file_upload';

		if($channel['channel_id'] != local_channel()) {
			$_REQUEST['contact_allow'] = expand_acl($channel['channel_allow_cid']);
			$_REQUEST['group_allow']   = expand_acl($channel['channel_allow_gid']);
			$_REQUEST['contact_deny']  = expand_acl($channel['channel_deny_cid']);
			$_REQUEST['group_deny']    = expand_acl($channel['channel_deny_gid']);
		}

		if($_REQUEST['filename']) {
			$_REQUEST['allow_cid'] = perms2str($_REQUEST['contact_allow']);
			$_REQUEST['allow_gid'] = perms2str($_REQUEST['group_allow']);
			$_REQUEST['deny_cid'] = perms2str($_REQUEST['contact_deny']);
			$_REQUEST['deny_gid'] = perms2str($_REQUEST['group_deny']);
			$r = attach_mkdir($channel,get_observer_hash(),$_REQUEST);
		}
		else {
			$r = attach_store($channel,get_observer_hash(), '', $_REQUEST);
		}
		goaway(z_root() . '/' . $_REQUEST['return_url']);
	
	}
	
}
