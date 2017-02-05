<?php
/**
 * @file include/network.php
 */

/**
 * @brief Returns path to CA file.
 *
 * @return string
 */
function get_capath() {
	return appdirpath() . '/library/cacert.pem';
}

/**
 * @brief fetches an URL.
 *
 * @param string $url
 *    URL to fetch
 * @param boolean $binary default false
 *    TRUE if asked to return binary results (file download)
 * @param int $redirects default 0
 *    internal use, recursion counter
 * @param array $opts (optional parameters) associative array with:
 *  * \b accept_content => supply Accept: header with 'accept_content' as the value
 *  * \b timeout => int seconds, default system config value or 60 seconds
 *  * \b http_auth => username:password
 *  * \b novalidate => do not validate SSL certs, default is to validate using our CA list
 *  * \b nobody => only return the header
 *  * \b filep => stream resource to write body to. header and body are not returned when using this option.
 *  * \b custom => custom request method: e.g. 'PUT', 'DELETE'
 *  * \b cookiejar => cookie file (write)
 *  * \b cookiefile => cookie file (read)
 *
 * @return array an associative array with:
 *  * \e int \b return_code => HTTP return code or 0 if timeout or failure
 *  * \e boolean \b success => boolean true (if HTTP 2xx result) or false
 *  * \e string \b header => HTTP headers
 *  * \e string \b body => fetched content
 */
function z_fetch_url($url, $binary = false, $redirects = 0, $opts = array()) {

	$ret = array('return_code' => 0, 'success' => false, 'header' => "", 'body' => "");

	$ch = @curl_init($url);
	if(($redirects > 8) || (! $ch))
		return $ret;

	@curl_setopt($ch, CURLOPT_HEADER, true);
	@curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	@curl_setopt($ch, CURLOPT_CAINFO, get_capath());
	@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	@curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	@curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Red)");

	$ciphers = @get_config('system','curl_ssl_ciphers');
	if($ciphers)
		@curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, $ciphers);

	if(x($opts,'filep')) {
		@curl_setopt($ch, CURLOPT_FILE, $opts['filep']);
		@curl_setopt($ch, CURLOPT_HEADER, false);
	}

	if(x($opts,'upload'))
		@curl_setopt($ch, CURLOPT_UPLOAD, $opts['upload']);

	if(x($opts,'infile'))
		@curl_setopt($ch, CURLOPT_INFILE, $opts['infile']);

	if(x($opts,'infilesize'))
		@curl_setopt($ch, CURLOPT_INFILESIZE, $opts['infilesize']);

	if(x($opts,'readfunc'))
		@curl_setopt($ch, CURLOPT_READFUNCTION, $opts['readfunc']);

	if(x($opts,'headers'))
		@curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);

	if(x($opts,'nobody'))
		@curl_setopt($ch, CURLOPT_NOBODY, $opts['nobody']);

	if(x($opts,'custom'))
		@curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $opts['custom']);

	if(x($opts,'timeout') && intval($opts['timeout'])) {
		@curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
	}
	else {
		$curl_time = intval(@get_config('system','curl_timeout'));
		@curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));
	}

	if(x($opts,'http_auth')) {
		// "username" . ':' . "password"
		@curl_setopt($ch, CURLOPT_USERPWD, $opts['http_auth']);
	}

	if(x($opts,'cookiejar'))
		@curl_setopt($ch, CURLOPT_COOKIEJAR, $opts['cookiejar']);
	if(x($opts,'cookiefile'))
		@curl_setopt($ch, CURLOPT_COOKIEFILE, $opts['cookiefile']);

	if(x($opts,'cookie'))
		@curl_setopt($ch, CURLOPT_COOKIE, $opts['cookie']);

	@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,
		((x($opts,'novalidate') && intval($opts['novalidate'])) ? false : true));

	$prx = @get_config('system','proxy');
	if(strlen($prx)) {
		@curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		@curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = @get_config('system','proxyuser');
		if(strlen($prxusr))
			@curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}
	if($binary)
		@curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);


	// don't let curl abort the entire application'
	// if it throws any errors.

	$s = @curl_exec($ch);

	$base = $s;
	$curl_info = @curl_getinfo($ch);
	$http_code = $curl_info['http_code'];
	//logger('fetch_url:' . $http_code . ' data: ' . $s);
	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while(preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	if($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307 || $http_code == 308) {
		$matches = array();
		preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
		$newurl = trim(array_pop($matches));
		if(strpos($newurl,'/') === 0)
			$newurl = $url . $newurl;
		$url_parsed = @parse_url($newurl);
		if (isset($url_parsed)) {
			@curl_close($ch);
			return z_fetch_url($newurl,$binary,++$redirects,$opts);
		}
	}

	$rc = intval($http_code);
	$ret['return_code'] = $rc;
	$ret['success'] = (($rc >= 200 && $rc <= 299) ? true : false);
	if(! $ret['success']) {
		$ret['error'] = curl_error($ch);
		$ret['debug'] = $curl_info;
		logger('z_fetch_url: error: ' . $url . ': ' . $ret['error'], LOGGER_DEBUG);
		logger('z_fetch_url: debug: ' . print_r($curl_info,true), LOGGER_DATA);
	}
	$ret['body'] = substr($s,strlen($header));
	$ret['header'] = $header;

	if(x($opts,'debug')) {
		$ret['debug'] = $curl_info;
	}

	@curl_close($ch);
	return($ret);
}


/**
 * @brief
 *
 * @param string $url
 *    URL to post
 * @param mixed $params
 *   The full data to post in a HTTP "POST" operation. This parameter can
 *   either be passed as a urlencoded string like 'para1=val1&para2=val2&...'
 *   or as an array with the field name as key and field data as value. If value
 *   is an array, the Content-Type header will be set to multipart/form-data.
 * @param int $redirects = 0
 *    internal use, recursion counter
 * @param array $opts (optional parameters)
 *    'accept_content' => supply Accept: header with 'accept_content' as the value
 *    'timeout' => int seconds, default system config value or 60 seconds
 *    'http_auth' => username:password
 *    'novalidate' => do not validate SSL certs, default is to validate using our CA list
 *    'filep' => stream resource to write body to. header and body are not returned when using this option.
 *    'custom' => custom request method: e.g. 'PUT', 'DELETE'
 *
 * @return array an associative array with:
 *  * \e int \b return_code => HTTP return code or 0 if timeout or failure
 *  * \e boolean \b success => boolean true (if HTTP 2xx result) or false
 *  * \e string \b header => HTTP headers
 *  * \e string \b body => content
 *  * \e string \b debug => from curl_info()
 */
function z_post_url($url,$params, $redirects = 0, $opts = array()) {

//	logger('url: ' . $url);
//	logger('params: ' . print_r($params,true));
//	logger('opts: ' . print_r($opts,true));

	$ret = array('return_code' => 0, 'success' => false, 'header' => "", 'body' => "");

	$ch = curl_init($url);
	if(($redirects > 8) || (! $ch))
		return $ret;

	@curl_setopt($ch, CURLOPT_HEADER, true);
	@curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	@curl_setopt($ch, CURLOPT_CAINFO, get_capath());
	@curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	@curl_setopt($ch, CURLOPT_POST,1);
	@curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
	@curl_setopt($ch, CURLOPT_USERAGENT, "Red");

	$ciphers = @get_config('system','curl_ssl_ciphers');
	if($ciphers)
		@curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, $ciphers);

	if(x($opts,'filep')) {
		@curl_setopt($ch, CURLOPT_FILE, $opts['filep']);
		@curl_setopt($ch, CURLOPT_HEADER, false);
	}

	if(x($opts,'headers')) {
		@curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);
	}

	if(x($opts,'nobody'))
		@curl_setopt($ch, CURLOPT_NOBODY, $opts['nobody']);

	if(x($opts,'custom')) {
		@curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $opts['custom']);
		@curl_setopt($ch, CURLOPT_POST,0);
	}


	if(x($opts,'timeout') && intval($opts['timeout'])) {
		@curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
	}
	else {
		$curl_time = intval(@get_config('system','curl_timeout'));
		@curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));
	}

	if(x($opts,'http_auth')) {
		// "username" . ':' . "password"
		@curl_setopt($ch, CURLOPT_USERPWD, $opts['http_auth']);
	}


	if(x($opts,'cookiejar'))
		@curl_setopt($ch, CURLOPT_COOKIEJAR, $opts['cookiejar']);
	if(x($opts,'cookiefile'))
		@curl_setopt($ch, CURLOPT_COOKIEFILE, $opts['cookiefile']);


	if(x($opts,'cookie'))
		@curl_setopt($ch, CURLOPT_COOKIE, $opts['cookie']);

	@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,
		((x($opts,'novalidate') && intval($opts['novalidate'])) ? false : true));

	$prx = get_config('system','proxy');
	if(strlen($prx)) {
		@curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		@curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = get_config('system','proxyuser');
		if(strlen($prxusr))
			@curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}

	// don't let curl abort the entire application
	// if it throws any errors.

	$s = @curl_exec($ch);

	$base = $s;
	$curl_info = @curl_getinfo($ch);
	$http_code = $curl_info['http_code'];

	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while(preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	// would somebody take lighttpd and just shoot it?

	if($http_code == 417) {
		curl_close($ch);
		if($opts) {
			if($opts['headers'])
				$opts['headers'][] = 'Expect:';
			else
				$opts['headers'] = array('Expect:');
		}
		else
			$opts = array('headers' => array('Expect:'));
		return z_post_url($url,$params,++$redirects,$opts);
	}

	if($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307 || $http_code == 308) {
		$matches = array();
		preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
		$newurl = trim(array_pop($matches));
		if(strpos($newurl,'/') === 0)
			$newurl = $url . $newurl;
		$url_parsed = @parse_url($newurl);
		if (isset($url_parsed)) {
			curl_close($ch);
			if($http_code == 303) {
				return z_fetch_url($newurl,false,$redirects++,$opts);
			} else {
				return z_post_url($newurl,$params,++$redirects,$opts);
			}
		}
	}
	$rc = intval($http_code);
	$ret['return_code'] = $rc;
	$ret['success'] = (($rc >= 200 && $rc <= 299) ? true : false);
	if(! $ret['success']) {
		$ret['error'] = curl_error($ch);
		$ret['debug'] = $curl_info;
		logger('z_post_url: error: ' . $url . ': ' . $ret['error'], LOGGER_DEBUG);
		logger('z_post_url: debug: ' . print_r($curl_info,true), LOGGER_DATA);
	}

	$ret['body'] = substr($s, strlen($header));
	$ret['header'] = $header;

	if(x($opts,'debug')) {
		$ret['debug'] = $curl_info;
	}

	curl_close($ch);
	return($ret);
}

