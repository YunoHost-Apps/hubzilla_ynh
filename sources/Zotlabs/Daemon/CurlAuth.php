<?php

namespace Zotlabs\Daemon;

// generate a curl compatible cookie file with an authenticated session for the given channel_id. 
// If this file is then used with curl and the destination url is sent through zid() or manually 
// manipulated to add a zid, it should allow curl to provide zot magic-auth across domains.

// Handles expiration of stale cookies currently by deleting them and rewriting the file.  

class CurlAuth {

	static public function run($argc,$argv) {

		if($argc != 2)
			killme();

		\App::$session->start();

		$_SESSION['authenticated'] = 1;
		$_SESSION['uid'] = $argv[1];

		$x = session_id();

		$f = 'store/[data]/cookie_' . $argv[1];
		$c = 'store/[data]/cookien_' . $argv[1];

		$e = file_exists($f);

		$output = '';

		if($e) {
			$lines = file($f);
			if($lines) {
				foreach($lines as $line) {
					if(strlen($line) > 0 && $line[0] != '#' && substr_count($line, "\t") == 6) {
						$tokens = explode("\t", $line);
						$tokens = array_map('trim', $tokens);
						if($tokens[4] > time()) {
							$output .= $line . "\n";
						}
					}
					else
						$output .= $line;
				}
			}
		}
		$t = time() + (24 * 3600);
		file_put_contents($f, $output . 'HttpOnly_' . \App::get_hostname() . "\tFALSE\t/\tTRUE\t$t\tPHPSESSID\t" . $x, (($e) ? FILE_APPEND : 0));

		file_put_contents($c,$x);

		killme();
	}
}