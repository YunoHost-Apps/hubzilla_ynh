<?php

/* @file profile_photo.php
   @brief Module-file with functions for handling of profile-photos

*/

require_once('include/photo/photo_driver.php');

/* @brief Function for sync'ing  permissions of profile-photos and their profile
*
*  @param $profileid The id number of the profile to sync
*  @return void
*/

function profile_photo_set_profile_perms($profileid = '') {

	$allowcid = '';
	if (x($profileid)) {

		$r = q("SELECT photo, profile_guid, id, is_default, uid  FROM profile WHERE profile.id = %d OR profile.profile_guid = '%s' LIMIT 1", intval($profileid), dbesc($profileid));

	} else {

		logger('Resetting permissions on default-profile-photo for user'.local_channel());
		$r = q("SELECT photo, profile_guid, id, is_default, uid  FROM profile WHERE profile.uid = %d AND is_default = 1 LIMIT 1", intval(local_channel()) ); //If no profile is given, we update the default profile
	}

	$profile = $r[0];
	if(x($profile['id']) && x($profile['photo'])) { 
	       	preg_match("@\w*(?=-\d*$)@i", $profile['photo'], $resource_id);
	       	$resource_id = $resource_id[0];

		if (intval($profile['is_default']) != 1) {
			$r0 = q("SELECT channel_hash FROM channel WHERE channel_id = %d LIMIT 1", intval(local_channel()) );
			$r1 = q("SELECT abook.abook_xchan FROM abook WHERE abook_profile = '%d' ", intval($profile['id'])); //Should not be needed in future. Catches old int-profile-ids.
			$r2 = q("SELECT abook.abook_xchan FROM abook WHERE abook_profile = '%s'", dbesc($profile['profile_guid']));
			$allowcid = "<" . $r0[0]['channel_hash'] . ">";
			foreach ($r1 as $entry) {
				$allowcid .= "<" . $entry['abook_xchan'] . ">"; 
			}
			foreach ($r2 as $entry) {
               	                $allowcid .= "<" . $entry['abook_xchan'] . ">";
	                      	}

			q("UPDATE `photo` SET allow_cid = '%s' WHERE resource_id = '%s' AND uid = %d",dbesc($allowcid),dbesc($resource_id),intval($profile['uid']));

		} else {
			q("UPDATE `photo` SET allow_cid = '' WHERE profile = 1 AND uid = %d",intval($profile['uid'])); //Reset permissions on default profile picture to public
		}
	}

	return;
}

/* @brief Initalize the profile-photo edit view
 *
 * @param $a Current application
 * @return void
 *
 */

function profile_photo_init(&$a) {

	if(! local_channel()) {
		return;
	}

	$channel = $a->get_channel();
	profile_load($a,$channel['channel_address']);

}

/* @brief Evaluate posted values
 *
 * @param $a Current application
 * @return void
 *
 */

