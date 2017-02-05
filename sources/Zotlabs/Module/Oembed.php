<?php
namespace Zotlabs\Module;
require_once("include/oembed.php");


class Oembed extends \Zotlabs\Web\Controller {

	function init(){
		// logger('mod_oembed ' . \App::$query_string, LOGGER_ALL);
	
		if(argc() > 1) {
			if (argv(1) == 'b2h'){
				$url = array( "", trim(hex2bin($_REQUEST['url'])));
				echo oembed_replacecb($url);
				killme();
			}
		
			elseif (argv(1) == 'h2b'){
				$text = trim(hex2bin($_REQUEST['text']));
				echo oembed_html2bbcode($text);
				killme();
			}
		
			else {
				echo "<html><head><base target=\"_blank\" /></head><body>";
				$src = base64url_decode(argv(1));
				$j = oembed_fetch_url($src);
				echo $j['html'];
	//		    logger('mod-oembed ' . $h, LOGGER_ALL);
				echo "</body></html>";
			}
		}
		killme();
	}
	
}
