<?php
/**
 * @file include/config.php
 * @brief Arbitrary configuration storage.
 *
 * @note Please do not store booleans - convert to 0/1 integer values.
 * The get_?config() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 *
 * Arrays get stored as serialize strings.
 *
 * @todo There are a few places in the code (such as the admin panel) where
 * boolean configurations need to be fixed as of 10/08/2011.
 *
 * - <b>config</b> is used for hub specific configurations. It overrides the
 * configurations from .htconfig file. The storage is of size TEXT.
 * - <b>pconfig</b> is used for channel specific configurations and takes a
 * <i>channel_id</i> as identifier. It stores for example which features are
 * enabled per channel. The storage is of size MEDIUMTEXT.
 * @code{.php} $var = get_pconfig(local_channel(), 'category', 'key');@endcode
 * - <b>xconfig</b> is the same as pconfig, except that it uses <i>xchan</i> as
 * an identifier. This is for example for people who do not have a local account.
 * The storage is of size MEDIUMTEXT.
 * @code{.php}
 * $observer = App::get_observer_hash();
 * if ($observer) {
 *     $var = get_xconfig($observer, 'category', 'key');
 * }@endcode
 *
 * - get_config() and set_config() can also be done through the command line tool
 * @ref util/config.md "util/config"
 * - get_pconfig() and set_pconfig() can also be done through the command line tool
 * @ref util/pconfig.md "util/pconfig" and takes a channel_id as first argument. 
 *
 */

/**
 * @brief Loads the hub's configuration from database to a cached storage.
 *
 * Retrieve a category ($family) of config variables from database to a cached
 * storage in the global App::$config[$family].
 *
 * @param string $family
 *  The category of the configuration value
 */
function load_config($family) {
	global $a;

	if(! array_key_exists($family, App::$config))
		App::$config[$family] = array();

	if(! array_key_exists('config_loaded', App::$config[$family])) {
		$r = q("SELECT * FROM config WHERE cat = '%s'", dbesc($family));
		if($r !== false) {
			if($r) {
				foreach($r as $rr) {
					$k = $rr['k'];
					App::$config[$family][$k] = $rr['v'];
				}
			}
			App::$config[$family]['config_loaded'] = true;
		}
	} 
}

/**
 * @brief Get a particular config variable given the category name ($family)
 * and a key.
 *
 * Get a particular config variable from the given category ($family) and the
 * $key from a cached storage in App::$config[$family]. If a key is found in the
 * DB but does not exist in local config cache, pull it into the cache so we
 * do not have to hit the DB again for this item.
 * 
 * Returns false if not set.
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to query
 * @return mixed Return value or false on error or if not set
 */
