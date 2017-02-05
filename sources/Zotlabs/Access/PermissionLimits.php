<?php

namespace Zotlabs\Access;

use \Zotlabs\Lib as ZLib;

class PermissionLimits {

	static public function Std_Limits() {
		$perms = Permissions::Perms();
		$limits = array();
		foreach($perms as $k => $v) {
			if(strstr($k,'view'))
				$limits[$k] = PERMS_PUBLIC;
			else
				$limits[$k] = PERMS_SPECIFIC;
		}
		return $limits;
	}

	static public function Set($channel_id,$perm,$perm_limit) {
		ZLib\PConfig::Set($channel_id,'perm_limits',$perm,$perm_limit);
	}

	static public function Get($channel_id,$perm = '') {
		if($perm) {
			return Zlib\PConfig::Get($channel_id,'perm_limits',$perm);
		}
		else {
			Zlib\PConfig::Load($channel_id);
			if(array_key_exists($channel_id,\App::$config) && array_key_exists('perm_limits',\App::$config[$channel_id]))
				return \App::$config[$channel_id]['perm_limits'];
			return false;
		}
	}	
}