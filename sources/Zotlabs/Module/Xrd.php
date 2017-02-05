<?php
namespace Zotlabs\Module;

require_once('include/crypto.php');


class Xrd extends \Zotlabs\Web\Controller {

	function init() {
	
		$uri = urldecode(notags(trim($_GET['uri'])));
		logger('xrd: ' . $uri,LOGGER_DEBUG);
	
		$resource = $uri;
	
		if(substr($uri,0,4) === 'http') {
			$uri = str_replace('~','',$uri);
			$name = basename($uri);
		}
		else {
			$local = str_replace('acct:', '', $uri);
			if(substr($local,0,2) == '//')
				$local = substr($local,2);
	
			$name = substr($local,0,strpos($local,'@'));
		}
	
		$r = q("SELECT * FROM channel WHERE channel_address = '%s' LIMIT 1",
			dbesc($name)
		);
		if(! $r) 
			killme();
	
		$dspr = replace_macros(get_markup_template('xrd_diaspora.tpl'),array(
			'$baseurl' => z_root(),
			'$dspr_guid' => $r[0]['channel_guid'] . str_replace('.','',\App::get_hostname()),
			'$dspr_key' => base64_encode(pemtorsa($r[0]['channel_pubkey']))
		));
	
		$salmon_key = salmon_key($r[0]['channel_pubkey']);
	
		header('Access-Control-Allow-Origin: *');
		header("Content-type: application/xrd+xml");
	
	
		$aliases = array('acct:' . channel_reddress($r[0]), z_root() . '/channel/' . $r[0]['channel_address'], z_root() . '/~' . $r[0]['channel_address']);
	
		for($x = 0; $x < count($aliases); $x ++) {
			if($aliases[$x] === $resource)
				unset($aliases[$x]);
		}
	
	
		$o = replace_macros(get_markup_template('xrd_person.tpl'), array(
			'$nick'        => $r[0]['channel_address'],
			'$accturi'     => $resource,
			'$aliases'     => $aliases,
			'$profile_url' => z_root() . '/channel/'       . $r[0]['channel_address'],
			'$hcard_url'   => z_root() . '/hcard/'         . $r[0]['channel_address'],
			'$atom'        => z_root() . '/feed/'          . $r[0]['channel_address'],
			'$zot_post'    => z_root() . '/post/'          . $r[0]['channel_address'],
			'$poco_url'    => z_root() . '/poco/'          . $r[0]['channel_address'],
			'$photo'       => z_root() . '/photo/profile/l/' . $r[0]['channel_id'],
			'$dspr'        => $dspr,
	//		'$salmon'      => z_root() . '/salmon/'        . $r[0]['channel_address'],
	//		'$salmen'      => z_root() . '/salmon/'        . $r[0]['channel_address'] . '/mention',
			'$modexp'      => 'data:application/magic-public-key,'  . $salmon_key,
			'$subscribe'   => z_root() . '/follow?url={uri}',
			'$bigkey'      =>  salmon_key($r[0]['channel_pubkey'])
		));
	
	
		$arr = array('user' => $r[0], 'xml' => $o);
		call_hooks('personal_xrd', $arr);
	
		echo $arr['xml'];
		killme();
	
	}
	
}
