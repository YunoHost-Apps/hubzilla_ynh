<?php /** @file */

namespace Zotlabs\Module; 

//require_once('include/apps.php');

use \Zotlabs\Lib as Zlib;

class Appman extends \Zotlabs\Web\Controller {

	function post() {
	
		if(! local_channel())
			return;
	
		if($_POST['url']) {
			$arr = array(
				'uid' => intval($_REQUEST['uid']),
				'url' => escape_tags($_REQUEST['url']),
				'guid' => escape_tags($_REQUEST['guid']),
				'author' => escape_tags($_REQUEST['author']),
				'addr' => escape_tags($_REQUEST['addr']),
				'name' => escape_tags($_REQUEST['name']),
				'desc' => escape_tags($_REQUEST['desc']),
				'photo' => escape_tags($_REQUEST['photo']),
				'version' => escape_tags($_REQUEST['version']),
				'price' => escape_tags($_REQUEST['price']),
				'requires' => escape_tags($_REQUEST['requires']),
				'system' => intval($_REQUEST['system']),
				'sig' => escape_tags($_REQUEST['sig']),
				'categories' => escape_tags($_REQUEST['categories'])
			);
	
			$_REQUEST['appid'] = Zlib\Apps::app_install(local_channel(),$arr);
	
			if(Zlib\Apps::app_installed(local_channel(),$arr))
				info( t('App installed.') . EOL);
	
			return;
		}
	
	
		$papp = Zlib\Apps::app_decode($_POST['papp']);
	
		if(! is_array($papp)) {
			notice( t('Malformed app.') . EOL);
			return;
		}
	
		if($_POST['install']) {
			Zlib\Apps::app_install(local_channel(),$papp);
			if(Zlib\Apps::app_installed(local_channel(),$papp))
				info( t('App installed.') . EOL);
		}
	
		if($_POST['delete']) {
			Zlib\Apps::app_destroy(local_channel(),$papp);
		}
	
		if($_POST['edit']) {
			return;
		}
	
		if($_SESSION['return_url']) 
			goaway(z_root() . '/' . $_SESSION['return_url']);
		goaway(z_root() . '/apps');
	
	
	}
	
	
	function get() {
	
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		$channel = \App::get_channel();
		$app = null;
		$embed = null;
		if($_REQUEST['appid']) {
			$r = q("select * from app where app_id = '%s' and app_channel = %d limit 1",
				dbesc($_REQUEST['appid']),
				dbesc(local_channel())
			);
			if($r) {
				$app = $r[0];

				$term = q("select * from term where otype = %d and oid = %d",
					intval(TERM_OBJ_APP),
					intval($r[0]['id'])
				);
				if($term) {
					$app['categories'] = '';
					foreach($term as $t) {
						if($app['categories'])
							$app['categories'] .= ',';
						$app['categories'] .= $t['term'];
					}
				}
			}

			$embed = array('embed', t('Embed code'), Zlib\Apps::app_encode($app,true),'', 'onclick="this.select();"');
	
		}
				
		return replace_macros(get_markup_template('app_create.tpl'), array(
	
			'$banner' => (($app) ? t('Edit App') : t('Create App')),
			'$app' => $app,
			'$guid' => (($app) ? $app['app_id'] : ''),
			'$author' => (($app) ? $app['app_author'] : $channel['channel_hash']),
			'$addr' => (($app) ? $app['app_addr'] : $channel['xchan_addr']),
			'$name' => array('name', t('Name of app'),(($app) ? $app['app_name'] : ''), t('Required')),
			'$url' => array('url', t('Location (URL) of app'),(($app) ? $app['app_url'] : ''), t('Required')),
	 		'$desc' => array('desc', t('Description'),(($app) ? $app['app_desc'] : ''), ''),
			'$photo' => array('photo', t('Photo icon URL'),(($app) ? $app['app_photo'] : ''), t('80 x 80 pixels - optional')),
			'$categories' => array('categories',t('Categories (optional, comma separated list)'),(($app) ? $app['categories'] : ''),''),
			'$version' => array('version', t('Version ID'),(($app) ? $app['app_version'] : ''), ''),
			'$price' => array('price', t('Price of app'),(($app) ? $app['app_price'] : ''), ''),
			'$page' => array('page', t('Location (URL) to purchase app'),(($app) ? $app['app_page'] : ''), ''),
			'$system' => (($app) ? intval($app['app_system']) : 0),
			'$requires' => (($app) ? $app['app_requires'] : ''),
			'$embed' => $embed,
			'$submit' => t('Submit')
		));
	
	}
	
}