function profile_photo_post(&$a) {

	if(! local_channel()) {
		return;
	}
	
	check_form_security_token_redirectOnErr('/profile_photo', 'profile_photo');
        
	if((x($_POST,'cropfinal')) && ($_POST['cropfinal'] == 1)) {

		// unless proven otherwise
		$is_default_profile = 1;

		if($_REQUEST['profile']) {
			$r = q("select id, profile_guid, is_default, gender from profile where id = %d and uid = %d limit 1",
				intval($_REQUEST['profile']),
				intval(local_channel())
			);
			if($r) {
				$profile = $r[0];
				if(! intval($profile['is_default']))
					$is_default_profile = 0;
			}
		} 

		

		// phase 2 - we have finished cropping

		if(argc() != 2) {
			notice( t('Image uploaded but image cropping failed.') . EOL );
			return;
		}

		$image_id = argv(1);

		if(substr($image_id,-2,1) == '-') {
			$scale = substr($image_id,-1,1);
			$image_id = substr($image_id,0,-2);
		}
			

		$srcX = $_POST['xstart'];
		$srcY = $_POST['ystart'];
		$srcW = $_POST['xfinal'] - $srcX;
		$srcH = $_POST['yfinal'] - $srcY;

		$r = q("SELECT * FROM photo WHERE resource_id = '%s' AND uid = %d AND scale = %d LIMIT 1",
			dbesc($image_id),
			dbesc(local_channel()),
			intval($scale));

		if($r) {

			$base_image = $r[0];
			$base_image['data'] = (($r[0]['os_storage']) ? @file_get_contents($base_image['data']) : dbunescbin($base_image['data']));
		
			$im = photo_factory($base_image['data'], $base_image['type']);
			if($im->is_valid()) {

				$im->cropImage(300,$srcX,$srcY,$srcW,$srcH);

				$aid = get_account_id();

				$p = array('aid' => $aid, 'uid' => local_channel(), 'resource_id' => $base_image['resource_id'],
					'filename' => $base_image['filename'], 'album' => t('Profile Photos'));

				$p['scale'] = 4;
				$p['photo_usage'] = (($is_default_profile) ? PHOTO_PROFILE : PHOTO_NORMAL);

				$r1 = $im->save($p);

				$im->scaleImage(80);
				$p['scale'] = 5;

				$r2 = $im->save($p);
			
				$im->scaleImage(48);
				$p['scale'] = 6;

				$r3 = $im->save($p);
			
				if($r1 === false || $r2 === false || $r3 === false) {
					// if one failed, delete them all so we can start over.
					notice( t('Image resize failed.') . EOL );
					$x = q("delete from photo where resource_id = '%s' and uid = %d and scale >= 4 ",
						dbesc($base_image['resource_id']),
						local_channel()
					);
					return;
				}

				$channel = $a->get_channel();

				// If setting for the default profile, unset the profile photo flag from any other photos I own

				if($is_default_profile) {
					$r = q("UPDATE photo SET photo_usage = %d WHERE photo_usage = %d
						AND resource_id != '%s' AND `uid` = %d",
						intval(PHOTO_NORMAL),
						intval(PHOTO_PROFILE),
						dbesc($base_image['resource_id']),
						intval(local_channel())
					);

					send_profile_photo_activity($channel,$base_image,$profile);

				}
				else {
					$r = q("update profile set photo = '%s', thumb = '%s' where id = %d and uid = %d",
						dbesc($a->get_baseurl() . '/photo/' . $base_image['resource_id'] . '-4'),
						dbesc($a->get_baseurl() . '/photo/' . $base_image['resource_id'] . '-5'),
						intval($_REQUEST['profile']),
						intval(local_channel())
					);
				}

				// We'll set the updated profile-photo timestamp even if it isn't the default profile,
				// so that browsers will do a cache update unconditionally


				$r = q("UPDATE xchan set xchan_photo_mimetype = '%s', xchan_photo_date = '%s' 
					where xchan_hash = '%s'",
					dbesc($im->getType()),
					dbesc(datetime_convert()),
					dbesc($channel['xchan_hash'])
				);

				info( t('Shift-reload the page or clear browser cache if the new photo does not display immediately.') . EOL);

				// Update directory in background
				proc_run('php',"include/directory.php",$channel['channel_id']);

				// Now copy profile-permissions to pictures, to prevent privacyleaks by automatically created folder 'Profile Pictures'

				profile_photo_set_profile_perms($_REQUEST['profile']);



			}
			else
				notice( t('Unable to process image') . EOL);
		}

		goaway($a->get_baseurl() . '/profiles');
		return; // NOTREACHED
	}



	$hash = photo_new_resource();
	$smallest = 0;

	require_once('include/attach.php');

	$res = attach_store($a->get_channel(), get_observer_hash(), '', array('album' => t('Profile Photos'), 'hash' => $hash));

	logger('attach_store: ' . print_r($res,true));

	if($res && intval($res['data']['is_photo'])) {
		$i = q("select * from photo where resource_id = '%s' and uid = %d order by scale",
			dbesc($hash),
			intval(local_channel())
		);

		if(! $i) {
			notice( t('Image upload failed.') . EOL );
			return;
		}
		$os_storage = false;

		foreach($i as $ii) {
			if(intval($ii['scale']) < 2) {
				$smallest = intval($ii['scale']);
				$os_storage = intval($ii['os_storage']);
				$imagedata = $ii['data'];
				$filetype = $ii['type'];
			}
		}
	}

	$imagedata = (($os_storage) ? @file_get_contents($imagedata) : $imagedata);
	$ph = photo_factory($imagedata, $filetype);

	if(! $ph->is_valid()) {
		notice( t('Unable to process image.') . EOL );
		return;
	}

	return profile_photo_crop_ui_head($a, $ph, $hash, $smallest);
	
}

