<?php
/**
 * @file include/photos.php
 * @brief Functions related to photo handling.
 */

require_once('include/permissions.php');
require_once('include/items.php');
require_once('include/photo/photo_driver.php');
require_once('include/text.php');

/**
 * @brief
 *
 * @param array $channel
 * @param array $observer
 * @param array $args
 * @return array
 */
function photo_upload($channel, $observer, $args) {

	$ret = array('success' => false);
	$channel_id = $channel['channel_id'];
	$account_id = $channel['channel_account_id'];

	if(! perm_is_allowed($channel_id, $observer['xchan_hash'], 'write_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}


//call_hooks('photo_upload_begin', $args);

	/*
	 * Determine the album to use
	 */

	$album    = $args['album'];

	if(intval($args['visible']) || $args['visible'] === 'true')
		$visible = 1;
	else
		$visible = 0;

	$deliver = true;
	if(array_key_exists('deliver',$args))
		$deliver = intval($args['deliver']);

	// Set to default channel permissions. If the parent directory (album) has permissions set,
	// use those instead. If we have specific permissions supplied, they take precedence over
	// all other settings. 'allow_cid' being passed from an external source takes priority over channel settings.
	// ...messy... needs re-factoring once the photos/files integration stabilises

	$acl = new Zotlabs\Access\AccessList($channel);
	if(array_key_exists('directory',$args) && $args['directory'])
		$acl->set($args['directory']);
	if(array_key_exists('allow_cid',$args))
		$acl->set($args);
	if( (array_key_exists('group_allow',$args))
		|| (array_key_exists('contact_allow',$args))
		|| (array_key_exists('group_deny',$args))
		|| (array_key_exists('contact_deny',$args))) {
		$acl->set_from_array($args);
	}

	$ac = $acl->get();

	$os_storage = 0;

	if($args['os_path'] && $args['getimagesize']) {
		$imagedata = @file_get_contents($args['os_path']);
		$filename = $args['filename'];
		$filesize = strlen($imagedata);
		// this is going to be deleted if it exists
		$src = '/tmp/deletemenow';
		$type = $args['getimagesize']['mime'];
		$os_storage = 1;
	}
	elseif ($args['data'] || $args['content']) {

		// allow an import from a binary string representing the image.
		// This bypasses the upload step and max size limit checking

		$imagedata = (($args['content']) ? $args['content'] : $args['data']);
		$filename = $args['filename'];
		$filesize = strlen($imagedata);
		// this is going to be deleted if it exists
		$src = '/tmp/deletemenow';
		$type = (($args['mimetype']) ? $args['mimetype'] : $args['type']);
	} else {
		$f = array('src' => '', 'filename' => '', 'filesize' => 0, 'type' => '');

//		call_hooks('photo_upload_file',$f);

		if (x($f,'src') && x($f,'filesize')) {
			$src      = $f['src'];
			$filename = $f['filename'];
			$filesize = $f['filesize'];
			$type     = $f['type'];
		} else {
			$src      = $_FILES['userfile']['tmp_name'];
			$filename = basename($_FILES['userfile']['name']);
			$filesize = intval($_FILES['userfile']['size']);
			$type     = $_FILES['userfile']['type'];
		}

		if (! $type)
			$type=guess_image_type($filename);

		logger('photo_upload: received file: ' . $filename . ' as ' . $src . ' ('. $type . ') ' . $filesize . ' bytes', LOGGER_DEBUG);

		$maximagesize = get_config('system','maximagesize');

		if (($maximagesize) && ($filesize > $maximagesize)) {
			$ret['message'] =  sprintf ( t('Image exceeds website size limit of %lu bytes'), $maximagesize);
			@unlink($src);
			call_hooks('photo_upload_end',$ret);
			return $ret;
		}

		if (! $filesize) {
			$ret['message'] = t('Image file is empty.');
			@unlink($src);
			call_hooks('photo_post_end',$ret);
			return $ret;
		}

		logger('photo_upload: loading the contents of ' . $src , LOGGER_DEBUG);

		$imagedata = @file_get_contents($src);
	}

	$r = q("select sum(filesize) as total from photo where aid = %d and imgscale = 0 ",
		intval($account_id)
	);

	$limit = engr_units_to_bytes(service_class_fetch($channel_id,'photo_upload_limit'));

	if (($r) && ($limit !== false) && (($r[0]['total'] + strlen($imagedata)) > $limit)) {
		$ret['message'] = upgrade_message();
		@unlink($src);
		call_hooks('photo_post_end',$ret);
		return $ret;
	}

	$ph = photo_factory($imagedata, $type);

	if (! $ph->is_valid()) {
		$ret['message'] = t('Unable to process image');
		logger('photo_upload: unable to process image');
		@unlink($src);
		call_hooks('photo_upload_end',$ret);
		return $ret;
	}

	$exif = $ph->orient(($args['os_path']) ? $args['os_path'] : $src);

	@unlink($src);

	$max_length = get_config('system','max_image_length');
	if (! $max_length)
		$max_length = MAX_IMAGE_LENGTH;
	if ($max_length > 0)
		$ph->scaleImage($max_length);

	$width  = $ph->getWidth();
	$height = $ph->getHeight();

	$smallest = 0;

	$photo_hash = (($args['resource_id']) ? $args['resource_id'] : photo_new_resource());

	$visitor = '';
	if ($channel['channel_hash'] !== $observer['xchan_hash'])
		$visitor = $observer['xchan_hash'];

	$errors = false;

	$p = array('aid' => $account_id, 'uid' => $channel_id, 'xchan' => $visitor, 'resource_id' => $photo_hash,
		'filename' => $filename, 'album' => $album, 'imgscale' => 0, 'photo_usage' => PHOTO_NORMAL,
		'allow_cid' => $ac['allow_cid'], 'allow_gid' => $ac['allow_gid'],
		'deny_cid' => $ac['deny_cid'], 'deny_gid' => $ac['deny_gid'],
		'os_storage' => $os_storage, 'os_path' => $args['os_path']
	);
	if($args['created'])
		$p['created'] = $args['created'];
	if($args['edited'])
		$p['edited'] = $args['edited'];
	if($args['title'])
		$p['title'] = $args['title'];
	if($args['description'])
		$p['description'] = $args['description'];

	$link   = array();

	$r0 = $ph->save($p);
	$link[0] = array(
		'rel'  => 'alternate',
		'type' => 'text/html',
		'href' => z_root() . '/photo/' . $photo_hash . '-0.' . $ph->getExt(),
		'width' => $ph->getWidth(),
		'height' => $ph->getHeight()
	);
	if(! $r0)
		$errors = true;

	unset($p['os_storage']);
	unset($p['os_path']);

	if(($width > 1024 || $height > 1024) && (! $errors))
		$ph->scaleImage(1024);

	$p['imgscale'] = 1;
	$r1 = $ph->save($p);
	$link[1] = array(
		'rel'  => 'alternate',
		'type' => 'text/html',
		'href' => z_root() . '/photo/' . $photo_hash . '-1.' . $ph->getExt(),
		'width' => $ph->getWidth(),
		'height' => $ph->getHeight()
	);
	if(! $r1)
		$errors = true;

	if(($width > 640 || $height > 640) && (! $errors))
		$ph->scaleImage(640);

	$p['imgscale'] = 2;
	$r2 = $ph->save($p);
	$link[2] = array(
		'rel'  => 'alternate',
		'type' => 'text/html',
		'href' => z_root() . '/photo/' . $photo_hash . '-2.' . $ph->getExt(),
		'width' => $ph->getWidth(),
		'height' => $ph->getHeight()
	);
	if(! $r2)
		$errors = true;

	if(($width > 320 || $height > 320) && (! $errors))
		$ph->scaleImage(320);

	$p['imgscale'] = 3;
	$r3 = $ph->save($p);
	$link[3] = array(
		'rel'  => 'alternate',
		'type' => 'text/html',
		'href' => z_root() . '/photo/' . $photo_hash . '-3.' . $ph->getExt(),
		'width' => $ph->getWidth(),
		'height' => $ph->getHeight()
	);
	if(! $r3)
		$errors = true;

	if($errors) {
		q("delete from photo where resource_id = '%s' and uid = %d",
			dbesc($photo_hash),
			intval($channel_id)
		);
		$ret['message'] = t('Photo storage failed.');
		logger('photo_upload: photo store failed.');
		call_hooks('photo_upload_end',$ret);
		return $ret;
	}

	$item_hidden = (($visible) ? 0 : 1 );

	$lat = $lon = null;

	if($exif && $exif['GPS']) {
		if(feature_enabled($channel_id,'photo_location')) {
			$lat = getGps($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
			$lon = getGps($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);
		}
	}

	$title = (($args['description']) ? $args['description'] : $args['filename']);

	$large_photos = feature_enabled($channel['channel_id'], 'large_photos');

	linkify_tags($a, $args['body'], $channel_id);

	if($large_photos) {
		$scale = 1;
		$width = $link[1]['width'];
		$height = $link[1]['height'];
		$tag = (($r1) ? '[zmg=' . $width . 'x' . $height . ']' : '[zmg]');


	}
	else {
		$scale = 2;
		$width = $link[2]['width'];
		$height = $link[2]['height'];
		$tag = (($r2) ? '[zmg=' . $width . 'x' . $height . ']' : '[zmg]');
	}

	$author_link = '[zrl=' . z_root() . '/channel/' . $channel['channel_address'] . ']' . $channel['channel_name'] . '[/zrl]';

	$photo_link = '[zrl=' . z_root() . '/photos/' . $channel['channel_address'] . '/image/' . $photo_hash . ']' . t('a new photo') . '[/zrl]';

	$album_link = '[zrl=' . z_root() . '/photos/' . $channel['channel_address'] . '/album/' . bin2hex($album) . ']' . ((strlen($album)) ? $album : '/') . '[/zrl]';

	$activity_format = sprintf(t('%1$s posted %2$s to %3$s','photo_upload'), $author_link, $photo_link, $album_link);

	$summary = (($args['body']) ? $args['body'] : '') . '[footer]' . $activity_format . '[/footer]';

	$obj_body =  '[zrl=' . z_root() . '/photos/' . $channel['channel_address'] . '/image/' . $photo_hash . ']'
		. $tag . z_root() . "/photo/{$photo_hash}-{$scale}." . $ph->getExt() . '[/zmg]'
		. '[/zrl]';

	// Create item object
	$object = array(
		'type'    => ACTIVITY_OBJ_PHOTO,
		'title'   => $title,
		'created' => $p['created'],
		'edited'  => $p['edited'],
		'id'      => z_root() . '/photos/' . $channel['channel_address'] . '/image/' . $photo_hash,
		'link'    => $link,
		'body'    => $obj_body
	);

	$target = array(
		'type'    => ACTIVITY_OBJ_ALBUM,
		'title'   => (($album) ? $album : '/'),
		'id'      => z_root() . '/photos/' . $channel['channel_address'] . '/album/' . bin2hex($album)
	);

	// Create item container
	if($args['item']) {
		foreach($args['item'] as $i) {

			$item = get_item_elements($i);
			$force = false;

			if($item['mid'] === $item['parent_mid']) {

				$item['body'] = $summary;
				$item['obj_type'] = ACTIVITY_OBJ_PHOTO;
				$item['obj']	= json_encode($object);

				$item['tgt_type'] = ACTIVITY_OBJ_ALBUM;
				$item['target']	= json_encode($target);

				if($item['author_xchan'] === $channel['channel_hash']) {
					$item['sig'] = base64url_encode(rsa_sign($item['body'],$channel['channel_prvkey']));
					$item['item_verified']  = 1;
				}
				else {
					$item['sig'] = '';
				}
				$force = true;

			}
			$r = q("select id, edited from item where mid = '%s' and uid = %d limit 1",
				dbesc($item['mid']),
				intval($channel['channel_id'])
			);
			if($r) {
				if(($item['edited'] > $r[0]['edited']) || $force) {
					$item['id'] = $r[0]['id'];
					$item['uid'] = $channel['channel_id'];
					item_store_update($item,false,$deliver);
					continue;
				}
			}
			else {
				$item['aid'] = $channel['channel_account_id'];
				$item['uid'] = $channel['channel_id'];
				$item_result = item_store($item,false,$deliver);
			}
		}
	}
	else {
		$mid = item_message_id();

		$arr = array();

		if($lat && $lon)
			$arr['coord'] = $lat . ' ' . $lon;

		$arr['aid']             = $account_id;
		$arr['uid']             = $channel_id;
		$arr['mid']             = $mid;
		$arr['parent_mid']      = $mid;
		$arr['item_hidden']     = $item_hidden;
		$arr['resource_type']   = 'photo';
		$arr['resource_id']     = $photo_hash;
		$arr['owner_xchan']     = $channel['channel_hash'];
		$arr['author_xchan']    = $observer['xchan_hash'];
		$arr['title']           = $title;
		$arr['allow_cid']       = $ac['allow_cid'];
		$arr['allow_gid']       = $ac['allow_gid'];
		$arr['deny_cid']        = $ac['deny_cid'];
		$arr['deny_gid']        = $ac['deny_gid'];
		$arr['verb']            = ACTIVITY_POST;
		$arr['obj_type']	    = ACTIVITY_OBJ_PHOTO;
		$arr['obj']		        = json_encode($object);
		$arr['tgt_type']        = ACTIVITY_OBJ_ALBUM;
		$arr['target']	        = json_encode($target);
		$arr['item_wall']       = 1;
		$arr['item_origin']     = 1;
		$arr['item_thread_top'] = 1;
		$arr['item_private']    = intval($acl->is_private());
		$arr['plink']           = z_root() . '/channel/' . $channel['channel_address'] . '/?f=&mid=' . $arr['mid'];
		$arr['body']		= $summary;


		// this one is tricky because the item and the photo have the same permissions, those of the photo.
		// Use the channel read_stream permissions to get the correct public_policy for the item and recalculate the
		// private flag accordingly. This may cause subtle bugs due to custom permissions roles. We want to use
		// public policy when federating items to other sites, but should probably ignore them when accessing the item
		// in the photos pages - using the photos permissions instead. We need the public policy to keep the photo
		// linked item from leaking into the feed when somebody has a channel with read_stream restrictions.

		$arr['public_policy']   = map_scope(\Zotlabs\Access\PermissionLimits::Get($channel['channel_id'],'view_stream'),true);
		if($arr['public_policy'])
			$arr['item_private'] = 1;



		$result = item_store($arr,false,$deliver);
		$item_id = $result['item_id'];

		if($visible && $deliver)
			Zotlabs\Daemon\Master::Summon(array('Notifier', 'wall-new', $item_id));
	}

	$ret['success'] = true;
	$ret['item'] = $arr;
	$ret['body'] = $obj_body;
	$ret['resource_id'] = $photo_hash;
	$ret['photoitem_id'] = $item_id;

	call_hooks('photo_upload_end',$ret);

	return $ret;
}

/**
 * @brief Returns a list with all photo albums observer is allowed to see.
 *
 * Returns an associative array with all albums where observer has permissions.
 *
 * @param array $channel
 * @param array $observer
 * @param array $sort_key (optional) default album
 * @param array $direction (optional) default asc
 *
 * @return bool|array false if no view_storage permission or an array
 *   * \e boolean \b success
 *   * \e array \b albums
 */
function photos_albums_list($channel, $observer, $sort_key = 'album', $direction = 'asc') {

	$channel_id     = $channel['channel_id'];
	$observer_xchan = (($observer) ? $observer['xchan_hash'] : '');

	if(! perm_is_allowed($channel_id, $observer_xchan, 'view_storage'))
		return false;

	$sql_extra = permissions_sql($channel_id,$observer_xchan);

	$sort_key = dbesc($sort_key);
	$direction = dbesc($direction);

	$albums = q("SELECT count( distinct resource_id ) as total, album from photo where uid = %d and photo_usage IN ( %d, %d ) $sql_extra group by album order by $sort_key $direction",
		intval($channel_id),
		intval(PHOTO_NORMAL),
		intval(PHOTO_PROFILE)
	);

	// add various encodings to the array so we can just loop through and pick them out in a template

	$ret = array('success' => false);

	if($albums) {
		$ret['success'] = true;
		$ret['albums'] = array();
		foreach($albums as $k => $album) {
			$entry = array(
				'text' => (($album['album']) ? $album['album'] : '/'),
				'total' => $album['total'],
				'url' => z_root() . '/photos/' . $channel['channel_address'] . '/album/' . bin2hex($album['album']),
				'urlencode' => urlencode($album['album']),
				'bin2hex' => bin2hex($album['album'])
			);
			$ret['albums'][] = $entry;
		}
	}

	return $ret;
}

function photos_album_widget($channelx,$observer,$sortkey = 'album',$direction = 'asc') {

	$o = '';

	if(array_key_exists('albums', App::$data))
		$albums = App::$data['albums'];
	else
		$albums = photos_albums_list($channelx,$observer,$sortkey,$direction);

	if($albums['success']) {
		$o = replace_macros(get_markup_template('photo_albums.tpl'),array(
			'$nick'    => $channelx['channel_address'],
			'$title'   => t('Photo Albums'),
			'$albums'  => $albums['albums'],
			'$baseurl' => z_root(),
			'$upload'  => ((perm_is_allowed($channelx['channel_id'],(($observer) ? $observer['xchan_hash'] : ''),'write_storage'))
				? t('Upload New Photos') : '')
		));
	}

	return $o;
}

/**
 * @brief
 *
 * @param array $channel
 * @param array $observer
 * @param string $album default empty
 * @return boolean|array
 */
function photos_list_photos($channel, $observer, $album = '') {

	$channel_id     = $channel['channel_id'];
	$observer_xchan = (($observer) ? $observer['xchan_hash'] : '');

	if(! perm_is_allowed($channel_id,$observer_xchan,'view_storage'))
		return false;

	$sql_extra = permissions_sql($channel_id);

	if($album)
		$sql_extra .= " and album = '" . protect_sprintf(dbesc($album)) . "' ";

	$ret = array('success' => false);

	$r = q("select resource_id, created, edited, title, description, album, filename, mimetype, height, width, filesize, imgscale, photo_usage, allow_cid, allow_gid, deny_cid, deny_gid from photo where uid = %d and photo_usage in ( %d, %d ) $sql_extra ",
		intval($channel_id),
		intval(PHOTO_NORMAL),
		intval(PHOTO_PROFILE)
	);

	if($r) {
		for($x = 0; $x < count($r); $x ++) {
			$r[$x]['src'] = z_root() . '/photo/' . $r[$x]['resource_id'] . '-' . $r[$x]['imgscale'];
		}
		$ret['success'] = true;
		$ret['photos'] = $r;
	}

	return $ret;
}

/**
 * @brief Check if given photo album exists in channel.
 *
 * @param int $channel_id id of the channel
 * @param string $album name of the album
 * @return boolean
 */
function photos_album_exists($channel_id, $album) {
	$r = q("SELECT id FROM photo WHERE album = '%s' AND uid = %d limit 1",
		dbesc($album),
		intval($channel_id)
	);

	return (($r) ? true : false);
}

/**
 * @brief Renames a photo album in a channel.
 *
 * @todo Do we need to check if new album name already exists?
 *
 * @param int $channel_id id of the channel
 * @param string $oldname The name of the album to rename
 * @param string $newname The new name of the album
 * @return bool|array
 */
function photos_album_rename($channel_id, $oldname, $newname) {
	return q("UPDATE photo SET album = '%s' WHERE album = '%s' AND uid = %d",
		dbesc($newname),
		dbesc($oldname),
		intval($channel_id)
	);
}



/**
 * @brief
 *
 * @param int $channel_id
 * @param string $album
 * @param string $remote_xchan
 * @return string|boolean
 */
function photos_album_get_db_idstr($channel_id, $album, $remote_xchan = '') {

	if ($remote_xchan) {
		$r = q("SELECT distinct resource_id from photo where xchan = '%s' and uid = %d and album = '%s' ",
			dbesc($remote_xchan),
			intval($channel_id),
			dbesc($album)
		);
	} else {
		$r = q("SELECT distinct resource_id from photo where uid = %d and album = '%s' ",
			intval($channel_id),
			dbesc($album)
		);
	}
	if ($r) {
		$arr = array();
		foreach ($r as $rr) {
			$arr[] = "'" . dbesc($rr['resource_id']) . "'" ;
		}
		$str = implode(',',$arr);
		return $str;
	}

	return false;
}

/**
 * @brief Creates a new photo item.
 *
 * @param array $channel
 * @param string $creator_hash
 * @param array $photo
 * @param boolean $visible default false
 * @return int item_id
 */
function photos_create_item($channel, $creator_hash, $photo, $visible = false) {

	// Create item container


	$item_hidden = (($visible) ? 0 : 1 );

	$mid = item_message_id();

	$arr = array();

	$arr['aid']             = $channel['channel_account_id'];
	$arr['uid']             = $channel['channel_id'];
	$arr['mid']             = $mid;
	$arr['parent_mid']      = $mid;
	$arr['item_wall']       = 1;
	$arr['item_origin']     = 1;
	$arr['item_thread_top'] = 1;
	$arr['item_hidden']     = $item_hidden;
	$arr['resource_type']   = 'photo';
	$arr['resource_id']     = $photo['resource_id'];
	$arr['owner_xchan']     = $channel['channel_hash'];
	$arr['author_xchan']    = $creator_hash;

	$arr['allow_cid']       = $photo['allow_cid'];
	$arr['allow_gid']       = $photo['allow_gid'];
	$arr['deny_cid']        = $photo['deny_cid'];
	$arr['deny_gid']        = $photo['deny_gid'];

	$arr['plink']           = z_root() . '/channel/' . $channel['channel_address'] . '/?f=&mid=' . $arr['mid'];

	$arr['body']            = '[zrl=' . z_root() . '/photos/' . $channel['channel_address'] . '/image/' . $photo['resource_id'] . ']'
		. '[zmg]' . z_root() . '/photo/' . $photo['resource_id'] . '-' . $photo['imgscale'] . '[/zmg]'
		. '[/zrl]';

	$result = item_store($arr);
	$item_id = $result['item_id'];

	return $item_id;
}


function getGps($exifCoord, $hemi) {

    $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

    return floatval($flip * ($degrees + ($minutes / 60) + ($seconds / 3600)));
}

function getGpstimestamp($exifCoord) {

    $hours = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

	return sprintf('%02d:%02d:%02d',$hours,$minutes,$seconds);
}


function gps2Num($coordPart) {

    $parts = explode('/', $coordPart);

    if (count($parts) <= 0)
        return 0;

    if (count($parts) == 1)
        return $parts[0];

    return floatval($parts[0]) / floatval($parts[1]);
}

function profile_photo_set_profile_perms($uid, $profileid = 0) {

		$allowcid = '';
		if($profileid) {
			$r = q("SELECT photo, profile_guid, id, is_default, uid
				FROM profile WHERE uid = %d and ( profile.id = %d OR profile.profile_guid = '%s') LIMIT 1",
				intval($uid),
				intval($profileid),
				dbesc($profileid)
			);
		}
		else {
			logger('Resetting permissions on default-profile-photo for user'.local_channel());

			$r = q("SELECT photo, profile_guid, id, is_default, uid  FROM profile
				WHERE profile.uid = %d AND is_default = 1 LIMIT 1",
				intval($uid)
			); //If no profile is given, we update the default profile
		}
		if(! $r)
			return;

		$profile = $r[0];

		if($profile['id'] && $profile['photo']) {
	      	preg_match("@\w*(?=-\d*$)@i", $profile['photo'], $resource_id);
	       	$resource_id = $resource_id[0];

			if (! intval($profile['is_default'])) {
				$r0 = q("SELECT channel_hash FROM channel WHERE channel_id = %d LIMIT 1",
					intval($uid)
				);
				//Should not be needed in future. Catches old int-profile-ids.
				$r1 = q("SELECT abook.abook_xchan FROM abook WHERE abook_profile = '%d' ",
					intval($profile['id'])
				);
				$r2 = q("SELECT abook.abook_xchan FROM abook WHERE abook_profile = '%s'",
					dbesc($profile['profile_guid'])
				);
				$allowcid = "<" . $r0[0]['channel_hash'] . ">";
				foreach ($r1 as $entry) {
					$allowcid .= "<" . $entry['abook_xchan'] . ">";
				}
				foreach ($r2 as $entry) {
					$allowcid .= "<" . $entry['abook_xchan'] . ">";
				}

				q("UPDATE photo SET allow_cid = '%s' WHERE resource_id = '%s' AND uid = %d",
					dbesc($allowcid),
					dbesc($resource_id),
					intval($uid)
				);

			}
			else {
				//Reset permissions on default profile picture to public
				q("UPDATE photo SET allow_cid = '' WHERE photo_usage = %d AND uid = %d",
					intval(PHOTO_PROFILE),
					intval($uid)
				);
			}
		}

		return;
	}
