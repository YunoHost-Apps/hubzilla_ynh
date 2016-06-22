<?php
namespace Zotlabs\Module;

// oembed provider




class Oep extends \Zotlabs\Web\Controller {

	function init() {
	
		logger('oep: ' . print_r($_REQUEST,true), LOGGER_DEBUG, LOG_INFO);
	
		$html = ((argc() > 1 && argv(1) === 'html') ? true : false);
		if($_REQUEST['url']) {
			$_REQUEST['url'] = strip_zids($_REQUEST['url']);
			$url = $_REQUEST['url'];
		}
	
		if(! $url)
			http_status_exit(404, 'Not found');
	
		$maxwidth  = $_REQUEST['maxwidth'];
		$maxheight = $_REQUEST['maxheight'];
		$format = $_REQUEST['format'];
		if($format && $format !== 'json')
			http_status_exit(501, 'Not implemented');
	
		if(fnmatch('*/photos/*/album/*',$url))
			$arr = $this->oep_album_reply($_REQUEST);
		elseif(fnmatch('*/photos/*/image/*',$url))
			$arr = $this->oep_photo_reply($_REQUEST);
		elseif(fnmatch('*/photos*',$url))
			$arr = $this->oep_phototop_reply($_REQUEST);
		elseif(fnmatch('*/display/*',$url))
			$arr = $this->oep_display_reply($_REQUEST);
		elseif(fnmatch('*/channel/*mid=*',$url))
			$arr = $this->oep_mid_reply($_REQUEST);
		elseif(fnmatch('*/channel*',$url))
			$arr = $this->oep_profile_reply($_REQUEST);
		elseif(fnmatch('*/profile/*',$url))
			$arr = $this->oep_profile_reply($_REQUEST);
	
		if($arr) {
			if($html) {
				if($arr['type'] === 'rich') {
					header('Content-Type: text/html');
					echo $arr['html'];
				}
			}
			else {
				header('Content-Type: application/json+oembed');
				echo json_encode($arr);
			}
			killme();
		}
	
		http_status_exit(404,'Not found');
	
	}
	
	function oep_display_reply($args) {
	
		$ret = array();
		$url = $args['url'];
		$maxwidth  = intval($args['maxwidth']);
		$maxheight = intval($args['maxheight']);
	
		if(preg_match('#//(.*?)/(.*?)/(.*?)/(.*?)mid\=(.*?)(&|$)#',$url,$matches)) {
			$chn = $matches[3];
			$res = $matches[5];
		}
	
		if(! ($chn && $res))
			return;
		$c = q("select * from channel where channel_address = '%s' limit 1",
			dbesc($chn)
		);
	
		if(! $c)
			return;
	
		$sql_extra = item_permissions_sql($c[0]['channel_id']);
	
		$p = q("select * from item where mid = '%s' and uid = %d $sql_extra limit 1",
	  		dbesc($res),
			intval($c[0]['channel_id'])
		);
		if(! $p)
			return;
		
		xchan_query($p,true);
		$p = fetch_post_tags($p,true);
	        
		$o = "[share author='".urlencode($p[0]['author']['xchan_name']).
	            "' profile='".$p[0]['author']['xchan_url'] .
	            "' avatar='".$p[0]['author']['xchan_photo_s'].
	            "' link='".$p[0]['plink'].
	            "' posted='".$p[0]['created'].
	            "' message_id='".$p[0]['mid']."']";
	    if($p[0]['title'])
	            $o .= '[b]'.$p[0]['title'].'[/b]'."\r\n";
	        $o .= $p[0]['body'];
	        $o .= "[/share]";
		$o = bbcode($o);
	
		$ret['type'] = 'rich';
	
		$w = (($maxwidth) ? $maxwidth : 640);
		$h = (($maxheight) ? $maxheight : $w * 2 / 3);
	
		$ret['html'] = '<div style="width: ' . $w . '; height: ' . $h . '; font-family: sans-serif,arial,freesans;" >' . $o . '</div>';
		
		$ret['width'] = $w;
		$ret['height'] = $h;
	
		return $ret;
	
	}
	
