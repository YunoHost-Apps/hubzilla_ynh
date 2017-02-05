<?php

namespace Zotlabs\Lib;

class System {

	static public function get_platform_name() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && array_key_exists('platform_name',\App::$config['system']))
			return \App::$config['system']['platform_name'];
		return PLATFORM_NAME;
	}

	static public function get_site_name() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['sitename'])
			return \App::$config['system']['sitename'];
		return '';
	}

	static public function get_project_version() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['hide_version'])
			return '';
		return self::get_std_version();
	}

	static public function get_update_version() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['hide_version'])
			return '';
		return DB_UPDATE_VERSION;
	}


	static public function get_notify_icon() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['email_notify_icon_url'])
			return \App::$config['system']['email_notify_icon_url'];
		return z_root() . DEFAULT_NOTIFY_ICON;
	}

	static public function get_site_icon() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['site_icon_url'])
			return \App::$config['system']['site_icon_url'];
		return z_root() . DEFAULT_PLATFORM_ICON ;
	}


	static public function get_project_link() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['project_link'])
			return \App::$config['system']['project_link'];
		return 'https://hubzilla.org';
	}

	static public function get_project_srclink() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['project_srclink'])
			return \App::$config['system']['project_srclink'];
		return 'https://github.com/redmatrix/hubzilla';
	}



	static public function get_server_role() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['server_role'])
			return \App::$config['system']['server_role'];
		return 'standard';
	}

	static public function get_std_version() {
		if(defined('STD_VERSION'))
			return STD_VERSION;
		return '0.0.0';
	}

	static public function compatible_project($p) {

		if(get_directory_realm() != DIRECTORY_REALM)
			return true;

		foreach(['hubzilla','zap'] as $t) {
			if(stristr($p,$t))
				return true;
		}
		return false;
	}
}