/**
 * @brief Like z_post_url() but with an application/json HTTP header.
 *
 * Add a "Content-Type: application/json" HTTP-header to $opts and call z_post_url().
 *
 * @see z_post_url()
 *
 * @param string $url
 * @param array $params
 * @param number $redirects default 0
 * @param array $opts (optional) curl options
 * @return z_post_url()
 */
function z_post_url_json($url, $params, $redirects = 0, $opts = array()) {

	$opts = array_merge($opts, array('headers' => array('Content-Type: application/json')));

	return z_post_url($url,json_encode($params),$redirects,$opts);
}


function json_return_and_die($x, $content_type = 'application/json') {
	header("Content-type: $content_type");
	echo json_encode($x);
	killme();
}



// Generic XML return
// Outputs a basic dfrn XML status structure to STDOUT, with a <status> variable
// of $st and an optional text <message> of $message and terminates the current process.


function xml_status($st, $message = '') {

	$xml_message = ((strlen($message)) ? "\t<message>" . xmlify($message) . "</message>\r\n" : '');

	if($st)
		logger('xml_status returning non_zero: ' . $st . " message=" . $message);

	header( "Content-type: text/xml" );
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
	echo "<result>\r\n\t<status>$st</status>\r\n$xml_message</result>\r\n";
	killme();
}



/**
 * @brief Send HTTP status header
 *
 * @param int $val
 *    integer HTTP status result value
 * @param string $msg
 *    optional message
 * @returns nil
 */
function http_status($val, $msg = '') {
	if ($val >= 400)
		$msg = (($msg) ? $msg : 'Error');
	if ($val >= 200 && $val < 300)
		$msg = (($msg) ? $msg : 'OK');

	logger('http_status_exit ' . $val . ' ' . $msg);
	header($_SERVER['SERVER_PROTOCOL'] . ' ' . $val . ' ' . $msg);
}



/**
 * @brief Send HTTP status header and exit.
 *
 * @param int $val
 *    integer HTTP status result value
 * @param string $msg
 *    optional message
 * @returns (does not return, process is terminated)
 */
function http_status_exit($val, $msg = '') {
	http_status($val, $msg);
	killme();
}



// convert an XML document to a normalised, case-corrected array
// used by webfinger


function convert_xml_element_to_array($xml_element, &$recursion_depth=0) {

		// If we're getting too deep, bail out
		if ($recursion_depth > 512) {
				return(null);
		}

		if (!is_string($xml_element) &&
		!is_array($xml_element) &&
		(get_class($xml_element) == 'SimpleXMLElement')) {
				$xml_element_copy = $xml_element;
				$xml_element = get_object_vars($xml_element);
		}

		if (is_array($xml_element)) {
				$result_array = array();
				if (count($xml_element) <= 0) {
						return (trim(strval($xml_element_copy)));
				}

				foreach($xml_element as $key=>$value) {

						$recursion_depth++;
						$result_array[strtolower($key)] =
				convert_xml_element_to_array($value, $recursion_depth);
						$recursion_depth--;
				}
				if ($recursion_depth == 0) {
						$temp_array = $result_array;
						$result_array = array(
								strtolower($xml_element_copy->getName()) => $temp_array,
						);
				}

				return ($result_array);

		} else {
				return (trim(strval($xml_element)));
		}
}



function z_dns_check($h,$check_mx = 0) {

	// dns_get_record() has issues on some platforms
	// so allow somebody to ignore it completely
	// Use config values from memory as this can be called during setup
	// before a database or even any config structure exists.

	if(is_array(\App::$config) && array_key_exists('system',\App::$config)
		&& is_array(\App::$config['system'])
		&& array_key_exists('do_not_check_dns',\App::$config['system']) 
		&& \App::$config['system']['do_not_check_dns'])
		return true;


	//$opts = DNS_A + DNS_CNAME + DNS_PTR;
	//if($check_mx)
	//	$opts += DNS_MX;
	// Specific record type flags are unreliable on FreeBSD and Mac, 
	// so now we'll ignore these and just check for the existence of any DNS record. 
	return((@dns_get_record($h) || filter_var($h, FILTER_VALIDATE_IP)) ? true : false);

}

// Take a URL from the wild, prepend http:// if necessary
// and check DNS to see if it's real (or check if is a valid IP address)
// return true if it's OK, false if something is wrong with it


function validate_url(&$url) {

	// no naked subdomains (allow localhost for tests)
	if(strpos($url,'.') === false && strpos($url,'/localhost/') === false)
		return false;
	if(substr($url,0,4) != 'http')
		$url = 'http://' . $url;
	$h = @parse_url($url);

	if(($h) && z_dns_check($h['host'])) {
		return true;
	}
	return false;
}

// checks that email is an actual resolvable internet address


function validate_email($addr) {

	if(get_config('system','disable_email_validation'))
		return true;

	if(! strpos($addr,'@'))
		return false;
	$h = substr($addr,strpos($addr,'@') + 1);

	if(($h) && z_dns_check($h,true)) {
		return true;
	}
	return false;
}

// Check $url against our list of allowed sites,
// wildcards allowed. If allowed_sites is unset return true;
// If url is allowed, return true.
// otherwise, return false


function allowed_url($url) {

	$h = @parse_url($url);

	if(! $h) {
		return false;
	}

	$str_allowed = get_config('system','allowed_sites');
	if(! $str_allowed)
		return true;

	$found = false;

	$host = strtolower($h['host']);

	// always allow our own site

	if($host == strtolower($_SERVER['SERVER_NAME']))
		return true;

	$fnmatch = function_exists('fnmatch');
	$allowed = explode(',',$str_allowed);

	if(count($allowed)) {
		foreach($allowed as $a) {
			$pat = strtolower(trim($a));
			if(($fnmatch && fnmatch($pat,$host)) || ($pat == $host)) {
				$found = true;
				break;
			}
		}
	}
	return $found;
}

// check if email address is allowed to register here.
// Compare against our list (wildcards allowed).
// Returns false if not allowed, true if allowed or if
// allowed list is not configured.


function allowed_email($email) {


	$domain = strtolower(substr($email,strpos($email,'@') + 1));
	if(! $domain)
		return false;

	$str_allowed = get_config('system','allowed_email');
	$str_not_allowed = get_config('system','not_allowed_email');

	if(! $str_allowed && ! $str_not_allowed)
		return true;

	$return = false;
	$found_allowed = false;
	$found_not_allowed = false;

	$fnmatch = function_exists('fnmatch');

	$allowed = explode(',',$str_allowed);

	if(count($allowed)) {
		foreach($allowed as $a) {
			$pat = strtolower(trim($a));
			if(($fnmatch && fnmatch($pat,$email)) || ($pat == $domain)) {
				$found_allowed = true;
				break;
			}
		}
	}

	$not_allowed = explode(',',$str_not_allowed);

	if(count($not_allowed)) {
		foreach($not_allowed as $na) {
			$pat = strtolower(trim($na));
			if(($fnmatch && fnmatch($pat,$email)) || ($pat == $domain)) {
				$found_not_allowed = true;
				break;
			}
		}
	}

	if ($found_allowed) {
		$return = true;
	} elseif (!$str_allowed && !$found_not_allowed) {
		$return = true;
	}
	return $return;
}



