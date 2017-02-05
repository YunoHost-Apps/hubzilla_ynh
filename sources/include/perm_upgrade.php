<?php

function perm_limits_upgrade($channel) {
	set_pconfig($channel['channel_id'],'perm_limits','view_stream',$channel['channel_r_stream']);
	set_pconfig($channel['channel_id'],'perm_limits','view_profile',$channel['channel_r_profile']);
	set_pconfig($channel['channel_id'],'perm_limits','view_contacts',$channel['channel_r_abook']);
	set_pconfig($channel['channel_id'],'perm_limits','view_storage',$channel['channel_r_storage']);
	set_pconfig($channel['channel_id'],'perm_limits','view_pages',$channel['channel_r_pages']);
	set_pconfig($channel['channel_id'],'perm_limits','send_stream',$channel['channel_w_stream']);
	set_pconfig($channel['channel_id'],'perm_limits','post_wall',$channel['channel_w_wall']);
	set_pconfig($channel['channel_id'],'perm_limits','post_comments',$channel['channel_w_comment']);
	set_pconfig($channel['channel_id'],'perm_limits','post_mail',$channel['channel_w_mail']);
	set_pconfig($channel['channel_id'],'perm_limits','post_like',$channel['channel_w_like']);
	set_pconfig($channel['channel_id'],'perm_limits','tag_deliver',$channel['channel_w_tagwall']);
	set_pconfig($channel['channel_id'],'perm_limits','chat',$channel['channel_w_chat']);
	set_pconfig($channel['channel_id'],'perm_limits','write_storage',$channel['channel_w_storage']);
	set_pconfig($channel['channel_id'],'perm_limits','write_pages',$channel['channel_w_pages']);
	set_pconfig($channel['channel_id'],'perm_limits','republish',$channel['channel_a_republish']);
	set_pconfig($channel['channel_id'],'perm_limits','delegate',$channel['channel_a_delegate']);
}

function perms_int_to_array($p) {

	$ret = [];

	$ret['view_stream']   = (($p & PERMS_R_STREAM)    ? 1 : 0);
	$ret['view_profile']  = (($p & PERMS_R_PROFILE)   ? 1 : 0);
	$ret['view_contacts'] = (($p & PERMS_R_ABOOK)     ? 1 : 0);
	$ret['view_storage']  = (($p & PERMS_R_STORAGE)   ? 1 : 0);
	$ret['view_pages']    = (($p & PERMS_R_PAGES)     ? 1 : 0);
	$ret['send_stream']   = (($p & PERMS_W_STREAM)    ? 1 : 0);
	$ret['post_wall']     = (($p & PERMS_W_WALL)      ? 1 : 0);
	$ret['post_comments'] = (($p & PERMS_W_COMMENT)   ? 1 : 0);
	$ret['post_mail']     = (($p & PERMS_W_MAIL)      ? 1 : 0);
	$ret['post_like']     = (($p & PERMS_W_LIKE)      ? 1 : 0);
	$ret['tag_deliver']   = (($p & PERMS_W_TAGWALL)   ? 1 : 0);
	$ret['chat']          = (($p & PERMS_W_CHAT)      ? 1 : 0);
	$ret['write_storage'] = (($p & PERMS_W_STORAGE)   ? 1 : 0);
	$ret['write_pages']   = (($p & PERMS_W_PAGES)     ? 1 : 0);
	$ret['republish']     = (($p & PERMS_A_REPUBLISH) ? 1 : 0);
	$ret['delegate']      = (($p & PERMS_A_DELEGATE)  ? 1 : 0);

	return $ret;
}

function autoperms_upgrade($channel) {
	$x = get_pconfig($channel['channel_id'],'system','autoperms');
	if(intval($x)) {
		$y = perms_int_to_array($x);
		if($y) {
			foreach($y as $k => $v) {
				set_pconfig($channel['channel_id'],'autoperms',$k,$v);
			}
		}
	}
}


function perm_abook_upgrade($abook) {

	$x = perms_int_to_array($abook['abook_their_perms']);
	if($x) {
		foreach($x as $k => $v) {
			set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms',$k, $v);
		}
	}

	$x = perms_int_to_array($abook['abook_my_perms']);
	if($x) {
		foreach($x as $k => $v) {
			set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms',$k, $v);
		}
	}
}

function translate_channel_perms_outbound(&$channel) {
	$r = q("select * from pconfig where uid = %d and cat = 'perm_limits' ",
		intval($channel['channel_id'])
	);

	if($r) {
		foreach($r as $rr) {
			if($rr['k'] === 'view_stream')
				$channel['channel_r_stream'] = $rr['v'];
			if($rr['k'] === 'view_profile')
				$channel['channel_r_profile'] = $rr['v'];
			if($rr['k'] === 'view_contacts')
				$channel['channel_r_abook'] = $rr['v'];
			if($rr['k'] === 'view_storage')
				$channel['channel_r_storage'] = $rr['v'];
			if($rr['k'] === 'view_pages')
				$channel['channel_r_pages'] = $rr['v'];
			if($rr['k'] === 'send_stream')
				$channel['channel_w_stream'] = $rr['v'];
			if($rr['k'] === 'post_wall')
				$channel['channel_w_wall'] = $rr['v'];
			if($rr['k'] === 'post_comments')
				$channel['channel_w_comment'] = $rr['v'];
			if($rr['k'] === 'post_mail')
				$channel['channel_w_mail'] = $rr['v'];
			if($rr['k'] === 'post_like')
				$channel['channel_w_like'] = $rr['v'];
			if($rr['k'] === 'tag_deliver')
				$channel['channel_w_tagwall'] = $rr['v'];
			if($rr['k'] === 'chat')
				$channel['channel_w_chat'] = $rr['v'];
			if($rr['k'] === 'write_storage')
				$channel['channel_w_storage'] = $rr['v'];
			if($rr['k'] === 'write_pages')
				$channel['channel_w_pages'] = $rr['v'];
			if($rr['k'] === 'republish')
				$channel['channel_a_republish'] = $rr['v'];
			if($rr['k'] === 'delegate')
				$channel['channel_a_delegate'] = $rr['v'];

		}
		$channel['perm_limits'] = $r;
	}
}