	function oep_mid_reply($args) {
	
		$ret = array();
		$url = $args['url'];
		$maxwidth  = intval($args['maxwidth']);
		$maxheight = intval($args['maxheight']);
	
		if(preg_match('#//(.*?)/(.*?)/(.*?)/(.*?)mid\=(.*?)(&|$)#',$url,$matches)) {
			$chn = $matches[3];
			$res = $matches[5];
		}
	
		if(! ($chn && $res))
			return;
		$c = q("select * from channel where channel_address = '%s' limit 1",
			dbesc($chn)
		);
	
		if(! $c)
			return;
	
		$sql_extra = item_permissions_sql($c[0]['channel_id']);
	
		$p = q("select * from item where mid = '%s' and uid = %d $sql_extra limit 1",
	  		dbesc($res),
			intval($c[0]['channel_id'])
		);
		if(! $p)
			return;
		
		xchan_query($p,true);
		$p = fetch_post_tags($p,true);
	        
		$o = "[share author='".urlencode($p[0]['author']['xchan_name']).
	            "' profile='".$p[0]['author']['xchan_url'] .
	            "' avatar='".$p[0]['author']['xchan_photo_s'].
	            "' link='".$p[0]['plink'].
	            "' posted='".$p[0]['created'].
	            "' message_id='".$p[0]['mid']."']";
	    if($p[0]['title'])
	            $o .= '[b]'.$p[0]['title'].'[/b]'."\r\n";
	        $o .= $p[0]['body'];
	        $o .= "[/share]";
		$o = bbcode($o);
	
		$ret['type'] = 'rich';
	
		$w = (($maxwidth) ? $maxwidth : 640);
		$h = (($maxheight) ? $maxheight : $w * 2 / 3);
	
		$ret['html'] = '<div style="width: ' . $w . '; height: ' . $h . '; font-family: sans-serif,arial,freesans;" >' . $o . '</div>';
		
		$ret['width'] = $w;
		$ret['height'] = $h;
	
		return $ret;
	
	}
	
	function oep_profile_reply($args) {
	
		
		require_once('include/channel.php');

		$url = $args['url'];
	
		if(preg_match('#//(.*?)/(.*?)/(.*?)(/|\?|&|$)#',$url,$matches)) {
			$chn = $matches[3];
		}
	
		if(! $chn)
			return;
	
		$c = channelx_by_nick($chn);
	
		if(! $c)
			return;
	
	
		$maxwidth  = intval($args['maxwidth']);
		$maxheight = intval($args['maxheight']);
	
		$width = 800;
		$height = 375;
	
		if($maxwidth) {
			$width = $maxwidth;
			$height = (375 / 800) * $width;
		}
		if($maxheight) {
			if($maxheight < $height) {
				$width = (800 / 375) * $maxheight;
				$height = $maxheight;
			}
		} 
		$ret = array();
	
		$ret['type'] = 'rich';
		$ret['width'] = intval($width);
		$ret['height'] = intval($height);
	
		$ret['html'] = get_zcard_embed($c,get_observer_hash(),array('width' => $width, 'height' => $height));
	
		return $ret;
	
	}
	
