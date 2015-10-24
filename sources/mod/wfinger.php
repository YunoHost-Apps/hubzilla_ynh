<?php

require_once('include/zot.php');

function wfinger_init(&$a) {

	$result = array();

	$scheme = '';

	if(x($_SERVER,'HTTPS') && $_SERVER['HTTPS'])
		$scheme = 'https';
	elseif(x($_SERVER,'SERVER_PORT') && (intval($_SERVER['SERVER_PORT']) == 443))
		$scheme = 'https';

	$zot = intval($_REQUEST['zot']);

	if(($scheme !== 'https') && (! $zot)) {
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . 500 . ' ' . 'Webfinger requires HTTPS');
		killme();
	}


	$resource = $_REQUEST['resource'];
	logger('webfinger: ' . $resource,LOGGER_DEBUG);

	$r = null;

	if($resource) {

		if(strpos($resource,'acct:') === 0) {
			$channel = str_replace('acct:','',$resource);
			if(strpos($channel,'@') !== false) {
				$host = substr($channel,strpos($channel,'@')+1);
				if(strcasecmp($host,get_app()->get_hostname())) {
					goaway('https://' . $host . '/.well-known/webfinger?f=&resource=' . $resource . (($zot) ? '&zot=' . $zot : ''));
				}
				$channel = substr($channel,0,strpos($channel,'@'));
			}		
		}
		if(strpos($resource,'http') === 0) {
			$channel = str_replace('~','',basename($resource));
		}

		$r = q("select * from channel left join xchan on channel_hash = xchan_hash 
			where channel_address = '%s' limit 1",
			dbesc($channel)
		);

	}

	header('Access-Control-Allow-Origin: *');

	header('Content-type: application/jrd+json');


	if($resource && $r) {

		$h = q("select hubloc_addr from hubloc where hubloc_hash = '%s' and hubloc_deleted = 0",
			dbesc($r[0]['channel_hash'])
		);

		$result['subject'] = $resource;

		$aliases = array(
			z_root() . '/channel/' . $r[0]['channel_address'],
			z_root() . '/~' . $r[0]['channel_address']
		);

		if($h) {
			foreach($h as $hh) {
				$aliases[] = 'acct:' . $hh['hubloc_addr'];
			}
		}

		$result['aliases'] = array();

		$result['properties'] = array('http://webfinger.net/ns/name' => $r[0]['channel_name']);

		foreach($aliases as $alias) 
			if($alias != $resource)
				$result['aliases'][] = $alias;


		$result['links'] = array(

			array(
				'rel' => 'http://webfinger.net/rel/avatar',
				'type' => $r[0]['xchan_photo_mimetype'],
				'href' => $r[0]['xchan_photo_l']	
			),

			array(
				'rel' => 'http://webfinger.net/rel/profile-page',
				'href' => z_root() . '/profile/' . $r[0]['channel_address'],
			),

			array(
				'rel' => 'http://webfinger.net/rel/blog',
				'href' => z_root() . '/channel/' . $r[0]['channel_address'],
			),

			array(
				'rel' => 'http://purl.org/zot/protocol',
				'href' => z_root() . '/.well-known/zot-info' . '?address=' . $r[0]['xchan_addr'],
			)
		);

		if($zot) {
			// get a zotinfo packet and return it with webfinger
			$result['zot'] = zotinfo(array('address' => $r[0]['xchan_addr']));
		}
	}
	else {
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . 400 . ' ' . 'Bad Request');
		killme();
	}

	$arr = array('channel' => $r[0], 'request' => $_REQUEST, 'result' => $result);
	call_hooks('webfinger',$arr);

	echo json_encode($arr['result']);
	killme();

}