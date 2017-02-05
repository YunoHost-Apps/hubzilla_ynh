<?php

namespace Zotlabs\Module;

/**
 * @brief
 *
 */
class Embedphotos extends \Zotlabs\Web\Controller {

	function get() {

	}

	/**
	 *
	 * This is the POST destination for the embedphotos button
	 *
	 */
	function post() {
		if (argc() > 1 && argv(1) === 'album') {
			// API: /embedphotos/album
			$name = (x($_POST,'name') ? $_POST['name'] : null );
			if(!$name) {
				json_return_and_die(array('errormsg' => 'Error retrieving album', 'status' => false));
			}
			$album = $this->embedphotos_widget_album(array('channel' => \App::get_channel(), 'album' => $name));
			json_return_and_die(array('status' => true, 'content' => $album));
		}
		if(argc() > 1 && argv(1) === 'albumlist') {
			// API: /embedphotos/albumlist
			$album_list = $this->embedphotos_album_list($a);
			json_return_and_die(array('status' => true, 'albumlist' => $album_list));
		}
		if(argc() > 1 && argv(1) === 'photolink') {
			// API: /embedphotos/photolink
			$href = (x($_POST,'href') ? $_POST['href'] : null );
			if(!$href) {
				json_return_and_die(array('errormsg' => 'Error retrieving link ' . $href, 'status' => false));
			}
			$resource_id = array_pop(explode("/", $href));
			$r = q("SELECT obj from item where resource_type = 'photo' and resource_id = '%s' limit 1",
				dbesc($resource_id)
			);
			if(!$r) {
				json_return_and_die(array('errormsg' => 'Error retrieving resource ' . $resource_id, 'status' => false));
			}
			$obj = json_decode($r[0]['obj'], true);
			if(x($obj,'body')) {
				$photolink = $obj['body'];
			} elseif (x($obj,'bbcode')) {
				$photolink = $obj['bbcode'];
			} else {
				json_return_and_die(array('errormsg' => 'Error retrieving resource ' . $resource_id, 'status' => false));
			}
			json_return_and_die(array('status' => true, 'photolink' => $photolink));
		}
	}

	/**
	 * Copied from include/widgets.php::widget_album() with a modification to get the profile_uid from
	 * the input array as in widget_item()
	 *
	 * @param array $args
	 * @return string with HTML
	 */
	function embedphotos_widget_album($args) {

		$channel_id = 0;
		if(array_key_exists('channel', $args))
			$channel = $args['channel'];
		$channel_id = intval($channel['channel_id']);
		if(! $channel_id)
			$channel_id = \App::$profile_uid;
		if(! $channel_id)
			return '';

		$owner_uid = $channel_id;
		require_once('include/security.php');
		$sql_extra = permissions_sql($channel_id);

		if(! perm_is_allowed($channel_id,get_observer_hash(),'view_storage'))
			return '';

		if($args['album'])
			$album = $args['album'];
		if($args['title'])
			$title = $args['title'];

		/**
		 * This may return incorrect permissions if you have multiple directories of the same name.
		 * It is a limitation of the photo table using a name for a photo album instead of a folder hash
		 */
		if($album) {
			$x = q("select hash from attach where filename = '%s' and uid = %d limit 1",
				dbesc($album),
				intval($owner_uid)
			);
			if($x) {
				$y = attach_can_view_folder($owner_uid,get_observer_hash(),$x[0]['hash']);
				if(! $y)
					return '';
			}
		}

		$order = 'DESC';

		$r = q("SELECT p.resource_id, p.id, p.filename, p.mimetype, p.imgscale, p.description, p.created FROM photo p INNER JOIN
				(SELECT resource_id, max(imgscale) imgscale FROM photo WHERE uid = %d AND album = '%s' AND imgscale <= 4 AND photo_usage IN ( %d, %d ) $sql_extra GROUP BY resource_id) ph
				ON (p.resource_id = ph.resource_id AND p.imgscale = ph.imgscale)
				ORDER BY created $order",
				intval($owner_uid),
				dbesc($album),
				intval(PHOTO_NORMAL),
				intval(PHOTO_PROFILE)
		);

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

		$tpl = get_markup_template('photo_album.tpl');
		$o .= replace_macros($tpl, array(
			'$photos' => $photos,
			'$album' => (($title) ? $title : $album),
			'$album_id' => rand(),
			'$album_edit' => array(t('Edit Album'), $album_edit),
			'$can_post' => false,
			'$upload' => array(t('Upload'), z_root() . '/photos/' . \App::$profile['channel_address'] . '/upload/' . bin2hex($album)),
			'$order' => false,
			'$upload_form' => $upload_form,
			'$no_fullscreen_btn' => true
		));

		return $o;
	}

	function embedphotos_album_list($a) {
		require_once('include/photos.php');
		$p = photos_albums_list(\App::get_channel(), \App::get_observer());
		if($p['success']) {
			return $p['albums'];
		} else {
			return null;
		}
	}

}
