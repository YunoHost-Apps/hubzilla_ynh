<?php
namespace Zotlabs\Module;

require_once('include/photo/photo_driver.php');
require_once('include/photos.php');
require_once('include/items.php');
require_once('include/acl_selectors.php');
require_once('include/bbcode.php');
require_once('include/security.php');
require_once('include/attach.php');
require_once('include/text.php');


class Photos extends \Zotlabs\Web\Controller {

	function init() {
	
	
		if(observer_prohibited()) {
			return;
		}
	
		$o = '';
	
		if(argc() > 1) {
			$nick = argv(1);
	
			profile_load($nick);
	
			$channelx = channelx_by_nick($nick);
	
			if(! $channelx)
				return;
	
			\App::$data['channel'] = $channelx;
	
			$observer = \App::get_observer();
			\App::$data['observer'] = $observer;
	
			$observer_xchan = (($observer) ? $observer['xchan_hash'] : '');
	
			head_set_icon(\App::$data['channel']['xchan_photo_s']);
	
			\App::$page['htmlhead'] .= "<script> var profile_uid = " . ((\App::$data['channel']) ? \App::$data['channel']['channel_id'] : 0) . "; </script>" ;
	
		}
	
		return;
	}
	
	
	
	function post() {
	
		logger('mod-photos: photos_post: begin' , LOGGER_DEBUG);
	
	
		logger('mod_photos: REQUEST ' . print_r($_REQUEST,true), LOGGER_DATA);
		logger('mod_photos: FILES '   . print_r($_FILES,true), LOGGER_DATA);
	
		$ph = photo_factory('');
	
		$phototypes = $ph->supportedTypes();
	
		$can_post  = false;
	
		$page_owner_uid = \App::$data['channel']['channel_id'];
	
		if(perm_is_allowed($page_owner_uid,get_observer_hash(),'write_storage'))
			$can_post = true;
	
		if(! $can_post) {
			notice( t('Permission denied.') . EOL );
			if(is_ajax())
				killme();
			return;
		}
	
		$s = abook_self($page_owner_uid);
	
		if(! $s) {
			notice( t('Page owner information could not be retrieved.') . EOL);
			logger('mod_photos: post: unable to locate contact record for page owner. uid=' . $page_owner_uid);
			if(is_ajax())
				killme();
			return;
		}
	
		$owner_record = $s[0];	
	
		$acl = new \Zotlabs\Access\AccessList(\App::$data['channel']);
	
		if((argc() > 3) && (argv(2) === 'album')) {
	
			$album = hex2bin(argv(3));
	
			if($album === t('Profile Photos')) {
				// not allowed
				goaway(z_root() . '/' . $_SESSION['photo_return']);
			}
	
			if(! photos_album_exists($page_owner_uid,$album)) {
				notice( t('Album not found.') . EOL);
				goaway(z_root() . '/' . $_SESSION['photo_return']);
			}
	
	
			/*
			 * DELETE photo album and all its photos
			 */
	
			if($_REQUEST['dropalbum'] == t('Delete Album')) {
	
	
				// This is dangerous because we combined file storage and photos into one interface
				// This function will remove all photos from any directory with the same name since
				// we have not passed the path value.
	
				// The correct solution would be to use a full pathname from your storage root for 'album'
				// We also need to prevent/block removing the storage root folder.
	
				$folder_hash = '';
	 
				$r = q("select * from attach where is_dir = 1 and uid = %d and filename = '%s'",
					intval($page_owner_uid),
					dbesc($album)
				);
				if(! $r) {
					notice( t('Album not found.') . EOL);
					return;
				}
				if(count($r) > 1) {
					notice( t('Multiple storage folders exist with this album name, but within different directories. Please remove the desired folder or folders using the Files manager') . EOL);
					return;
				}
				else {
					$folder_hash = $r[0]['hash'];
				}
	
	
	
				$res = array();
	
				// get the list of photos we are about to delete
	
				if(remote_channel() && (! local_channel())) {
					$str = photos_album_get_db_idstr($page_owner_uid,$album,remote_channel());
				}
				elseif(local_channel()) {
					$str = photos_album_get_db_idstr(local_channel(),$album);
				}
				else {
					$str = null;
				}
				if(! $str) {
					goaway(z_root() . '/' . $_SESSION['photo_return']);
				}
	
				$r = q("select id from item where resource_id in ( $str ) and resource_type = 'photo' and uid = %d " . item_normal(),
					intval($page_owner_uid)
				);
				if($r) {
					foreach($r as $i) {
						attach_delete($page_owner_uid, $i['resource_id'], 1 );
					}
				}
	
				// remove the associated photos in case they weren't attached to an item
	
				q("delete from photo where resource_id in ( $str ) and uid = %d",
					intval($page_owner_uid)
				);
	
				// @FIXME do the same for the linked attach
	
				if($folder_hash) {
					attach_delete($page_owner_uid,$folder_hash, 1);
	
					$sync = attach_export_data(\App::$data['channel'],$folder_hash, true);
	
					if($sync) 
						build_sync_packet($page_owner_uid,array('file' => array($sync)));
				}
	
			}
			
			goaway(z_root() . '/photos/' . \App::$data['channel']['channel_address']);
		}
	
		if((argc() > 2) && (x($_REQUEST,'delete')) && ($_REQUEST['delete'] === t('Delete Photo'))) {
	
			// same as above but remove single photo
	
			$ob_hash = get_observer_hash();
			if(! $ob_hash)
				goaway(z_root() . '/' . $_SESSION['photo_return']);
	
			$r = q("SELECT id, resource_id FROM photo WHERE ( xchan = '%s' or uid = %d ) AND resource_id = '%s' LIMIT 1",
				dbesc($ob_hash),
				intval(local_channel()),
				dbesc(\App::$argv[2])
			);
	
			if($r) {
				attach_delete($page_owner_uid, $r[0]['resource_id'], 1 );
				$sync = attach_export_data(\App::$data['channel'],$r[0]['resource_id'], true);
	
				if($sync) 
					build_sync_packet($page_owner_uid,array('file' => array($sync)));
			}
	
			goaway(z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/album/' . $_SESSION['album_return']);
		}

		if((argc() > 2) && array_key_exists('move_to_album',$_POST)) {
			$m = q("select folder from attach where hash = '%s' and uid = %d limit 1",
				dbesc(argv(2)),
				intval($page_owner_uid)
			);
			if(($m) && ($m[0]['folder'] != $_POST['move_to_album'])) {
				attach_move($page_owner_uid,argv(2),$_POST['move_to_album']);			
				if(! ($_POST['desc'] && $_POST['newtag']))
					goaway(z_root() . '/' . $_SESSION['photo_return']);
			}
		}

		if((argc() > 2) && ((x($_POST,'desc') !== false) || (x($_POST,'newtag') !== false))) {
	
			$desc        = ((x($_POST,'desc'))    ? notags(trim($_POST['desc']))    : '');
			$rawtags     = ((x($_POST,'newtag'))  ? notags(trim($_POST['newtag']))  : '');
			$item_id     = ((x($_POST,'item_id')) ? intval($_POST['item_id'])       : 0);

			$is_nsfw     = ((x($_POST,'adult'))   ? intval($_POST['adult'])         : 0);
		
			$acl->set_from_array($_POST);
			$perm = $acl->get();
	
			$resource_id = argv(2);
	
			if((x($_POST,'rotate') !== false) && 
			   ( (intval($_POST['rotate']) == 1) || (intval($_POST['rotate']) == 2) )) {
				logger('rotate');
	
				$r = q("select * from photo where resource_id = '%s' and uid = %d and imgscale = 0 limit 1",
					dbesc($resource_id),
					intval($page_owner_uid)
				);
				if(count($r)) {
					$d = (($r[0]['os_storage']) ? @file_get_contents(dbunescbin($r[0]['content'])) : dbunescbin($r[0]['content']));
					$ph = photo_factory($d, $r[0]['mimetype']);
					if($ph->is_valid()) {
						$rotate_deg = ( (intval($_POST['rotate']) == 1) ? 270 : 90 );
						$ph->rotate($rotate_deg);
	
						$width  = $ph->getWidth();
						$height = $ph->getHeight();
						
						if(intval($r[0]['os_storage'])) {
							@file_put_contents($r[0]['content'],$ph->imageString());
							$data = $r[0]['content'];
							$fsize = @filesize($r[0]['content']);
							q("update attach set filesize = %d where hash = '%s' and uid = %d",
								intval($fsize),
								dbesc($resource_id),
								intval($page_owner_uid)
							);
						}
						else {
							$data = $ph->imageString();
							$fsize = strlen($data);
						}
	
						$x = q("update photo set content = '%s', filesize = %d, height = %d, width = %d where resource_id = '%s' and uid = %d and imgscale = 0",
							dbescbin($data),
							intval($fsize),
							intval($height),
							intval($width),
							dbesc($resource_id),
							intval($page_owner_uid)
						);
	
						if($width > 1024 || $height > 1024) 
							$ph->scaleImage(1024);
	
						$width  = $ph->getWidth();
						$height = $ph->getHeight();
	
						$x = q("update photo set content = '%s', height = %d, width = %d where resource_id = '%s' and uid = %d and imgscale = 1",
							dbescbin($ph->imageString()),
							intval($height),
							intval($width),
							dbesc($resource_id),
							intval($page_owner_uid)
						);
	
	
						if($width > 640 || $height > 640) 
							$ph->scaleImage(640);
	
						$width  = $ph->getWidth();
						$height = $ph->getHeight();
	
						$x = q("update photo set content = '%s', height = %d, width = %d where resource_id = '%s' and uid = %d and imgscale = 2",
							dbescbin($ph->imageString()),
							intval($height),
							intval($width),
							dbesc($resource_id),
							intval($page_owner_uid)
						);
	
	
						if($width > 320 || $height > 320) 
							$ph->scaleImage(320);
	
						$width  = $ph->getWidth();
						$height = $ph->getHeight();
	
						$x = q("update photo set content = '%s', height = %d, width = %d where resource_id = '%s' and uid = %d and imgscale = 3",
							dbescbin($ph->imageString()),
							intval($height),
							intval($width),
							dbesc($resource_id),
							intval($page_owner_uid)
						);
					}
				}
			}
	
			$p = q("SELECT mimetype, is_nsfw, description, resource_id, imgscale, allow_cid, allow_gid, deny_cid, deny_gid FROM photo WHERE resource_id = '%s' AND uid = %d ORDER BY imgscale DESC",
				dbesc($resource_id),
				intval($page_owner_uid)
			);
			if($p) {
				$ext = $phototypes[$p[0]['mimetype']];
	
				$r = q("UPDATE photo SET description = '%s', allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s' WHERE resource_id = '%s' AND uid = %d",
					dbesc($desc),
					dbesc($perm['allow_cid']),
					dbesc($perm['allow_gid']),
					dbesc($perm['deny_cid']),
					dbesc($perm['deny_gid']),
					dbesc($resource_id),
					intval($page_owner_uid)
				);
			}
	
			$item_private = (($str_contact_allow || $str_group_allow || $str_contact_deny || $str_group_deny) ? true : false);
	
			$old_is_nsfw = $p[0]['is_nsfw'];
			if($old_is_nsfw != $is_nsfw) {
				$r = q("update photo set is_nsfw = %d where resource_id = '%s' and uid = %d",
					intval($is_nsfw),
					dbesc($resource_id),
					intval($page_owner_uid)
				);
			}
	
			/* Don't make the item visible if the only change was the album name */
	
			$visibility = 0;
			if($p[0]['description'] !== $desc || strlen($rawtags))
				$visibility = 1;
	
			if(! $item_id) {
				$item_id = photos_create_item(\App::$data['channel'],get_observer_hash(),$p[0],$visibility);
	
			}
	
			if($item_id) {
				$r = q("SELECT * FROM item WHERE id = %d AND uid = %d LIMIT 1",
					intval($item_id),
					intval($page_owner_uid)
				);
	
				if($r) {
					$old_tag    = $r[0]['tag'];
					$old_inform = $r[0]['inform'];
				}
			}
	
	
			// make sure the linked item has the same permissions as the photo regardless of any other changes
			$x = q("update item set allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', item_private = %d
				where id = %d",
					dbesc($perm['allow_cid']),
					dbesc($perm['allow_gid']),
					dbesc($perm['deny_cid']),
					dbesc($perm['deny_gid']),
					intval($acl->is_private()),
					intval($item_id)
			);
	
			// make sure the attach has the same permissions as the photo regardless of any other changes
			$x = q("update attach set allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s' where hash = '%s' and uid = %d and is_photo = 1",
					dbesc($perm['allow_cid']),
					dbesc($perm['allow_gid']),
					dbesc($perm['deny_cid']),
					dbesc($perm['deny_gid']),
					dbesc($resource_id),
					intval($page_owner_uid)
			);
	
	
	
			if(strlen($rawtags)) {
	
				$str_tags = '';
				$inform   = '';
	
				// if the new tag doesn't have a namespace specifier (@foo or #foo) give it a mention
	
				$x = substr($rawtags,0,1);
				if($x !== '@' && $x !== '#')
					$rawtags = '@' . $rawtags;
	
				require_once('include/text.php');
				$profile_uid = \App::$profile['profile_uid'];
	
				$results = linkify_tags($a, $rawtags, (local_channel()) ? local_channel() : $profile_uid);
	
				$success = $results['success'];
				$post_tags = array();
	
				foreach($results as $result) {
					$success = $result['success'];
					if($success['replaced']) {
						$post_tags[] = array(
							'uid'   => $profile_uid, 
							'ttype'  => $success['termtype'],
							'otype' => TERM_OBJ_POST,
							'term'  => $success['term'],
							'url'   => $success['url']
						); 				
					}
				}
	
				$r = q("select * from item where id = %d and uid = %d limit 1",
					intval($item_id),
					intval($page_owner_uid)
				);
	
				if($r) {
					$r = fetch_post_tags($r,true);
					$datarray = $r[0];
					if($post_tags) {
						if((! array_key_exists('term',$datarray)) || (! is_array($datarray['term'])))
							$datarray['term'] = $post_tags;
						else
							$datarray['term'] = array_merge($datarray['term'],$post_tags);  
					}
					item_store_update($datarray,$execflag);
				}
	
			}

			$sync = attach_export_data(\App::$data['channel'],$resource_id);
	
			if($sync) 
				build_sync_packet($page_owner_uid,array('file' => array($sync)));
		
			goaway(z_root() . '/' . $_SESSION['photo_return']);
			return; // NOTREACHED
	
	
		}
	
	
		/**
		 * default post action - upload a photo
		 */
	
		$channel = \App::$data['channel'];
		$observer = \App::$data['observer'];
	
		$_REQUEST['source'] = 'photos';
		require_once('include/attach.php');
	
		if(! local_channel()) {
			$_REQUEST['contact_allow'] = expand_acl($channel['channel_allow_cid']);
			$_REQUEST['group_allow']   = expand_acl($channel['channel_allow_gid']);
			$_REQUEST['contact_deny']  = expand_acl($channel['channel_deny_cid']);
			$_REQUEST['group_deny']    = expand_acl($channel['channel_deny_gid']);
		}
	
		$r = attach_store($channel,get_observer_hash(), '', $_REQUEST);
	
		if(! $r['success']) {
			notice($r['message'] . EOL);
		}		
		
		if($_REQUEST['newalbum'])
			goaway(z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/album/' . bin2hex($_REQUEST['newalbum']));
		else
			goaway(z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/album/' . bin2hex(datetime_convert('UTC',date_default_timezone_get(),'now', 'Y')));		
	
	}
	
	
	
	function get() {
	
		// URLs:
		// photos/name
		// photos/name/album/xxxxx (xxxxx is album name)
		// photos/name/image/xxxxx
	
	
		if(observer_prohibited()) {
			notice( t('Public access denied.') . EOL);
			return;
		}
	
		$unsafe = ((array_key_exists('unsafe',$_REQUEST) && $_REQUEST['unsafe']) ? 1 : 0);
			
		require_once('include/bbcode.php');
		require_once('include/security.php');
		require_once('include/conversation.php');
	
		if(! x(\App::$data,'channel')) {
			notice( t('No photos selected') . EOL );
			return;
		}
	
		$ph = photo_factory('');
		$phototypes = $ph->supportedTypes();
	
		$_SESSION['photo_return'] = \App::$cmd;
	
		//
		// Parse arguments 
		//
	
		$can_comment = perm_is_allowed(\App::$profile['profile_uid'],get_observer_hash(),'post_comments');
	
		if(argc() > 3) {
			$datatype = argv(2);
			$datum = argv(3);
		} else {
			if(argc() > 2) {
				$datatype = argv(2);
				$datum = '';
			}
			else
				$datatype = 'summary';
		}
	
		if(argc() > 4)
			$cmd = argv(4);
		else
			$cmd = 'view';
	
		//
		// Setup permissions structures
		//
	
		$can_post       = false;
		$visitor        = 0;
	
	
		$owner_uid = \App::$data['channel']['channel_id'];
		$owner_aid = \App::$data['channel']['channel_account_id'];
	
		$observer = \App::get_observer();
	
		$can_post = perm_is_allowed($owner_uid,$observer['xchan_hash'],'write_storage');
		$can_view = perm_is_allowed($owner_uid,$observer['xchan_hash'],'view_storage');
	
		if(! $can_view) {
			notice( t('Access to this item is restricted.') . EOL);
			return;
		}
	
		$sql_extra = permissions_sql($owner_uid);
	
		$o = "";
	
		$o .= "<script> var profile_uid = " . \App::$profile['profile_uid'] 
			. "; var netargs = '?f='; var profile_page = " . \App::$pager['page'] . "; </script>\r\n";
	
		// tabs
	
		$_is_owner = (local_channel() && (local_channel() == $owner_uid));
		$o .= profile_tabs($a,$_is_owner, \App::$data['channel']['channel_address']);	
	
		/**
		 * Display upload form
		 */
	
		if( $can_post) {
	
			$uploader = '';
	
			$ret = array('post_url' => z_root() . '/photos/' . \App::$data['channel']['channel_address'],
					'addon_text' => $uploader,
					'default_upload' => true);
	
			call_hooks('photo_upload_form',$ret);
	
			/* Show space usage */
	
			$r = q("select sum(filesize) as total from photo where aid = %d and imgscale = 0 ",
				intval(\App::$data['channel']['channel_account_id'])
			);
	
	
			$limit = engr_units_to_bytes(service_class_fetch(\App::$data['channel']['channel_id'],'photo_upload_limit'));
			if($limit !== false) {
				$usage_message = sprintf( t("%1$.2f MB of %2$.2f MB photo storage used."), $r[0]['total'] / 1024000, $limit / 1024000 );
			}
			else {
				$usage_message = sprintf( t('%1$.2f MB photo storage used.'), $r[0]['total'] / 1024000 );
	 		}
	
			if($_is_owner) {
				$channel = \App::get_channel();
	
				$acl = new \Zotlabs\Access\AccessList($channel);
				$channel_acl = $acl->get();
	
				$lockstate = (($acl->is_private()) ? 'lock' : 'unlock');
			}
	
			$aclselect = (($_is_owner) ? populate_acl($channel_acl,false, \Zotlabs\Lib\PermissionDescription::fromGlobalPermission('view_storage')) : '');
	
			// this is wrong but is to work around an issue with js_upload wherein it chokes if these variables
			// don't exist. They really should be set to a parseable representation of the channel's default permissions 
			// which can be processed by getSelected() 
	
			if(! $aclselect) {
				$aclselect = '<input id="group_allow" type="hidden" name="allow_gid[]" value="" /><input id="contact_allow" type="hidden" name="allow_cid[]" value="" /><input id="group_deny" type="hidden" name="deny_gid[]" value="" /><input id="contact_deny" type="hidden" name="deny_cid[]" value="" />';
			}
	
			$selname = (($datum) ? hex2bin($datum) : '');
	
			$albums = ((array_key_exists('albums', \App::$data)) ? \App::$data['albums'] : photos_albums_list(\App::$data['channel'],\App::$data['observer']));
	
			if(! $selname) {
				$def_album = get_pconfig(\App::$data['channel']['channel_id'],'system','photo_path');
				if($def_album) {
					$selname = filepath_macro($def_album);
					$albums['album'][] = array('text' => $selname);
				}
			}
	
			$tpl = get_markup_template('photos_upload.tpl');
			$upload_form = replace_macros($tpl,array(
				'$pagename' => t('Upload Photos'),
				'$sessid' => session_id(),
				'$usage' => $usage_message,
				'$nickname' => \App::$data['channel']['channel_address'],
				'$newalbum_label' => t('Enter an album name'),
				'$newalbum_placeholder' => t('or select an existing album (doubleclick)'),
				'$visible' => array('visible', t('Create a status post for this upload'), 0,'', array(t('No'), t('Yes')), 'onclick="showHideBodyTextarea();"'),
				'$caption' => array('description', t('Caption (optional):')),
				'$body' => array('body', t('Description (optional):'),'', 'Description will only appear in the status post'),
				'$albums' => $albums['albums'],
				'$selname' => $selname,
				'$permissions' => t('Permissions'),
				'$aclselect' => $aclselect,
				'$allow_cid' => acl2json($channel_acl['allow_cid']),
				'$allow_gid' => acl2json($channel_acl['allow_gid']),
				'$deny_cid' => acl2json($channel_acl['deny_cid']),
				'$deny_gid' => acl2json($channel_acl['deny_gid']),
				'$lockstate' => $lockstate,
				'$uploader' => $ret['addon_text'],
				'$default' => (($ret['default_upload']) ? true : false),
				'$uploadurl' => $ret['post_url'],
				'$submit' => t('Submit')
	
			));
	
		}
	
		//
		// dispatch request
		//
	
		/*
		 * Display a single photo album
		 */
	
		if($datatype === 'album') {
	
			if(strlen($datum)) {
				if((strlen($datum) & 1) || (! ctype_xdigit($datum))) {
					notice( t('Album name could not be decoded') . EOL);
					logger('mod_photos: illegal album encoding: ' . $datum);
					$datum = '';
				}
			}
	
			$album = (($datum) ? hex2bin($datum) : '');
	
	
			\App::$page['htmlhead'] .= "\r\n" . '<link rel="alternate" type="application/json+oembed" href="' . z_root() . '/oep?f=&url=' . urlencode(z_root() . '/' . \App::$cmd) . '" title="oembed" />' . "\r\n";
	
	
			$r = q("SELECT resource_id, max(imgscale) AS imgscale FROM photo WHERE uid = %d AND album = '%s' 
				AND imgscale <= 4 and photo_usage IN ( %d, %d ) and is_nsfw = %d $sql_extra GROUP BY resource_id",
				intval($owner_uid),
				dbesc($album),
				intval(PHOTO_NORMAL),
				intval(PHOTO_PROFILE),
				intval($unsafe)
			);
			if(count($r)) {
				\App::set_pager_total(count($r));
				\App::set_pager_itemspage(60);
			} else {
				goaway(z_root() . '/photos/' . \App::$data['channel']['channel_address']);
			}
	
			if($_GET['order'] === 'posted')
				$order = 'ASC';
			else
				$order = 'DESC';
	
				
			$r = q("SELECT p.resource_id, p.id, p.filename, p.mimetype, p.imgscale, p.description, p.created FROM photo p INNER JOIN
					(SELECT resource_id, max(imgscale) imgscale FROM photo WHERE uid = %d AND album = '%s' AND imgscale <= 4 AND photo_usage IN ( %d, %d ) and is_nsfw = %d $sql_extra GROUP BY resource_id) ph 
					ON (p.resource_id = ph.resource_id AND p.imgscale = ph.imgscale)
				ORDER BY created $order LIMIT %d OFFSET %d",
				intval($owner_uid),
				dbesc($album),
				intval(PHOTO_NORMAL),
				intval(PHOTO_PROFILE),
				intval($unsafe),
				intval(\App::$pager['itemspage']),
				intval(\App::$pager['start'])
			);
			
			//edit album name
			$album_edit = null;
			if(($album !== t('Profile Photos')) && ($album !== 'Profile Photos') && ($album !== 'Contact Photos') && ($album !== t('Contact Photos'))) {
				if($can_post) {
					$album_e = $album;
					$albums = ((array_key_exists('albums', \App::$data)) ? \App::$data['albums'] : photos_albums_list(\App::$data['channel'],\App::$data['observer']));
	
					// @fixme - syncronise actions with DAV
		
	//				$edit_tpl = get_markup_template('album_edit.tpl');
	//				$album_edit = replace_macros($edit_tpl,array(
	//					'$nametext' => t('Enter a new album name'),
	//					'$name_placeholder' => t('or select an existing one (doubleclick)'),
	//					'$nickname' => \App::$data['channel']['channel_address'],
	//					'$album' => $album_e,
	//					'$albums' => $albums['albums'],
	//					'$hexalbum' => bin2hex($album),
	//					'$submit' => t('Submit'),
	//					'$dropsubmit' => t('Delete Album')
	//				));
	
				}
			}
	
			if($_GET['order'] === 'posted')
				$order =  array(t('Show Newest First'), z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/album/' . bin2hex($album));
			else
				$order = array(t('Show Oldest First'), z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/album/' . bin2hex($album) . '?f=&order=posted');
	
			$photos = array();
			if(count($r)) {
				$twist = 'rotright';
				foreach($r as $rr) {
	
					if($twist == 'rotright')
						$twist = 'rotleft';
					else
						$twist = 'rotright';
					
					$ext = $phototypes[$rr['mimetype']];
	
					$imgalt_e = $rr['filename'];
					$desc_e = $rr['description'];
	
					$imagelink = (z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/image/' . $rr['resource_id']
					. (($_GET['order'] === 'posted') ? '?f=&order=posted' : ''));
	
					$photos[] = array(
						'id' => $rr['id'],
						'twist' => ' ' . $twist . rand(2,4),
						'link' => $imagelink,
						'title' => t('View Photo'),
						'src' => z_root() . '/photo/' . $rr['resource_id'] . '-' . $rr['imgscale'] . '.' .$ext,
						'alt' => $imgalt_e,
						'desc'=> $desc_e,
						'ext' => $ext,
						'hash'=> $rr['resource_id'],
						'unknown' => t('Unknown')
					);
				}
			}
	
			if($_REQUEST['aj']) {
				if($photos) {
					$o = replace_macros(get_markup_template('photosajax.tpl'),array(
						'$photos' => $photos,
						'$album_id' => bin2hex($album)
					));
				}
				else {
					$o = '<div id="content-complete"></div>';
				}
				echo $o;
				killme();
			}
			else {
				$o .= "<script> var page_query = '" . $_GET['q'] . "'; var extra_args = '" . extra_query_args() . "' ; </script>";
				$tpl = get_markup_template('photo_album.tpl');
				$o .= replace_macros($tpl, array(
					'$photos' => $photos,
					'$album' => $album,
					'$album_id' => bin2hex($album),
					'$album_edit' => array(t('Edit Album'), $album_edit),
					'$can_post' => $can_post,
					'$upload' => array(t('Upload'), z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/upload/' . bin2hex($album)),
					'$order' => $order,
					'$upload_form' => $upload_form,
					'$usage' => $usage_message
				));
	
			}
	
			if((! $photos) && ($_REQUEST['aj'])) {
				$o .= '<div id="content-complete"></div>';
				echo $o;
				killme();
			}
	
	//		$o .= paginate($a);
	
			return $o;
	
		}	
	
		/** 
		 * Display one photo
		 */
	
		if($datatype === 'image') {
	
			\App::$page['htmlhead'] .= "\r\n" . '<link rel="alternate" type="application/json+oembed" href="' . z_root() . '/oep?f=&url=' . urlencode(z_root() . '/' . \App::$cmd) . '" title="oembed" />' . "\r\n";
	
			// fetch image, item containing image, then comments
	
			$ph = q("SELECT id,aid,uid,xchan,resource_id,created,edited,title,description,album,filename,mimetype,height,width,filesize,imgscale,photo_usage,is_nsfw,allow_cid,allow_gid,deny_cid,deny_gid FROM photo WHERE uid = %d AND resource_id = '%s' 
				$sql_extra ORDER BY imgscale ASC ",
				intval($owner_uid),
				dbesc($datum)
			);
	
			if(! $ph) {
	
				/* Check again - this time without specifying permissions */
	
				$ph = q("SELECT id FROM photo WHERE uid = %d AND resource_id = '%s' LIMIT 1",
					intval($owner_uid),
					dbesc($datum)
				);
				if($ph) 
					notice( t('Permission denied. Access to this item may be restricted.') . EOL);
				else
					notice( t('Photo not available') . EOL );
				return;
			}
	
	
	
			$prevlink = '';
			$nextlink = '';
	
			if($_GET['order'] === 'posted')
				$order = 'ASC';
			else
				$order = 'DESC';
	
	
			$prvnxt = q("SELECT resource_id FROM photo WHERE album = '%s' AND uid = %d AND imgscale = 0 
				$sql_extra ORDER BY created $order ",
				dbesc($ph[0]['album']),
				intval($owner_uid)
			); 
	
			if(count($prvnxt)) {
				for($z = 0; $z < count($prvnxt); $z++) {
					if($prvnxt[$z]['resource_id'] == $ph[0]['resource_id']) {
						$prv = $z - 1;
						$nxt = $z + 1;
						if($prv < 0)
							$prv = count($prvnxt) - 1;
						if($nxt >= count($prvnxt))
							$nxt = 0;
						break;
					}
				}
	
				$prevlink = z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/image/' . $prvnxt[$prv]['resource_id'] . (($_GET['order'] === 'posted') ? '?f=&order=posted' : '');
				$nextlink = z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/image/' . $prvnxt[$nxt]['resource_id'] . (($_GET['order'] === 'posted') ? '?f=&order=posted' : '');
	 		}
	
	
			if(count($ph) == 1)
				$hires = $lores = $ph[0];
			if(count($ph) > 1) {
				if($ph[1]['imgscale'] == 2) {
					// original is 640 or less, we can display it directly
					$hires = $lores = $ph[0];
				}
				else {
				$hires = $ph[0];
				$lores = $ph[1];
				}
			}
	
			$album_link = z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/album/' . bin2hex($ph[0]['album']);
	 		$tools = Null;
	 		$lock = Null;
	 
			if($can_post && ($ph[0]['uid'] == $owner_uid)) {
				$tools = array(
					'profile'=>array(z_root() . '/profile_photo/use/'.$ph[0]['resource_id'], t('Use as profile photo')),
					'cover'=>array(z_root() . '/cover_photo/use/'.$ph[0]['resource_id'], t('Use as cover photo')),
				);
			}
	
			// lockstate
			$lockstate = ( ( (strlen($ph[0]['allow_cid']) || strlen($ph[0]['allow_gid'])
					|| strlen($ph[0]['deny_cid']) || strlen($ph[0]['deny_gid'])) )
					? array('lock', t('Private Photo'))
					: array('unlock', Null));
	
			\App::$page['htmlhead'] .= '<script>$(document).keydown(function(event) {' . "\n";
			if($prevlink)
				\App::$page['htmlhead'] .= 'if(event.ctrlKey && event.keyCode == 37) { event.preventDefault(); window.location.href = \'' . $prevlink . '\'; }' . "\n";
			if($nextlink)
				\App::$page['htmlhead'] .= 'if(event.ctrlKey && event.keyCode == 39) { event.preventDefault(); window.location.href = \'' . $nextlink . '\'; }' . "\n";
			\App::$page['htmlhead'] .= '});</script>';
	
			if($prevlink)
				$prevlink = array($prevlink, t('Previous'));
	
			$photo = array(
				'href' => z_root() . '/photo/' . $hires['resource_id'] . '-' . $hires['imgscale'] . '.' . $phototypes[$hires['mimetype']],
				'title'=> t('View Full Size'),
				'src'  => z_root() . '/photo/' . $lores['resource_id'] . '-' . $lores['imgscale'] . '.' . $phototypes[$lores['mimetype']] . '?f=&_u=' . datetime_convert('','','','ymdhis')
			);
	
			if($nextlink)
				$nextlink = array($nextlink, t('Next'));
	
	
			// Do we have an item for this photo?
	
			$linked_items = q("SELECT * FROM item WHERE resource_id = '%s' and resource_type = 'photo' 
				$sql_extra LIMIT 1",
				dbesc($datum)
			);
	
			$map = null;
	
			if($linked_items) {
	
				xchan_query($linked_items);
				$linked_items = fetch_post_tags($linked_items,true);
	
				$link_item = $linked_items[0];
				$item_normal = item_normal();
	
				$r = q("select * from item where parent_mid = '%s' 
					$item_normal and uid = %d $sql_extra ",
					dbesc($link_item['mid']),
					intval($link_item['uid'])
	
				);
	
				if($r) {
					xchan_query($r);
					$r = fetch_post_tags($r,true);
					$r = conv_sort($r,'commented');
				}
	
				$tags = array();
				if($link_item['term']) {
					$cnt = 0;
					foreach($link_item['term'] as $t) {
						$tags[$cnt] = array(0 => format_term_for_display($t));
						if($can_post && ($ph[0]['uid'] == $owner_uid)) {
							$tags[$cnt][1] = 'tagrm/drop/' . $link_item['id'] . '/' . bin2hex($t['term']);   //?f=&item=' . $link_item['id'];
							$tags[$cnt][2] = t('Remove');
						}
						$cnt ++;
					}
				}
	
				if((local_channel()) && (local_channel() == $link_item['uid'])) {
					q("UPDATE item SET item_unseen = 0 WHERE parent = %d and uid = %d and item_unseen = 1",
						intval($link_item['parent']),
						intval(local_channel())
					);
				}
	
				if($link_item['coord']) {
					$map = generate_map($link_item['coord']);
				}
			}
	
	//		logger('mod_photo: link_item' . print_r($link_item,true));
	
			// FIXME - remove this when we move to conversation module 
	
			$r = $r[0]['children'];

			$edit = null;
			if($can_post) {

				$m = q("select folder from attach where hash = '%s' and uid = %d limit 1",
					dbesc($ph[0]['resource_id']),
					intval($ph[0]['uid'])
				);
				if($m)
					$album_hash = $m[0]['folder'];
					
				$album_e = $ph[0]['album'];
				$caption_e = $ph[0]['description'];
				$aclselect_e = (($_is_owner) ? populate_acl($ph[0], true, \Zotlabs\Lib\PermissionDescription::fromGlobalPermission('view_storage')) : '');
				$albums = ((array_key_exists('albums', \App::$data)) ? \App::$data['albums'] : photos_albums_list(\App::$data['channel'],\App::$data['observer']));
	
				$_SESSION['album_return'] = bin2hex($ph[0]['album']);

				$folder_list = attach_folder_select_list($ph[0]['uid']);
	
				$edit = array(
					'edit' => t('Edit photo'),
					'id' => $link_item['id'],
					'rotatecw' => t('Rotate CW (right)'),
					'rotateccw' => t('Rotate CCW (left)'),
					'albums' => $albums['albums'],
					'album' => $album_e,
					'album_select' => [ 'move_to_album', t('Move photo to album'), $album_hash, '', $folder_list ],
					'newalbum_label' => t('Enter a new album name'),
					'newalbum_placeholder' => t('or select an existing one (doubleclick)'),
					'nickname' => \App::$data['channel']['channel_address'],
					'resource_id' => $ph[0]['resource_id'],
					'capt_label' => t('Caption'),
					'caption' => $caption_e,
					'tag_label' => t('Add a Tag'),
					'permissions' => t('Permissions'),
					'aclselect' => $aclselect_e,
					'allow_cid' => acl2json($ph[0]['allow_cid']),
					'allow_gid' => acl2json($ph[0]['allow_gid']),
					'deny_cid' => acl2json($ph[0]['deny_cid']),
					'deny_gid' => acl2json($ph[0]['deny_gid']),
					'lockstate' => $lockstate[0],
					'help_tags' => t('Example: @bob, @Barbara_Jensen, @jim@example.com'),
					'item_id' => ((count($linked_items)) ? $link_item['id'] : 0),
					'adult_enabled' => feature_enabled($owner_uid,'adult_photo_flagging'),
					'adult' => array('adult',t('Flag as adult in album view'), intval($ph[0]['is_nsfw']),''),
					'submit' => t('Submit'),
					'delete' => t('Delete Photo')
				);
			}
	
			if(count($linked_items)) {
	
				$cmnt_tpl = get_markup_template('comment_item.tpl');
				$tpl = get_markup_template('photo_item.tpl');
				$return_url = \App::$cmd;
	
				$like_tpl = get_markup_template('like_noshare.tpl');
	
				$likebuttons = '';
	
				if($can_post || $can_comment) {
					$likebuttons = array(
						'id' => $link_item['id'],
						'likethis' => t("I like this \x28toggle\x29"),
						'nolike' => t("I don't like this \x28toggle\x29"),
						'share' => t('Share'),
						'wait' => t('Please wait')
					);
				}
	
				$comments = '';
				if(! count($r)) {
					if($can_post || $can_comment) {
						$commentbox = replace_macros($cmnt_tpl,array(
							'$return_path' => '', 
							'$mode' => 'photos',
							'$jsreload' => $return_url,
							'$type' => 'wall-comment',
							'$id' => $link_item['id'],
							'$parent' => $link_item['id'],
							'$profile_uid' =>  $owner_uid,
							'$mylink' => $observer['xchan_url'],
							'$mytitle' => t('This is you'),
							'$myphoto' => $observer['xchan_photo_s'],
							'$comment' => t('Comment'),
							'$submit' => t('Submit'),
							'$preview' => t('Preview'),
							'$ww' => '',
							'$feature_encrypt' => false
						));
					}
				}
	
				$alike = array();
				$dlike = array();
				
				$like = '';
				$dislike = '';
	
				$conv_responses = array(
					'like' => array('title' => t('Likes','title')),'dislike' => array('title' => t('Dislikes','title')),
					'agree' => array('title' => t('Agree','title')),'disagree' => array('title' => t('Disagree','title')), 'abstain' => array('title' => t('Abstain','title')), 
					'attendyes' => array('title' => t('Attending','title')), 'attendno' => array('title' => t('Not attending','title')), 'attendmaybe' => array('title' => t('Might attend','title'))
				);
	
	
	
	
				if($r) {
	
					foreach($r as $item) {
						builtin_activity_puller($item, $conv_responses);
					}
	
	
					$like_count = ((x($alike,$link_item['mid'])) ? $alike[$link_item['mid']] : '');
					$like_list = ((x($alike,$link_item['mid'])) ? $alike[$link_item['mid'] . '-l'] : '');
					if (count($like_list) > MAX_LIKERS) {
						$like_list_part = array_slice($like_list, 0, MAX_LIKERS);
						array_push($like_list_part, '<a href="#" data-toggle="modal" data-target="#likeModal-' . $this->get_id() . '"><b>' . t('View all') . '</b></a>');
					} else {
						$like_list_part = '';
					}
					$like_button_label = tt('Like','Likes',$like_count,'noun');
	
					//if (feature_enabled($conv->get_profile_owner(),'dislike')) {
						$dislike_count = ((x($dlike,$link_item['mid'])) ? $dlike[$link_item['mid']] : '');
						$dislike_list = ((x($dlike,$link_item['mid'])) ? $dlike[$link_item['mid'] . '-l'] : '');
						$dislike_button_label = tt('Dislike','Dislikes',$dislike_count,'noun');
						if (count($dislike_list) > MAX_LIKERS) {
							$dislike_list_part = array_slice($dislike_list, 0, MAX_LIKERS);
							array_push($dislike_list_part, '<a href="#" data-toggle="modal" data-target="#dislikeModal-' . $this->get_id() . '"><b>' . t('View all') . '</b></a>');
						} else {
							$dislike_list_part = '';
						}
					//}
	
	
					$like    = ((isset($alike[$link_item['mid']])) ? format_like($alike[$link_item['mid']],$alike[$link_item['mid'] . '-l'],'like',$link_item['mid']) : '');
					$dislike = ((isset($dlike[$link_item['mid']])) ? format_like($dlike[$link_item['mid']],$dlike[$link_item['mid'] . '-l'],'dislike',$link_item['mid']) : '');
	
					// display comments
	
					foreach($r as $item) {
						$comment = '';
						$template = $tpl;
						$sparkle = '';
	
						if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE))) && ($item['id'] != $item['parent']))
							continue;
	
						$redirect_url = z_root() . '/redir/' . $item['cid'] ;
				
	
						$profile_url = zid($item['author']['xchan_url']);
						$sparkle = '';
	
	
						$profile_name   = $item['author']['xchan_name'];
						$profile_avatar = $item['author']['xchan_photo_m'];
	
						$profile_link = $profile_url;
	
						$drop = '';
	
						if($observer['xchan_hash'] === $item['author_xchan'] || $observer['xchan_hash'] === $item['owner_xchan'])
							$drop = replace_macros(get_markup_template('photo_drop.tpl'), array('$id' => $item['id'], '$delete' => t('Delete')));
	
	
						$name_e = $profile_name;
						$title_e = $item['title'];
						unobscure($item);
						$body_e = prepare_text($item['body'],$item['mimetype']);
	
						$comments .= replace_macros($template,array(
							'$id' => $item['id'],
							'$mode' => 'photos',
							'$profile_url' => $profile_link,
							'$name' => $name_e,
							'$thumb' => $profile_avatar,
							'$sparkle' => $sparkle,
							'$title' => $title_e,
							'$body' => $body_e,
							'$ago' => relative_date($item['created']),
							'$indent' => (($item['parent'] != $item['id']) ? ' comment' : ''),
							'$drop' => $drop,
							'$comment' => $comment
						));
	
					}
				
					if($can_post || $can_comment) {
						$commentbox = replace_macros($cmnt_tpl,array(
							'$return_path' => '',
							'$jsreload' => $return_url,
							'$type' => 'wall-comment',
							'$id' => $link_item['id'],
							'$parent' => $link_item['id'],
							'$profile_uid' =>  $owner_uid,
							'$mylink' => $observer['xchan_url'],
							'$mytitle' => t('This is you'),
							'$myphoto' => $observer['xchan_photo_s'],
							'$comment' => t('Comment'),
							'$submit' => t('Submit'),
							'$ww' => ''
						));
					}
	
				}
				$paginate = paginate($a);
			}
			
			$album_e = array($album_link,$ph[0]['album']);
			$like_e = $like;
			$dislike_e = $dislike;
	
	
			$response_verbs = array('like');
			if(feature_enabled($owner_uid,'dislike'))
				$response_verbs[] = 'dislike';
	
	
			$responses = get_responses($conv_responses,$response_verbs,'',$link_item);
	
			$photo_tpl = get_markup_template('photo_view.tpl');
			$o .= replace_macros($photo_tpl, array(
				'$id' => $ph[0]['id'],
				'$album' => $album_e,
				'$tools_label' => t('Photo Tools'),
				'$tools' => $tools,
				'$lock' => $lockstate[1],
				'$photo' => $photo,
				'$prevlink' => $prevlink,
				'$nextlink' => $nextlink,
				'$desc' => $ph[0]['description'],
				'$filename' => $ph[0]['filename'],
				'$unknown' => t('Unknown'),
				'$tag_hdr' => t('In This Photo:'),
				'$tags' => $tags,
				'responses' => $responses,
				'$edit' => $edit,	
				'$map' => $map,
				'$map_text' => t('Map'),
				'$likebuttons' => $likebuttons,
				'$like' => $like_e,
				'$dislike' => $dislike_e,
				'$like_count' => $like_count,
				'$like_list' => $like_list,
				'$like_list_part' => $like_list_part,
				'$like_button_label' => $like_button_label,
				'$like_modal_title' => t('Likes','noun'),
				'$dislike_modal_title' => t('Dislikes','noun'),
				'$dislike_count' => $dislike_count,  //((feature_enabled($conv->get_profile_owner(),'dislike')) ? $dislike_count : ''),
				'$dislike_list' => $dislike_list, //((feature_enabled($conv->get_profile_owner(),'dislike')) ? $dislike_list : ''),
				'$dislike_list_part' => $dislike_list_part, //((feature_enabled($conv->get_profile_owner(),'dislike')) ? $dislike_list_part : ''),
				'$dislike_button_label' => $dislike_button_label, //((feature_enabled($conv->get_profile_owner(),'dislike')) ? $dislike_button_label : ''),
				'$modal_dismiss' => t('Close'),
				'$comments' => $comments,
				'$commentbox' => $commentbox,
				'$paginate' => $paginate,
			));
	
			\App::$data['photo_html'] = $o;
			
			return $o;
		}
	
		// Default - show recent photos with upload link (if applicable)
		//$o = '';
	
			\App::$page['htmlhead'] .= "\r\n" . '<link rel="alternate" type="application/json+oembed" href="' . z_root() . '/oep?f=&url=' . urlencode(z_root() . '/' . \App::$cmd) . '" title="oembed" />' . "\r\n";
	
	
		$r = q("SELECT resource_id, max(imgscale) AS imgscale FROM photo WHERE uid = %d 
			and photo_usage in ( %d, %d ) and is_nsfw = %d $sql_extra GROUP BY resource_id",
			intval(\App::$data['channel']['channel_id']),
			intval(PHOTO_NORMAL),
			intval(PHOTO_PROFILE),
			intval($unsafe)
		);
		if($r) {
			\App::set_pager_total(count($r));
			\App::set_pager_itemspage(60);
		}
		
		$r = q("SELECT p.resource_id, p.id, p.filename, p.mimetype, p.album, p.imgscale, p.created FROM photo p 
			INNER JOIN ( SELECT resource_id, max(imgscale) imgscale FROM photo 
				WHERE uid = %d AND photo_usage IN ( %d, %d ) 
				AND is_nsfw = %d $sql_extra group by resource_id ) ph 
			ON (p.resource_id = ph.resource_id and p.imgscale = ph.imgscale) 
			ORDER by p.created DESC LIMIT %d OFFSET %d",
			intval(\App::$data['channel']['channel_id']),
			intval(PHOTO_NORMAL),
			intval(PHOTO_PROFILE),
			intval($unsafe),
			intval(\App::$pager['itemspage']),
			intval(\App::$pager['start'])
		);
	
	
	
		$photos = array();
		if($r) {
			$twist = 'rotright';
			foreach($r as $rr) {
				if($twist == 'rotright')
					$twist = 'rotleft';
				else
					$twist = 'rotright';
				$ext = $phototypes[$rr['mimetype']];
				
				if(\App::get_template_engine() === 'internal') {
					$alt_e = template_escape($rr['filename']);
					$name_e = template_escape($rr['album']);
				}
				else {
					$alt_e = $rr['filename'];
					$name_e = $rr['album'];
				}
	
				$photos[] = array(
					'id'       => $rr['id'],
					'twist'    => ' ' . $twist . rand(2,4),
					'link'  	=> z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/image/' . $rr['resource_id'],
					'title' 	=> t('View Photo'),
					'src'     	=> z_root() . '/photo/' . $rr['resource_id'] . '-' . ((($rr['imgscale']) == 6) ? 4 : $rr['imgscale']) . '.' . $ext,
					'alt'     	=> $alt_e,
					'album'	=> array(
						'link'  => z_root() . '/photos/' . \App::$data['channel']['channel_address'] . '/album/' . bin2hex($rr['album']),
						'name'  => $name_e,
						'alt'   => t('View Album'),
					),
					
				);
			}
		}
		
		if($_REQUEST['aj']) {
			if($photos) {
				$o = replace_macros(get_markup_template('photosajax.tpl'),array(
					'$photos' => $photos,
					'$album_id' => bin2hex(t('Recent Photos'))
				));
			}
			else {
				$o = '<div id="content-complete"></div>';
			}
			echo $o;
			killme();
		}
		else {
			$o .= "<script> var page_query = '" . $_GET['q'] . "'; var extra_args = '" . extra_query_args() . "' ; </script>";
			$tpl = get_markup_template('photos_recent.tpl'); 
			$o .= replace_macros($tpl, array(
				'$title' => t('Recent Photos'),
				'$album_id' => bin2hex(t('Recent Photos')),
				'$can_post' => $can_post,
				'$upload' => array(t('Upload'), z_root().'/photos/'.\App::$data['channel']['channel_address'].'/upload'),
				'$photos' => $photos,
				'$upload_form' => $upload_form,
				'$usage' => $usage_message
			));
	
		}
	
		if((! $photos) && ($_REQUEST['aj'])) {
			$o .= '<div id="content-complete"></div>';
			echo $o;
			killme();
		}
	
	//	paginate($a);
		return $o;
	}
	
	
}
