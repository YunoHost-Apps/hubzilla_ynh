<?php

namespace Zotlabs\Lib;


class AbConfig {

	static public function Load($chan,$xhash,$family = '') {
		if($family)
			$where = sprintf(" and cat = '%s' ",dbesc($family));
		$r = q("select * from abconfig where chan = %d and xchan = '%s' $where",
			intval($chan),
			dbesc($xhash)
		);
		return $r;
	}


	static public function Get($chan,$xhash,$family,$key, $default = false) {
		$r = q("select * from abconfig where chan = %d and xchan = '%s' and cat = '%s' and k = '%s' limit 1",
			intval($chan),
			dbesc($xhash),
			dbesc($family),
			dbesc($key)		
		);
		if($r) {
			return ((preg_match('|^a:[0-9]+:{.*}$|s', $r[0]['v'])) ? unserialize($r[0]['v']) : $r[0]['v']);
		}
		return $default;
	}


	static public function Set($chan,$xhash,$family,$key,$value) {

		$dbvalue = ((is_array($value))  ? serialize($value) : $value);
		$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

		if(self::Get($chan,$xhash,$family,$key) === false) {
			$r = q("insert into abconfig ( chan, xchan, cat, k, v ) values ( %d, '%s', '%s', '%s', '%s' ) ",
				intval($chan),
				dbesc($xhash),
				dbesc($family),
				dbesc($key),
				dbesc($dbvalue)		
			);
		}
		else {
			$r = q("update abconfig set v = '%s' where chan = %d and xchan = '%s' and cat = '%s' and k = '%s' ",
				dbesc($dbvalue),		
				dbesc($chan),
				dbesc($xhash),
				dbesc($family),
				dbesc($key)
			);
		}
	
		if($r)
			return $value;
		return false;
	}


	static public function Delete($chan,$xhash,$family,$key) {

		$r = q("delete from abconfig where chan = %d and xchan = '%s' and cat = '%s' and k = '%s' ",
			intval($chan),
			dbesc($xhash),
			dbesc($family),
			dbesc($key)
		);

		return $r;
	}

}
