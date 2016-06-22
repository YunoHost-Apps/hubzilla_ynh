<?php

namespace Zotlabs\Lib;


class AbConfig {

	static public function Load($chash,$xhash) {
		$r = q("select * from abconfig where chan = '%s' and xchan = '%s'",
			dbesc($chash),
			dbesc($xhash)
		);
		return $r;
	}


	static public function Get($chash,$xhash,$family,$key) {
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


	static public function Set($chash,$xhash,$family,$key,$value) {

		$dbvalue = ((is_array($value))  ? serialize($value) : $value);
		$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

		if(self::Get($chash,$xhash,$family,$key) === false) {
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


	static public function Delete($chash,$xhash,$family,$key) {

		$r = q("delete from abconfig where chan = '%s' and xchan = '%s' and cat = '%s' and k = '%s' ",
			dbesc($chash),
			dbesc($xhash),
			dbesc($family),
			dbesc($key)
		);

		return $r;
	}

}