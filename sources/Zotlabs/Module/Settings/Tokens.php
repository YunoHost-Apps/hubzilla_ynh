<?php

namespace Zotlabs\Module\Settings;



class Tokens {

	function post() {

		$channel = \App::get_channel();

		check_form_security_token_redirectOnErr('/settings/tokens', 'settings_tokens');
		$token_errs = 0;
		if(array_key_exists('token',$_POST)) {
			$atoken_id = (($_POST['atoken_id']) ? intval($_POST['atoken_id']) : 0);
			$name = trim(escape_tags($_POST['name']));
			$token = trim($_POST['token']);
			if((! $name) || (! $token))
					$token_errs ++;
			if(trim($_POST['expires']))
				$expires = datetime_convert(date_default_timezone_get(),'UTC',$_POST['expires']);
			else
				$expires = NULL_DATE;
			$max_atokens = service_class_fetch(local_channel(),'access_tokens');
			if($max_atokens) {
				$r = q("select count(atoken_id) as total where atoken_uid = %d",
					intval(local_channel())
				);
				if($r && intval($r[0]['total']) >= $max_tokens) {
					notice( sprintf( t('This channel is limited to %d tokens'), $max_tokens) . EOL);
					return;
				}
			}
		}
		if($token_errs) {
			notice( t('Name and Password are required.') . EOL);
			return;
		}
		if($atoken_id) {
			$r = q("update atoken set atoken_name = '%s', atoken_token = '%s', atoken_expires = '%s' 
				where atoken_id = %d and atoken_uid = %d",
				dbesc($name),
				dbesc($token),
				dbesc($expires),
				intval($atoken_id),
				intval($channel['channel_id'])
			);
		}
		else {
			$r = q("insert into atoken ( atoken_aid, atoken_uid, atoken_name, atoken_token, atoken_expires )
				values ( %d, %d, '%s', '%s', '%s' ) ",
				intval($channel['channel_account_id']),
				intval($channel['channel_id']),
				dbesc($name),
				dbesc($token),
				dbesc($expires)
			);
		}

		$atoken_xchan = substr($channel['channel_hash'],0,16) . '.' . $name;

		$all_perms = \Zotlabs\Access\Permissions::Perms();

		if($all_perms) {
			foreach($all_perms as $perm => $desc) {
				if(array_key_exists('perms_' . $perm, $_POST)) {
					set_abconfig($channel['channel_id'],$atoken_xchan,'my_perms',$perm,intval($_POST['perms_' . $perm]));
				}
				else {
					set_abconfig($channel['channel_id'],$atoken_xchan,'my_perms',$perm,0);
				}
			}
		}
		

		info( t('Token saved.') . EOL);
		return;
	}
	

	function get() {

		$channel = \App::get_channel();

		$atoken = null;
		$atoken_xchan = '';

		if(argc() > 2) {
			$id = argv(2);			

			$atoken = q("select * from atoken where atoken_id = %d and atoken_uid = %d",
				intval($id),
				intval(local_channel())
			);

			if($atoken) {
				$atoken = $atoken[0];
				$atoken_xchan = substr($channel['channel_hash'],0,16) . '.' . $atoken['atoken_name'];
			}

			if($atoken && argc() > 3 && argv(3) === 'drop') {
				atoken_delete($id);
				$atoken = null;
				$atoken_xchan = '';
			}
		}

		$t = q("select * from atoken where atoken_uid = %d",
			intval(local_channel())
		);			

		$desc = t('Use this form to create temporary access identifiers to share things with non-members. These identities may be used in Access Control Lists and visitors may login using these credentials to access private content.');

		$desc2 = t('You may also provide <em>dropbox</em> style access links to friends and associates by adding the Login Password to any specific site URL as shown. Examples:');

		$global_perms = \Zotlabs\Access\Permissions::Perms();
		$their_perms = [];

		$existing = get_all_perms(local_channel(),(($atoken_xchan) ? $atoken_xchan : ''));

		if($atoken_xchan) {
			$theirs = q("select * from abconfig where chan = %d and xchan = '%s' and cat = 'their_perms'",
				intval(local_channel()),
				dbesc($atoken_xchan)
			);
			if($theirs) {
				foreach($theirs as $t) {
					$their_perms[$t['k']] = $t['v'];
				}
			}
		}
		foreach($global_perms as $k => $v) {
			$thisperm = get_abconfig(local_channel(),$contact['abook_xchan'],'my_perms',$k);
//fixme

			$checkinherited = \Zotlabs\Access\PermissionLimits::Get(local_channel(),$k);

			if($existing[$k])
				$thisperm = "1";

			$perms[] = array('perms_' . $k, $v, ((array_key_exists($k,$their_perms)) ? intval($their_perms[$k]) : ''),$thisperm, 1, (($checkinherited & PERMS_SPECIFIC) ? '' : '1'), '', $checkinherited);
		}



		$tpl = get_markup_template("settings_tokens.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_tokens"),
			'$title'	=> t('Guest Access Tokens'),
			'$desc'     => $desc,
			'$desc2' => $desc2,
			'$tokens' => $t,
			'$atoken' => $atoken,
			'$url1' => z_root() . '/channel/' . $channel['channel_address'],
			'$url2' => z_root() . '/photos/' . $channel['channel_address'],
			'$name' => array('name', t('Login Name') . ' <span class="required">*</span>', (($atoken) ? $atoken['atoken_name'] : ''),''),
			'$token'=> array('token', t('Login Password') . ' <span class="required">*</span>',(($atoken) ? $atoken['atoken_token'] : autoname(8)), ''),
			'$expires'=> array('expires', t('Expires (yyyy-mm-dd)'), (($atoken['atoken_expires'] && $atoken['atoken_expires'] > NULL_DATE) ? datetime_convert('UTC',date_default_timezone_get(),$atoken['atoken_expires']) : ''), ''),
			'$them' => t('Their Settings'),
			'$me' => t('My Settings'),
			'$perms' => $perms,
			'$inherited' => t('inherited'),
			'$notself' => '1',
			'$permlbl' => t('Individual Permissions'),
			'$permnote'       => t('Some permissions may be inherited from your channel\'s <a href="settings"><strong>privacy settings</strong></a>, which have higher priority than individual settings. You can <strong>not</strong> change those settings here.'),
			'$submit' 	=> t('Submit')
		));
		return $o;
	}
	
}