function translate_channel_perms_inbound($channel) {
	
	if($channel['perm_limits']) {
		foreach($channel['perm_limits'] as $p) {
			set_pconfig($channel['channel_id'],'perm_limits',$p['k'],$p['v']);
		}
	}
	else {
		perm_limits_upgrade($channel);
	}

}

function translate_abook_perms_outbound(&$abook) {
	$my_perms = 0;
	$their_perms = 0;

	if(array_key_exists('abconfig',$abook) && is_array($abook['abconfig']) && $abook['abconfig']) {
		foreach($abook['abconfig'] as $p) {
			if($p['cat'] === 'their_perms') {
				if($p['k'] === 'view_stream' && intval($p['v']))
					$their_perms += PERMS_R_STREAM; 
				if($p['k'] === 'view_profile' && intval($p['v']))
					$their_perms += PERMS_R_PROFILE;
				if($p['k'] === 'view_contacts' && intval($p['v']))
					$their_perms += PERMS_R_ABOOK; 
				if($p['k'] === 'view_storage' && intval($p['v']))
					$their_perms += PERMS_R_STORAGE; 
				if($p['k'] === 'view_pages' && intval($p['v']))
					$their_perms += PERMS_R_PAGES; 
				if($p['k'] === 'send_stream' && intval($p['v']))
					$their_perms += PERMS_W_STREAM; 
				if($p['k'] === 'post_wall' && intval($p['v']))
					$their_perms += PERMS_W_WALL; 
				if($p['k'] === 'post_comments' && intval($p['v']))
					$their_perms += PERMS_W_COMMENT; 
				if($p['k'] === 'post_mail' && intval($p['v']))
					$their_perms += PERMS_W_MAIL; 
				if($p['k'] === 'post_like' && intval($p['v']))
					$their_perms += PERMS_W_LIKE; 
				if($p['k'] === 'tag_deliver' && intval($p['v']))
					$their_perms += PERMS_W_TAGWALL; 
				if($p['k'] === 'chat' && intval($p['v']))
					$their_perms += PERMS_W_CHAT; 
				if($p['k'] === 'write_storage' && intval($p['v']))
					$their_perms += PERMS_W_STORAGE; 
				if($p['k'] === 'write_pages' && intval($p['v']))
					$their_perms += PERMS_W_PAGES; 
				if($p['k'] === 'republish' && intval($p['v']))
					$their_perms += PERMS_A_REPUBLISH; 
				if($p['k'] === 'delegate' && intval($p['v']))
					$their_perms += PERMS_A_DELEGATE; 
			}
			if($p['cat'] === 'my_perms') {
				if($p['k'] === 'view_stream' && intval($p['v']))
					$my_perms += PERMS_R_STREAM; 
				if($p['k'] === 'view_profile' && intval($p['v']))
					$my_perms += PERMS_R_PROFILE;
				if($p['k'] === 'view_contacts' && intval($p['v']))
					$my_perms += PERMS_R_ABOOK; 
				if($p['k'] === 'view_storage' && intval($p['v']))
					$my_perms += PERMS_R_STORAGE; 
				if($p['k'] === 'view_pages' && intval($p['v']))
					$my_perms += PERMS_R_PAGES; 
				if($p['k'] === 'send_stream' && intval($p['v']))
					$my_perms += PERMS_W_STREAM; 
				if($p['k'] === 'post_wall' && intval($p['v']))
					$my_perms += PERMS_W_WALL; 
				if($p['k'] === 'post_comments' && intval($p['v']))
					$my_perms += PERMS_W_COMMENT; 
				if($p['k'] === 'post_mail' && intval($p['v']))
					$my_perms += PERMS_W_MAIL; 
				if($p['k'] === 'post_like' && intval($p['v']))
					$my_perms += PERMS_W_LIKE; 
				if($p['k'] === 'tag_deliver' && intval($p['v']))
					$my_perms += PERMS_W_TAGWALL; 
				if($p['k'] === 'chat' && intval($p['v']))
					$my_perms += PERMS_W_CHAT; 
				if($p['k'] === 'write_storage' && intval($p['v']))
					$my_perms += PERMS_W_STORAGE; 
				if($p['k'] === 'write_pages' && intval($p['v']))
					$my_perms += PERMS_W_PAGES; 
				if($p['k'] === 'republish' && intval($p['v']))
					$my_perms += PERMS_A_REPUBLISH; 
				if($p['k'] === 'delegate' && intval($p['v']))
					$my_perms += PERMS_A_DELEGATE; 
			}
		}

		$abook['abook_their_perms'] = $their_perms;
		$abook['abook_my_perms'] = $my_perms;
	}
}

function translate_abook_perms_inbound($channel,$abook) {

	$new_perms = false;
	$abook['abook_channel'] = $channel['channel_id'];

	if(array_key_exists('abconfig',$abook) && is_array($abook['abconfig']) && $abook['abconfig']) {
			foreach($abook['abconfig'] as $p) {
			if($p['cat'] == 'their_perms' || $p['cat'] == 'my_perms') {
				$new_perms = true; 
				break;
			}
		}
	}

	if($new_perms == false) {
		perm_abook_upgrade($abook);
	}

}



