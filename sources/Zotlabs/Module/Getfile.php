<?php
namespace Zotlabs\Module;

/**
 * module: getfile
 * 
 * used for synchronising files and photos across clones
 * 
 * The site initiating the file operation will send a sync packet to known clones.
 * They will respond by building the DB structures they require, then will provide a
 * post request to this site to grab the file data. This is sent as a stream direct to
 * disk at the other end, avoiding memory issues.
 *
 * Since magic-auth cannot easily be used by the CURL process at the other end,
 * we will require a signed request which includes a timestamp. This should not be 
 * used without SSL and is potentially vulnerable to replay if an attacker decrypts 
 * the SSL traffic fast enough. The amount of time slop is configurable but defaults
 * to 3 minutes.
 * 
 */



require_once('include/attach.php');


class Getfile extends \Zotlabs\Web\Controller {

	function post() {

		logger('post: ' . print_r($_POST,true),LOGGER_DEBUG,LOG_INFO);
	
		$hash     = $_POST['hash'];
		$time     = $_POST['time'];
		$sig      = $_POST['signature'];
		$resource = $_POST['resource'];
		$revision = intval($_POST['revision']);
	
		if(! $hash)
			killme();
	
		$channel = channelx_by_hash($hash);

		if((! $channel) || (! $time) || (! $sig)) {
			logger('error: missing info');
			killme();
		}
	
		$slop = intval(get_pconfig($channel['channel_id'],'system','getfile_time_slop'));
		if($slop < 1)
			$slop = 3;
	
		$d1 = datetime_convert('UTC','UTC',"now + $slop minutes");
		$d2 = datetime_convert('UTC','UTC',"now - $slop minutes");	
	
		if(($time > $d1) || ($time < $d2)) {
			logger('time outside allowable range');
			killme();
		}
	
		if(! rsa_verify($hash . '.' . $time,base64url_decode($sig),$channel['channel_pubkey'])) {
			logger('verify failed.');
			killme();
		}
		
		$r = attach_by_hash($resource,$channel['channel_hash'],$revision);
	
		if(! $r['success']) {
			logger('attach_by_hash failed: ' . $r['message']);
			notice( $r['message'] . EOL);
			return;
		}
			
		$unsafe_types = array('text/html','text/css','application/javascript');
	
		if(in_array($r['data']['filetype'],$unsafe_types)) {
				header('Content-type: text/plain');
		}
		else {
			header('Content-type: ' . $r['data']['filetype']);
		}

		header('Content-disposition: attachment; filename="' . $r['data']['filename'] . '"');
		if(intval($r['data']['os_storage'])) {
			$fname = dbunescbin($r['data']['content']);
			if(strpos($fname,'store') !== false)
				$istream = fopen($fname,'rb');
			else
				$istream = fopen('store/' . $channel['channel_address'] . '/' . $fname,'rb');
			$ostream = fopen('php://output','wb');
			if($istream && $ostream) {
				pipe_streams($istream,$ostream);
				fclose($istream);
				fclose($ostream);
			}
		}
		else {
			echo dbunescbin($r['data']['content']);
		}
		killme();
	}
}
