<?php
namespace Zotlabs\Module;

require_once('include/zot.php');

class Chanview extends \Zotlabs\Web\Controller {

	function get() {
	
		$observer = \App::get_observer();
		$xchan = null;
	
		$r = null;
	
		if($_REQUEST['hash']) {
			$r = q("select * from xchan where xchan_hash = '%s' limit 1",
				dbesc($_REQUEST['hash'])
			);
		}
		if($_REQUEST['address']) {
			$r = q("select * from xchan where xchan_addr = '%s' limit 1",
				dbesc($_REQUEST['address'])
			);
		}
		elseif(local_channel() && intval($_REQUEST['cid'])) {
			$r = q("SELECT abook.*, xchan.* 
				FROM abook left join xchan on abook_xchan = xchan_hash
				WHERE abook_channel = %d and abook_id = %d LIMIT 1",
				intval(local_channel()),
				intval($_REQUEST['cid'])
			);
		}	
		elseif($_REQUEST['url']) {
	
			// if somebody re-installed they will have more than one xchan, use the most recent name date as this is
			// the most useful consistently ascending table item we have. 
	
			$r = q("select * from xchan where xchan_url = '%s' order by xchan_name_date desc limit 1",
				dbesc($_REQUEST['url'])
			);
		}
		if($r) {
			\App::$poi = $r[0];
		}
	
	
		// Here, let's see if we have an xchan. If we don't, how we proceed is determined by what
		// info we do have. If it's a URL, we can offer to visit it directly. If it's a webbie or 
		// address, we can and should try to import it. If it's just a hash, we can't continue, but we 
		// probably wouldn't have a hash if we don't already have an xchan for this channel.
	
		if(! \App::$poi) {
			logger('mod_chanview: fallback');
			// This is hackish - construct a zot address from the url
			if($_REQUEST['url']) {
				if(preg_match('/https?\:\/\/(.*?)(\/channel\/|\/profile\/)(.*?)$/ism',$_REQUEST['url'],$matches)) {
					$_REQUEST['address'] = $matches[3] . '@' . $matches[1];
				}
				logger('mod_chanview: constructed address ' . print_r($matches,true)); 
			}
	
			if($_REQUEST['address']) {
				$j = \Zotlabs\Zot\Finger::run($_REQUEST['address'],null);
				if($j['success']) {
					import_xchan($j);
					$r = q("select * from xchan where xchan_addr = '%s' limit 1",
						dbesc($_REQUEST['address'])
					);
					if($r)
						\App::$poi = $r[0];
				}
			}
		}
	
		if(! \App::$poi) {
	//		We don't know who this is, and we can't figure it out from the URL
	//		On the plus side, there's a good chance we know somebody else at that 
	//		hub so sending them there with a Zid will probably work anyway.
			$url = ($_REQUEST['url']);
			if($observer)
				$url = zid($url);
		}
	
		if (\App::$poi) {
		$url = \App::$poi['xchan_url'];
		if($observer)
			$url = zid($url);
		}
		// let somebody over-ride the iframed viewport presentation
		// or let's just declare this a failed experiment.
	
	//	if((! local_channel()) || (get_pconfig(local_channel(),'system','chanview_full')))
		
		goaway($url);
	
	//	$o = replace_macros(get_markup_template('chanview.tpl'),array(
	//		'$url' => $url,
	//		'$full' => t('toggle full screen mode')
	//	));
	
	//	return $o;
	
	}
	
}
