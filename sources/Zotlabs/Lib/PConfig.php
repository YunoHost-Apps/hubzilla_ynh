<?php /** @file */

namespace Zotlabs\Lib;


class PConfig {

	/**
	 * @brief Loads all configuration values of a channel into a cached storage.
	 *
	 * All configuration values of the given channel are stored in global cache
	 * which is available under the global variable App::$config[$uid].
	 *
	 * @param string $uid
	 *  The channel_id
	 * @return void|false Nothing or false if $uid is false
	 */

	static public function Load($uid) {
		if(is_null($uid) || $uid === false)
			return false;

		if(! array_key_exists($uid, \App::$config))
			\App::$config[$uid] = array();

		if(! is_array(\App::$config)) {
			btlogger('App::$config not an array: ' . $uid);
		}

		if(! is_array(\App::$config[$uid])) {
			btlogger('App::$config[$uid] not an array: ' . $uid);
		}

		$r = q("SELECT * FROM pconfig WHERE uid = %d",
			intval($uid)
		);

		if($r) {
			foreach($r as $rr) {
				$k = $rr['k'];
				$c = $rr['cat'];
				if(! array_key_exists($c, \App::$config[$uid])) {
					\App::$config[$uid][$c] = array();
					\App::$config[$uid][$c]['config_loaded'] = true;
				}
				\App::$config[$uid][$c][$k] = $rr['v'];
			}
		}
	}

	/**
	 * @brief Get a particular channel's config variable given the category name
	 * ($family) and a key.
	 *
	 * Get a particular channel's config value from the given category ($family)
	 * and the $key from a cached storage in App::$config[$uid].
	 *
	 * Returns false if not set.
	 *
	 * @param string $uid
	 *  The channel_id
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to query
	 * @param boolean $instore (deprecated, without function)
	 * @return mixed Stored value or false if it does not exist
	 */

	static public function Get($uid,$family,$key,$default = false) {

		if(is_null($uid) || $uid === false)
			return $default;

		if(! array_key_exists($uid, \App::$config))
			self::Load($uid);

		if((! array_key_exists($family, \App::$config[$uid])) || (! array_key_exists($key, \App::$config[$uid][$family])))
			return $default;

		return ((! is_array(\App::$config[$uid][$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', \App::$config[$uid][$family][$key])) 
			? unserialize(\App::$config[$uid][$family][$key])
			: \App::$config[$uid][$family][$key]
		);

	}

	/**
	 * @brief Sets a configuration value for a channel.
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the channel_id $uid.
	 *
	 * @param string $uid
	 *  The channel_id
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to set
	 * @param string $value
	 *  The value to store
	 * @return mixed Stored $value or false
	 */

	static public function Set($uid, $family, $key, $value) {

		// this catches subtle errors where this function has been called 
		// with local_channel() when not logged in (which returns false)
		// and throws an error in array_key_exists below. 
		// we provide a function backtrace in the logs so that we can find
		// and fix the calling function.

		if(is_null($uid) || $uid === false) {
			btlogger('UID is FALSE!', LOGGER_NORMAL, LOG_ERR);
			return;
		}

		// manage array value
		$dbvalue = ((is_array($value))  ? serialize($value) : $value);
		$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

		if(get_pconfig($uid, $family, $key) === false) {
			if(! array_key_exists($uid, \App::$config))
				\App::$config[$uid] = array();
			if(! array_key_exists($family, \App::$config[$uid]))
				\App::$config[$uid][$family] = array();

			$ret = q("INSERT INTO pconfig ( uid, cat, k, v ) VALUES ( %d, '%s', '%s', '%s' ) ",
				intval($uid),
				dbesc($family),
				dbesc($key),
				dbesc($dbvalue)
			);

		}
		else {

			$ret = q("UPDATE pconfig SET v = '%s' WHERE uid = %d and cat = '%s' AND k = '%s'",
				dbesc($dbvalue),
				intval($uid),
				dbesc($family),
				dbesc($key)
			);

		}

		// keep a separate copy for all variables which were
		// set in the life of this page. We need this to
		// synchronise channel clones.

		if(! array_key_exists('transient', \App::$config[$uid]))
			\App::$config[$uid]['transient'] = array();
		if(! array_key_exists($family, \App::$config[$uid]['transient']))
			\App::$config[$uid]['transient'][$family] = array();

		\App::$config[$uid][$family][$key] = $value;
		\App::$config[$uid]['transient'][$family][$key] = $value;

		if($ret)
			return $value;

		return $ret;
	}


	/**
	 * @brief Deletes the given key from the channel's configuration.
	 *
	 * Removes the configured value from the stored cache in App::$config[$uid]
	 * and removes it from the database.
	 *
	 * @param string $uid
	 *  The channel_id
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to delete
	 * @return mixed
	 */
 
	static public function Delete($uid, $family, $key) {

		if(is_null($uid) || $uid === false)
			return false;

		$ret = false;

		if(array_key_exists($uid,\App::$config) 
			&& is_array(\App::$config['uid']) 
			&& array_key_exists($family,\App::$config['uid']) 
			&& array_key_exists($key, \App::$config[$uid][$family]))
			unset(\App::$config[$uid][$family][$key]);
			
		$ret = q("DELETE FROM pconfig WHERE uid = %d AND cat = '%s' AND k = '%s'",
			intval($uid),
			dbesc($family),
			dbesc($key)
		);

		return $ret;
	}

}
		