<?php
/**
 * @file include/config.php
 * @brief Arbitrary configuration storage.
 *
 * Arrays get stored as serialized strings.
 * Booleans are stored as integer 0/1.
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


use Zotlabs\Lib as Zlib;

function load_config($family) {
	Zlib\Config::Load($family);
}

function get_config($family, $key, $default = false) {
	return Zlib\Config::Get($family,$key,$default);
}

function set_config($family, $key, $value) {
	return Zlib\Config::Set($family,$key,$value);
}

function del_config($family, $key) {
	return Zlib\Config::Delete($family,$key);
}

function load_pconfig($uid) {
	Zlib\PConfig::Load($uid);
}

function get_pconfig($uid, $family, $key, $default = false) {
	return Zlib\PConfig::Get($uid,$family,$key,$default);
}

function set_pconfig($uid, $family, $key, $value) {
	return Zlib\PConfig::Set($uid,$family,$key,$value);
}

function del_pconfig($uid, $family, $key) {
	return Zlib\PConfig::Delete($uid,$family,$key);
}

function load_xconfig($xchan) {
	Zlib\XConfig::Load($xchan);
}

function get_xconfig($xchan, $family, $key, $default = false) {
	return Zlib\XConfig::Get($xchan,$family,$key, $default);
}

function set_xconfig($xchan, $family, $key, $value) {
	return Zlib\XConfig::Set($xchan,$family,$key,$value);
}

function del_xconfig($xchan, $family, $key) {
	return Zlib\XConfig::Delete($xchan,$family,$key);
}

function load_aconfig($account_id) {
	Zlib\AConfig::Load($account_id);
}

function get_aconfig($account_id, $family, $key, $default = false) {
	return Zlib\AConfig::Get($account_id, $family, $key, $default);
}

function set_aconfig($account_id, $family, $key, $value) {
	return Zlib\AConfig::Set($account_id, $family, $key, $value);
}

function del_aconfig($account_id, $family, $key) {
	return Zlib\AConfig::Delete($account_id, $family, $key);
}

function load_abconfig($chan, $xhash, $family = '') {
	return Zlib\AbConfig::Load($chan,$xhash,$family);
}

function get_abconfig($chan,$xhash,$family,$key, $default = false) {
	return Zlib\AbConfig::Get($chan,$xhash,$family,$key, $default);
}

function set_abconfig($chan,$xhash,$family,$key,$value) {
	return Zlib\AbConfig::Set($chan,$xhash,$family,$key,$value);
}

function del_abconfig($chan,$xhash,$family,$key) {
	return Zlib\AbConfig::Delete($chan,$xhash,$family,$key);
}

function load_iconfig(&$item) {
	Zlib\IConfig::Load($item);
}

function get_iconfig(&$item, $family, $key, $default = false) {
	return Zlib\IConfig::Get($item, $family, $key, $default);
}

function set_iconfig(&$item, $family, $key, $value, $sharing = false) {
	return Zlib\IConfig::Set($item, $family, $key, $value, $sharing);
}

function del_iconfig(&$item, $family, $key) {
	return Zlib\IConfig::Delete($item, $family, $key);
}
