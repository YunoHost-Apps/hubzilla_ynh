<?php
namespace Zotlabs\Module;

require_once('include/dir_fns.php');


class Ratings extends \Zotlabs\Web\Controller {

	function init() {
	
		if(observer_prohibited()) {
			return;
		}
	
		if(local_channel())
			load_contact_links(local_channel());
	
		$dirmode = intval(get_config('system','directory_mode'));
	
		$x = find_upstream_directory($dirmode);
		if($x)
			$url = $x['url'];
	
		$rating_enabled = get_config('system','rating_enabled');
	
		if(! $rating_enabled)
			return;
	
		if(argc() > 1)
			$hash = argv(1);
	
		if(! $hash) {
			notice('Must supply a channel identififier.');
			return;
		}
	
		$results = false;
	
		$x = z_fetch_url($url . '/ratingsearch/' . urlencode($hash));
	
	
		if($x['success'])
			$results = json_decode($x['body'],true);
	
	
		if((! $results) || (! $results['success'])) {
	
			notice('No results.');
			return;
		} 
	
		if(array_key_exists('xchan_hash',$results['target']))
			\App::$poi = $results['target'];
		
		$friends = array();
		$others = array();
	
		if($results['ratings']) {
			foreach($results['ratings'] as $n) {
				if(is_array(\App::$contacts) && array_key_exists($n['xchan_hash'],\App::$contacts))
					$friends[] = $n;
				else
					$others[] = $n;
			}
		}
	
		\App::$data = array('target' => $results['target'], 'results' => array_merge($friends,$others));
	
		if(! \App::$data['results']) {
			notice( t('No ratings') . EOL);
		}
	
		return;
	}
	
	
	
	
	
	function get() {
	
		if(observer_prohibited()) {
			notice( t('Public access denied.') . EOL);
			return;
		}
	
		$rating_enabled = get_config('system','rating_enabled');
	
		if(! $rating_enabled)
			return;
	
		$site_target = ((array_key_exists('target',\App::$data) && array_key_exists('site_url',\App::$data['target'])) ?
			'<a href="' . \App::$data['target']['site_url'] . '" >' . \App::$data['target']['site_url'] . '</a>' : '');
	
	
		$o = replace_macros(get_markup_template('prep.tpl'),array(
			'$header' => t('Ratings'),
			'$rating_lbl' => t('Rating: ' ),
			'$website' => t('Website: '),
			'$site' => $site_target,
			'$rating_text_lbl' => t('Description: '),
			'$raters' => \App::$data['results']
		));
	
		return $o;
	}
	
				
}
