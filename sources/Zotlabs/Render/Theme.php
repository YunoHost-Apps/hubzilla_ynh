<?php

namespace Zotlabs\Render;


class Theme {

	static $system_theme = null;
	static $system_mobile_theme = null;

	static $session_theme = null;
	static $session_mobile_theme = null;

	static $base_themes = array('redbasic');	

	static public function current(){

		self::$system_theme = ((isset(\App::$config['system']['theme'])) 
			? \App::$config['system']['theme'] : '');
		self::$session_theme = ((isset($_SESSION) && x($_SESSION,'theme')) 
			? $_SESSION['theme'] : self::$system_theme);
		self::$system_mobile_theme = ((isset(\App::$config['system']['mobile_theme'])) 
			? \App::$config['system']['mobile_theme'] : '');
		self::$session_mobile_theme = ((isset($_SESSION) && x($_SESSION,'mobile_theme')) 
			? $_SESSION['mobile_theme'] : self::$system_mobile_theme);

		$page_theme = null;

		// Find the theme that belongs to the channel whose stuff we are looking at

		if(\App::$profile_uid) {
			$r = q("select channel_theme from channel where channel_id = %d limit 1",
				intval(\App::$profile_uid)
			);
			if($r) {
				$page_theme = $r[0]['channel_theme'];
			}
		}

		// Themes from Comanche layouts over-ride the channel theme

		if(array_key_exists('theme', \App::$layout) && \App::$layout['theme'])
			$page_theme = \App::$layout['theme'];

		// If the viewer is on a mobile device, ensure that we're using a mobile
		// theme of some kind or whatever the viewer's preference is for mobile
		// viewing (if applicable)

		if(\App::$is_mobile || \App::$is_tablet) {
			if(isset($_SESSION['show_mobile']) && (! $_SESSION['show_mobile'])) {
				$chosen_theme = self::$session_theme;
			}
			else {
				$chosen_theme = self::$session_mobile_theme;

				if($chosen_theme === '' || $chosen_theme === '---' ) {
					// user has selected to have the mobile theme be the same as the normal one
					$chosen_theme = self::$session_theme;
				}
			}
		}
		else {
			$chosen_theme = self::$session_theme;

			if($page_theme) {
				$chosen_theme = $page_theme;
			}
		}
		if(array_key_exists('theme_preview',$_GET))
			$chosen_theme = $_GET['theme_preview'];

		// Allow theme selection of the form 'theme_name:schema_name'

		$themepair = explode(':', $chosen_theme);

		if($chosen_theme && (file_exists('view/theme/' . $themepair[0] . '/css/style.css') || file_exists('view/theme/' . $themepair[0] . '/php/style.php'))) {
			return($themepair);
		}

		foreach(self::$base_themes as $t) {
			if(file_exists('view/theme/' . $t . '/css/style.css') ||
				file_exists('view/theme/' . $t . '/php/style.php')) {
					return(array($t));
			}
		}

		// Worst case scenario, the default base theme or themes don't exist; perhaps somebody renamed it/them.
 
		// Find any theme at all and use it. 

		$fallback = array_merge(glob('view/theme/*/css/style.css'),glob('view/theme/*/php/style.php'));
		if(count($fallback))
			return(array(str_replace('view/theme/','', substr($fallback[0],0,-14))));


	}


	/**
	 * @brief Return full URL to theme which is currently in effect.
	 *
	 * Provide a sane default if nothing is chosen or the specified theme does not exist.
	 *
	 * @param bool $installing default false
	 *
	 * @return string
	 */

	function url($installing = false) {

		if($installing)
			return self::$base_themes[0];

		$theme = self::current();

		$t = $theme[0];
		$s = ((count($theme) > 1) ? $theme[1] : '');

		$opts = '';
		$opts = ((\App::$profile_uid) ? '?f=&puid=' . \App::$profile_uid : '');

		$schema_str = ((x(\App::$layout,'schema')) ? '&schema=' . App::$layout['schema'] : ''); 
		if(($s) && (! $schema_str))
			$schema_str = '&schema=' . $s;
		$opts .= $schema_str;

		if(file_exists('view/theme/' . $t . '/php/style.php'))
			return('view/theme/' . $t . '/php/style.pcss' . $opts);

		return('view/theme/' . $t . '/css/style.css');
	}

	function debug() {
		logger('system_theme: ' . self::$system_theme);
		logger('session_theme: ' . self::$session_theme);

	}

}