function parse_xml_string($s,$strict = true) {
	if($strict) {
		if(! strstr($s,'<?xml'))
			return false;
		$s2 = substr($s,strpos($s,'<?xml'));
	}
	else
		$s2 = $s;
	libxml_use_internal_errors(true);

	$x = @simplexml_load_string($s2);
	if(! $x) {
		logger('libxml: parse: error: ' . $s2, LOGGER_DATA);
		foreach(libxml_get_errors() as $err)
			logger('libxml: parse: ' . $err->code." at ".$err->line.":".$err->column." : ".$err->message, LOGGER_DATA);
		libxml_clear_errors();
	}
	return $x;
}


function scale_external_images($s, $include_link = true, $scale_replace = false) {

	// Picture addresses can contain special characters
	$s = htmlspecialchars_decode($s, ENT_COMPAT);

	$matches = null;
	$c = preg_match_all('/\[([zi])mg(.*?)\](.*?)\[\/[zi]mg\]/ism',$s,$matches,PREG_SET_ORDER);
	if($c) {
		require_once('include/photo/photo_driver.php');

		foreach($matches as $mtch) {
			logger('scale_external_image: ' . $mtch[2] . ' ' . $mtch[3]);

			if(substr($mtch[1],0,1) == '=') {
				$owidth = intval(substr($mtch[2],1));
				if(intval($owidth) > 0 && intval($owidth) < 1024)
					continue;
			}

			$hostname = str_replace('www.','',substr(z_root(),strpos(z_root(),'://')+3));
			if(stristr($mtch[3],$hostname))
				continue;

			// $scale_replace, if passed, is an array of two elements. The
			// first is the name of the full-size image. The second is the
			// name of a remote, scaled-down version of the full size image.
			// This allows Friendica to display the smaller remote image if
			// one exists, while still linking to the full-size image
			if($scale_replace)
				$scaled = str_replace($scale_replace[0], $scale_replace[1], $mtch[3]);
			else
				$scaled = $mtch[3];
			$i = z_fetch_url($scaled,true);


			$cache = get_config('system','itemcache');
			if (($cache != '') and is_dir($cache)) {
				$cachefile = $cache."/".hash("md5", $scaled);
				file_put_contents($cachefile, $i['body']);
			}

			// guess mimetype from headers or filename

			$type = guess_image_type($mtch[3],$i['header']);
			if(strpos($type,'image') === false)
				continue;

			if($i['success']) {
				$ph = photo_factory($i['body'], $type);
				if($ph->is_valid()) {
					$orig_width = $ph->getWidth();
					$orig_height = $ph->getHeight();

					if($orig_width > 1024 || $orig_height > 1024) {
						$tag = (($match[1] == 'z') ? 'zmg' : 'img');
						$ph->scaleImage(1024);
						$new_width = $ph->getWidth();
						$new_height = $ph->getHeight();
						logger('scale_external_images: ' . $orig_width . '->' . $new_width . 'w ' . $orig_height . '->' . $new_height . 'h' . ' match: ' . $mtch[0], LOGGER_DEBUG);
						$s = str_replace($mtch[0],'[' . $tag . '=' . $new_width . 'x' . $new_height. ']' . $scaled . '[/' . $tag . ']'
							. "\n" . (($include_link)
								? '[zrl=' . $mtch[2] . ']' . t('view full size') . '[/zrl]' . "\n"
								: ''),$s);
						logger('scale_external_images: new string: ' . $s, LOGGER_DEBUG);
					}
				}
			}
		}
	}

	// replace the special char encoding

	$s = htmlspecialchars($s,ENT_COMPAT,'UTF-8');

	return $s;
}

/**
 * xml2array() will convert the given XML text to an array in the XML structure.
 * Link: http://www.bin-co.com/php/scripts/xml2array/
 * Portions significantly re-written by mike@macgirvin.com for Friendica (namespaces, lowercase tags, get_attribute default changed, more...)
 * Arguments : $contents - The XML text
 *                $namespaces - true or false include namespace information in the returned array as array elements.
 *                $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value.
 *                $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.
 * Return: The parsed XML in an array form. Use print_r() to see the resulting array structure.
 * Examples: $array =  xml2array(file_get_contents('feed.xml'));
 *              $array =  xml2array(file_get_contents('feed.xml', true, 1, 'attribute'));
 */

function xml2array($contents, $namespaces = true, $get_attributes=1, $priority = 'attribute') {
	if(!$contents) return array();

	if(!function_exists('xml_parser_create')) {
		logger('xml2array: parser function missing');
		return array();
	}


	libxml_use_internal_errors(true);
	libxml_clear_errors();

	if($namespaces)
		$parser = @xml_parser_create_ns("UTF-8",':');
	else
		$parser = @xml_parser_create();

	if(! $parser) {
		logger('xml2array: xml_parser_create: no resource');
		return array();
	}

	xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	// http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	@xml_parse_into_struct($parser, trim($contents), $xml_values);
	@xml_parser_free($parser);

	if(! $xml_values) {
		logger('xml2array: libxml: parse error: ' . $contents, LOGGER_DATA);
		foreach(libxml_get_errors() as $err)
			logger('libxml: parse: ' . $err->code . " at " . $err->line . ":" . $err->column . " : " . $err->message, LOGGER_DATA);
		libxml_clear_errors();
		return;
	}

	//Initializations
	$xml_array = array();
	$parents = array();
	$opened_tags = array();
	$arr = array();

	$current = &$xml_array; // Reference

	// Go through the tags.
	$repeated_tag_index = array(); // Multiple tags with same name will be turned into an array
	foreach($xml_values as $data) {
		unset($attributes,$value); // Remove existing values, or there will be trouble

		// This command will extract these variables into the foreach scope
		// tag(string), type(string), level(int), attributes(array).
		extract($data); // We could use the array by itself, but this cooler.

		$result = array();
		$attributes_data = array();

		if(isset($value)) {
			if($priority == 'tag') $result = $value;
			else $result['value'] = $value; // Put the value in a assoc array if we are in the 'Attribute' mode
		}

		//Set the attributes too.
		if(isset($attributes) and $get_attributes) {
			foreach($attributes as $attr => $val) {
				if($priority == 'tag') $attributes_data[$attr] = $val;
				else $result['@attributes'][$attr] = $val; // Set all the attributes in a array called 'attr'
			}
		}

		// See tag status and do the needed.
		if($namespaces && strpos($tag,':')) {
			$namespc = substr($tag,0,strrpos($tag,':'));
			$tag = strtolower(substr($tag,strlen($namespc)+1));
			$result['@namespace'] = $namespc;
		}
		$tag = strtolower($tag);

		if($type == "open") {   // The starting of the tag '<tag>'
			$parent[$level-1] = &$current;
			if(!is_array($current) or (!in_array($tag, array_keys($current)))) { // Insert New tag
				$current[$tag] = $result;
				if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
				$repeated_tag_index[$tag.'_'.$level] = 1;

				$current = &$current[$tag];

			} else { // There was another element with the same tag name

				if(isset($current[$tag][0])) { // If there is a 0th element it is already an array
					$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
					$repeated_tag_index[$tag.'_'.$level]++;
				} else { // This section will make the value an array if multiple tags with the same name appear together
					$current[$tag] = array($current[$tag],$result); // This will combine the existing item and the new item together to make an array
					$repeated_tag_index[$tag.'_'.$level] = 2;

					if(isset($current[$tag.'_attr'])) { // The attribute of the last(0th) tag must be moved as well
						$current[$tag]['0_attr'] = $current[$tag.'_attr'];
						unset($current[$tag.'_attr']);
					}

				}
				$last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
				$current = &$current[$tag][$last_item_index];
			}

		} elseif($type == "complete") { // Tags that ends in 1 line '<tag />'
			//See if the key is already taken.
			if(!isset($current[$tag])) { //New Key
				$current[$tag] = $result;
				$repeated_tag_index[$tag.'_'.$level] = 1;
				if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

			} else { // If taken, put all things inside a list(array)
				if(isset($current[$tag][0]) and is_array($current[$tag])) { // If it is already an array...

					// ...push the new element into that array.
					$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

					if($priority == 'tag' and $get_attributes and $attributes_data) {
						$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag.'_'.$level]++;

				} else { // If it is not an array...
					$current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
					$repeated_tag_index[$tag.'_'.$level] = 1;
					if($priority == 'tag' and $get_attributes) {
						if(isset($current[$tag.'_attr'])) { // The attribute of the last(0th) tag must be moved as well

							$current[$tag]['0_attr'] = $current[$tag.'_attr'];
							unset($current[$tag.'_attr']);
						}

						if($attributes_data) {
							$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
						}
					}
					$repeated_tag_index[$tag.'_'.$level]++; // 0 and 1 indexes are already taken
				}
			}

		} elseif($type == 'close') { // End of tag '</tag>'
			$current = &$parent[$level-1];
		}
	}

	return($xml_array);
}


