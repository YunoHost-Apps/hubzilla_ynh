<?php /** @file */


function oembed_replacecb($matches){

	$embedurl=$matches[1];

	$result = oembed_action($embedurl);
	if($result['action'] === 'block') {
		return '<a href="' . $result['url'] . '">' . $result['url'] . '</a>';
	}

	$j = oembed_fetch_url($result['url']);
	$s = oembed_format_object($j);
	return $s;  
}


function oembed_action($embedurl) {

	$host = '';
	$action = 'filter';

	$embedurl = trim(str_replace('&amp;','&', $embedurl));

	logger('oembed_action: ' . $embedurl, LOGGER_DEBUG, LOG_INFO);

	// These media files should now be caught in bbcode.php
	// left here as a fallback in case this is called from another source

	$noexts = array("mp3","mp4","ogg","ogv","oga","ogm","webm","opus");
	$ext = pathinfo(strtolower($embedurl),PATHINFO_EXTENSION);

	if(strpos($embedurl,'http://') === 0) {
		if(intval(get_config('system','embed_sslonly'))) {
			$action = 'block';
		}
	}

	// site white/black list

	if(($x = get_config('system','embed_deny'))) {
		if(($x) && (! is_array($x)))
			$x = explode("\n",$x);
		if($x) {
			foreach($x as $ll) {
				$t = trim($ll);
				if(($t) && (strpos($embedurl,$t) !== false)) {
					$action = 'block';
					break;
				}
			}
		}
	}
	
	$found = false;

	if(($x = get_config('system','embed_allow'))) {
		if(($x) && (! is_array($x)))
			$x = explode("\n",$x);
		if($x) {
			foreach($x as $ll) {
				$t = trim($ll);
				if(($t) && (strpos($embedurl,$t) !== false) && ($action !== 'block')) {
					$found = true;
					$action = 'allow';
					break;
				}
			}
		}
		if((! $found) && ($action !== 'block')) {
			$action = 'filter';
		}
	}

	// allow individual members to block something that wasn't blocked already.
	// They cannot over-ride the site to allow or change the filtering on an 
	// embed that is not allowed by the site admin.

	if(local_channel()) {
		if(($x = get_pconfig(local_channel(),'system','embed_deny'))) {
			if(($x) && (! is_array($x)))
				$x = explode("\n",$x);
			if($x) {
				foreach($x as $ll) {
					$t = trim($ll);
					if(($t) && (strpos($embedurl,$t) !== false)) {
						$action = 'block';
						break;
					}
				}
			}
		}
	}

	$arr = array('url' => $embedurl, 'action' => $action);
	call_hooks('oembed_action',$arr);

	logger('action: ' . $arr['action'] . ' url: ' . $arr['url'], LOGGER_DEBUG,LOG_DEBUG); 

	return $arr;

}

// if the url is embeddable with oembed, return the bbcode link.

function oembed_process($url) {
	$j = oembed_fetch_url($url);
	logger('oembed_process: ' . print_r($j,true));
	if($j && $j->type !== 'error')
		return '[embed]' . $url . '[/embed]';
	return false;
}