function send_profile_photo_activity($channel,$photo,$profile) {

	// for now only create activities for the default profile

	if(! intval($profile['is_default']))
		return;

	$arr = array();
	$arr['item_thread_top'] = 1;
	$arr['item_origin'] = 1;
	$arr['item_wall'] = 1;
	$arr['obj_type'] = ACTIVITY_OBJ_PHOTO;
	$arr['verb'] = ACTIVITY_UPDATE;

	$arr['object'] = json_encode(array(
		'type' => $arr['obj_type'],
		'id' => z_root() . '/photo/profile/l/' . $channel['channel_id'],
		'link' => array('rel' => 'photo', 'type' => $photo['type'], 'href' => z_root() . '/photo/profile/l/' . $channel['channel_id'])
	));

	if(stripos($profile['gender'],t('female')) !== false)
		$t = t('%1$s updated her %2$s');
	elseif(stripos($profile['gender'],t('male')) !== false)
		$t = t('%1$s updated his %2$s');
	else
		$t = t('%1$s updated their %2$s');

	$ptext = '[zrl=' . z_root() . '/photos/' . $channel['channel_address'] . '/image/' . $photo['resource_id'] . ']' . t('profile photo') . '[/zrl]';

	$ltext = '[zrl=' . z_root() . '/profile/' . $channel['channel_address'] . ']' . '[zmg=150x150]' . z_root() . '/photo/' . $photo['resource_id'] . '-4[/zmg][/zrl]'; 

	$arr['body'] = sprintf($t,$channel['channel_name'],$ptext) . "\n\n" . $ltext;

	$acl = new AccessList($channel);
	$x = $acl->get();
	$arr['allow_cid'] = $x['allow_cid'];

	$arr['allow_gid'] = $x['allow_gid'];
	$arr['deny_cid'] = $x['deny_cid'];
	$arr['deny_gid'] = $x['deny_gid'];

	$arr['uid'] = $channel['channel_id'];
	$arr['aid'] = $channel['channel_account_id'];

	$arr['owner_xchan'] = $channel['channel_hash'];
	$arr['author_xchan'] = $channel['channel_hash'];

	post_activity_item($arr);


}


/* @brief Generate content of profile-photo view
 *
 * @param $a Current application
 * @return void
 *
 */