function email_header_encode($in_str, $charset = 'UTF-8') {
	$out_str = $in_str;
	$need_to_convert = false;

	for($x = 0; $x < strlen($in_str); $x ++) {
		if((ord($in_str[$x]) == 0) || ((ord($in_str[$x]) > 128))) {
			$need_to_convert = true;
		}
	}

	if(! $need_to_convert)
		return $in_str;

	if ($out_str && $charset) {

		// define start delimimter, end delimiter and spacer
		$end = "?=";
		$start = "=?" . $charset . "?B?";
		$spacer = $end . "\r\n " . $start;

		// determine length of encoded text within chunks
		// and ensure length is even
		$length = 75 - strlen($start) - strlen($end);

		/*
			[EDIT BY danbrown AT php DOT net: The following
			is a bugfix provided by (gardan AT gmx DOT de)
			on 31-MAR-2005 with the following note:
			"This means: $length should not be even,
			but divisible by 4. The reason is that in
			base64-encoding 3 8-bit-chars are represented
			by 4 6-bit-chars. These 4 chars must not be
			split between two encoded words, according
			to RFC-2047.
		*/
		$length = $length - ($length % 4);

		// encode the string and split it into chunks
		// with spacers after each chunk
		$out_str = base64_encode($out_str);
		$out_str = chunk_split($out_str, $length, $spacer);

		// remove trailing spacer and
		// add start and end delimiters
		$spacer = preg_quote($spacer,'/');
		$out_str = preg_replace("/" . $spacer . "$/", "", $out_str);
		$out_str = $start . $out_str . $end;
	}
	return $out_str;
}

function email_send($addr, $subject, $headers, $item) {
	//$headers .= 'MIME-Version: 1.0' . "\n";
	//$headers .= 'Content-Type: text/html; charset=UTF-8' . "\n";
	//$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\n";
	//$headers .= 'Content-Transfer-Encoding: 8bit' . "\n\n";

	$part = uniqid("", true);

	$html	= prepare_body($item);

	$headers .= "Mime-Version: 1.0\n";
	$headers .= 'Content-Type: multipart/alternative; boundary="=_'.$part.'"'."\n\n";

	$body = "\n--=_".$part."\n";
	$body .= "Content-Transfer-Encoding: 8bit\n";
	$body .= "Content-Type: text/plain; charset=utf-8; format=flowed\n\n";

	$body .= html2plain($html)."\n";

	$body .= "--=_".$part."\n";
	$body .= "Content-Transfer-Encoding: 8bit\n";
	$body .= "Content-Type: text/html; charset=utf-8\n\n";

	$body .= '<html><head></head><body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; ">'.$html."</body></html>\n";

	$body .= "--=_".$part."--";

	//$message = '<html><body>' . $html . '</body></html>';
	//$message = html2plain($html);
	logger('notifier: email delivery to ' . $addr);
	mail($addr, $subject, $body, $headers);
}



function discover_by_url($url,$arr = null) {
	require_once('library/HTML5/Parser.php');

	$x = scrape_feed($url);
	if(! $x) {
		if(! $arr)
			return false;
		$network = (($arr['network']) ? $arr['network'] : 'unknown');
		$name = (($arr['name']) ? $arr['name'] : 'unknown');
		$photo = (($arr['photo']) ? $arr['photo'] : '');
		$addr = (($arr['addr']) ? $arr['addr'] : '');
		$guid = $url;
	}

	$profile = $url;

	logger('scrape_feed results: ' . print_r($x,true));

	if($x['feed_atom'])
		$guid = $x['feed_atom'];
	if($x['feed_rss'])
		$guid = $x['feed_rss'];

	if(! $guid)
		return false;


	// try and discover stuff from the feeed

	require_once('library/simplepie/simplepie.inc');
	$feed = new SimplePie();
	$level = 0;
    $x = z_fetch_url($guid,false,$level,array('novalidate' => true));
	if(! $x['success']) {
		logger('probe_url: feed fetch failed for ' . $poll);
		return false;
	}
	$xml = $x['body'];
	logger('probe_url: fetch feed: ' . $guid . ' returns: ' . $xml, LOGGER_DATA);
	logger('probe_url: scrape_feed: headers: ' . $x['header'], LOGGER_DATA);

	// Don't try and parse an empty string
	$feed->set_raw_data(($xml) ? $xml : '<?xml version="1.0" encoding="utf-8" ?><xml></xml>');

	$feed->init();
	if($feed->error())
		logger('probe_url: scrape_feed: Error parsing XML: ' . $feed->error());

	$name = unxmlify(trim($feed->get_title()));
	$photo = $feed->get_image_url();
	$author = $feed->get_author();

	if($author) {
		if(! $name)
			$name = unxmlify(trim($author->get_name()));
		if(! $name) {
			$name = trim(unxmlify($author->get_email()));
			if(strpos($name,'@') !== false)
				$name = substr($name,0,strpos($name,'@'));
		}
		if(! $profile && $author->get_link())
			$profile = trim(unxmlify($author->get_link()));
		if(! $photo) {
			$rawtags = $feed->get_feed_tags( SIMPLEPIE_NAMESPACE_ATOM_10, 'author');
			if($rawtags) {
				$elems = $rawtags[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10];
				if((x($elems,'link')) && ($elems['link'][0]['attribs']['']['rel'] === 'photo'))
					$photo = $elems['link'][0]['attribs']['']['href'];
			}
		}
	}
	else {
		$item = $feed->get_item(0);
		if($item) {
			$author = $item->get_author();
			if($author) {
				if(! $name) {
					$name = trim(unxmlify($author->get_name()));
					if(! $name)
						$name = trim(unxmlify($author->get_email()));
					if(strpos($name,'@') !== false)
						$name = substr($name,0,strpos($name,'@'));
				}
				if(! $profile && $author->get_link())
					$profile = trim(unxmlify($author->get_link()));
			}
			if(! $photo) {
				$rawmedia = $item->get_item_tags('http://search.yahoo.com/mrss/','thumbnail');
				if($rawmedia && $rawmedia[0]['attribs']['']['url'])
					$photo = unxmlify($rawmedia[0]['attribs']['']['url']);
			}
			if(! $photo) {
				$rawtags = $item->get_item_tags( SIMPLEPIE_NAMESPACE_ATOM_10, 'author');
				if($rawtags) {
					$elems = $rawtags[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10];
					if((x($elems,'link')) && ($elems['link'][0]['attribs']['']['rel'] === 'photo'))
						$photo = $elems['link'][0]['attribs']['']['href'];
				}
			}
		}
	}
	if($poll === $profile)
		$lnk = $feed->get_permalink();
	if(isset($lnk) && strlen($lnk))
		$profile = $lnk;

	if(! $network) {
		$network = 'rss';
	}

	if(! $name)
		$name = notags($feed->get_description());

	if(! $guid)
		return false;

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($guid)
	);
	if($r)
		return true;

	if(! $photo)
		$photo = z_root() . '/images/rss_icon.png';

	$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_pubkey, xchan_addr, xchan_url, xchan_name, xchan_network, xchan_instance_url, xchan_name_date ) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
		dbesc($guid),
		dbesc($guid),
		dbesc($pubkey),
		dbesc($addr),
		dbesc($profile),
		dbesc($name),
		dbesc($network),
		dbesc(z_root()),
		dbesc(datetime_convert())
	);

	$photos = import_xchan_photo($photo,$guid);
	$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_hash = '%s'",
		dbesc(datetime_convert()),
		dbesc($photos[0]),
		dbesc($photos[1]),
		dbesc($photos[2]),
		dbesc($photos[3]),
		dbesc($guid)
	);
	return true;

}


