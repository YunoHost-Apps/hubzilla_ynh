<?php

namespace Zotlabs\Zot;

/**
 * @brief Finger
 *
 */
class Finger {

	static private $token;

	/**
	 * @brief Look up information about channel.
	 *
	 * @param string $webbie
	 *   does not have to be host qualified e.g. 'foo' is treated as 'foo\@thishub'
	 * @param array $channel
	 *   (optional), if supplied permissions will be enumerated specifically for $channel
	 * @param boolean $autofallback
	 *   fallback/failover to http if https connection cannot be established. Default is true.
	 *
	 * @return zotinfo array (with 'success' => true) or array('success' => false);
	 */
	static public function run($webbie, $channel = null, $autofallback = true) {

		$ret = array('success' => false);

		self::$token = random_string();

		if (strpos($webbie, '@') === false) {
			$address = $webbie;
			$host = \App::get_hostname();
		} else {
			$address = substr($webbie,0,strpos($webbie,'@'));
			$host = substr($webbie,strpos($webbie,'@')+1);
			if(strpos($host,'/'))
				$host = substr($host,0,strpos($host,'/'));
		}

		$xchan_addr = $address . '@' . $host;

		if ((! $address) || (! $xchan_addr)) {
			logger('zot_finger: no address :' . $webbie);

			return $ret;
		}

		logger('using xchan_addr: ' . $xchan_addr, LOGGER_DATA, LOG_DEBUG);

		// potential issue here; the xchan_addr points to the primary hub.
		// The webbie we were called with may not, so it might not be found
		// unless we query for hubloc_addr instead of xchan_addr

		$r = q("select xchan.*, hubloc.* from xchan
			left join hubloc on xchan_hash = hubloc_hash
			where xchan_addr = '%s' and hubloc_primary = 1 limit 1",
			dbesc($xchan_addr)
		);

		if($r) {
			$url = $r[0]['hubloc_url'];

			if($r[0]['hubloc_network'] && $r[0]['hubloc_network'] !== 'zot') {
				logger('zot_finger: alternate network: ' . $webbie);
				logger('url: ' . $url . ', net: ' . var_export($r[0]['hubloc_network'],true), LOGGER_DATA, LOG_DEBUG);
				return $ret;
			}
		} else {
			$url = 'https://' . $host;
		}

		$rhs = '/.well-known/zot-info';
		$https = ((strpos($url,'https://') === 0) ? true : false);

		logger('zot_finger: ' . $address . ' at ' . $url, LOGGER_DEBUG);

		if ($channel) {
			$postvars = array(
				'address'    => $address,
				'target'     => $channel['channel_guid'],
				'target_sig' => $channel['channel_guid_sig'],
				'key'        => $channel['channel_pubkey'],
				'token'      => self::$token
			);

			$result = z_post_url($url . $rhs,$postvars);

			if ((! $result['success']) && ($autofallback)) {
				if ($https) {
					logger('zot_finger: https failed. falling back to http');
					$result = z_post_url('http://' . $host . $rhs,$postvars);
				}
			}
		} else {
			$rhs .= '?f=&address=' . urlencode($address) . '&token=' . self::$token;

			$result =  z_fetch_url($url . $rhs);
			if((! $result['success']) && ($autofallback)) {
				if($https) {
					logger('zot_finger: https failed. falling back to http');
					$result = z_fetch_url('http://' . $host . $rhs);
				}
			}
		}

		if(! $result['success']) {
			logger('zot_finger: no results');

			return $ret;
		}

		$x = json_decode($result['body'], true);
		if($x) {
			$signed_token = ((is_array($x) && array_key_exists('signed_token', $x)) ? $x['signed_token'] : null);
			if($signed_token) {
				$valid = rsa_verify('token.' . self::$token, base64url_decode($signed_token), $x['key']);
				if(! $valid) {
					logger('invalid signed token: ' . $url . $rhs, LOGGER_NORMAL, LOG_ERR);

					return $ret;
				}
			}
			else {
				logger('No signed token from '  . $url . $rhs, LOGGER_NORMAL, LOG_WARNING);
				// after 2017-01-01 this will be a hard error unless you over-ride it.
				if((time() > 1483228800) && (! get_config('system', 'allow_unsigned_zotfinger')))
					return $ret;
			}
		}

		return $x;
	}

}
