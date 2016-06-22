<?php

namespace Zotlabs\Zot;

interface IHandler {

	function Ping();

	function Pickup($data);

	function Notify($data);

	function Request($data);

	function AuthCheck($data,$encrypted);

	function Purge($sender,$recipients);

	function Refresh($sender,$recipients);

}