function discover_by_webbie($webbie) {
	require_once('library/HTML5/Parser.php');

	$result   = array();

	$network  = null;

	$diaspora = false;
	$gnusoc   = false;
	$dfrn     = false;

	$has_salmon = false;
	$salmon_key = false;
	$atom_feed = false;
	$diaspora_base = '';
	$diaspora_guid = '';
	$diaspora_key = '';

	$webbie = strtolower($webbie);

	$x = webfinger_rfc7033($webbie,true);
	if($x && array_key_exists('links',$x) && $x['links']) {
		foreach($x['links'] as $link) {
			if(array_key_exists('rel',$link)) {

				// If we discover zot - don't search further; grab the info and get out of
				// here.

				if($link['rel'] === PROTOCOL_ZOT) {
					logger('discover_by_webbie: zot found for ' . $webbie, LOGGER_DEBUG);
					if(array_key_exists('zot',$x) && $x['zot']['success']) {
						$i = import_xchan($x['zot']);
						return true;
					}
					else {
						$z = z_fetch_url($link['href']);
						if($z['success']) {
							$j = json_decode($z['body'],true);
							$i = import_xchan($j);
							return true;
						}
					}
				}
				if($link['rel'] == NAMESPACE_DFRN) {
					 $dfrn = $link['href'];
				}
				if($link['rel'] == 'magic-public-key') {
        			if(substr($link['href'],0,5) === 'data:') {
						$salmon_key = convert_salmon_key($link['href']);
					}
				}
				if($link['rel'] == 'salmon') {
					$has_salmon = true;
					$salmon = $link['href'];
				}
				if($link['rel'] == 'http://schemas.google.com/g/2010#updates-from') {
					$atom_feed = $link['href'];
				}
			}
		}
	}

	logger('webfinger: ' . print_r($x,true), LOGGER_DATA, LOG_INFO);

	$arr = array('address' => $webbie, 'success' => false, 'webfinger' => $x);
	call_hooks('discover_channel_webfinger', $arr);
	if($arr['success'])
		return true;

	$aliases = array();

	// Now let's make some decisions on what we may need
	// to obtain further info

	$probe_atom  = false;
	$probe_old   = false;
	$probe_hcard = false;

	$address  = '';
	$location = '';
	$nickname = '';
	$fullname = '';
	$avatar   = '';
	$pubkey   = '';

	if(is_array($x)) {
		if(array_key_exists('address',$x))
			$address = $x['address'];
		if(array_key_exists('location',$x))
			$location = $x['location'];
		if(array_key_exists('nickname',$x))
			$nickname = $x['nickname'];
	}

	if(! $x)
		$probe_old = true;


	if((! $dfrn) && (! $has_salmon))
		$probe_old = true;

	if($probe_old) {
		$y = old_webfinger($webbie);
		if($y) {
			logger('old_webfinger: ' . print_r($x,true));
			foreach($y as $link) {
				if($link['@attributes']['rel'] === NAMESPACE_DFRN)
					$dfrn = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'salmon')
					$notify = unamp($link['@attributes']['href']);
	 			if($link['@attributes']['rel'] === NAMESPACE_FEED)
					$poll = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'http://microformats.org/profile/hcard')
					$hcard = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page')
					$profile = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'http://portablecontacts.net/spec/1.0')
					$poco = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'http://joindiaspora.com/seed_location') {
					$diaspora_base = unamp($link['@attributes']['href']);
					$diaspora = true;
				}
				if($link['@attributes']['rel'] === 'http://joindiaspora.com/guid') {
					$diaspora_guid = unamp($link['@attributes']['href']);
					$diaspora = true;
				}
				if($link['@attributes']['rel'] === 'diaspora-public-key') {
					$diaspora_key = base64_decode(unamp($link['@attributes']['href']));
					if(strstr($diaspora_key,'RSA '))
						$pubkey = rsatopem($diaspora_key);
					else
						$pubkey = $diaspora_key;
					$diaspora = true;
				}
				if($link['@attributes']['rel'] == 'magic-public-key') {
        			if(substr($link['@attributes']['href'],0,5) === 'data:') {
						$salmon_key = convert_salmon_key($link['@attributes']['href']);
					}
				}
				if($link['@attributes']['rel'] == 'salmon') {
					$has_salmon = true;
					$salmon = $link['@attributes']['href'];
				}

				if($link['@attributes']['rel'] == 'http://schemas.google.com/g/2010#updates-from') {
					$atom_feed = $link['@attributes']['href'];
				}
				if($link['@attributes']['rel'] === 'alias') {
					$aliases[] = $link['@attributes']['href'];
				}
				if($link['@attributes']['rel'] === 'subject') {
					$subject = $link['@attributes']['href'];
				}
			}
		}
	}

	if($subject || $aliases) {
		if(strpos($webbie,'@')) {
			$rhs = substr($webbie,strpos($webbie,'@')+1);
		}
		else {
			$m = parse_url($webbie);
			if($m) {
				$rhs = $m['host'] . (($m['port']) ? ':' . $m['port'] : '');
			}
		}

		$v = array('subject' => $subject,'aliases' => $aliases);
		$address = find_webfinger_address($v,$rhs);
		$location = find_webfinger_location($v,$rhs);
		if($address)
			$nickname = substr($address,0,strpos($address,'@'));

	}

	if($salmon_key && $has_salmon && $atom_feed && (! $dfrn) && (! $diaspora)) {
		$gnusoc = true;
		$probe_atom = true;
	}

	if(! $pubkey)
		$pubkey = $salmon_key;

	if(($dfrn || $diaspora) && $hcard)
		$probe_hcard = true;

	if(! $fullname)
		$fullname = $nickname;

	if($probe_atom) {
		$k = z_fetch_url($atom_feed);
		if($k['success'])
			$feed_meta = feed_meta($k['body']);
		if($feed_meta) {

			// stash any discovered pubsubhubbub hubs in case we need to follow them
			// this will save an expensive lookup later

			if($feed_meta['hubs'] && $address) {
				set_xconfig($address,'system','push_hubs',$feed_meta['hubs']);
				set_xconfig($address,'system','feed_url',$atom_feed);
			}
			if($feed_meta['author']['author_name']) {
				$fullname = $feed_meta['author']['author_name'];
			}
			if(! $avatar) {
				if($feed_meta['author']['author_photo'])
					$avatar = $feed_meta['author']['author_photo'];
			}

			// for GNU-social over-ride any url aliases we may have picked up in webfinger
			// The author.uri element in the feed is likely to be more accurate

			if($gnusoc && $feed_meta['author']['author_uri'])
				$location = $feed_meta['author']['author_uri'];
		}
	}
	else {
		if($probe_hcard) {
			$vcard = scrape_vcard($hcard);
			if($vcard) {
				logger('vcard: ' . print_r($vcard,true), LOGGER_DATA);
				if($vcard['fn'])
					$fullname = $vcard['fn'];
				if($vcard['photo'] && (strpos($vcard['photo'],'http') !== 0))
					$vcard['photo'] = $diaspora_base . '/' . $vcard['photo'];
				if(($vcard['public_key']) && (! $pubkey)) {
					$diaspora_key = $vcard['public_key'];
					if(strstr($diaspora_key,'RSA '))
						$pubkey = rsatopem($diaspora_key);
					else
						$pubkey = $diaspora_key;
				}
				if(! $avatar)
					$avatar = $vcard['photo'];
				if($diaspora) {
					if(($vcard['uid']) && (! $diaspora_guid))
						$diaspora_guid = $vcard['uid'];
					if(($vcard['url']) && (! $diaspora_base))
						$diaspora_base = $vcard['url'];




				}

			}
		}
	}

	if(($profile) && (! $location))
		$location = $profile;

	if($location) {
		$m = parse_url($location);
		$base = $m['scheme'] . '://' . $m['host'];
		$host = $m['host'];
	}


	if($diaspora && $diaspora_base && $diaspora_guid) {
		if($dfrn)
			$network = 'friendica-over-diaspora';
		else
			$network = 'diaspora';

		$base = trim($diaspora_base,'/');
		$notify = $base . '/receive';

	}
	else {
		if($gnusoc) {
			$network = 'gnusoc';
			$notify = $salmon;
		}
	}


	logger('network: ' . $network);
	logger('address: ' . $address);
	logger('fullname: ' . $fullname);
	logger('pubkey: ' . $pubkey);
	logger('location: ' . $location);



	// if we have everything we need, let's create the records

	if($network && $address && $fullname && $pubkey && $location) {
		$r = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($address)
		);
		if($r) {
			$r = q("update xchan set xchan_name = '%s', xchan_network = '%s', xchan_name_date = '%s' where xchan_hash = '%s'",
				dbesc($fullname),
				dbesc($network),
				dbesc(datetime_convert()),
				dbesc($address)
			);
		}
		else {
			$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_pubkey, xchan_addr, xchan_url, xchan_name, xchan_network, xchan_name_date ) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
				dbesc($address),
				dbesc(($diaspora_guid) ? $diaspora_guid : $location),
				dbesc($pubkey),
				dbesc($address),
				dbesc($location),
				dbesc($fullname),
				dbesc($network),
				dbescdate(datetime_convert())
			);
		}

		$r = q("select * from hubloc where hubloc_hash = '%s' limit 1",
			dbesc($address)
		);

		if(! $r) {
			$r = q("insert into hubloc ( hubloc_guid, hubloc_hash, hubloc_addr, hubloc_network, hubloc_url, hubloc_host, hubloc_callback, hubloc_updated, hubloc_primary ) values ('%s','%s','%s','%s','%s','%s','%s','%s', 1)",
				dbesc(($diaspora_guid) ? $diaspora_guid : $location),
				dbesc($address),
				dbesc($address),
				dbesc($network),
				dbesc($base),
				dbesc($host),
				dbesc($notify),
				dbescdate(datetime_convert())
			);
		}
		$photos = import_xchan_photo($avatar,$address);
		$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_hash = '%s'",
			dbescdate(datetime_convert()),
			dbesc($photos[0]),
			dbesc($photos[1]),
			dbesc($photos[2]),
			dbesc($photos[3]),
			dbesc($address)
		);
		return true;
	}
	return false;
}



