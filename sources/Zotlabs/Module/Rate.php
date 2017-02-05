<?php
namespace Zotlabs\Module;



class Rate extends \Zotlabs\Web\Controller {

	function init() {
	
		if(! local_channel())
			return;
	
		$channel = \App::get_channel();
	
		$target = $_REQUEST['target'];
		if(! $target)
			return;
	
		\App::$data['target'] = $target;
	
		if($target) {
			$r = q("SELECT * FROM xchan where xchan_hash like '%s' LIMIT 1",
				dbesc($target)
			);
			if($r) {
				\App::$poi = $r[0];
			}
			else {
				$r = q("select * from site where site_url like '%s' and site_type = %d",
					dbesc('%' . $target),
					intval(SITE_TYPE_ZOT)
				);
				if($r) {
					\App::$data['site'] = $r[0];
					\App::$data['site']['site_url'] = strtolower($r[0]['site_url']);
				}
			}
		}
	
	
		return;
	
	}
	
	
	function post() {
	
		if(! local_channel())
			return;
	
		if(! \App::$data['target'])
			return;
	
		if(! $_REQUEST['execute'])
			return;
	
		$channel = \App::get_channel();
	
		$rating = intval($_POST['rating']);
		if($rating < (-10))
			$rating = (-10);
		if($rating > 10)
			$rating = 10;
	
		$rating_text = trim(escape_tags($_REQUEST['rating_text']));
	
		$signed = \App::$data['target'] . '.' . $rating . '.' . $rating_text;
	
		$sig = base64url_encode(rsa_sign($signed,$channel['channel_prvkey']));
	
		$z = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1 limit 1",
			dbesc($channel['channel_hash']),
			dbesc(\App::$data['target'])
		);
	
		if($z) {
			$record = $z[0]['xlink_id'];
			$w = q("update xlink set xlink_rating = '%d', xlink_rating_text = '%s', xlink_sig = '%s', xlink_updated = '%s'
				where xlink_id = %d",
				intval($rating),
				dbesc($rating_text),
				dbesc($sig),
				dbesc(datetime_convert()),
				intval($record)
			);
		}
		else {
			$w = q("insert into xlink ( xlink_xchan, xlink_link, xlink_rating, xlink_rating_text, xlink_sig, xlink_updated, xlink_static ) values ( '%s', '%s', %d, '%s', '%s', '%s', 1 ) ",
				dbesc($channel['channel_hash']),
				dbesc(\App::$data['target']),
				intval($rating),
				dbesc($rating_text),
				dbesc($sig),
				dbesc(datetime_convert())
			);
			$z = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1 limit 1",
				dbesc($channel['channel_hash']),
				dbesc(\App::$data['target'])
			);
			if($z)
				$record = $z[0]['xlink_id'];
		}
	
		if($record) {
			\Zotlabs\Daemon\Master::Summon(array('Ratenotif','rating',$record));
		}
	
	}
	
	function get() {
	
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
	//	if(! \App::$data['target']) {
	//		notice( t('No recipients.') . EOL);
	//		return;
	//	}
	
		$rating_enabled = get_config('system','rating_enabled');
		if(! $rating_enabled) {
			notice('Ratings are disabled on this site.');
			return;
		}
	
		$channel = \App::get_channel();
	
		$r = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1",
			dbesc($channel['channel_hash']),
			dbesc(\App::$data['target'])
		);
		if($r) {
			\App::$data['xlink'] = $r[0];				
			$rating_val = $r[0]['xlink_rating'];
			$rating_text = $r[0]['xlink_rating_text'];
		}
		else {
			$rating_val = 0;
			$rating_text = '';
		}
	
		if($rating_enabled) {
			$rating = replace_macros(get_markup_template('rating_slider.tpl'),array(
				'$min' => -10,
				'$val' => $rating_val
			));
		}
		else {
			$rating = false;
		}
	
		$o = replace_macros(get_markup_template('rating_form.tpl'),array(
			'$header' => t('Rating'),
			'$website' => t('Website:'),
			'$site' => ((\App::$data['site']) ? '<a href="' . \App::$data['site']['site_url'] . '" >' . \App::$data['site']['site_url'] . '</a>' : ''),
			'target' => \App::$data['target'],
			'$tgt_name' => ((\App::$poi && \App::$poi['xchan_name']) ? \App::$poi['xchan_name'] : sprintf( t('Remote Channel [%s] (not yet known on this site)'), substr(\App::$data['target'],0,16))),
			'$lbl_rating'     => t('Rating (this information is public)'),
			'$lbl_rating_txt' => t('Optionally explain your rating (this information is public)'),
			'$rating_txt'     => $rating_text,
			'$rating'         => $rating,
			'$rating_val'     => $rating_val,
			'$slide'          => $slide,
			'$submit' => t('Submit')
		));
	
		return $o;
	
	}
}
