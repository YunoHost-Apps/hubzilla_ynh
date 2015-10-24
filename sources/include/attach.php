<?php
/**
 * @file include/attach.php
 *
 * @brief File/attach API with the potential for revision control.
 *
 * @TODO A filesystem storage abstraction which maintains security (and 'data'
 * contains a system filename which is inaccessible from the web). This could
 * get around PHP storage limits and store videos and larger items, using fread
 * or OS methods or native code to read/write or chunk it through.
 * @todo Also an 'append' option to the storage function might be a useful addition.
 */

require_once('include/permissions.php');
require_once('include/security.php');

/**
 * @brief Guess the mimetype from file ending.
 *
 * This function takes a file name and guess the mimetype from the
 * filename extension.
 *
 * @param $filename a string filename
 * @return string The mimetype according to a file ending.
 */
function z_mime_content_type($filename) {

	$mime_types = array(

	'txt' => 'text/plain',
	'htm' => 'text/html',
	'html' => 'text/html',
	'php' => 'text/html',
	'css' => 'text/css',
	'js' => 'application/javascript',
	'json' => 'application/json',
	'xml' => 'application/xml',
	'swf' => 'application/x-shockwave-flash',
	'flv' => 'video/x-flv',
	'epub' => 'application/epub+zip',

	// images
	'png' => 'image/png',
	'jpe' => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'jpg' => 'image/jpeg',
	'gif' => 'image/gif',
	'bmp' => 'image/bmp',
	'ico' => 'image/vnd.microsoft.icon',
	'tiff' => 'image/tiff',
	'tif' => 'image/tiff',
	'svg' => 'image/svg+xml',
	'svgz' => 'image/svg+xml',

	// archives
	'zip' => 'application/zip',
	'rar' => 'application/x-rar-compressed',
	'exe' => 'application/x-msdownload',
	'msi' => 'application/x-msdownload',
	'cab' => 'application/vnd.ms-cab-compressed',

	// audio/video
	'mp3' => 'audio/mpeg',
	'wav' => 'audio/wav',
	'qt' => 'video/quicktime',
	'mov' => 'video/quicktime',
	'ogg' => 'application/ogg',
	'opus' => 'audio/ogg',
	'webm' => 'video/webm',
//	'webm' => 'audio/webm',
	'mp4' => 'video/mp4',
//	'mp4' => 'audio/mp4',

	// adobe
	'pdf' => 'application/pdf',
	'psd' => 'image/vnd.adobe.photoshop',
	'ai' => 'application/postscript',
	'eps' => 'application/postscript',
	'ps' => 'application/postscript',

	// ms office
	'doc' => 'application/msword',
	'rtf' => 'application/rtf',
	'xls' => 'application/vnd.ms-excel',
	'ppt' => 'application/vnd.ms-powerpoint',

	// open office
	'odt' => 'application/vnd.oasis.opendocument.text',
	'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
	'odp' => 'application/vnd.oasis.opendocument.presentation',
	'odg' => 'application/vnd.oasis.opendocument.graphics',
	'odc' => 'application/vnd.oasis.opendocument.chart',
	'odf' => 'application/vnd.oasis.opendocument.formula',
	'odi' => 'application/vnd.oasis.opendocument.image',
	'odm' => 'application/vnd.oasis.opendocument.text-master',
	'odb' => 'application/vnd.oasis.opendocument.base',
	'odb' => 'application/vnd.oasis.opendocument.database',
	'ott' => 'application/vnd.oasis.opendocument.text-template',
	'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
	'otp' => 'application/vnd.oasis.opendocument.presentation-template',
	'otg' => 'application/vnd.oasis.opendocument.graphics-template',
	'otc' => 'application/vnd.oasis.opendocument.chart-template',
	'otf' => 'application/vnd.oasis.opendocument.formula-template',
	'oti' => 'application/vnd.oasis.opendocument.image-template',
	'oth' => 'application/vnd.oasis.opendocument.text-web'
	);

	$dot = strpos($filename, '.');
	if ($dot !== false) {
		$ext = strtolower(substr($filename, $dot + 1));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		}
	}

	return 'application/octet-stream';
}

/**
 * @brief Count files/attachments.
 *
 * @param int $channel_id
 * @param string $observer
 * @param string $hash (optional)
 * @param string $filename (optional)
 * @param string $filetype (optional)
 * @return associative array with:
 *  * \e boolean \b success
 *  * \e int|boolean \b results amount of found results, or false
 *  * \e string \b message with error messages if any
 */