function webfinger_rfc7033($webbie,$zot = false) {


	if(strpos($webbie,'@')) {
		$lhs = substr($webbie,0,strpos($webbie,'@'));
		$rhs = substr($webbie,strpos($webbie,'@')+1);
		$resource = 'acct:' . $webbie;
	}
	else {
		$m = parse_url($webbie);
		if($m) {
			if($m['scheme'] !== 'https')
				return false;
			$rhs = $m['host'] . (($m['port']) ? ':' . $m['port'] : '');
			$resource = urlencode($webbie);
		}
		else
			return false;
	}
	logger('fetching url from resource: ' . $rhs . ':' . $webbie);

	$s = z_fetch_url('https://' . $rhs . '/.well-known/webfinger?f=&resource=' . $resource . (($zot) ? '&zot=1' : ''));

	if($s['success']) {
		$j = json_decode($s['body'],true);

		// We could have a number of URL aliases and webbies
		// make an executive decision about the most likely "best" of each
		// by comparing against some examples from known networks we're likely to encounter.
		// Otherwise we have to store every alias that we may ever encounter and
		// validate every URL we ever find against every possible alias

		// @fixme pump.io is going to be a real bugger since it doesn't return subject or aliases
		// or provide lookup by url

		$j['address'] = find_webfinger_address($j,$rhs);
		$j['location'] = find_webfinger_location($j,$rhs);
		if($j['address'])
			$j['nickname'] = substr($j['address'],0,strpos($j['address'],'@'));
	}
	else
		return false;

	return($j);
}

function find_webfinger_address($j,$rhs) {
	if(is_array($j) && ($j)) {
		if(strpos($j['subject'],'acct:') !== false && strpos($j['subject'],'@' . $rhs))
			return str_replace('acct:','',$j['subject']);
		if($j['aliases']) {
			foreach($j['aliases'] as $alias) {
				if(strpos($alias,'acct:') !== false && strpos($alias,'@' . $rhs)) {
					return str_replace('acct:','',$alias);
				}
			}
		}
	}
	return '';
}


function find_webfinger_location($j,$rhs) {
	if(is_array($j) && ($j)) {
		if(strpos($j['subject'],'http') === 0) {
			$x = match_webfinger_location($j['subject'],$rhs);
			if($x)
				return $x;
		}
		if($j['aliases']) {
			foreach($j['aliases'] as $alias) {
				if(strpos($alias,'http') === 0) {
					$x = match_webfinger_location($alias,$rhs);
					if($x)
						return($x);
				}
			}
		}
	}
	return '';
}

function match_webfinger_location($s,$h) {

	// GNU-social and the older StatusNet - the $host/user/123 form doesn't work
	if(preg_match('|' . $h . '/index.php/user/([0-9]*?)$|',$s))
		return $s;
	// Redmatrix / hubzilla
	if(preg_match('|' . $h . '/channel/|',$s))
		return $s;
	// Friendica
	if(preg_match('|' . $h . '/profile/|',$s))
		return $s;

	$arr = array('test' => $s, 'host' => $h, 'success' => false);
	call_hooks('match_webfinger_location',$arr);
	if($arr['success'])
		return $s;
	return '';
}







function old_webfinger($webbie) {

	$host = '';
	if(strstr($webbie,'@'))
		$host = substr($webbie,strpos($webbie,'@') + 1);

	if(strlen($host)) {
		$tpl = fetch_lrdd_template($host);
		logger('old_webfinger: lrdd template: ' . $tpl,LOGGER_DATA);
		if(strlen($tpl)) {
			$pxrd = str_replace('{uri}', urlencode('acct:' . $webbie), $tpl);
			logger('old_webfinger: pxrd: ' . $pxrd,LOGGER_DATA);
			$links = fetch_xrd_links($pxrd);
			if(! count($links)) {
				// try with double slashes
				$pxrd = str_replace('{uri}', urlencode('acct://' . $webbie), $tpl);
				logger('old_webfinger: pxrd: ' . $pxrd,LOGGER_DATA);
				$links = fetch_xrd_links($pxrd);
			}
			return $links;
		}
	}
	return array();
}


function fetch_lrdd_template($host) {
	$tpl = '';

	$url1 = 'https://' . $host . '/.well-known/host-meta' ;
	$url2 = 'http://' . $host . '/.well-known/host-meta' ;
	$links = fetch_xrd_links($url1);
	logger('fetch_lrdd_template from: ' . $url1, LOGGER_DEBUG);
	logger('template (https): ' . print_r($links,true),LOGGER_DEBUG);
	if(! count($links)) {
		logger('fetch_lrdd_template from: ' . $url2);
		$links = fetch_xrd_links($url2);
		logger('template (http): ' . print_r($links,true),LOGGER_DEBUG);
	}
	if(count($links)) {
		foreach($links as $link)
			if($link['@attributes']['rel'] && $link['@attributes']['rel'] === 'lrdd' && (!$link['@attributes']['type'] || $link['@attributes']['type'] === 'application/xrd+xml'))
				$tpl = $link['@attributes']['template'];
	}
	if(! strpos($tpl,'{uri}'))
		$tpl = '';
	return $tpl;

}


function fetch_xrd_links($url) {

	logger('fetch_xrd_links: ' . $url, LOGGER_DEBUG);

	$redirects = 0;
	$x = z_fetch_url($url,false,$redirects,array('timeout' => 20));

	if(! $x['success'])
		return array();

	$xml = $x['body'];
	logger('fetch_xrd_links: ' . $xml, LOGGER_DATA);

	if ((! $xml) || (! stristr($xml,'<xrd')))
		return array();

	// fix diaspora's bad xml
	$xml = str_replace(array('href=&quot;','&quot;/>'),array('href="','"/>'),$xml);

	$h = parse_xml_string($xml);
	if(! $h)
		return array();

	$arr = convert_xml_element_to_array($h);

	$links = array();

	if(isset($arr['xrd']['link'])) {
		$link = $arr['xrd']['link'];

		if(! isset($link[0]))
			$links = array($link);
		else
			$links = $link;
	}
	if(isset($arr['xrd']['alias'])) {
		$alias = $arr['xrd']['alias'];
		if(! isset($alias[0]))
			$aliases = array($alias);
		else
			$aliases = $alias;
		if(is_array($aliases) && count($aliases)) {
			foreach($aliases as $alias) {
				$links[]['@attributes'] = array('rel' => 'alias' , 'href' => $alias);
			}
		}
	}

	if(isset($arr['xrd']['subject'])) {
		$links[]['@attributes'] = array('rel' => 'subject' , 'href' => $arr['xrd']['subject']);
	}

	logger('fetch_xrd_links: ' . print_r($links,true), LOGGER_DATA);

	return $links;
}


function scrape_vcard($url) {

	$ret = array();

	logger('scrape_vcard: url=' . $url);

	$x = z_fetch_url($url);
	if(! $x['success'])
		return $ret;

	$s = $x['body'];

	if(! $s)
		return $ret;

	$headers = $x['header'];
	$lines = explode("\n",$headers);
	if(count($lines)) {
		foreach($lines as $line) {
			// don't try and run feeds through the html5 parser
			if(stristr($line,'content-type:') && ((stristr($line,'application/atom+xml')) || (stristr($line,'application/rss+xml'))))
				return ret;
		}
	}

	try {
		$dom = HTML5_Parser::parse($s);
	} catch (DOMException $e) {
		logger('scrape_vcard: parse error: ' . $e);
	}

	if(! $dom)
		return $ret;

	// Pull out hCard profile elements

	$largest_photo = 0;

	$items = $dom->getElementsByTagName('*');
	foreach($items as $item) {
		if(attribute_contains($item->getAttribute('class'), 'vcard')) {
			$level2 = $item->getElementsByTagName('*');
			foreach($level2 as $x) {
				if(attribute_contains($x->getAttribute('id'),'pod_location'))
					$ret['pod_location'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'fn'))
					$ret['fn'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'uid'))
					$ret['uid'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'nickname'))
					$ret['nick'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'searchable'))
					$ret['searchable'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'key'))
					$ret['public_key'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'given_name'))
					$ret['given_name'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'family_name'))
					$ret['family_name'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'url'))
					$ret['url'] = $x->textContent;

				if((attribute_contains($x->getAttribute('class'),'photo'))
					|| (attribute_contains($x->getAttribute('class'),'avatar'))) {
					$size = intval($x->getAttribute('width'));
					if(($size > $largest_photo) || (! $largest_photo)) {
						$ret['photo'] = $x->getAttribute('src');
						$largest_photo = $size;
					}
				}
			}
		}
	}

	return $ret;
}



