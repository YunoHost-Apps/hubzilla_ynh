<?php

namespace Zotlabs\Lib;

// account configuration storage is built on top of the under-utilised xconfig

class AConfig {

	static public function Load($account_id) {
		return XConfig::Load('a_' . $account_id);
	}

	static public function Get($account_id,$family,$key,$default = false) {
		return XConfig::Get('a_' . $account_id,$family,$key, $default);
	}

	static public function Set($account_id,$family,$key,$value) {
		return XConfig::Set('a_' . $account_id,$family,$key,$value);
	}

	static public function Delete($account_id,$family,$key) {
		return XConfig::Delete('a_' . $account_id,$family,$key);
	}

}
