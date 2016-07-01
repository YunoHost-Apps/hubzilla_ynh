<?php

namespace Zotlabs\Lib;


class XConfig {

	/**
	 * @brief Loads a full xchan's configuration into a cached storage.
	 *
	 * All configuration values of the given observer hash are stored in global
	 * cache which is available under the global variable App::$config[$xchan].
	 *
	 * @param string $xchan
	 *  The observer's hash
	 * @return void|false Returns false if xchan is not set
	 */

	static public function Load($xchan) {

		if(! $xchan)
			return false;

		if(! array_key_exists($xchan, \App::$config))
			\App::$config[$xchan] = array();

		$r = q("SELECT * FROM xconfig WHERE xchan = '%s'",
			dbesc($xchan)
		);

		if($r) {
			foreach($r as $rr) {
				$k = $rr['k'];
				$c = $rr['cat'];
				if(! array_key_exists($c, \App::$config[$xchan])) {
					\App::$config[$xchan][$c] = array();
					\App::$config[$xchan][$c]['config_loaded'] = true;
				}
				\App::$config[$xchan][$c][$k] = $rr['v'];
			}
		}
	}

	/**
	 * @brief Get a particular observer's config variable given the category
	 * name ($family) and a key.
	 *
	 * Get a particular observer's config value from the given category ($family)
	 * and the $key from a cached storage in App::$config[$xchan].
	 *
	 * Returns false if not set.
	 *
	 * @param string $xchan
	 *  The observer's hash
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to query
	 * @return mixed Stored $value or false if it does not exist
	 */

	static public function Get($xchan, $family, $key) {

		if(! $xchan)
			return false;

		if(! array_key_exists($xchan, \App::$config))
			load_xconfig($xchan);

		if((! array_key_exists($family, \App::$config[$xchan])) || (! array_key_exists($key, \App::$config[$xchan][$family])))
			return false;

		return ((! is_array(\App::$config[$xchan][$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', \App::$config[$xchan][$family][$key])) 
			? unserialize(\App::$config[$xchan][$family][$key])
			: \App::$config[$xchan][$family][$key]
		);
	}

	/**
	 * @brief Sets a configuration value for an observer.
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the observer's $xchan hash.
	 *
	 *
	 * @param string $xchan
	 *  The observer's hash
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to set
	 * @param string $value
	 *  The value to store
	 * @return mixed Stored $value or false
	 */

	static public function Set($xchan, $family, $key, $value) {

		// manage array value
		$dbvalue = ((is_array($value))  ? serialize($value) : $value);
		$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

		if(self::Get($xchan, $family, $key) === false) {
			if(! array_key_exists($xchan, \App::$config))
				\App::$config[$xchan] = array();
			if(! array_key_exists($family, \App::$config[$xchan]))
				\App::$config[$xchan][$family] = array();

			$ret = q("INSERT INTO xconfig ( xchan, cat, k, v ) VALUES ( '%s', '%s', '%s', '%s' ) ",
				dbesc($xchan),
				dbesc($family),
				dbesc($key),
				dbesc($dbvalue)
			);
		}
		else {
			$ret = q("UPDATE xconfig SET v = '%s' WHERE xchan = '%s' and cat = '%s' AND k = '%s'",
				dbesc($dbvalue),
				dbesc($xchan),
				dbesc($family),
				dbesc($key)
			);
		}

		\App::$config[$xchan][$family][$key] = $value;

		if($ret)
			return $value;
		return $ret;
	}

	/**
	 * @brief Deletes the given key from the observer's config.
	 *
	 * Removes the configured value from the stored cache in App::$config[$xchan]
	 * and removes it from the database.
	 *
	 * @param string $xchan
	 *  The observer's hash
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to delete
	 * @return mixed
	 */

	static public function Delete($xchan, $family, $key) {

		if(x(\App::$config[$xchan][$family], $key))
			unset(\App::$config[$xchan][$family][$key]);
		$ret = q("DELETE FROM xconfig WHERE xchan = '%s' AND cat = '%s' AND k = '%s'",
			dbesc($xchan),
			dbesc($family),
			dbesc($key)
		);

		return $ret;
	}

}
