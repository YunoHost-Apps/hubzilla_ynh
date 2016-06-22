<?php

namespace Zotlabs\Zot;

class ZotHandler implements IHandler {

	function Ping() {
		zot_reply_ping();
	}

	function Pickup($data) {
		zot_reply_pickup($data);
	}

	function Notify($data) {
		zot_reply_notify($data);
	}

	function Request($data) {
		zot_reply_message_request($data);
	}

	function AuthCheck($data,$encrypted) {
		zot_reply_auth_check($data,$encrypted);
	}

	function Purge($sender,$recipients) {
		zot_reply_purge($sender,$recipients);
	}

	function Refresh($sender,$recipients) {
		zot_reply_refresh($sender,$recipients);
	}

}