function get_config($family, $key) {
	global $a;

	if((! array_key_exists($family, App::$config)) || (! array_key_exists('config_loaded', App::$config[$family])))
		load_config($family);

	if(array_key_exists('config_loaded', App::$config[$family])) {
		if(! array_key_exists($key, App::$config[$family])) {
			return false;		
		}
		return ((! is_array(App::$config[$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', App::$config[$family][$key])) 
			? unserialize(App::$config[$family][$key])
			: App::$config[$family][$key]
		);
	}
	return false;
}

/**
 * @brief Returns a value directly from the database configuration storage.
 *
 * This function queries directly the database and bypasses the chached storage
 * from get_config($family, $key).
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to query
 * @return mixed
 */
function get_config_from_storage($family, $key) {
	$ret = q("SELECT * FROM config WHERE cat = '%s' AND k = '%s' LIMIT 1",
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}

/**
 * @brief Sets a configuration value for the hub.
 *
 * Stores a config value ($value) in the category ($family) under the key ($key).
 *
 * @note Please do not store booleans - convert to 0/1 integer values!
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to set
 * @param mixed $value
 *  The value to store in the configuration
 * @return mixed
 *  Return the set value, or false if the database update failed
 */
function set_config($family, $key, $value) {
	global $a;

	// manage array value
	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_config($family, $key) === false || (! get_config_from_storage($family, $key))) {
		$ret = q("INSERT INTO config ( cat, k, v ) VALUES ( '%s', '%s', '%s' ) ",
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret) {
			App::$config[$family][$key] = $value;
			$ret = $value;
		}
		return $ret;
	}

	$ret = q("UPDATE config SET v = '%s' WHERE cat = '%s' AND k = '%s'",
		dbesc($dbvalue),
		dbesc($family),
		dbesc($key)
	);

	if($ret) {
		App::$config[$family][$key] = $value;
		$ret = $value;
	}
	return $ret;
}

/**
 * @brief Deletes the given key from the hub's configuration database.
 *
 * Removes the configured value from the stored cache in App::$config[$family]
 * and removes it from the database.
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to delete
 * @return mixed
 */
function del_config($family, $key) {
	global $a;
	$ret = false;

	if(array_key_exists($family, App::$config) && array_key_exists($key, App::$config[$family]))
		unset(App::$config[$family][$key]);
		$ret = q("DELETE FROM config WHERE cat = '%s' AND k = '%s'",
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}


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
function load_pconfig($uid) {
	global $a;

	if($uid === false)
		return false;

	if(! array_key_exists($uid, App::$config))
		App::$config[$uid] = array();

	$r = q("SELECT * FROM pconfig WHERE uid = %d",
		intval($uid)
	);

	if($r) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$c = $rr['cat'];
			if(! array_key_exists($c, App::$config[$uid])) {
				App::$config[$uid][$c] = array();
				App::$config[$uid][$c]['config_loaded'] = true;
			}
			App::$config[$uid][$c][$k] = $rr['v'];
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
function get_pconfig($uid, $family, $key, $instore = false) {
//	logger('include/config.php: get_pconfig() deprecated instore param used', LOGGER_DEBUG);
	global $a;

	if($uid === false)
		return false;

	if(! array_key_exists($uid, App::$config))
		load_pconfig($uid);

	if((! array_key_exists($family, App::$config[$uid])) || (! array_key_exists($key, App::$config[$uid][$family])))
		return false;

	return ((! is_array(App::$config[$uid][$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', App::$config[$uid][$family][$key])) 
		? unserialize(App::$config[$uid][$family][$key])
		: App::$config[$uid][$family][$key]
	);
}

/**
 * @brief Sets a configuration value for a channel.
 *
 * Stores a config value ($value) in the category ($family) under the key ($key)
 * for the channel_id $uid.
 *
 * @note Please do not store booleans - convert to 0/1 integer values!
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
function set_pconfig($uid, $family, $key, $value) {
	global $a;

	// this catches subtle errors where this function has been called 
	// with local_channel() when not logged in (which returns false)
	// and throws an error in array_key_exists below. 
	// we provide a function backtrace in the logs so that we can find
	// and fix the calling function.

	if($uid === false) {
		btlogger('UID is FALSE!', LOGGER_NORMAL, LOG_ERR);
		return;
	}

	// manage array value
	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_pconfig($uid, $family, $key) === false) {
		if(! array_key_exists($uid, App::$config))
			App::$config[$uid] = array();
		if(! array_key_exists($family, App::$config[$uid]))
			App::$config[$uid][$family] = array();

		// keep a separate copy for all variables which were
		// set in the life of this page. We need this to
		// synchronise channel clones.

		if(! array_key_exists('transient', App::$config[$uid]))
			App::$config[$uid]['transient'] = array();
		if(! array_key_exists($family, App::$config[$uid]['transient']))
			App::$config[$uid]['transient'][$family] = array();

		App::$config[$uid][$family][$key] = $value;
		App::$config[$uid]['transient'][$family][$key] = $value;

		$ret = q("INSERT INTO pconfig ( uid, cat, k, v ) VALUES ( %d, '%s', '%s', '%s' ) ",
			intval($uid),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret)
			return $value;

		return $ret;
	}

	$ret = q("UPDATE pconfig SET v = '%s' WHERE uid = %d and cat = '%s' AND k = '%s'",
		dbesc($dbvalue),
		intval($uid),
		dbesc($family),
		dbesc($key)
	);

	// keep a separate copy for all variables which were
	// set in the life of this page. We need this to
	// synchronise channel clones.

	if(! array_key_exists('transient', App::$config[$uid]))
		App::$config[$uid]['transient'] = array();
	if(! array_key_exists($family, App::$config[$uid]['transient']))
		App::$config[$uid]['transient'][$family] = array();

	App::$config[$uid][$family][$key] = $value;
	App::$config[$uid]['transient'][$family][$key] = $value;

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
function del_pconfig($uid, $family, $key) {
	global $a;
	$ret = false;

	if (x(App::$config[$uid][$family], $key))
		unset(App::$config[$uid][$family][$key]);
		$ret = q("DELETE FROM pconfig WHERE uid = %d AND cat = '%s' AND k = '%s'",
		intval($uid),
		dbesc($family),
		dbesc($key)
	);

	return $ret;
}


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
function load_xconfig($xchan) {
	global $a;

	if(! $xchan)
		return false;

	if(! array_key_exists($xchan, App::$config))
		App::$config[$xchan] = array();

	$r = q("SELECT * FROM xconfig WHERE xchan = '%s'",
		dbesc($xchan)
	);

	if($r) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$c = $rr['cat'];
			if(! array_key_exists($c, App::$config[$xchan])) {
				App::$config[$xchan][$c] = array();
				App::$config[$xchan][$c]['config_loaded'] = true;
			}
			App::$config[$xchan][$c][$k] = $rr['v'];
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
function get_xconfig($xchan, $family, $key) {
	global $a;

	if(! $xchan)
		return false;

	if(! array_key_exists($xchan, App::$config))
		load_xconfig($xchan);

	if((! array_key_exists($family, App::$config[$xchan])) || (! array_key_exists($key, App::$config[$xchan][$family])))
		return false;

	return ((! is_array(App::$config[$xchan][$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', App::$config[$xchan][$family][$key])) 
		? unserialize(App::$config[$xchan][$family][$key])
		: App::$config[$xchan][$family][$key]
	);
}

/**
 * @brief Sets a configuration value for an observer.
 *
 * Stores a config value ($value) in the category ($family) under the key ($key)
 * for the observer's $xchan hash.
 *
 * @note Please do not store booleans - convert to 0/1 integer values!
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
function set_xconfig($xchan, $family, $key, $value) {
	global $a;

	// manage array value
	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_xconfig($xchan, $family, $key) === false) {
		if(! array_key_exists($xchan, App::$config))
			App::$config[$xchan] = array();
		if(! array_key_exists($family, App::$config[$xchan]))
			App::$config[$xchan][$family] = array();

		App::$config[$xchan][$family][$key] = $value;
		$ret = q("INSERT INTO xconfig ( xchan, cat, k, v ) VALUES ( '%s', '%s', '%s', '%s' ) ",
			dbesc($xchan),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret)
			return $value;
		return $ret;
	}

	$ret = q("UPDATE xconfig SET v = '%s' WHERE xchan = '%s' and cat = '%s' AND k = '%s'",
		dbesc($dbvalue),
		dbesc($xchan),
		dbesc($family),
		dbesc($key)
	);

	App::$config[$xchan][$family][$key] = $value;

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
function del_xconfig($xchan, $family, $key) {
	global $a;
	$ret = false;

	if(x(App::$config[$xchan][$family], $key))
		unset(App::$config[$xchan][$family][$key]);
	$ret = q("DELETE FROM xconfig WHERE xchan = '%s' AND cat = '%s' AND k = '%s'",
		dbesc($xchan),
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}


// account configuration storage is built on top of the under-utilised xconfig

function load_aconfig($account_id) {
	load_xconfig('a_' . $account_id);
}

function get_aconfig($account_id, $family, $key) {
	return get_xconfig('a_' . $account_id, $family, $key);
}

function set_aconfig($account_id, $family, $key, $value) {
	return set_xconfig('a_' . $account_id, $family, $key, $value);
}

function del_aconfig($account_id, $family, $key) {
	return del_xconfig('a_' . $account_id, $family, $key);
}


function load_abconfig($chash,$xhash) {
	$r = q("select * from abconfig where chan = '%s' and xchan = '%s'",
		dbesc($chash),
		dbesc($xhash)
	);
	return $r;
}

function get_abconfig($chash,$xhash,$family,$key) {
	$r = q("select * from abconfig where chan = '%s' and xchan = '%s' and cat = '%s' and k = '%s' limit 1",
		dbesc($chash),
		dbesc($xhash),
		dbesc($family),
		dbesc($key)		
	);
	if($r) {
		return ((preg_match('|^a:[0-9]+:{.*}$|s', $r[0]['v'])) ? unserialize($r[0]['v']) : $r[0]['v']);
	}
	return false;
}


function set_abconfig($chash,$xhash,$family,$key,$value) {

	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_abconfig($chash,$xhash,$family,$key) === false) {
		$r = q("insert into abconfig ( chan, xchan, cat, k, v ) values ( '%s', '%s', '%s', '%s', '%s' ) ",
			dbesc($chash),
			dbesc($xhash),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)		
		);
	}
	else {
		$r = q("update abconfig set v = '%s' where chan = '%s' and xchan = '%s' and cat = '%s' and k = '%s' ",
			dbesc($dbvalue),		
			dbesc($chash),
			dbesc($xhash),
			dbesc($family),
			dbesc($key)
		);
	}
	if($r)
		return $value;
	return false;
}


function del_abconfig($chash,$xhash,$family,$key) {

	$r = q("delete from abconfig where chan = '%s' and xchan = '%s' and cat = '%s' and k = '%s' ",
		dbesc($chash),
		dbesc($xhash),
		dbesc($family),
		dbesc($key)
	);

	return $r;
}
