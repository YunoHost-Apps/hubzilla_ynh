<?php

namespace Zotlabs\Web;


class CheckJS {

	private static $jsdisabled = 0;

	function __construct($test = 0) {
		if(intval($_REQUEST['jsdisabled']))
			$this->jsdisabled = 1;
		if(intval($_COOKIE['jsdisabled']))
			$this->jsdisabled = 1;

		if(! $this->jsdisabled) {
			$page = urlencode(\App::$query_string);

			if($test) {
				\App::$page['htmlhead'] .= "\r\n" . '<meta http-equiv="refresh" content="0; url=' . z_root() . '/nojs?f=&redir=' . $page . '">' . "\r\n";
			}
			else {
				\App::$page['htmlhead'] .= "\r\n" . '<noscript><meta http-equiv="refresh" content="0; url=' . z_root() . '/nojs?f=&redir=' . $page . '"></noscript>' . "\r\n";
			}
		}

	}

	function disabled() {
		return self::$jsdisabled;
	}


}


