<?php

namespace Zotlabs\Web;


class CheckJS {

	private static $jsdisabled = 0;

	function __construct($test = 0) {
		if(intval($_REQUEST['jsdisabled']))
			$this->jsdisabled = 1;
		else
			$this->jsdisabled = 0;
		if(intval($_COOKIE['jsdisabled']))
			$this->jsdisabled = 1;
		else
			$this->jsdisabled = 0;

		if(! $this->jsdisabled) {
			$page = urlencode(\App::$query_string);

			if($test) {
				self::$jsdisabled = 1;
				if(array_key_exists('jsdisabled',$_COOKIE))
					self::$jsdisabled = $_COOKIE['jsdisabled'];

    			if(! array_key_exists('jsdisabled',$_COOKIE)) {
			        \App::$page['htmlhead'] .= "\r\n" . '<script>document.cookie="jsdisabled=0; path=/"; var jsMatch = /\&jsdisabled=0/; if (!jsMatch.exec(location.href)) { location.href = "' . z_root() . '/nojs/0?f=&redir=' . $page . '" ; }</script>' . "\r\n";
			        /* emulate JS cookie if cookies are not accepted */
			        if (array_key_exists('jsdisabled',$_GET)) {
            			$_COOKIE['jsdisabled'] = $_GET['jsdisabled'];
        			}
				}
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