	function oep_album_reply($args) {
	
		$ret = array();
		$url = $args['url'];
		$maxwidth  = intval($args['maxwidth']);
		$maxheight = intval($args['maxheight']);
	
		if(preg_match('|//(.*?)/(.*?)/(.*?)/album/|',$url,$matches)) {
			$chn = $matches[3];
			$res = hex2bin(basename($url));
		}
	
		if(! ($chn && $res))
			return;
		$c = q("select * from channel where channel_address = '%s' limit 1",
			dbesc($chn)
		);
	
		if(! $c)
			return;
	
		$sql_extra = permissions_sql($c[0]['channel_id']);
	
		$p = q("select resource_id from photo where album = '%s' and uid = %d and imgscale = 0 $sql_extra order by created desc limit 1",
	  		dbesc($res),
			intval($c[0]['channel_id'])
		);
		if(! $p)
			return;
	
		$res = $p[0]['resource_id'];
	
		$r = q("select height, width, imgscale, resource_id from photo where uid = %d and resource_id = '%s' $sql_extra order by imgscale asc",
			intval($c[0]['channel_id']),
			dbesc($res)
		);
	
		if($r) {
			foreach($r as $rr) {
				$foundres = false;
				if($maxheight && $rr['height'] > $maxheight)
					continue;
				if($maxwidth && $rr['width'] > $maxwidth)
					continue;
				$foundres = true;			
				break;
			}
	
			if($foundres) {
				$ret['type'] = 'link';
				$ret['thumbnail_url'] = z_root() . '/photo/' . '/' . $rr['resource_id'] . '-' . $rr['imgscale'];
				$ret['thumbnail_width'] = $rr['width'];
				$ret['thumbnail_height'] = $rr['height'];
			}
				
	
		}
		return $ret;
	
	}
	
	
	function oep_phototop_reply($args) {
	
		$ret = array();
		$url = $args['url'];
		$maxwidth  = intval($args['maxwidth']);
		$maxheight = intval($args['maxheight']);
	
		if(preg_match('|//(.*?)/(.*?)/(.*?)$|',$url,$matches)) {
			$chn = $matches[3];
		}
	
		if(! $chn)
			return;
		$c = q("select * from channel where channel_address = '%s' limit 1",
			dbesc($chn)
		);
	
		if(! $c)
			return;
	
		$sql_extra = permissions_sql($c[0]['channel_id']);
	
		$p = q("select resource_id from photo where uid = %d and imgscale = 0 $sql_extra order by created desc limit 1",
			intval($c[0]['channel_id'])
		);
		if(! $p)
			return;
	
		$res = $p[0]['resource_id'];
	
		$r = q("select height, width, imgscale, resource_id from photo where uid = %d and resource_id = '%s' $sql_extra order by imgscale asc",
			intval($c[0]['channel_id']),
			dbesc($res)
		);
	
		if($r) {
			foreach($r as $rr) {
				$foundres = false;
				if($maxheight && $rr['height'] > $maxheight)
					continue;
				if($maxwidth && $rr['width'] > $maxwidth)
					continue;
				$foundres = true;			
				break;
			}
	
			if($foundres) {
				$ret['type'] = 'link';
				$ret['thumbnail_url'] = z_root() . '/photo/' . '/' . $rr['resource_id'] . '-' . $rr['imgscale'];
				$ret['thumbnail_width'] = $rr['width'];
				$ret['thumbnail_height'] = $rr['height'];
			}
				
	
		}
		return $ret;
	
	}
	
	
	function oep_photo_reply($args) {
	
		$ret = array();
		$url = $args['url'];
		$maxwidth  = intval($args['maxwidth']);
		$maxheight = intval($args['maxheight']);
	
		if(preg_match('|//(.*?)/(.*?)/(.*?)/image/|',$url,$matches)) {
			$chn = $matches[3];
			$res = basename($url);
		}
	
		if(! ($chn && $res))
			return;
		$c = q("select * from channel where channel_address = '%s' limit 1",
			dbesc($chn)
		);
	
		if(! $c)
			return;
	
		$sql_extra = permissions_sql($c[0]['channel_id']);
	
	
		$r = q("select height, width, imgscale, resource_id from photo where uid = %d and resource_id = '%s' $sql_extra order by imgscale asc",
			intval($c[0]['channel_id']),
			dbesc($res)
		);
	
		if($r) {
			foreach($r as $rr) {
				$foundres = false;
				if($maxheight && $rr['height'] > $maxheight)
					continue;
				if($maxwidth && $rr['width'] > $maxwidth)
					continue;
				$foundres = true;			
				break;
			}
	
			if($foundres) {
				$ret['type'] = 'link';
				$ret['thumbnail_url'] = z_root() . '/photo/' . '/' . $rr['resource_id'] . '-' . $rr['imgscale'];
				$ret['thumbnail_width'] = $rr['width'];
				$ret['thumbnail_height'] = $rr['height'];
			}
				
	
		}
		return $ret;
	
	}
}