function scrape_feed($url) {

	$ret = array();
	$level = 0;
	$x = z_fetch_url($url,false,$level,array('novalidate' => true));

	if(! $x['success'])
		return $ret;

	$headers = $x['header'];
	$code = $x['return_code'];
	$s = $x['body'];

	logger('scrape_feed: returns: ' . $code . ' headers=' . $headers, LOGGER_DEBUG);

	if(! $s) {
		logger('scrape_feed: no data returned for ' . $url);
		return $ret;
	}


	$lines = explode("\n",$headers);
	if(count($lines)) {
		foreach($lines as $line) {
			if(stristr($line,'content-type:')) {
				if(stristr($line,'application/atom+xml') || stristr($s,'<feed')) {
					$ret['feed_atom'] = $url;
					return $ret;
				}
 				if(stristr($line,'application/rss+xml') || stristr($s,'<rss')) {
					$ret['feed_rss'] = $url;
					return $ret;
				}
			}
		}
		// perhaps an RSS version 1 feed with a generic or incorrect content-type?
		if(stristr($s,'</item>')) {
			$ret['feed_rss'] = $url;
			return $ret;
		}
	}

	try {
		$dom = HTML5_Parser::parse($s);
	} catch (DOMException $e) {
		logger('scrape_feed: parse error: ' . $e);
	}

	if(! $dom) {
		logger('scrape_feed: failed to parse.');
		return $ret;
	}


	$head = $dom->getElementsByTagName('base');
	if($head) {
		foreach($head as $head0) {
			$basename = $head0->getAttribute('href');
			break;
		}
	}
	if(! $basename)
		$basename = implode('/', array_slice(explode('/',$url),0,3)) . '/';

	$items = $dom->getElementsByTagName('link');

	// get Atom/RSS link elements, take the first one of either.

	if($items) {
		foreach($items as $item) {
			$x = $item->getAttribute('rel');
			if(($x === 'alternate') && ($item->getAttribute('type') === 'application/atom+xml')) {
				if(! x($ret,'feed_atom'))
					$ret['feed_atom'] = $item->getAttribute('href');
			}
			if(($x === 'alternate') && ($item->getAttribute('type') === 'application/rss+xml')) {
				if(! x($ret,'feed_rss'))
					$ret['feed_rss'] = $item->getAttribute('href');
			}
		}
	}

	// Drupal and perhaps others only provide relative URL's. Turn them into absolute.

	if(x($ret,'feed_atom') && (! strstr($ret['feed_atom'],'://')))
		$ret['feed_atom'] = $basename . $ret['feed_atom'];
	if(x($ret,'feed_rss') && (! strstr($ret['feed_rss'],'://')))
		$ret['feed_rss'] = $basename . $ret['feed_rss'];

	return $ret;
}



function service_plink($contact, $guid) {

	$plink = '';

	$m = parse_url($contact['xchan_url']);
	if($m) {
		$url = $m['scheme'] . '://' . $m['host'] . (($m['port']) ? ':' . $m['port'] : '');
	}
	else
		$url = 'https://' . substr($contact['xchan_addr'],strpos($contact['xchan_addr'],'@')+1);

	$handle = substr($contact['xchan_addr'], 0, strpos($contact['xchan_addr'],'@'));

	if($contact['xchan_network'] === 'diaspora')
		$plink = $url . '/posts/' . $guid;
	if($contact['xchan_network'] === 'friendica-over-diaspora')
		$plink = $url . '/display/' . $handle . '/' . $guid;
	if($contact['xchan_network'] === 'zot')
		$plink = $url . '/channel/' . $handle . '?f=&mid=' . $guid;

	return $plink;
}


function format_and_send_email($sender,$xchan,$item) {

	$title = $item['title'];
	$body = $item['body'];

    $textversion = strip_tags(html_entity_decode(bbcode(str_replace(array("\\r", "\\n"), array( "", "\n"), $body)),ENT_QUOTES,'UTF-8'));

	$htmlversion = bbcode(str_replace(array("\\r","\\n"), array("","<br />\n"),$body));

   $banner     = t('$Projectname Notification');
    $product    = t('$projectname'); // PLATFORM_NAME;
    $siteurl    = z_root();
    $thanks     = t('Thank You,');
    $sitename   = get_config('system','sitename');
    $site_admin = sprintf( t('%s Administrator'), $sitename);

		// load the template for private message notifications
		$tpl = get_markup_template('email_notify_html.tpl');
		$email_html_body = replace_macros($tpl,array(
			'$banner'	    => $banner,
			'$notify_icon'  => Zotlabs\Lib\System::get_notify_icon(),
			'$product'	    => $product,
			'$preamble'	    => '',
			'$sitename'	    => $sitename,
			'$siteurl'	    => $siteurl,
			'$source_name'  => $sender['xchan_name'],
			'$source_link'  => $sender['xchan_url'],
			'$source_photo' => $sender['xchan_photo_m'],
			'$username'	    => $xchan['xchan_name'],
			'$hsitelink'	=> $datarray['hsitelink'],
			'$hitemlink'	=> $datarray['hitemlink'],
			'$thanks'	    => $thanks,
			'$site_admin'   => $site_admin,
			'$title'		=> $title,
			'$htmlversion'  => $htmlversion,
		));

		// load the template for private message notifications
		$tpl = get_markup_template('email_notify_text.tpl');
		$email_text_body = replace_macros($tpl, array(
			'$banner'       => $banner,
			'$product'      => $product,
			'$preamble'     => '',
			'$sitename'     => $sitename,
			'$siteurl'      => $siteurl,
			'$source_name'  => $sender['xchan_name'],
			'$source_link'  => $sender['xchan_url'],
			'$source_photo' => $sender['xchan_photo_m'],
			'$username'     => $xchan['xchan_name'],
			'$hsitelink'    => $datarray['hsitelink'],
			'$hitemlink'    => $datarray['hitemlink'],
			'$thanks'       => $thanks,
			'$site_admin'   => $site_admin,
			'$title'        => $title,
			'$textversion'  => $textversion
		));

		$sender_name = t('Administrator');

  		$hostname = App::get_hostname();
	    if(strpos($hostname,':'))
    	    $hostname = substr($hostname,0,strpos($hostname,':'));
		$sender_email = get_config('system','reply_address');
		if(! $sender_email)
			$sender_email = 'noreply' . '@' . $hostname;

		// use the EmailNotification library to send the message

		Zotlabs\Lib\Enotify::send(array(
			'fromName'             => $product,
			'fromEmail'            => $sender_email,
			'replyTo'              => $sender_email,
			'toEmail'              => str_replace('mailto:','',$xchan['xchan_addr']),
			'messageSubject'       => (($title) ? $title : t('No Subject')),
			'htmlVersion'          => $email_html_body,
			'textVersion'          => $email_text_body,
			'additionalMailHeader' => '',
		));

}


function do_delivery($deliveries) {

	if(! (is_array($deliveries) && count($deliveries)))
		return;

	$interval = ((get_config('system','delivery_interval') !== false)
			? intval(get_config('system','delivery_interval')) : 2 );

	$deliveries_per_process = intval(get_config('system','delivery_batch_count'));

	if($deliveries_per_process <= 0)
		$deliveries_per_process = 1;


	$deliver = array();
	foreach($deliveries as $d) {

		if(! $d)
			continue;

		$deliver[] = $d;

		if(count($deliver) >= $deliveries_per_process) {
			Zotlabs\Daemon\Master::Summon(array('Deliver',$deliver));
			$deliver = array();
			if($interval)
				@time_sleep_until(microtime(true) + (float) $interval);
		}
	}

	// catch any stragglers

	if($deliver)
		Zotlabs\Daemon\Master::Summon(array('Deliver',$deliver));


}