function oembed_fetch_url($embedurl){

	// These media files should now be caught in bbcode.php
	// left here as a fallback in case this is called from another source

	$noexts = array("mp3","mp4","ogg","ogv","oga","ogm","webm","opus");
	$ext = pathinfo(strtolower($embedurl),PATHINFO_EXTENSION);

	$result = oembed_action($embedurl); 

	$embedurl = $result['url'];
	$action = $result['action'];

	$txt = null;

	if($action !== 'block') {
		$txt = Cache::get(App::$videowidth . $embedurl);

		if(strstr($txt,'youtu') && strstr(z_root(),'https:')) {
			$txt = str_replace('http:','https:',$txt);
		}
	}
		
	if(is_null($txt)) {

		$txt = "";
		$furl = $embedurl;
		$zrl = false;

		if(local_channel()) {
			require_once('include/hubloc.php');
			$zrl = is_matrix_url($furl);
			if($zrl) 
				$furl = zid($furl);	
		}


		if (! in_array($ext, $noexts) && $action !== 'block') {
			// try oembed autodiscovery
			$redirects = 0;
			$result = z_fetch_url($furl, false, $redirects, array('timeout' => 15, 'accept_content' => "text/*", 'novalidate' => true ));
			if($result['success'])
				$html_text = $result['body'];

			if($html_text) {
				$dom = @DOMDocument::loadHTML($html_text);
				if ($dom){
					$xpath = new DOMXPath($dom);
					$attr = "oembed";
					$xattr = oe_build_xpath("class","oembed");

					$entries = $xpath->query("//link[@type='application/json+oembed']");
					foreach($entries as $e){
						$href = $e->getAttributeNode("href")->nodeValue;
						$x = z_fetch_url($href . '&maxwidth=' . App::$videowidth);
						$txt = $x['body'];
						break;
					}
					// soundcloud is now using text/json+oembed instead of application/json+oembed, 
					// others may be also
					$entries = $xpath->query("//link[@type='text/json+oembed']");
					foreach($entries as $e){
						$href = $e->getAttributeNode("href")->nodeValue;
						$x = z_fetch_url($href . '&maxwidth=' . App::$videowidth);
						$txt = $x['body'];
						break;
					}
				}
			}
		}
		
		if ($txt==false || $txt=="") {
			$x = array('url' => $embedurl,'videowidth' => App::$videowidth);
			call_hooks('oembed_probe',$x);
			if(array_key_exists('embed',$x))
				$txt = $x['embed'];
		}
		
		$txt=trim($txt);

		if ($txt[0]!="{") $txt='{"type":"error"}';
	
		//save in cache

		if(! get_config('system','oembed_cache_disable'))
			Cache::set(App::$videowidth . $embedurl,$txt);

	}


	$j = json_decode($txt);

	if($action === 'filter') {
		if($j->html) {
			$orig = $j->html;
			$allow_position = (($zrl) ? true : false);
			$j->html = purify_html($j->html,$allow_position);
			if($j->html != $orig) {
				logger('oembed html was purified. original: ' . $orig . ' purified: ' . $j->html, LOGGER_DEBUG, LOG_INFO); 
			}
		}
	}

	$j->embedurl = $embedurl;

// logger('fetch return: ' . print_r($j,true));

	return $j;


}
	
function oembed_format_object($j){

    $embedurl = $j->embedurl;

// logger('format: ' . print_r($j,true));

	$jhtml = oembed_iframe($j->embedurl,(isset($j->width) ? $j->width : null), (isset($j->height) ? $j->height : null));

	$ret="<span class='oembed ".$j->type."'>";
	switch ($j->type) {
		case "video": {
			if (isset($j->thumbnail_url)) {
				$tw = (isset($j->thumbnail_width)) ? $j->thumbnail_width:200;
				$th = (isset($j->thumbnail_height)) ? $j->thumbnail_height:180;
				$tr = $tw/$th;
				
				$th=120; $tw = $th*$tr;
				$tpl=get_markup_template('oembed_video.tpl');
				if(strstr($embedurl,'youtu') && strstr(z_root(),'https:')) {
					$embedurl = str_replace('http:','https:',$embedurl);
					$j->thumbnail_url = str_replace('http:','https:', $j->thumbnail_url);
					$jhtml = str_replace('http:','https:', $jhtml);
					$j->html = str_replace('http:','https:', $j->html);
				
				}
				$ret.=replace_macros($tpl, array(
                    '$baseurl' => z_root(),
					'$embedurl'=>$embedurl,
					'$escapedhtml'=>base64_encode($jhtml),
					'$tw'=>$tw,
					'$th'=>$th,
					'$turl'=>$j->thumbnail_url,
				));
				
			} else {
				$ret=$jhtml;
			}
			$ret.="<br>";
		}; break;
		case "photo": {
			$ret.= "<img width='".$j->width."' src='".$j->url."'>";
			$ret.="<br>";
		}; break;  
		case "link": {
			if($j->thumbnail_url) {
				if(is_matrix_url($embedurl)) {
					$embedurl = zid($embedurl);
					$j->thumbnail_url = zid($j->thumbnail_url);
				}
				$ret = '<a href="' . $embedurl . '" ><img src="' . $j->thumbnail_url . '" alt="thumbnail" /></a><br /><br />';
			}

			//$ret = "<a href='".$embedurl."'>".$j->title."</a>";
		}; break;  
		case "rich": {
			// not so safe.. 
			$ret.= $jhtml;
		}; break;
	}

	// add link to source if not present in "rich" type
	if (  $j->type!='rich' || !strpos($j->html,$embedurl) ){
		$embedlink = (isset($j->title))?$j->title:$embedurl;
		$ret .= '<br />' . "<a href='$embedurl' rel='oembed'>$embedlink</a>";
		$ret .= "<br />";
		if (isset($j->author_name)) $ret.=" by ".$j->author_name;
		if (isset($j->provider_name)) $ret.=" on ".$j->provider_name;
	} else {
		// add <a> for html2bbcode conversion
		$ret .= "<br /><a href='$embedurl' rel='oembed'>$embedurl</a>";
	}
	$ret.="<br style='clear:left'></span>";
	return  mb_convert_encoding($ret, 'HTML-ENTITIES', mb_detect_encoding($ret));
}

