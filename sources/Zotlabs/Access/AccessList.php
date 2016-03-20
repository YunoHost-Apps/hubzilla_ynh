<?php

namespace Zotlabs\Access;


class AccessList {

	private $allow_cid;
	private $allow_gid;
	private $deny_cid;
	private $deny_gid;

	/* indicates if we are using the default constructor values or values that have been set explicitly. */

	private $explicit; 

	function __construct($channel) {

		if($channel) {		
			$this->allow_cid = $channel['channel_allow_cid'];
			$this->allow_gid = $channel['channel_allow_gid'];
			$this->deny_cid  = $channel['channel_deny_cid'];
			$this->deny_gid  = $channel['channel_deny_gid'];
		}
		else {
			$this->allow_cid = '';
			$this->allow_gid = '';
			$this->deny_cid  = '';
			$this->deny_gid  = '';
		}

		$this->explicit = false;
	}

	function get_explicit() {
		return $this->explicit;
	}

	/**
	 * Set AccessList from strings such as those in already
	 * existing stored data items
	 */

	function set($arr,$explicit = true) {
		$this->allow_cid = $arr['allow_cid'];
		$this->allow_gid = $arr['allow_gid'];
		$this->deny_cid  = $arr['deny_cid'];
		$this->deny_gid  = $arr['deny_gid'];

		$this->explicit = $explicit;
	}

	/**
	 * return an array consisting of the current
	 * access list components where the elements
	 * are directly storable. 
	 */

	function get() {
		return array(
			'allow_cid' => $this->allow_cid,
			'allow_gid' => $this->allow_gid,
			'deny_cid'  => $this->deny_cid,
			'deny_gid'  => $this->deny_gid,
		);
	}

	/**
	 * Set AccessList from arrays, such as those provided by
	 * acl_selector(). For convenience, a string (or non-array) input is 
	 * assumed to be a comma-separated list and auto-converted into an array. 
	 */ 

	function set_from_array($arr,$explicit = true) {
		$this->allow_cid = perms2str((is_array($arr['contact_allow'])) 
			? $arr['contact_allow'] : explode(',',$arr['contact_allow']));
		$this->allow_gid = perms2str((is_array($arr['group_allow']))
			? $arr['group_allow'] : explode(',',$arr['group_allow']));
		$this->deny_cid  = perms2str((is_array($arr['contact_deny']))
			? $arr['contact_deny'] : explode(',',$arr['contact_deny']));
		$this->deny_gid  = perms2str((is_array($arr['group_deny']))
			? $arr['group_deny'] : explode(',',$arr['group_deny']));

		$this->explicit = $explicit;
	}

	function is_private() {
		return (($this->allow_cid || $this->allow_gid || $this->deny_cid || $this->deny_gid) ? true : false);
	}

}