function get_site_info() {

	$register_policy = Array('REGISTER_CLOSED', 'REGISTER_APPROVE', 'REGISTER_OPEN');
	$directory_mode = Array('DIRECTORY_MODE_NORMAL', 'DIRECTORY_MODE_PRIMARY', 'DIRECTORY_MODE_SECONDARY', 256 => 'DIRECTORY_MODE_STANDALONE');

	$sql_extra = '';

	$r = q("select * from channel left join account on account_id = channel_account_id where ( account_roles & 4096 )>0 and account_default_channel = channel_id");


	if($r) {
		$admin = array();
		foreach($r as $rr) {
			if($rr['channel_pageflags'] & PAGE_HUBADMIN)
				$admin[] = array( 'name' => $rr['channel_name'], 'address' => channel_reddress($rr), 'channel' => z_root() . '/channel/' . $rr['channel_address']);
		}
		if(! $admin) {
			foreach($r as $rr) {
				$admin[] = array( 'name' => $rr['channel_name'], 'address' => channel_reddress($rr), 'channel' => z_root() . '/channel/' . $rr['channel_address']);
			}
		}
	}
	else {
		$admin = false;
	}

	$def_service_class = get_config('system','default_service_class');
	if($def_service_class)
		$service_class = get_config('service_class',$def_service_class);
	else
		$service_class = false;

	$visible_plugins = visible_plugin_list();

	if(@is_dir('.git') && function_exists('shell_exec'))
		$commit = trim(@shell_exec('git log -1 --format="%h"'));
	if(! isset($commit) || strlen($commit) > 16)
		$commit = '';

	$site_info = get_config('system','info');
	$site_name = get_config('system','sitename');
	if(! get_config('system','hidden_version_siteinfo')) {
		$version = Zotlabs\Lib\System::get_project_version();
		$tag = Zotlabs\Lib\System::get_std_version();

		if(@is_dir('.git') && function_exists('shell_exec')) {
			$commit = trim( @shell_exec('git log -1 --format="%h"'));
		}

		if(! isset($commit) || strlen($commit) > 16)
			$commit = '';
	}
	else {
			$version = $commit = '';
	}

	//Statistics
	$channels_total_stat = intval(get_config('system','channels_total_stat'));
	$channels_active_halfyear_stat = intval(get_config('system','channels_active_halfyear_stat'));
	$channels_active_monthly_stat = intval(get_config('system','channels_active_monthly_stat'));
	$local_posts_stat = intval(get_config('system','local_posts_stat'));
	$hide_in_statistics = intval(get_config('system','hide_in_statistics'));
	$site_expire = intval(get_config('system', 'default_expire_days'));

	load_config('feature_lock');
	$locked_features = array();
	if(is_array(App::$config['feature_lock']) && count(App::$config['feature_lock'])) {
		foreach(App::$config['feature_lock'] as $k => $v) {
			if($k === 'config_loaded')
				continue;
			$locked_features[$k] = intval($v);
		}
	}



	$data = Array(
		'version' => $version,
		'version_tag' => $tag,
		'server_role' => Zotlabs\Lib\System::get_server_role(),
		'commit' => $commit,
		'url' => z_root(),
		'plugins' => $visible_plugins,
		'register_policy' =>  $register_policy[get_config('system','register_policy')],
		'invitation_only' => intval(get_config('system','invitation_only')),
		'directory_mode' =>  $directory_mode[get_config('system','directory_mode')],
		'language' => get_config('system','language'),
		'rss_connections' => intval(get_config('system','feed_contacts')),
		'expiration' => $site_expire,
		'default_service_restrictions' => $service_class,
		'locked_features' => $locked_features,
		'admin' => $admin,
		'site_name' => (($site_name) ? $site_name : ''),
		'platform' => Zotlabs\Lib\System::get_platform_name(),
		'dbdriver' => DBA::$dba->getdriver(),
		'lastpoll' => get_config('system','lastpoll'),
		'info' => (($site_info) ? $site_info : ''),
		'channels_total' => $channels_total_stat,
		'channels_active_halfyear' => $channels_active_halfyear_stat,
		'channels_active_monthly' => $channels_active_monthly_stat,
		'local_posts' => $local_posts_stat,
		'hide_in_statistics' => $hide_in_statistics
	);
	return $data;
}



function check_siteallowed($url) {

	$retvalue = true;


	$arr = array('url' => $url);
	call_hooks('check_siteallowed',$arr);

	if(array_key_exists('allowed',$arr))
		return $arr['allowed'];

	$bl1 = get_config('system','whitelisted_sites');
	if(is_array($bl1) && $bl1) {
		foreach($bl1 as $bl) {
			if($bl1 === '*')
				$retvalue = true;
			if($bl && strpos($url,$bl) !== false)
				return true;
		}
	}
	$bl1 = get_config('system','blacklisted_sites');
	if(is_array($bl1) && $bl1) {
		foreach($bl1 as $bl) {
			if($bl1 === '*')
				$retvalue = false;
			if($bl && strpos($url,$bl) !== false) {
				return false;
			}
		}
	}
	return $retvalue;
}

function check_channelallowed($hash) {

	$retvalue = true;

	$arr = array('hash' => $hash);
	call_hooks('check_channelallowed',$arr);

	if(array_key_exists('allowed',$arr))
		return $arr['allowed'];

	$bl1 = get_config('system','whitelisted_channels');
	if(is_array($bl1) && $bl1) {
		foreach($bl1 as $bl) {
			if($bl1 === '*')
				$retvalue = true;
			if($bl && strpos($hash,$bl) !== false)
				return true;
		}
	}
	$bl1 = get_config('system','blacklisted_channels');
	if(is_array($bl1) && $bl1) {
		foreach($bl1 as $bl) {
			if($bl1 === '*')
				$retvalue = false;
			if($bl && strpos($hash,$bl) !== false) {
				return false;
			}
		}
	}
	return $retvalue;
}

function deliverable_singleton($channel_id,$xchan) {
	$r = q("select abook_instance from abook where abook_channel = %d and abook_xchan = '%s' limit 1",
		intval($channel_id),
		dbesc($xchan['xchan_hash'])
	);
	if($r) {
		if(! $r[0]['abook_instance'])
			return true;
		if(strpos($r[0]['abook_instance'],z_root()) !== false)
			return true;
	}
	return false;
}



function get_repository_version($branch = 'master') {

	$path = "https://raw.githubusercontent.com/redmatrix/hubzilla/$branch/boot.php";

	$x = z_fetch_url($path);
	if($x['success']) {
		$y = preg_match('/define(.*?)STD_VERSION(.*?)([0-9.].*)\'/',$x['body'],$matches);
		if($y)
			return $matches[3];
	}
	return '?.?';

}

function network_to_name($s) {

	$nets = array(
		NETWORK_DFRN      => t('Friendica'),
		NETWORK_FRND      => t('Friendica'),
		NETWORK_OSTATUS   => t('OStatus'),
		NETWORK_GNUSOCIAL => t('GNU-Social'),
		NETWORK_FEED      => t('RSS/Atom'),
		NETWORK_MAIL      => t('Email'),
		NETWORK_DIASPORA  => t('Diaspora'),
		NETWORK_FACEBOOK  => t('Facebook'),
		NETWORK_ZOT       => t('Zot'),
		NETWORK_LINKEDIN  => t('LinkedIn'),
		NETWORK_XMPP      => t('XMPP/IM'),
		NETWORK_MYSPACE   => t('MySpace'),
	);

	call_hooks('network_to_name', $nets);

	$search  = array_keys($nets);
	$replace = array_values($nets);

	return str_replace($search,$replace,$s);

}


function z_mail($params) {

	/**
	 * @brief Send a text email message
	 *
	 * @param array $params an assoziative array with:
	 *  * \e string \b fromName        name of the sender
	 *  * \e string \b fromEmail       email of the sender
	 *  * \e string \b replyTo         replyTo address to direct responses
	 *  * \e string \b toEmail         destination email address
	 *  * \e string \b messageSubject  subject of the message
	 *  * \e string \b htmlVersion     html version of the message
	 *  * \e string \b textVersion     text only version of the message
	 *  * \e string \b additionalMailHeader  additions to the smtp mail header
	 */

	if(! $params['fromEmail']) {
		$params['fromEmail'] = get_config('system','from_email');
		if(! $params['fromEmail'])
			$params['fromEmail'] = 'Administrator' . '@' . App::get_hostname();
	}
	if(! $params['fromName']) {
		$params['fromName'] = get_config('system','from_email_name');
		if(! $params['fromName'])
			$params['fromName'] = Zotlabs\Lib\System::get_site_name();
	}
	if(! $params['replyTo']) {
		$params['replyTo'] = get_config('system','reply_address');
		if(! $params['replyTo'])
			$params['replyTo'] = 'noreply' . '@' . App::get_hostname();
	}

	$params['sent']   = false;
	$params['result'] = false;

	call_hooks('email_send', $params);

	if($params['sent']) {
		logger('notification: z_mail returns ' . (($params['result']) ? 'success' : 'failure'), LOGGER_DEBUG);
		return $params['result'];
	}

	$fromName = email_header_encode(html_entity_decode($params['fromName'],ENT_QUOTES,'UTF-8'),'UTF-8');
	$messageSubject = email_header_encode(html_entity_decode($params['messageSubject'],ENT_QUOTES,'UTF-8'),'UTF-8');

	$messageHeader =
		$params['additionalMailHeader'] .
		"From: $fromName <{$params['fromEmail']}>\n" .
		"Reply-To: $fromName <{$params['replyTo']}>";

	// send the message
	$res = mail(
		$params['toEmail'],								// send to address
		$messageSubject,								// subject
		$params['textVersion'],
		$messageHeader									// message headers
	);
	logger('notification: z_mail returns ' . (($res) ? 'success' : 'failure'), LOGGER_DEBUG);
	return $res;
}

// discover the best API path available for redmatrix/hubzilla servers

function probe_api_path($host) {

	$schemes = ['https', 'http' ];
	$paths   = ['/api/z/1.0/version', '/api/red/version' ];

	foreach($schemes as $scheme) {
		foreach($paths as $path) {
			$curpath = $scheme . '://' . $host . $path;
			$x = z_fetch_url($curpath);
			if($x['success'] && ! strlen($x['body'],'not implemented'))
				return str_replace('version','',$curpath);
		}
	}

	return '';
}