function oembed_iframe($src,$width,$height) {
	$scroll = ' scrolling="no" ';
	if(! $width || strstr($width,'%')) {
		$width = '640';
		$scroll = ' scrolling="auto" ';
	}
	if(! $height || strstr($height,'%')) {
		$height = '300';
		$scroll = ' scrolling="auto" ';
	}

	// try and leave some room for the description line. 
	$height = intval($height) + 80;
	$width  = intval($width) + 40;

	$s = z_root() . '/oembed/' . base64url_encode($src);

	// Make sure any children are sandboxed within their own iframe.

	return '<iframe ' . $scroll . 'height="' . $height . '" width="' . $width . '" src="' . $s . '" frameborder="no" >' 
		. t('Embedded content') . '</iframe>'; 

}



function oembed_bbcode2html($text){
	$stopoembed = get_config("system","no_oembed");
	if ($stopoembed == true){
		return preg_replace("/\[embed\](.+?)\[\/embed\]/is", "<!-- oembed $1 --><i>". t('Embedding disabled') ." : $1</i><!-- /oembed $1 -->" ,$text);
	}
	return preg_replace_callback("/\[embed\](.+?)\[\/embed\]/is", 'oembed_replacecb' ,$text);
}


function oe_build_xpath($attr, $value){
	// http://westhoffswelt.de/blog/0036_xpath_to_select_html_by_class.html
	return "contains( normalize-space( @$attr ), ' $value ' ) or substring( normalize-space( @$attr ), 1, string-length( '$value' ) + 1 ) = '$value ' or substring( normalize-space( @$attr ), string-length( @$attr ) - string-length( '$value' ) ) = ' $value' or @$attr = '$value'";
}

function oe_get_inner_html( $node ) {
    $innerHTML= '';
    $children = $node->childNodes;
    foreach ($children as $child) {
        $innerHTML .= $child->ownerDocument->saveXML( $child );
    }
    return $innerHTML;
} 

/**
 * Find <span class='oembed'>..<a href='url' rel='oembed'>..</a></span>
 * and replace it with [embed]url[/embed]
 */
function oembed_html2bbcode($text) {
	// start parser only if 'oembed' is in text
	if (strpos($text, "oembed")){
		
		// convert non ascii chars to html entities
		$html_text = mb_convert_encoding($text, 'HTML-ENTITIES', mb_detect_encoding($text));
		
		// If it doesn't parse at all, just return the text.
		$dom = @DOMDocument::loadHTML($html_text);
		if(! $dom)
			return $text;
		$xpath = new DOMXPath($dom);
		$attr = "oembed";
		
		$xattr = oe_build_xpath("class","oembed");
		$entries = $xpath->query("//span[$xattr]");

		$xattr = "@rel='oembed'";//oe_build_xpath("rel","oembed");
		foreach($entries as $e) {
			$href = $xpath->evaluate("a[$xattr]/@href", $e)->item(0)->nodeValue;
			if(!is_null($href)) $e->parentNode->replaceChild(new DOMText("[embed]".$href."[/embed]"), $e);
		}
		return oe_get_inner_html( $dom->getElementsByTagName("body")->item(0) );
	} else {
		return $text;
	} 
}



