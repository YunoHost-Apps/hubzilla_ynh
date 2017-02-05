<?php
namespace Zotlabs\Module;

require_once('include/zot.php');
require_once('include/crypto.php');

/* fix missing or damaged hublocs */


class Fhublocs extends \Zotlabs\Web\Controller {

	function get() {
		
		if(! is_site_admin())
			return;
	
		$o = '';
	
		$r = q("select * from channel where channel_removed = 0");
		$sitekey = get_config('system','pubkey');
		
		if($r) {
			foreach($r as $rr) {
				$found = false;
				$primary_address = '';
				$x = zot_get_hublocs($rr['channel_hash']);
				if($x) {
					foreach($x as $xx) {
						if($xx['hubloc_url'] === z_root() && $xx['hubloc_sitekey'] === $sitekey) {
							$found = true;
							break;
						}
					}
					if($found) {
						$o .= 'Hubloc exists for ' . $rr['channel_name'] . EOL;
						continue;
					}	
				}
				$y = q("select xchan_addr from xchan where xchan_hash = '%s' limit 1",
					dbesc($rr['channel_hash'])
				);
				if($y)
					$primary_address = $y[0]['xchan_addr'];
	
				$hub_address = channel_reddress($rr['channel']);
	
			
				$primary = (($hub_address === $primary_address) ? 1 : 0);
				if(! $y)
					$primary = 1;
	
				$m = q("delete from hubloc where hubloc_hash = '%s' and hubloc_url = '%s' ",
					dbesc($rr['channel_hash']),
					dbesc(z_root())
				);
	
				// Create a verified hub location pointing to this site.
	
				$h = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_addr, hubloc_primary, hubloc_url, hubloc_url_sig, hubloc_host, hubloc_callback, hubloc_sitekey, hubloc_network )
					values ( '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s' )",
					dbesc($rr['channel_guid']),
					dbesc($rr['channel_guid_sig']),
					dbesc($rr['channel_hash']),
					dbesc(channel_reddress($rr)),
					intval($primary),
					dbesc(z_root()),
					dbesc(base64url_encode(rsa_sign(z_root(),$rr['channel_prvkey']))),
					dbesc(\App::get_hostname()),
					dbesc(z_root() . '/post'),
					dbesc($sitekey),
					dbesc('zot')
				);
	
				if($h)
					$o . 'local hubloc created for ' . $rr['channel_name'] . EOL;
				else
					$o .= 'DB update failed for ' . $rr['channel_name'] . EOL;
	
			}
	
			return $o;
	
		}
	}
}
