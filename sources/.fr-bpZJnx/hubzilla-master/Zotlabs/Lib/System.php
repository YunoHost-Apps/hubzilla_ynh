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
		return z_root() . '/images/hz-white-32.png';
	}

	static public function get_site_icon() {
		if(is_array(\App::$config) && is_array(\App::$config['system']) && \App::$config['system']['site_icon_url'])
			return \App::$config['system']['site_icon_url'];
		return z_root() . '/images/hz-32.png';
	}


	static public function get_server_role() {
		if(UNO)
			return 'basic';
		return 'pro';
	}

	static public function get_std_version() {
		if(defined('STD_VERSION'))
			return STD_VERSION;
		return '0.0.0';
	}


}
