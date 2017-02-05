<?php

namespace Zotlabs\Lib;



class IConfig {

	static public function Load(&$item) {
		return;
	}

	static public function Get(&$item, $family, $key, $default = false) {

		$is_item = false;
	
		if(is_array($item)) {
			$is_item = true;
			if((! array_key_exists('iconfig',$item)) || (! is_array($item['iconfig'])))
				$item['iconfig'] = array();

			if(array_key_exists('item_id',$item))
				$iid = $item['item_id'];
			else
				$iid = $item['id'];
		}
		elseif(intval($item))
			$iid = $item;

		if(! $iid)
			return $default;

		if(is_array($item) && array_key_exists('iconfig',$item) && is_array($item['iconfig'])) {
			foreach($item['iconfig'] as $c) {
				if($c['iid'] == $iid && $c['cat'] == $family && $c['k'] == $key)
					return $c['v'];
			}
		}
		 
		$r = q("select * from iconfig where iid = %d and cat = '%s' and k = '%s' limit 1",
			intval($iid),
			dbesc($family),
			dbesc($key)
		);
		if($r) {
			$r[0]['v'] = ((preg_match('|^a:[0-9]+:{.*}$|s',$r[0]['v'])) ? unserialize($r[0]['v']) : $r[0]['v']);
			if($is_item)
				$item['iconfig'][] = $r[0];
			return $r[0]['v'];
		}
		return $default;

	}

	/**
	 * IConfig::Set(&$item, $family, $key, $value, $sharing = false);
	 *
	 * $item - item array or item id. If passed an array the iconfig meta information is
	 *    added to the item structure (which will need to be saved with item_store eventually).
	 *    If passed an id, the DB is updated, but may not be federated and/or cloned.
	 * $family - namespace of meta variable
	 * $key - key of meta variable
	 * $value - value of meta variable
	 * $sharing - boolean (default false); if true the meta information is propagated with the item
	 *   to other sites/channels, mostly useful when $item is an array and has not yet been stored/delivered.
	 *   If the meta information is added after delivery and you wish it to be shared, it may be necessary to 
	 *   alter the item edited timestamp and invoke the delivery process on the updated item. The edited 
	 *   timestamp needs to be altered in order to trigger an item_store_update() at the receiving end.
	 */
 

	static public function Set(&$item, $family, $key, $value, $sharing = false) {

		$dbvalue = ((is_array($value))  ? serialize($value) : $value);
		$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

		$is_item = false;
		$idx = null;

		if(is_array($item)) {
			$is_item = true;
			if((! array_key_exists('iconfig',$item)) || (! is_array($item['iconfig'])))
				$item['iconfig'] = array();
			elseif($item['iconfig']) {
				for($x = 0; $x < count($item['iconfig']); $x ++) {
					if($item['iconfig'][$x]['cat'] == $family && $item['iconfig'][$x]['k'] == $key) {
						$idx = $x;
					}
				}
			}
			$entry = array('cat' => $family, 'k' => $key, 'v' => $value, 'sharing' => $sharing);

			if(is_null($idx))
				$item['iconfig'][] = $entry;
			else
				$item['iconfig'][$idx] = $entry;
			return $value;
		}

		if(intval($item))
			$iid = intval($item);

		if(! $iid)
			return false;

		if(self::Get($item, $family, $key) === false) {
			$r = q("insert into iconfig( iid, cat, k, v, sharing ) values ( %d, '%s', '%s', '%s', %d ) ",
				intval($iid),
				dbesc($family),
				dbesc($key),
				dbesc($dbvalue),
				intval($sharing)
			);
		}
		else {
			$r = q("update iconfig set v = '%s', sharing = %d where iid = %d and cat = '%s' and  k = '%s' ",
				dbesc($dbvalue),
				intval($sharing),
				intval($iid),
				dbesc($family),
				dbesc($key)
			);
		}

		if(! $r)
			return false;

		return $value;
	}



	static public function Delete(&$item, $family, $key) {


		$is_item = false;
		$idx = null;

		if(is_array($item)) {
			$is_item = true;
			if(is_array($item['iconfig'])) {
				for($x = 0; $x < count($item['iconfig']); $x ++) {
					if($item['iconfig'][$x]['cat'] == $family && $item['iconfig'][$x]['k'] == $key) {
						unset($item['iconfig'][$x]);
					}
				}
			}
			return true;
		}

		if(intval($item))
			$iid = intval($item);

		if(! $iid)
			return false;

		return q("delete from iconfig where iid = %d and cat = '%s' and  k = '%s' ",
			intval($iid),
			dbesc($family),
			dbesc($key)
		);

	}

}