function profile_photo_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied.') . EOL );
		return;
	}

	$channel = $a->get_channel();

	$newuser = false;

	if(argc() == 2 && argv(1) === 'new')
		$newuser = true;

	if(argv(1) === 'use') {
		if (argc() < 3) {
			notice( t('Permission denied.') . EOL );
			return;
		};
		
//		check_form_security_token_redirectOnErr('/profile_photo', 'profile_photo');
        
		$resource_id = argv(2);


		$r = q("SELECT id, album, scale FROM photo WHERE uid = %d AND resource_id = '%s' ORDER BY scale ASC",
			intval(local_channel()),
			dbesc($resource_id)
		);
		if(! $r) {
			notice( t('Photo not available.') . EOL );
			return;
		}
		$havescale = false;
		foreach($r as $rr) {
			if($rr['scale'] == 5)
				$havescale = true;
		}

		// set an already loaded photo as profile photo

		if(($r[0]['album'] == t('Profile Photos')) && ($havescale)) {
			// unset any existing profile photos
			$r = q("UPDATE photo SET photo_usage = %d WHERE photo_usage = %d AND uid = %d",
				intval(PHOTO_NORMAL),
				intval(PHOTO_PROFILE),
				intval(local_channel()));

			$r = q("UPDATE photo SET photo_usage = %d WHERE uid = %d AND resource_id = '%s'",
				intval(PHOTO_PROFILE),
				intval(local_channel()),
				dbesc($resource_id)
				);

			$r = q("UPDATE xchan set xchan_photo_date = '%s' 
				where xchan_hash = '%s'",
				dbesc(datetime_convert()),
				dbesc($channel['xchan_hash'])
			);

			profile_photo_set_profile_perms(); //Reset default photo permissions to public
			proc_run('php','include/directory.php',local_channel());
			goaway($a->get_baseurl() . '/profiles');
		}

		$r = q("SELECT `data`, `type`, resource_id, os_storage FROM photo WHERE id = %d and uid = %d limit 1",
			intval($r[0]['id']),
			intval(local_channel())

		);
		if(! $r) {
			notice( t('Photo not available.') . EOL );
			return;
		}

		if(intval($r[0]['os_storage']))
			$data = @file_get_contents($r[0]['data']);
		else
			$data = dbunescbin($r[0]['data']); 

		$ph = photo_factory($data, $r[0]['type']);
		$smallest = 0;
		if($ph->is_valid()) {
			// go ahead as if we have just uploaded a new photo to crop
			$i = q("select resource_id, scale from photo where resource_id = '%s' and uid = %d order by scale",
				dbesc($r[0]['resource_id']),
				intval(local_channel())
			);

			if($i) {
				$hash = $i[0]['resource_id'];
				foreach($i as $ii) {
					if(intval($ii['scale']) < 2) {
						$smallest = intval($ii['scale']);
					}
				}
            }
        }
 
		profile_photo_crop_ui_head($a, $ph, $hash, $smallest);
	}

	$profiles = q("select id, profile_name as name, is_default from profile where uid = %d",
		intval(local_channel())
	);

	if(! x($a->data,'imagecrop')) {

		$tpl = get_markup_template('profile_photo.tpl');

		$o .= replace_macros($tpl,array(
			'$user' => $a->channel['channel_address'],
			'$lbl_upfile' => t('Upload File:'),
			'$lbl_profiles' => t('Select a profile:'),
			'$title' => t('Upload Profile Photo'),
			'$submit' => t('Upload'),
			'$profiles' => $profiles,
			'$form_security_token' => get_form_security_token("profile_photo"),
// FIXME - yuk  
			'$select' => sprintf('%s %s', t('or'), ($newuser) ? '<a href="' . $a->get_baseurl() . '">' . t('skip this step') . '</a>' : '<a href="'. $a->get_baseurl() . '/photos/' . $a->channel['channel_address'] . '">' . t('select a photo from your photo albums') . '</a>')
		));
		
		call_hooks('profile_photo_content_end', $o);
		
		return $o;
	}
	else {
		$filename = $a->data['imagecrop'] . '-' . $a->data['imagecrop_resolution'];
		$resolution = $a->data['imagecrop_resolution'];
		$tpl = get_markup_template("cropbody.tpl");
		$o .= replace_macros($tpl,array(
			'$filename' => $filename,
			'$profile' => intval($_REQUEST['profile']),
			'$resource' => $a->data['imagecrop'] . '-' . $a->data['imagecrop_resolution'],
			'$image_url' => $a->get_baseurl() . '/photo/' . $filename,
			'$title' => t('Crop Image'),
			'$desc' => t('Please adjust the image cropping for optimum viewing.'),
			'$form_security_token' => get_form_security_token("profile_photo"),
			'$done' => t('Done Editing')
		));
		return $o;
	}

	return; // NOTREACHED
}

/* @brief Generate the UI for photo-cropping
 *
 * @param $a Current application
 * @param $ph Photo-Factory
 * @return void
 *
 */



function profile_photo_crop_ui_head(&$a, $ph, $hash, $smallest){

	$max_length = get_config('system','max_image_length');
	if(! $max_length)
		$max_length = MAX_IMAGE_LENGTH;
	if($max_length > 0)
		$ph->scaleImage($max_length);

	$width  = $ph->getWidth();
	$height = $ph->getHeight();

	if($width < 300 || $height < 300) {
		$ph->scaleImageUp(200);
		$width  = $ph->getWidth();
		$height = $ph->getHeight();
	}


	$a->data['imagecrop'] = $hash;
	$a->data['imagecrop_resolution'] = $smallest;
	$a->page['htmlhead'] .= replace_macros(get_markup_template("crophead.tpl"), array());
	return;
}

