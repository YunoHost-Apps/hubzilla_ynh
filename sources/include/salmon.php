<?php

require_once('include/crypto.php');

function get_salmon_key($uri,$keyhash) {
	$ret = array();

	logger('Fetching salmon key for ' . $uri, LOGGER_DEBUG, LOG_INFO);

	$x = webfinger_rfc7033($uri,true);

	logger('webfinger returns: ' . print_r($x,true), LOGGER_DATA, LOG_DEBUG);

	if($x && array_key_exists('links',$x) && $x['links']) {
		foreach($x['links'] as $link) {
			if(array_key_exists('rel',$link) && $link['rel'] === 'magic-public-key') {
				$ret[] = $link['href'];
			}
		}
	}

	else {
		$arr = old_webfinger($uri);

		logger('old webfinger returns: ' . print_r($arr,true), LOGGER_DATA, LOG_DEBUG);

		if(is_array($arr)) {
			foreach($arr as $a) {
				if($a['@attributes']['rel'] === 'magic-public-key') {
					$ret[] = $a['@attributes']['href'];
				}
			}
		}
		else {
			return '';
		}
	}

	// We have found at least one key URL
	// If it's inline, parse it - otherwise get the key

	if(count($ret)) {
		for($x = 0; $x < count($ret); $x ++) {
			if(substr($ret[$x],0,5) === 'data:') {
				$ret[$x] = convert_salmon_key($ret[$x]);
			}
		}
	}


	logger('Key located: ' . print_r($ret,true), LOGGER_DEBUG, LOG_INFO);

	if(count($ret) == 1) {

		// We only found one one key so we don't care if the hash matches.
		// If it's the wrong key we'll find out soon enough because
		// message verification will fail. This also covers some older
		// software which don't supply a keyhash. As long as they only
		// have one key we'll be right.

		return $ret[0];
	}
	else {
		foreach($ret as $a) {
			$hash = base64url_encode(hash('sha256',$a));
			if($hash == $keyhash)
				return $a;
		}
	}

	return '';
}



function slapper($owner,$url,$slap) {

	// does contact have a salmon endpoint?

	if(! strlen($url))
		return;


	if(! $owner['channel_prvkey']) {
		logger(sprintf("channel '%s' (%d) does not have a salmon private key. Send failed.",
		$owner['channel_address'],$owner['channel_id']));
		return;
	}

	logger('slapper called for ' .$url . '. Data: ' . $slap, LOGGER_DATA, LOG_DEBUG);

	// create a magic envelope

	$data      = base64url_encode($slap);
	$data_type = 'application/atom+xml';
	$encoding  = 'base64url';
	$algorithm = 'RSA-SHA256';
	$keyhash   = base64url_encode(hash('sha256',salmon_key($owner['channel_pubkey'])),true);

	// precomputed base64url encoding of data_type, encoding, algorithm concatenated with periods

	$precomputed = '.YXBwbGljYXRpb24vYXRvbSt4bWw=.YmFzZTY0dXJs.UlNBLVNIQTI1Ng==';

	$signature   = base64url_encode(rsa_sign(str_replace('=','',$data . $precomputed),$owner['channel_prvkey']));

	$signature2  = base64url_encode(rsa_sign($data . $precomputed,$owner['channel_prvkey']));

	$signature3  = base64url_encode(rsa_sign($data,$owner['channel_prvkey']));

	$salmon_tpl = get_markup_template('magicsig.tpl');

	$salmon = replace_macros($salmon_tpl,array(
		'$data'      => $data,
		'$encoding'  => $encoding,
		'$algorithm' => $algorithm,
		'$keyhash'   => $keyhash,
		'$signature' => $signature
	));

	// slap them

	$redirects = 0;

	$ret = z_post_url($url,$salmon, $redirects, array('headers' => array(
		'Content-type: application/magic-envelope+xml',
		'Content-length: ' . strlen($salmon))
	));


	$return_code = $ret['return_code'];

	// check for success, e.g. 2xx

	if($return_code > 299) {

		logger('compliant salmon failed. Falling back to status.net hack2');

		// Entirely likely that their salmon implementation is
		// non-compliant. Let's try once more, this time only signing
		// the data, without stripping '=' chars

		$salmon = replace_macros($salmon_tpl,array(
			'$data'      => $data,
			'$encoding'  => $encoding,
			'$algorithm' => $algorithm,
			'$keyhash'   => $keyhash,
			'$signature' => $signature2
		));

		$redirects = 0;

		$ret = z_post_url($url,$salmon, $redirects, array('headers' => array(
			'Content-type: application/magic-envelope+xml',
			'Content-length: ' . strlen($salmon))
		));


		$return_code = $ret['return_code'];

		if($return_code > 299) {

			logger('compliant salmon failed. Falling back to status.net hack3');

			// Entirely likely that their salmon implementation is
			// non-compliant. Let's try once more, this time only signing
			// the data, without the precomputed blob

			$salmon = replace_macros($salmon_tpl,array(
				'$data'      => $data,
				'$encoding'  => $encoding,
				'$algorithm' => $algorithm,
				'$keyhash'   => $keyhash,
				'$signature' => $signature3
			));

			$redirects = 0;
	
			$ret = z_post_url($url,$salmon, $redirects, array('headers' => array(
				'Content-type: application/magic-envelope+xml',
				'Content-length: ' . strlen($salmon))
			));


			$return_code = $ret['return_code'];
		}
	}
	logger('slapper for ' . $url . ' returned ' . $return_code);

	if(! $return_code)
		return(-1);
	if(($return_code == 503) && (stristr($ret['header'],'retry-after')))
		return(-1);

	return ((($return_code >= 200) && ($return_code < 300)) ? 0 : 1);
}

