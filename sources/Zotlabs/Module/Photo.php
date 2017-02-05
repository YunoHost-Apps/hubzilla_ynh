<?php
namespace Zotlabs\Module;

require_once('include/security.php');
require_once('include/attach.php');
require_once('include/photo/photo_driver.php');


class Photo extends \Zotlabs\Web\Controller {

	function init() {
	
		$prvcachecontrol = false;
		$streaming = null;
		$channel = null;
	
		switch(argc()) {
			case 4:
				$person = argv(3);
				$res    = argv(2);
				$type   = argv(1);
				break;
			case 2:
				$photo = argv(1);
				break;
			case 1:
			default:
				killme();
				// NOTREACHED
		}
	
		$observer_xchan = get_observer_hash();
	
		$default = get_default_profile_photo();
	
		if(isset($type)) {
	
			/**
			 * Profile photos - Access controls on default profile photos are not honoured since they need to be exchanged with remote sites.
			 * 
			 */
	
			if($type === 'profile') {
				switch($res) {
	
					case 'm':
						$resolution = 5;
						$default = get_default_profile_photo(80);
						break;
					case 's':
						$resolution = 6;
						$default = get_default_profile_photo(48);
						break;
					case 'l':
					default:
						$resolution = 4;
						break;
				}
			}
	
			$uid = $person;

			$d = [ 'imgscale' => $resolution, 'channel_id' => $uid, 'default' => $default, 'data'  => '', 'mimetype' => '' ];
			call_hooks('get_profile_photo',$d);

			$resolution = $d['imgscale'];
			$uid        = $d['channel_id']; 	
			$default    = $d['default'];
			$data       = $d['data'];
			$mimetype   = $d['mimetype'];

			if(! $data) {
				$r = q("SELECT * FROM photo WHERE imgscale = %d AND uid = %d AND photo_usage = %d LIMIT 1",
					intval($resolution),
					intval($uid),
					intval(PHOTO_PROFILE)
				);
				if($r) {
					$data = dbunescbin($r[0]['content']);
					$mimetype = $r[0]['mimetype'];
				}
				if(intval($r[0]['os_storage']))
					$data = file_get_contents($data);
			}
			if(! $data) {
				$data = file_get_contents($default);
			}
			if(! $mimetype) {
				$mimetype = 'image/png';
			}
		}
		else {
	
			/**
			 * Other photos
			 */
	
			/* Check for a cookie to indicate display pixel density, in order to detect high-resolution
			   displays. This procedure was derived from the "Retina Images" by Jeremey Worboys,
			   used in accordance with the Creative Commons Attribution 3.0 Unported License.
			   Project link: https://github.com/Retina-Images/Retina-Images
			   License link: http://creativecommons.org/licenses/by/3.0/
			*/

			$cookie_value = false;
			if (isset($_COOKIE['devicePixelRatio'])) {
			  $cookie_value = intval($_COOKIE['devicePixelRatio']);
			}
			else {
			  // Force revalidation of cache on next request
			  $cache_directive = 'no-cache';
			  $status = 'no cookie';
			}
	
			$resolution = 0;
	
			if(strpos($photo,'.') !== false)
				$photo = substr($photo,0,strpos($photo,'.'));
		
			if(substr($photo,-2,1) == '-') {
				$resolution = intval(substr($photo,-1,1));
				$photo = substr($photo,0,-2);
				// If viewing on a high-res screen, attempt to serve a higher resolution image:
				if ($resolution == 2 && ($cookie_value > 1))
				  {
				    $resolution = 1;
				  }
			}
			

			$r = q("SELECT uid FROM photo WHERE resource_id = '%s' AND imgscale = %d LIMIT 1",
				dbesc($photo),
				intval($resolution)
			);
			if($r) {
				
				$allowed = (($r[0]['uid']) ? perm_is_allowed($r[0]['uid'],$observer_xchan,'view_storage') : true);
	
				$sql_extra = permissions_sql($r[0]['uid']);

				if(! $sql_extra)
					$sql_extra = ' and true ';

				// Only check permissions on normal photos. Those photos we don't check includes
				// profile photos, xchan photos (which are also profile photos), 'thing' photos,
				// and cover photos
	
				$sql_extra = " and (( photo_usage = 0 $sql_extra ) or photo_usage != 0 )";

				$channel = channelx_by_n($r[0]['uid']);

				// Now we'll see if we can access the photo
	
				$r = q("SELECT * FROM photo WHERE resource_id = '%s' AND imgscale = %d $sql_extra LIMIT 1",
					dbesc($photo),
					intval($resolution)
				);
	
				$d = [ 'imgscale' => $resolution, 'resource_id' => $photo, 'photo' => $r, 'allowed' => $allowed ];
				call_hooks('get_photo',$d);

				$resolution = $d['imgscale'];
				$photo      = $d['resource_id'];
				$r          = $d['photo'];
				$allowed    = $d['allowed'];

				if($r && $allowed) {
					$data = dbunescbin($r[0]['content']);
					$mimetype = $r[0]['mimetype'];
					if(intval($r[0]['os_storage'])) {
						$streaming = $data;
					}
				}
				else {
	
					// Does the picture exist? It may be a remote person with no credentials,
					// but who should otherwise be able to view it. Show a default image to let 
					// them know permissions was denied. It may be possible to view the image 
					// through an authenticated profile visit.
					// There won't be many completely unauthorised people seeing this because
					// they won't have the photo link, so there's a reasonable chance that the person
					// might be able to obtain permission to view it.
	
					$r = q("SELECT * FROM photo WHERE resource_id = '%s' AND imgscale = %d LIMIT 1",
						dbesc($photo),
						intval($resolution)
					);
	 
					if($r) {
						logger('mod_photo: forbidden. ' . \App::$query_string);
						$observer = \App::get_observer();
						logger('mod_photo: observer = ' . (($observer) ? $observer['xchan_addr'] : '(not authenticated)'));
						$data = file_get_contents('images/nosign.png');
						$mimetype = 'image/png';
						$prvcachecontrol = true;
					}
				}
			}
		}
	



		if(! isset($data)) {
			if(isset($resolution)) {
				switch($resolution) {
	
					case 4:
						$data = file_get_contents(get_default_profile_photo());
						$mimetype = 'image/png';
						break;
					case 5:
						$data = file_get_contents(get_default_profile_photo(80));
						$mimetype = 'image/png';
						break;
					case 6:
						$data = file_get_contents(get_default_profile_photo(48));
						$mimetype = 'image/png';
						break;
					default:
						killme();
						// NOTREACHED
						break;
				}
			}
		}
	
		if(isset($res) && intval($res) && $res < 500) {
			$ph = photo_factory($data, $mimetype);
			if($ph->is_valid()) {
				$ph->scaleImageSquare($res);
				$data = $ph->imageString();
				$mimetype = $ph->getType();
			}
		}
	
		// Writing in cachefile
		if (isset($cachefile) && $cachefile != '')
			file_put_contents($cachefile, $data);
	
		if(function_exists('header_remove')) {
			header_remove('Pragma');
			header_remove('pragma');
		}
	
		header("Content-type: " . $mimetype);
	
		if($prvcachecontrol) {
	
			// it is a private photo that they have no permission to view.
			// tell the browser not to cache it, in case they authenticate
			// and subsequently have permission to see it
	
			header("Cache-Control: no-store, no-cache, must-revalidate");
	
		}
		else {
			// The photo cache default is 1 day to provide a privacy trade-off,
			// as somebody reducing photo permissions on a photo that is already 
			// "in the wild" won't be able to stop the photo from being viewed
			// for this amount amount of time once it is in the browser cache.
			// The privacy expectations of your site members and their perception 
			// of privacy where it affects the entire project may be affected.
			// This has performance considerations but we highly recommend you 
			// leave it alone. 
	
			$cache = get_config('system','photo_cache_time');
			if(! $cache)
				$cache = (3600 * 24); // 1 day
	
		 	header("Expires: " . gmdate("D, d M Y H:i:s", time() + $cache) . " GMT");
			header("Cache-Control: max-age=" . $cache);
	
		}

		// If it's a file resource, stream it. 

		if($streaming && $channel) {
			if(strpos($streaming,'store') !== false)
				$istream = fopen($streaming,'rb');
			else
				$istream = fopen('store/' . $channel['channel_address'] . '/' . $streaming,'rb');
			$ostream = fopen('php://output','wb');
			if($istream && $ostream) {
				pipe_streams($istream,$ostream);
				fclose($istream);
				fclose($ostream);
			}
		}
		else {
			echo $data;
		}

		killme();
		// NOTREACHED
	}
	
}