function attach_count_files($channel_id, $observer, $hash = '', $filename = '', $filetype = '') {

	$ret = array('success' => false);

	if(! perm_is_allowed($channel_id, $observer, 'read_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	require_once('include/security.php');
	$sql_extra = permissions_sql($channel_id);

	if($hash)
		$sql_extra .= protect_sprintf(" and hash = '" . dbesc($hash) . "' ");

	if($filename)
		$sql_extra .= protect_sprintf(" and filename like '@" . dbesc($filename) . "@' ");

	if($filetype)
		$sql_extra .= protect_sprintf(" and filetype like '@" . dbesc($filetype) . "@' ");

	$r = q("select id, uid, folder from attach where uid = %d $sql_extra",
		intval($channel_id)
	);


	$ret['success'] = ((is_array($r)) ? true : false);
	$ret['results'] = ((is_array($r)) ? count($r) : false);

	return $ret;
}

/**
 * @brief Returns a list of files/attachments.
 *
 * @param $channel_id
 * @param $observer
 * @param $hash (optional)
 * @param $filename (optional)
 * @param $filetype (optional)
 * @param $orderby
 * @param $start
 * @param $entries
 * @return associative array with:
 *  * \e boolean \b success
 *  * \e array|boolean \b results array with results, or false
 *  * \e string \b message with error messages if any
 */
function attach_list_files($channel_id, $observer, $hash = '', $filename = '', $filetype = '', $orderby = 'created desc', $start = 0, $entries = 0) {

	$ret = array('success' => false);

	if(! perm_is_allowed($channel_id,$observer, 'view_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	require_once('include/security.php');
	$sql_extra = permissions_sql($channel_id);

	if($hash)
		$sql_extra .= protect_sprintf(" and hash = '" . dbesc($hash) . "' ");

	if($filename)
		$sql_extra .= protect_sprintf(" and filename like '@" . dbesc($filename) . "@' ");

	if($filetype)
		$sql_extra .= protect_sprintf(" and filetype like '@" . dbesc($filetype) . "@' ");

	if($entries)
		$limit = " limit " . intval($start) . ", " . intval(entries) . " ";

	// Retrieve all columns except 'data'

	$r = q("select id, aid, uid, hash, filename, filetype, filesize, revision, folder, os_storage, is_dir, is_photo, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where uid = %d $sql_extra ORDER BY $orderby $limit",
		intval($channel_id)
	);

	$ret['success'] = ((is_array($r)) ? true : false);
	$ret['results'] = ((is_array($r)) ? $r : false);

	return $ret;
}

/**
 * @brief Find an attachment by hash and revision.
 *
 * Returns the entire attach structure including data.
 *
 * This could exhaust memory so most useful only when immediately sending the data.
 *
 * @param string $hash
 * @param int $rev Revision
 * @return array
 */
function attach_by_hash($hash, $rev = 0) {

	$ret = array('success' => false);

	// Check for existence, which will also provide us the owner uid

	$sql_extra = '';
	if($rev == (-1))
		$sql_extra = " order by revision desc ";
	elseif($rev)
		$sql_extra = " and revision = " . intval($rev) . " ";

	$r = q("SELECT uid FROM attach WHERE hash = '%s' $sql_extra LIMIT 1",
		dbesc($hash)
	);
	if(! $r) {
		$ret['message'] = t('Item was not found.');
		return $ret;
	}

	if(! perm_is_allowed($r[0]['uid'], get_observer_hash(), 'view_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	$sql_extra = permissions_sql($r[0]['uid']);

	// Now we'll see if we can access the attachment

	$r = q("SELECT * FROM attach WHERE hash = '%s' and uid = %d $sql_extra LIMIT 1",
		dbesc($hash),
		intval($r[0]['uid'])
	);

	if(! $r) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	if($r[0]['folder']) {
		$x = attach_can_view_folder($r[0]['uid'],get_observer_hash(),$r[0]['folder']);
		if(! $x) {
			$ret['message'] = t('Permission denied.');
			return $ret;
		}
	}

	$ret['success'] = true;
	$ret['data'] = $r[0];

	return $ret;
}

function attach_can_view_folder($uid,$ob_hash,$folder_hash) {

	$sql_extra = permissions_sql($uid,$ob_hash);
	$hash = $folder_hash;	
	$result = false;

	do {
		$r = q("select folder from attach where hash = '%s' and uid = %d $sql_extra",
			dbesc($hash),
			intval($uid)
		);
		if(! $r)
			return false;
		$hash = $r[0]['folder'];
	}
	while($hash);
	return true;
}


/**
 * @brief Find an attachment by hash and revision.
 *
 * Returns the entire attach structure excluding data.
 *
 * @see attach_by_hash()
 * @param $hash
 * @param $rev revision default 0
 * @return associative array with everything except data
 *  * \e boolean \b success boolean true or false
 *  * \e string \b message (optional) only when success is false
 *  * \e array \b data array of attach DB entry without data component
 */
function attach_by_hash_nodata($hash, $rev = 0) {

	$ret = array('success' => false);

	// Check for existence, which will also provide us the owner uid

	$sql_extra = '';
	if($rev == (-1))
		$sql_extra = " order by revision desc ";
	elseif($rev)
		$sql_extra = " and revision = " . intval($rev) . " ";

	$r = q("SELECT uid FROM attach WHERE hash = '%s' $sql_extra LIMIT 1",
		dbesc($hash)
	);
	if(! $r) {
		$ret['message'] = t('Item was not found.');
		return $ret;
	}

	if(! perm_is_allowed($r[0]['uid'],get_observer_hash(),'view_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	$sql_extra = permissions_sql($r[0]['uid']);

	// Now we'll see if we can access the attachment

	$r = q("select id, aid, uid, hash, creator, filename, filetype, filesize, revision, folder, os_storage, is_photo, is_dir, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where uid = %d and hash = '%s' $sql_extra limit 1",
		intval($r[0]['uid']),
		dbesc($hash)
	);

	if(! $r) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	if($r[0]['folder']) {
		$x = attach_can_view_folder($r[0]['uid'],get_observer_hash(),$r[0]['folder']);
		if(! $x) {
			$ret['message'] = t('Permission denied.');
			return $ret;
		}
	}


	$ret['success'] = true;
	$ret['data'] = $r[0];

	return $ret;
}

/**
 * @brief Stores an attachment from a POST file upload.
 *
 * This function stores an attachment. It can be a new one, a replacement or a
 * new revision depending on value set in \e $options.
 *
 * @note Requires an input field \e userfile and does not accept multiple files
 * in one request.
 *
 * @param array $channel channel array of owner
 * @param string $observer_hash hash of current observer
 * @param string $options (optional) one of update, replace, revision
 * @param array $arr (optional) associative array
 */

/**
 * A lot going on in this function, and some of it is old cruft and some is new cruft
 * and the entire thing probably needs to be refactored. It started out just storing
 * files, before we had DAV. It was made extensible to do extra stuff like edit an 
 * existing file or optionally store a separate revision using $options to choose between different
 * storage models. Along the way we moved from
 * DB data storage to file system storage. 
 * Then DAV came along and used different upload methods depending on whether the 
 * file was stored as a DAV directory object or updated as a file object. One of these 
 * is essentially an update and the other is basically an upload, but doesn't use the traditional PHP
 * upload workflow. 
 * Then came hubzilla and we tried to merge photo functionality with the file storage. Most of
 * that integration occurs within this function. 
 * This required overlap with the old photo_upload stuff and photo albums were
 * completely different concepts from directories which needed to be reconciled somehow.
 * The old revision stuff is kind of orphaned currently. There's new revision stuff for photos
 * which attaches (2) etc. onto the name, but doesn't integrate with the attach table revisioning.
 * That's where it sits currently. I repeat it needs to be refactored, and this note is here
 * for future explorers and those who may be doing that work to understand where it came
 * from and got to be the monstrosity of tangled unrelated code that it currently is.
 */

function attach_store($channel, $observer_hash, $options = '', $arr = null) {

	require_once('include/photos.php');

	call_hooks('photo_upload_begin',$arr);

	$ret = array('success' => false);
	$channel_id = $channel['channel_id'];
	$sql_options = '';
	$source = (($arr) ? $arr['source'] : '');
	$album = (($arr) ? $arr['album'] : '');
	$newalbum = (($arr) ? $arr['newalbum'] : '');
	$hash = (($arr && $arr['hash']) ? $arr['hash'] : null);
	$upload_path = (($arr && $arr['directory']) ? $arr['directory'] : '');
	$visible = (($arr && $arr['visible']) ? $arr['visible'] : '');

	$observer = array();

	if($observer_hash) {
		$x = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($observer_hash)
		);
		if($x)
			$observer = $x[0];
	}

	logger('arr: ' . print_r($arr,true));

	if(! perm_is_allowed($channel_id,$observer_hash, 'write_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	$str_group_allow   = perms2str($arr['group_allow']); 
	$str_contact_allow = perms2str($arr['contact_allow']);
	$str_group_deny    = perms2str($arr['group_deny']);
	$str_contact_deny  = perms2str($arr['contact_deny']);


	// The 'update' option sets db values without uploading a new attachment
	// 'replace' replaces the existing uploaded data
	// 'revision' creates a new revision with new upload data
	// Default is to upload a new file

	// revise or update must provide $arr['hash'] of the thing to revise/update

	// By default remove $src when finished

	$remove_when_processed = true;

	if($options === 'import') {		
		$src      = $arr['src'];
		$filename = $arr['filename'];
		$filesize = @filesize($src);

		$hash     = $arr['resource_id'];

		if(array_key_exists('hash',$arr))
			$hash = $arr['hash'];
		if(array_key_exists('type',$arr))
			$type = $arr['type'];

		if($arr['preserve_original'])
			$remove_when_processed = false;

		// if importing a directory, just do it now and go home - we're done.

		if(array_key_exists('is_dir',$arr) && intval($arr['is_dir'])) {
			$x = attach_mkdir($channel,$observer_hash,$arr);
			if($x['message'])
				logger('import_directory: ' . $x['message']);
			return;
		}
	}
	elseif($options !== 'update') {
		$f = array('src' => '', 'filename' => '', 'filesize' => 0, 'type' => '');

        call_hooks('photo_upload_file',$f);
		call_hooks('attach_upload_file',$f);

        if (x($f,'src') && x($f,'filesize')) {
            $src      = $f['src'];
            $filename = $f['filename'];
            $filesize = $f['filesize'];
            $type     = $f['type'];

        } else {

			if(! x($_FILES,'userfile')) {
				$ret['message'] = t('No source file.');
				return $ret;
			}

			$src      = $_FILES['userfile']['tmp_name'];
			$filename = basename($_FILES['userfile']['name']);
			$filesize = intval($_FILES['userfile']['size']);
		}
	}

	$existing_size = 0;

	if($options === 'replace') {
		$x = q("select id, hash, filesize from attach where id = %d and uid = %d limit 1",
			intval($arr['id']),
			intval($channel_id)
		);
		if(! $x) {
			$ret['message'] = t('Cannot locate file to replace');
			return $ret;
		}
		$existing_id = $x[0]['id'];
		$existing_size = intval($x[0]['filesize']);
		$hash = $x[0]['hash'];
	}

	if($options === 'revise' || $options === 'update') {
		$sql_options = " order by revision desc ";
		if($options === 'update' &&  $arr && array_key_exists('revision',$arr))
			$sql_options = " and revision = " . intval($arr['revision']) . " ";

		$x = q("select id, aid, uid, filename, filetype, filesize, hash, revision, folder, os_storage, is_photo, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where hash = '%s' and uid = %d $sql_options limit 1",
			dbesc($arr['hash']),
			intval($channel_id)
		);
		if(! $x) {
			$ret['message'] = t('Cannot locate file to revise/update');
			return $ret;
		}
		$hash = $x[0]['hash'];
	}



	$def_extension = '';
	$is_photo = 0;
	$gis = @getimagesize($src);
	logger('getimagesize: ' . print_r($gis,true), LOGGER_DATA); 
	if(($gis) && ($gis[2] === IMAGETYPE_GIF || $gis[2] === IMAGETYPE_JPEG || $gis[2] === IMAGETYPE_PNG)) {
		$is_photo = 1;
		if($gis[2] === IMAGETYPE_GIF)
			$def_extension =  '.gif';
		if($gis[2] === IMAGETYPE_JPEG)
			$def_extension =  '.jpg';
		if($gis[2] === IMAGETYPE_PNG)
			$def_extension =  '.png';

	}

	$pathname = '';

	if($is_photo) {
		if($newalbum) {
			$pathname = filepath_macro($newalbum);
		}
		elseif(array_key_exists('folder',$arr)) {
			$x = q("select filename from attach where hash = '%s' and uid = %d limit 1",
				dbesc($arr['folder']),
				intval($channel['channel_id'])
			);
			if($x)
				$pathname = $x[0]['filename'];
		}
		else {
			$pathname = filepath_macro($album);
		}
	}
	else {
		$pathname = filepath_macro($upload_path);
	}

	$darr = array('pathname' => $pathname);

	// if we need to create a directory, use the channel default permissions.

	$darr['allow_cid'] = $channel['allow_cid'];
	$darr['allow_gid'] = $channel['allow_gid'];
	$darr['deny_cid']  = $channel['deny_cid'];
	$darr['deny_gid']  = $channel['deny_gid'];


	$direct = null;

	if($pathname) {
		$x = attach_mkdirp($channel, $observer_hash, $darr);
		$folder_hash = (($x['success']) ? $x['data']['hash'] : '');
		$direct = (($x['success']) ? $x['data'] : null);
		if((! $str_contact_allow) && (! $str_group_allow) && (! $str_contact_deny) && (! $str_group_deny)) {
			$str_contact_allow = $x['data']['allow_cid'];
			$str_group_allow = $x['data']['allow_gid'];
			$str_contact_deny = $x['data']['deny_cid'];
			$str_group_deny = $x['data']['deny_gid'];
		}
	}
	else {
		$folder_hash = ((($arr) && array_key_exists('folder',$arr)) ? $arr['folder'] : '');
	}		

	if((! $options) || ($options === 'import')) {

		// A freshly uploaded file. Check for duplicate and resolve with the channel's overwrite settings.

		$r = q("select filename, id, hash, filesize from attach where filename = '%s' and folder = '%s' ",
			dbesc($filename),
			dbesc($folder_hash)
		);
		if($r) {
			$overwrite = get_pconfig($channel_id,'system','overwrite_dup_files');
			if($overwrite) {
				$options = 'replace';
				$existing_id = $x[0]['id'];
				$existing_size = intval($x[0]['filesize']);
				$hash = $x[0]['hash'];
			}
			else {
				if(strpos($filename,'.') !== false) {
					$basename = substr($filename,0,strrpos($filename,'.'));
					$ext = substr($filename,strrpos($filename,'.'));
				}
				else {
					$basename = $filename;
					$ext = $def_extension;
				}

				$r = q("select filename from attach where ( filename = '%s' OR filename like '%s' ) and folder = '%s' ",
					dbesc($basename . $ext),
					dbesc($basename . '(%)' . $ext),
					dbesc($folder_hash)
				);

				if($r) {
					$x = 1;

					do {
						$found = false;
						foreach($r as $rr) {
							if($rr['filename'] === $basename . '(' . $x . ')' . $ext) {
								$found = true;
								break;
							}
						}
						if($found)
							$x++;
					}			
					while($found);
					$filename = $basename . '(' . $x . ')' . $ext;
				}
				else
					$filename = $basename . $ext;
			}
		}
	}

	if(! $hash)
		$hash = random_string();

	// Check storage limits
	if($options !== 'update') {
		$maxfilesize = get_config('system','maxfilesize');

		if(($maxfilesize) && ($filesize > $maxfilesize)) {
			$ret['message'] = sprintf( t('File exceeds size limit of %d'), $maxfilesize);
			if($remove_when_processed)
				@unlink($src);
			call_hooks('photo_upload_end',$ret);
			return $ret;
		}

		$limit = service_class_fetch($channel_id, 'attach_upload_limit');

		if($limit !== false) {
			$r = q("select sum(filesize) as total from attach where aid = %d ",
				intval($channel['channel_account_id'])
			);
			if(($r) &&  (($r[0]['total'] + $filesize) > ($limit - $existing_size))) {
				$ret['message'] = upgrade_message(true) . sprintf(t("You have reached your limit of %1$.0f Mbytes attachment storage."), $limit / 1024000);
				if($remove_when_processed)
					@unlink($src);

				call_hooks('photo_upload_end',$ret);
				return $ret;
			}
		}
		$mimetype = ((isset($type) && $type) ? $type : z_mime_content_type($filename));
	}

	$os_basepath = 'store/' . $channel['channel_address'] . '/' ;
	$os_relpath = '';

	if($folder_hash) {
		$curr = find_folder_hash_by_attach_hash($channel_id,$folder_hash,true);
		if($curr) 
			$os_relpath .= $curr . '/';
		$os_relpath .= $folder_hash . '/';
	}

	$os_relpath .= $hash;

	if($src)
		@file_put_contents($os_basepath . $os_relpath,@file_get_contents($src));

	if(array_key_exists('created', $arr))
		$created = $arr['created'];
	else
		$created = datetime_convert();

	if(array_key_exists('edited', $arr))
		$edited = $arr['edited'];
	else
		$edited = $created;

	if($options === 'replace') {
		$r = q("update attach set filename = '%s', filetype = '%s', folder = '%s', filesize = %d, os_storage = %d, is_photo = %d, data = '%s', edited = '%s' where id = %d and uid = %d",
			dbesc($filename),
			dbesc($mimetype),
			dbesc($folder_hash),
			intval($filesize),
			intval(1),
			intval($is_photo),
			dbesc($os_relpath),
			dbesc($created),
			intval($existing_id),
			intval($channel_id)
		);
	}
	elseif($options === 'revise') {
		$r = q("insert into attach ( aid, uid, hash, creator, filename, filetype, folder, filesize, revision, os_storage, is_photo, data, created, edited, allow_cid, allow_gid, deny_cid, deny_gid )
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
			intval($x[0]['aid']),
			intval($channel_id),
			dbesc($x[0]['hash']),
			dbesc($observer_hash),
			dbesc($filename),
			dbesc($mimetype),
			dbesc($folder_hash),
			intval($filesize),
			intval($x[0]['revision'] + 1),
			intval(1),
			intval($is_photo),
			dbesc($os_relpath),
			dbesc($created),
			dbesc($created),
			dbesc($x[0]['allow_cid']),
			dbesc($x[0]['allow_gid']),
			dbesc($x[0]['deny_cid']),
			dbesc($x[0]['deny_gid'])
		);
	}
	elseif($options === 'update') {
		$r = q("update attach set filename = '%s', filetype = '%s', folder = '%s', edited = '%s', os_storage = %d, is_photo = %d, 
			allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid  = '%s' where id = %d and uid = %d",
			dbesc((array_key_exists('filename',$arr))  ? $arr['filename']  : $x[0]['filename']),
			dbesc((array_key_exists('filetype',$arr))  ? $arr['filetype']  : $x[0]['filetype']),
			dbesc(($folder_hash) ? $folder_hash : $x[0]['folder']),
			dbesc($created),
			dbesc((array_key_exists('os_storage',$arr))  ? $arr['os_storage']  : $x[0]['os_storage']),
			dbesc((array_key_exists('is_photo',$arr))  ? $arr['is_photo']  : $x[0]['is_photo']),
			dbesc((array_key_exists('allow_cid',$arr)) ? $arr['allow_cid'] : $x[0]['allow_cid']),
			dbesc((array_key_exists('allow_gid',$arr)) ? $arr['allow_gid'] : $x[0]['allow_gid']),
			dbesc((array_key_exists('deny_cid',$arr))  ? $arr['deny_cid']  : $x[0]['deny_cid']),
			dbesc((array_key_exists('deny_gid',$arr))  ? $arr['deny_gid']  : $x[0]['deny_gid']),
			intval($x[0]['id']),
			intval($x[0]['uid'])
		);
	}
	else {

		$r = q("INSERT INTO attach ( aid, uid, hash, creator, filename, filetype, folder, filesize, revision, os_storage, is_photo, data, created, edited, allow_cid, allow_gid,deny_cid, deny_gid )
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
			intval($channel['channel_account_id']),
			intval($channel_id),
			dbesc($hash),
			dbesc(get_observer_hash()),
			dbesc($filename),
			dbesc($mimetype),
			dbesc($folder_hash),
			intval($filesize),
			intval(0),
			intval(1),
			intval($is_photo),
			dbesc($os_relpath),
			dbesc($created),
			dbesc($created),
			dbesc(($arr && array_key_exists('allow_cid',$arr)) ? $arr['allow_cid'] : $str_contact_allow),
			dbesc(($arr && array_key_exists('allow_gid',$arr)) ? $arr['allow_gid'] : $str_group_allow),
			dbesc(($arr && array_key_exists('deny_cid',$arr))  ? $arr['deny_cid']  : $str_contact_deny),
			dbesc(($arr && array_key_exists('deny_gid',$arr))  ? $arr['deny_gid']  : $str_group_deny)
		);
	}

	if($is_photo) {

		$args = array( 'source' => $source, 'visible' => $visible, 'resource_id' => $hash, 'album' => basename($pathname), 'os_path' => $os_basepath . $os_relpath, 'filename' => $filename, 'getimagesize' => $gis, 'directory' => $direct);
		if($arr['contact_allow'])
			$args['contact_allow'] = $arr['contact_allow'];
		if($arr['group_allow'])
			$args['group_allow'] = $arr['group_allow'];
		if($arr['contact_deny'])
			$args['contact_deny'] = $arr['contact_deny'];
		if($arr['group_deny'])
			$args['group_deny'] = $arr['group_deny'];
		if(array_key_exists('allow_cid',$arr))
			$args['allow_cid'] = $arr['allow_cid'];
		if(array_key_exists('allow_gid',$arr))
			$args['allow_gid'] = $arr['allow_gid'];
		if(array_key_exists('deny_cid',$arr))
			$args['deny_cid'] = $arr['deny_cid'];
		if(array_key_exists('deny_gid',$arr))
			$args['deny_gid'] = $arr['deny_gid'];

		$args['created'] = $created;
		$args['edited'] = $edited;
		if($arr['item'])
			$args['item'] = $arr['item'];

		$p = photo_upload($channel,$observer,$args);
		if($p['success']) {
			$ret['body'] = $p['body'];
		}
	}

	if(($options !== 'update') && ($remove_when_processed))
		@unlink($src);

	if(! $r) {
		$ret['message'] = t('File upload failed. Possible system limit or action terminated.');
		call_hooks('photo_upload_end',$ret);
		return $ret;
	}

	// Caution: This re-uses $sql_options set further above

	$r = q("select id, aid, uid, hash, creator, filename, filetype, filesize, revision, folder, os_storage, is_photo, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where uid = %d and hash = '%s' $sql_options limit 1",
		intval($channel_id),
		dbesc($hash)
	);

	if(! $r) {
		$ret['message'] = t('Stored file could not be verified. Upload failed.');
		call_hooks('photo_upload_end',$ret);
		return $ret;
	}


	$ret['success'] = true;
	$ret['data'] = $r[0];
	if(! $is_photo) {
		// This would've been called already with a success result in photos_upload() if it was a photo.
		call_hooks('photo_upload_end',$ret);
	}
	return $ret;
}

/**
 * @brief Read a virtual directory and return contents.
 *
 * Also checking permissions of all parent components.
 *
 * @param integer $channel_id
 * @param string $observer_hash hash of current observer
 * @param string $pathname
 * @param string $parent_hash (optional)
 *
 * @return array $ret
 *  * \e boolean \b success boolean true or false
 *  * \e string \b message error message if success is false
 *  * \e array \b data array of attach DB entries without data component
 */
function z_readdir($channel_id, $observer_hash, $pathname, $parent_hash = '') {
	$ret = array('success' => false);

	if(! perm_is_allowed($channel_id, get_observer_hash(), 'view_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	if(strpos($pathname, '/')) {
		$paths = explode('/', $pathname);
		if(count($paths) > 1) {
			$curpath = array_shift($paths);

			$r = q("select hash, id, is_dir from attach where uid = %d and filename = '%s' and is_dir != 0 " . permissions_sql($channel_id) . " limit 1",
				intval($channel_id),
				dbesc($curpath)
			);
			if(! $r) {
				$ret['message'] = t('Path not available.');
				return $ret;
			}

			return z_readdir($channel_id, $observer_hash, implode('/', $paths), $r[0]['hash']);
		}
	}
	else
		$paths = array($pathname);

	$r = q("select id, aid, uid, hash, creator, filename, filetype, filesize, revision, folder, is_photo, is_dir, os_storage, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where id = %d and folder = '%s' and filename = '%s' and is_dir != 0 " . permissions_sql($channel_id),
		intval($channel_id),
		dbesc($parent_hash),
		dbesc($paths[0])
	);
	if(! $r) {
		$ret['message'] = t('Path not available.');
		return $ret;
	}
	$ret['success'] = true;
	$ret['data'] = $r;

	return $ret;
}

/**
 * @brief Create directory.
 *
 * @param array $channel channel array of owner
 * @param string $observer_hash hash of current observer
 * @param array $arr parameter array to fulfil request
 * - Required:
 *  * \e string \b filename
 *  * \e string \b folder hash of parent directory, empty string for root directory
 * - Optional:
 *  * \e string \b hash precomputed hash for this node
 *  * \e tring  \b allow_cid
 *  * \e string \b allow_gid
 *  * \e string \b deny_cid
 *  * \e string \b deny_gid
 * @return array
 */
function attach_mkdir($channel, $observer_hash, $arr = null) {

	$ret = array('success' => false);
	$channel_id = $channel['channel_id'];

	$sql_options = '';

	$basepath = 'store/' . $channel['channel_address'];

	logger('attach_mkdir: basepath: ' . $basepath);

	if(! is_dir($basepath))
		os_mkdir($basepath,STORAGE_DEFAULT_PERMISSIONS, true);

	if(! perm_is_allowed($channel_id, $observer_hash, 'write_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	if(! $arr['filename']) {
		$ret['message'] = t('Empty pathname');
		return $ret;
	}

	$arr['hash'] = (($arr['hash']) ? $arr['hash'] : random_string());

	// Check for duplicate name.
	// Check both the filename and the hash as we will be making use of both.

	$r = q("select id, hash, is_dir, flags from attach where ( filename = '%s' or hash = '%s' ) and folder = '%s' and uid = %d limit 1",
		dbesc($arr['filename']),
		dbesc($arr['hash']),
		dbesc($arr['folder']),
		intval($channel['channel_id'])
	);
	if($r) {
		if(array_key_exists('force',$arr) && intval($arr['force']) 
			&& (intval($r[0]['is_dir']))) {
				$ret['success'] = true;
				$r = q("select * from attach where id = %d limit 1",
					intval($r[0]['id'])
				);
				if($r)
					$ret['data'] = $r[0];
				return $ret;
		}
		$ret['message'] = t('duplicate filename or path');
		return $ret;
	}

	if($arr['folder']) {

		// Walk the directory tree from parent back to root to make sure the parent is valid and name is unique and we
		// have permission to see this path. This implies the root directory itself is public since we won't have permissions
		// set on the psuedo-directory. We can however set permissions for anything and everything contained within it.

		$lpath = '';
		$lfile = $arr['folder'];
		$sql_options = permissions_sql($channel['channel_id']);

		do {
			$r = q("select filename, hash, flags, is_dir, folder from attach where uid = %d and hash = '%s' and is_dir != 0
				$sql_options limit 1",
				intval($channel['channel_id']),
				dbesc($lfile)
			);
			if(! $r) {
				logger('attach_mkdir: hash ' . $lfile . ' not found in ' . $lpath);
				$ret['message'] = t('Path not found.');
				return $ret;
			}
			if($lfile)
				$lpath = $r[0]['hash'] . '/' . $lpath;
			$lfile = $r[0]['folder'];
		} while ( ($r[0]['folder']) && intval($r[0]['is_dir'])) ;
		$path = $basepath . '/' . $lpath;
	}
	else
		$path = $basepath . '/';

	$path .= $arr['hash'];

	$created = datetime_convert();

	$r = q("INSERT INTO attach ( aid, uid, hash, creator, filename, filetype, filesize, revision, folder, os_storage, is_dir, data, created, edited, allow_cid, allow_gid, deny_cid, deny_gid )
		VALUES ( %d, %d, '%s', '%s', '%s', '%s', %d, %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
		intval($channel['channel_account_id']),
		intval($channel_id),
		dbesc($arr['hash']),
		dbesc(get_observer_hash()),
		dbesc($arr['filename']),
		dbesc('multipart/mixed'),
		intval(0),
		intval(0),
		dbesc($arr['folder']),
		intval(1),
		intval(1),
		dbesc($path),
		dbesc($created),
		dbesc($created),
		dbesc(($arr && array_key_exists('allow_cid',$arr)) ? $arr['allow_cid'] : $channel['channel_allow_cid']),
		dbesc(($arr && array_key_exists('allow_gid',$arr)) ? $arr['allow_gid'] : $channel['channel_allow_gid']),
		dbesc(($arr && array_key_exists('deny_cid',$arr))  ? $arr['deny_cid']  : $channel['channel_deny_cid']),
		dbesc(($arr && array_key_exists('deny_gid',$arr))  ? $arr['deny_gid']  : $channel['channel_deny_gid'])
	);

	if($r) {
		if(os_mkdir($path, STORAGE_DEFAULT_PERMISSIONS, true)) {
			$ret['success'] = true;

			// update the parent folder's lastmodified timestamp
			$e = q("UPDATE attach SET edited = '%s' WHERE hash = '%s' AND uid = %d",
				dbesc($created),
				dbesc($arr['folder']),
				intval($channel_id)
			);

			$z = q("select * from attach where hash = '%s' and uid = %d and is_dir = 1 limit 1",
				dbesc($arr['hash']),
				intval($channel_id)
			);
			if($z)
				$ret['data'] = $z[0];
		}
		else {
			logger('attach_mkdir: ' . mkdir . ' ' . $path . ' failed.');
			$ret['message'] = t('mkdir failed.');
		}
	}
	else {
		$ret['message'] = t('database storage failed.');
	}

	return $ret;
}

/**
 * @brief Create directory (recursive).
 *
 * @param array $channel channel array of owner
 * @param string $observer_hash hash of current observer
 * @param array $arr parameter array to fulfil request
 * - Required:
 *  * \e string \b pathname
 *  * \e string \b folder hash of parent directory, empty string for root directory
 * - Optional:
 *  * \e string \b allow_cid
 *  * \e string \b allow_gid
 *  * \e string \b deny_cid
 *  * \e string \b deny_gid
 * @return array
 */
function attach_mkdirp($channel, $observer_hash, $arr = null) {

	$ret = array('success' => false);
	$channel_id = $channel['channel_id'];

	$sql_options = '';

	$basepath = 'store/' . $channel['channel_address'];

	logger('attach_mkdirp: basepath: ' . $basepath);

	if(! is_dir($basepath))
		os_mkdir($basepath,STORAGE_DEFAULT_PERMISSIONS, true);

	if(! perm_is_allowed($channel_id, $observer_hash, 'write_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	if(! $arr['pathname']) {
		$ret['message'] = t('Empty pathname');
		return $ret;
	}

	$paths = explode('/',$arr['pathname']);
	if(! $paths) {
		$ret['message'] = t('Empty path');
		return $ret;
	}

	$current_parent = '';

	foreach($paths as $p) {
		if(! $p)
			continue;
		$arx = array(
			'filename' => $p, 
			'folder' => $current_parent,
			'force' => 1
		);
		if(array_key_exists('allow_cid',$arr))
			$arx['allow_cid'] = $arr['allow_cid'];
		if(array_key_exists('deny_cid',$arr))
			$arx['deny_cid'] = $arr['deny_cid'];
		if(array_key_exists('allow_gid',$arr))
			$arx['allow_gid'] = $arr['allow_gid'];
		if(array_key_exists('deny_gid',$arr))
			$arx['deny_gid'] = $arr['deny_gid'];

		$x = attach_mkdir($channel, $observer_hash, $arx);		
		if($x['success']) {
			$current_parent = $x['data']['hash'];
		}
		else {
			$ret['message'] = $x['message'];
			return $ret;
		}
	}
	if(isset($x)) {
		$ret['success'] = true;
		$ret['data'] = $x['data'];
	}

	return $ret;	

}







/**
 * @brief Changes permissions of a file.
 *
 * @param int $channel_id
 * @param array $resource
 * @param string $allow_cid
 * @param string $allow_gid
 * @param string $deny_cid
 * @param string $deny_gid
 * @param boolean $recurse (optional) default false
 */
function attach_change_permissions($channel_id, $resource, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $recurse = false) {

	$r = q("select hash, flags, is_dir, is_photo from attach where hash = '%s' and uid = %d limit 1",
		dbesc($resource),
		intval($channel_id)
	);

	if(! $r)
		return;

	if(intval($r[0]['is_dir'])) {
		if($recurse) {
			$r = q("select hash, flags, is_dir from attach where folder = '%s' and uid = %d",
				dbesc($resource),
				intval($channel_id)
			);
			if($r) {
				foreach($r as $rr) {
					attach_change_permissions($channel_id, $rr['hash'], $allow_cid, $allow_gid, $deny_cid, $deny_gid, $recurse);
				}
			}
		}
	}

	$x = q("update attach set allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s' where hash = '%s' and uid = %d",
		dbesc($allow_cid),
		dbesc($allow_gid),
		dbesc($deny_cid),
		dbesc($deny_gid),
		dbesc($resource),
		intval($channel_id)
	);
	if($r[0]['is_photo']) {
		$x = q("update photo set allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s' where resource_id = '%s' and uid = %d",
			dbesc($allow_cid),
			dbesc($allow_gid),
			dbesc($deny_cid),
			dbesc($deny_gid),
			dbesc($resource),
			intval($channel_id)
		);
	}
}

/**
 * @brief Delete a file/directory from a channel.
 *
 * If the provided resource hash is from a directory it will delete everything
 * recursively under this directory.
 *
 * @param int $channel_id
 *  The id of the channel
 * @param string $resource
 *  The hash to delete
 * @return void
 */
function attach_delete($channel_id, $resource, $is_photo = 0) {

	$c = q("SELECT channel_address FROM channel WHERE channel_id = %d LIMIT 1",
		intval($channel_id)
	);

	$channel_address = (($c) ? $c[0]['channel_address'] : 'notfound');
	$photo_sql = (($is_photo) ? " and is_photo = 1 " : '');

	$r = q("SELECT hash, flags, is_dir, is_photo, folder FROM attach WHERE hash = '%s' AND uid = %d $photo_sql limit 1",
		dbesc($resource),
		intval($channel_id)
	);

	if(! $r)
		return;

	$cloudpath = get_parent_cloudpath($channel_id, $channel_address, $resource);
	$object = get_file_activity_object($channel_id, $resource, $cloudpath);

	// If resource is a directory delete everything in the directory recursive
	if(intval($r[0]['is_dir'])) {
		$x = q("SELECT hash, os_storage, is_dir, flags FROM attach WHERE folder = '%s' AND uid = %d",
			dbesc($resource),
			intval($channel_id)
		);
		if($x) {
			foreach($x as $xx) {
				attach_delete($channel_id, $xx['hash']);
			}
		}
	}

	// delete a file from filesystem
	if(intval($r[0]['os_storage'])) {
		$y = q("SELECT data FROM attach WHERE hash = '%s' AND uid = %d LIMIT 1",
			dbesc($resource),
			intval($channel_id)
		);

		if($y) {
			$f = 'store/' . $channel_address . '/' . $y[0]['data'];
			if(is_dir($y[0]['data']))
				@rmdir($y[0]['data']);
			elseif(file_exists($f))
				unlink($f);
		}
	}

	// delete from database
	$z = q("DELETE FROM attach WHERE hash = '%s' AND uid = %d",
		dbesc($resource),
		intval($channel_id)
	);

	if($r[0]['is_photo']) {
		$x = q("select id, item_hidden from item where resource_id = '%s' and resource_type = 'photo' and uid = %d",
			dbesc($resource),
			intval($channel_id)
		);
		if($x) {
			drop_item($x[0]['id'],false,(($x[0]['item_hidden']) ? DROPITEM_NORMAL : DROPITEM_PHASE1),true);

			q("DELETE FROM photo WHERE uid = %d AND resource_id = '%s'",
				intval($channel_id),
				dbesc($resource)
			);
		}
	}
			
	// update the parent folder's lastmodified timestamp
	$e = q("UPDATE attach SET edited = '%s' WHERE hash = '%s' AND uid = %d",
		dbesc(datetime_convert()),
		dbesc($r[0]['folder']),
		intval($channel_id)
	);

	file_activity($channel_id, $object, $object['allow_cid'], $object['allow_gid'], $object['deny_cid'], $object['deny_gid'], 'update', $notify=0);
}

/**
 * @brief Returns path to file in cloud/.
 *
 * @warning This function cannot be used with mod/dav as it always returns a
 * path valid under mod/cloud.
 *
 * @param array $arr associative array with:
 *  * \e int \b uid the channel's uid
 *  * \e string \b folder
 *  * \e string \b filename
 * @return string
 *  path to the file in cloud/
 */
function get_cloudpath($arr) {
	$basepath = 'cloud/';

	if($arr['uid']) {
		$r = q("select channel_address from channel where channel_id = %d limit 1",
			intval($arr['uid'])
		);
		if($r)
			$basepath .= $r[0]['channel_address'] . '/';
	}

	$path = $basepath;

	if($arr['folder']) {
		$lpath = '';
		$lfile = $arr['folder'];

		do {
			$r = q("select filename, hash, flags, is_dir, folder from attach where uid = %d and hash = '%s' and is_dir != 0
				limit 1",
				intval($arr['uid']),
				dbesc($lfile)
			);

			if(! $r)
				break;

			if($lfile)
				$lpath = $r[0]['filename'] . '/' . $lpath;
			$lfile = $r[0]['folder'];

		} while ( ($r[0]['folder']) && intval($r[0]['is_dir']));

		$path .= $lpath;
	}
	$path .= $arr['filename'];

	return $path;
}

/**
 * @brief Returns path to parent folder in cloud/.
 * This function cannot be used with mod/dav as it always returns a path valid under mod/cloud
 *
 * @param int $channel_id
 *  The id of the channel
 * @param string $channel_name
 *  The name of the channel
 * @param string $attachHash
 * @return string with the full folder path
 */
function get_parent_cloudpath($channel_id, $channel_name, $attachHash) {
	$parentFullPath = '';
	// build directory tree
	$parentHash = $attachHash;
	do {
		$parentHash = find_folder_hash_by_attach_hash($channel_id, $parentHash);
		if ($parentHash) {
			$parentName = find_filename_by_hash($channel_id, $parentHash);
			$parentFullPath = $parentName . '/' . $parentFullPath;
		}
	} while ($parentHash);
	$parentFullPath = z_root() . '/cloud/' . $channel_name . '/' . $parentFullPath;

	return $parentFullPath;
}

/**
 * @brief Return the hash of an attachment's folder.
 *
 * @param int $channel_id
 *  The id of the channel
 * @param string $attachHash
 *  The hash of the attachment
 * @return string
 */
function find_folder_hash_by_attach_hash($channel_id, $attachHash, $recurse = false) {

logger('attach_hash: ' . $attachHash);
	$r = q("SELECT folder FROM attach WHERE uid = %d AND hash = '%s' LIMIT 1",
		intval($channel_id),
		dbesc($attachHash)
	);
	$hash = '';
	if($r && $r[0]['folder']) {
		if($recurse)
			$hash = find_folder_hash_by_attach_hash($channel_id,$r[0]['folder'],true) . '/' . $r[0]['folder']; 
		else
			$hash = $r[0]['folder'];
	}
	return $hash;
}

/**
 * @brief Returns the filename of an attachment in a given channel.
 *
 * @param int $channel_id
 *  The id of the channel
 * @param string $attachHash
 *  The hash of the attachment
 * @return string
 *  The filename of the attachment
 */
function find_filename_by_hash($channel_id, $attachHash) {
	$r = q("SELECT filename FROM attach WHERE uid = %d AND hash = '%s' LIMIT 1",
		intval($channel_id),
		dbesc($attachHash)
	);
	$filename = '';
	if ($r) {
		$filename = $r[0]['filename'];
	}

	return $filename;
}

/**
 *
 * @param $in
 * @param $out
 */
function pipe_streams($in, $out) {
	$size = 0;
	while (!feof($in))
		$size += fwrite($out, fread($in, 8192));

	return $size;
}

/**
 * @brief Activity for files.
 *
 * @param int $channel_id
 * @param array $object
 * @param string $allow_cid
 * @param string $allow_gid
 * @param string $deny_cid
 * @param string $deny_gid
 * @param string $verb
 * @param boolean $no_activity
 */
function file_activity($channel_id, $object, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $verb, $notify) {

	require_once('include/items.php');

	$poster = get_app()->get_observer();

	//if we got no object something went wrong
	if(!$object)
		return;

	//turn strings into arrays
	$arr_allow_cid = expand_acl($allow_cid);
	$arr_allow_gid = expand_acl($allow_gid);
	$arr_deny_cid = expand_acl($deny_cid);
	$arr_deny_gid = expand_acl($deny_gid);

	//filter out receivers which do not have permission to view filestorage
	$arr_allow_cid = check_list_permissions($channel_id, $arr_allow_cid, 'view_storage');

	$is_dir = (intval($object['is_dir']) ? true : false);

	//do not send activity for folders for now
	if($is_dir)
		return;

	//check for recursive perms if we are in a folder
	if($object['folder']) {

		$folder_hash = $object['folder'];

		$r_perms = recursive_activity_recipients($arr_allow_cid, $arr_allow_gid, $arr_deny_cid, $arr_deny_gid, $folder_hash);

		//split up returned perms
		$arr_allow_cid = $r_perms['allow_cid'];
		$arr_allow_gid = $r_perms['allow_gid'];
		$arr_deny_cid = $r_perms['deny_cid'];
		$arr_deny_gid = $r_perms['deny_gid'];

		//filter out receivers which do not have permission to view filestorage
		$arr_allow_cid = check_list_permissions($channel_id, $arr_allow_cid, 'view_storage');
	}

	$mid = item_message_id();

	$arr = array();

	$arr['item_wall'] = 1; 
	$arr['item_origin'] = 1;
	$arr['item_unseen'] = 1;

	$objtype = ACTIVITY_OBJ_FILE;

	$private = (($arr_allow_cid[0] || $arr_allow_gid[0] || $arr_deny_cid[0] || $arr_deny_gid[0]) ? 1 : 0);

	$jsonobject = json_encode($object);

	//check if item for this object exists
	$y = q("SELECT mid FROM item WHERE verb = '%s' AND obj_type = '%s' AND resource_id = '%s' AND uid = %d LIMIT 1",
		dbesc(ACTIVITY_POST),
		dbesc($objtype),
		dbesc($object['hash']),
		intval(local_channel())
	);

	if($y) {
		$update = true;
		$object['d_mid'] = $y[0]['mid']; //attach mid of the old object
		$u_jsonobject = json_encode($object);

		//we have got the relevant info - delete the old item before we create the new one
		$z = q("DELETE FROM item WHERE obj_type = '%s' AND verb = '%s' AND mid = '%s'",
			dbesc(ACTIVITY_OBJ_FILE),
			dbesc(ACTIVITY_POST),
			dbesc($y[0]['mid'])
		);

	}

	if($update && $verb == 'post' ) {
		//send update activity and create a new one

		//updates should be sent to everybody with recursive perms and all eventual former allowed members ($object['allow_cid'] etc.).
		$u_arr_allow_cid = array_unique(array_merge($arr_allow_cid, expand_acl($object['allow_cid'])));
		$u_arr_allow_gid = array_unique(array_merge($arr_allow_gid, expand_acl($object['allow_gid'])));
		$u_arr_deny_cid = array_unique(array_merge($arr_deny_cid, expand_acl($object['deny_cid'])));
		$u_arr_deny_gid = array_unique(array_merge($arr_deny_gid, expand_acl($object['deny_gid'])));

		$u_mid = item_message_id();

		$arr['aid']           = get_account_id();
		$arr['uid']           = $channel_id;
		$arr['mid']           = $u_mid;
		$arr['parent_mid']    = $u_mid;
		$arr['author_xchan']  = $poster['xchan_hash'];
		$arr['owner_xchan']   = $poster['xchan_hash'];
		$arr['title']         = '';
		//updates should be visible to everybody -> perms may have changed
		$arr['allow_cid']     = '';
		$arr['allow_gid']     = '';
		$arr['deny_cid']      = '';
		$arr['deny_gid']      = '';
		$arr['item_hidden']   = 1;
		$arr['item_private']  = 0;
		$arr['verb']          = ACTIVITY_UPDATE;
		$arr['obj_type']      = $objtype;
		$arr['object']        = $u_jsonobject;
		$arr['resource_id']   = $object['hash'];
		$arr['resource_type'] = 'attach';
		$arr['body']          = '';

		$post = item_store($arr);
		$item_id = $post['item_id'];
		if($item_id) {
			proc_run('php',"include/notifier.php","activity",$item_id);
		}

		call_hooks('post_local_end', $arr);

		$update = false;

		//notice( t('File activity updated') . EOL);
	}

	if(! $notify) {
		return;
	}

	$arr = array();

	$arr['aid']           = get_account_id();
	$arr['uid']           = $channel_id;
	$arr['mid']           = $mid;
	$arr['parent_mid']    = $mid;
	$arr['item_wall']     = 1; 
	$arr['item_origin']   = 1;
	$arr['item_unseen']   = 1;
	$arr['author_xchan']  = $poster['xchan_hash'];
	$arr['owner_xchan']   = $poster['xchan_hash'];
	$arr['title']         = '';
	$arr['allow_cid']     = perms2str($arr_allow_cid);
	$arr['allow_gid']     = perms2str($arr_allow_gid);
	$arr['deny_cid']      = perms2str($arr_deny_cid);
	$arr['deny_gid']      = perms2str($arr_deny_gid);
	$arr['item_hidden']   = 1;
	$arr['item_private']  = $private;
	$arr['verb']          = (($update) ? ACTIVITY_UPDATE : ACTIVITY_POST);
	$arr['obj_type']      = $objtype;
	$arr['resource_id']   = $object['hash'];
	$arr['resource_type'] = 'attach';
	$arr['object']        = (($update) ? $u_jsonobject : $jsonobject);
	$arr['body']          = '';

	$post = item_store($arr);
	$item_id = $post['item_id'];

	if($item_id) {
		proc_run('php',"include/notifier.php","activity",$item_id);
	}

	call_hooks('post_local_end', $arr);

	//(($verb === 'post') ?  notice( t('File activity posted') . EOL) : notice( t('File activity dropped') . EOL));

	return;
}

/**
 * @brief Create file activity object
 *
 * @param int $channel_id
 * @param string $hash
 * @param string $cloudpath
 */
function get_file_activity_object($channel_id, $hash, $cloudpath) {

	$x = q("SELECT creator, filename, filetype, filesize, revision, folder, os_storage, is_photo, is_dir, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid FROM attach WHERE uid = %d AND hash = '%s' LIMIT 1",
		intval($channel_id),
		dbesc($hash)
	);

	$url = rawurlencode($cloudpath . $x[0]['filename']);

	$links   = array();
	$links[] = array(
		'rel'  => 'alternate',
		'type' => 'text/html',
		'href' => $url
	);

	$object = array(
		'type'  => ACTIVITY_OBJ_FILE,
		'title' => $x[0]['filename'],
		'id'    => $url,
		'link'  => $links,

		'hash'		=> $hash,
		'creator'	=> $x[0]['creator'],
		'filename'	=> $x[0]['filename'],
		'filetype'	=> $x[0]['filetype'],
		'filesize'	=> $x[0]['filesize'],
		'revision'	=> $x[0]['revision'],
		'folder'	=> $x[0]['folder'],
		'flags'		=> $x[0]['flags'],
		'os_storage' => $x[0]['os_storage'],
		'is_photo'  => $x[0]['is_photo'],
		'is_dir'    => $x[0]['is_dir'],
		'created'	=> $x[0]['created'],
		'edited'	=> $x[0]['edited'],
		'allow_cid'	=> $x[0]['allow_cid'],
		'allow_gid'	=> $x[0]['allow_gid'],
		'deny_cid'	=> $x[0]['deny_cid'],
		'deny_gid'	=> $x[0]['deny_gid']
	);

	return $object;
}

/**
 * @brief Returns array of channels which have recursive permission for a file
 *
 * @param $arr_allow_cid
 * @param $arr_allow_gid
 * @param $arr_deny_cid
 * @param $arr_deny_gid
 * @param $folder_hash
 */
function recursive_activity_recipients($arr_allow_cid, $arr_allow_gid, $arr_deny_cid, $arr_deny_gid, $folder_hash) {

	$ret = array();
	$parent_arr = array();
	$count_values = array();
	$poster = get_app()->get_observer();

	//turn allow_gid into allow_cid's
	foreach($arr_allow_gid as $gid) {
		$in_group = in_group($gid);
		$arr_allow_cid = array_unique(array_merge($arr_allow_cid, $in_group));
	}

	$count = 0;
	while($folder_hash) {
		$x = q("SELECT allow_cid, allow_gid, deny_cid, deny_gid, folder FROM attach WHERE hash = '%s' LIMIT 1",
			dbesc($folder_hash)
		);

		//only process private folders
		if($x[0]['allow_cid'] || $x[0]['allow_gid'] || $x[0]['deny_cid'] || $x[0]['deny_gid']) {

			$parent_arr['allow_cid'][] = expand_acl($x[0]['allow_cid']);
			$parent_arr['allow_gid'][] = expand_acl($x[0]['allow_gid']);

			/**
			 * @TODO should find a much better solution for the allow_cid <-> allow_gid problem.
			 * Do not use allow_gid for now. Instead lookup the members of the group directly and add them to allow_cid.
			 * */
			if($parent_arr['allow_gid']) {
				foreach($parent_arr['allow_gid'][$count] as $gid) {
					$in_group = in_group($gid);
					$parent_arr['allow_cid'][$count] = array_unique(array_merge($parent_arr['allow_cid'][$count], $in_group));
				}
			}

			$parent_arr['deny_cid'][] = expand_acl($x[0]['deny_cid']);
			$parent_arr['deny_gid'][] = expand_acl($x[0]['deny_gid']);

			$count++;
		}

		$folder_hash = $x[0]['folder'];
	}

	//if none of the parent folders is private just return file perms
	if(!$parent_arr['allow_cid'] && !$parent_arr['allow_gid'] && !$parent_arr['deny_cid'] && !$parent_arr['deny_gid']) {
		$ret['allow_gid'] = $arr_allow_gid;
		$ret['allow_cid'] = $arr_allow_cid;
		$ret['deny_gid'] = $arr_deny_gid;
		$ret['deny_cid'] = $arr_deny_cid;

		return $ret;
	}

	//if there are no perms on the file we get them from the first parent folder
	if(!$arr_allow_cid && !$arr_allow_gid && !$arr_deny_cid && !$arr_deny_gid) {
		$arr_allow_cid = $parent_arr['allow_cid'][0];
		$arr_allow_gid = $parent_arr['allow_gid'][0];
		$arr_deny_cid = $parent_arr['deny_cid'][0];
		$arr_deny_gid = $parent_arr['deny_gid'][0];
	}

	//allow_cid
	$r_arr_allow_cid = false;
	foreach ($parent_arr['allow_cid'] as $folder_arr_allow_cid) {
		foreach ($folder_arr_allow_cid as $ac_hash) {
			$count_values[$ac_hash]++;
		}
	}
	foreach ($arr_allow_cid as $fac_hash) {
		if($count_values[$fac_hash] == $count)
			$r_arr_allow_cid[] = $fac_hash;
	}

	//allow_gid
	$r_arr_allow_gid = false;
	foreach ($parent_arr['allow_gid'] as $folder_arr_allow_gid) {
		foreach ($folder_arr_allow_gid as $ag_hash) {
			$count_values[$ag_hash]++;
		}
	}
	foreach ($arr_allow_gid as $fag_hash) {
		if($count_values[$fag_hash] == $count)
			$r_arr_allow_gid[] = $fag_hash;
	}

	//deny_gid
	foreach($parent_arr['deny_gid'] as $folder_arr_deny_gid) {
		$r_arr_deny_gid = array_merge($arr_deny_gid, $folder_arr_deny_gid);
	}
	$r_arr_deny_gid = array_unique($r_arr_deny_gid);

	//deny_cid
	foreach($parent_arr['deny_cid'] as $folder_arr_deny_cid) {
		$r_arr_deny_cid = array_merge($arr_deny_cid, $folder_arr_deny_cid);
	}
	$r_arr_deny_cid = array_unique($r_arr_deny_cid);

	//if none is allowed restrict to self
	if(($r_arr_allow_gid === false) && ($r_arr_allow_cid === false)) {
		$ret['allow_cid'] = $poster['xchan_hash'];
	} else {
		$ret['allow_gid'] = $r_arr_allow_gid;
		$ret['allow_cid'] = $r_arr_allow_cid;
		$ret['deny_gid'] = $r_arr_deny_gid;
		$ret['deny_cid'] = $r_arr_deny_cid;
	}

	return $ret;
}

/**
 * @brief Returns members of a group.
 *
 * @param int $group_id id of the group to look up
 */
function in_group($group_id) {
	$group_members = array();

	/** @TODO make these two queries one with a join. */
	$x = q("SELECT id FROM groups WHERE hash = '%s'",
		dbesc($group_id)
	);

	$r = q("SELECT xchan FROM group_member WHERE gid = %d",
		intval($x[0]['id'])
	);

	foreach($r as $ig) {
		$group_members[] = $ig['xchan'];
	}

	return $group_members;
}


function filepath_macro($s) {

	return str_replace(
		array( '%Y', '%m', '%d' ),
		array( datetime_convert('UTC',date_default_timezone_get(),'now', 'Y'),
			datetime_convert('UTC',date_default_timezone_get(),'now', 'm'),
			datetime_convert('UTC',date_default_timezone_get(),'now', 'd')
		), $s);

}

