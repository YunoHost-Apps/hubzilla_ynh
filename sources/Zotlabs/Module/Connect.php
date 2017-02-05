<?php
namespace Zotlabs\Module; /** @file */



require_once('include/contact_widgets.php');
require_once('include/items.php');



class Connect extends \Zotlabs\Web\Controller {

	function init() {
		if(argc() > 1)
			$which = argv(1);
		else {
			notice( t('Requested profile is not available.') . EOL );
			\App::$error = 404;
			return;
		}
	
		$r = q("select * from channel where channel_address = '%s' limit 1",
			dbesc($which)
		);
	
		if($r)
			\App::$data['channel'] = $r[0];
	
		profile_load($which,'');
	}
	
	function post() {
	
		if(! array_key_exists('channel', \App::$data))
			return;
	
		$edit = ((local_channel() && (local_channel() == \App::$data['channel']['channel_id'])) ? true : false);
	
		if($edit) {
			$has_premium = ((\App::$data['channel']['channel_pageflags'] & PAGE_PREMIUM) ? 1 : 0);
			$premium = (($_POST['premium']) ? intval($_POST['premium']) : 0);
			$text = escape_tags($_POST['text']);
			
			if($has_premium != $premium) {
				$r = q("update channel set channel_pageflags = ( channel_pageflags %s %d ) where channel_id = %d",
					db_getfunc('^'),
					intval(PAGE_PREMIUM),
					intval(local_channel()) 
				);
				
				\Zotlabs\Daemon\Master::Summon(array('Notifier','refresh_all',\App::$data['channel']['channel_id']));
			}
			set_pconfig(\App::$data['channel']['channel_id'],'system','selltext',$text);
			// reload the page completely to get fresh data
			goaway(z_root() . '/' . \App::$query_string);
	
		}
	
		$url = '';
		$observer = \App::get_observer();
		if(($observer) && ($_POST['submit'] === t('Continue'))) {
			if($observer['xchan_follow'])
				$url = sprintf($observer['xchan_follow'],urlencode(channel_reddress(\App::$data['channel'])));
			if(! $url) {
				$r = q("select * from hubloc where hubloc_hash = '%s' order by hubloc_id desc limit 1",
					dbesc($observer['xchan_hash'])
				);
				if($r)
					$url = $r[0]['hubloc_url'] . '/follow?f=&url=' . urlencode(channel_reddress(\App::$data['channel']));
			}
		}
		if($url)
			goaway($url . '&confirm=1');
		else
			notice('Unable to connect to your home hub location.');
	
	}
	
	
	
	function get() {
	
		$edit = ((local_channel() && (local_channel() == \App::$data['channel']['channel_id'])) ? true : false);
	
		$text = get_pconfig(\App::$data['channel']['channel_id'],'system','selltext');
	
		if($edit) {
	
			$o = replace_macros(get_markup_template('sellpage_edit.tpl'),array(
				'$header' => t('Premium Channel Setup'),
				'$address' => \App::$data['channel']['channel_address'],
				'$premium' => array('premium', t('Enable premium channel connection restrictions'),((\App::$data['channel']['channel_pageflags'] & PAGE_PREMIUM) ? '1' : ''),''),
				'$lbl_about' => t('Please enter your restrictions or conditions, such as paypal receipt, usage guidelines, etc.'),
	 			'$text' => $text,
				'$desc' => t('This channel may require additional steps or acknowledgement of the following conditions prior to connecting:'),
				'$lbl2' => t('Potential connections will then see the following text before proceeding:'),
				'$desc2' => t('By continuing, I certify that I have complied with any instructions provided on this page.'),
				'$submit' => t('Submit'),
	
	
			));
			return $o;
		}
		else {
			if(! $text)
				$text = t('(No specific instructions have been provided by the channel owner.)');
	
			$submit = replace_macros(get_markup_template('sellpage_submit.tpl'), array(
				'$continue' => t('Continue'),			
				'$address' => \App::$data['channel']['channel_address']
			));
	
			$o = replace_macros(get_markup_template('sellpage_view.tpl'),array(
				'$header' => t('Restricted or Premium Channel'),
				'$desc' => t('This channel may require additional steps or acknowledgement of the following conditions prior to connecting:'),
				'$text' => prepare_text($text), 
	
				'$desc2' => t('By continuing, I certify that I have complied with any instructions provided on this page.'),
				'$submit' => $submit,
	
			));
	
			$arr = array('channel' => \App::$data['channel'],'observer' => \App::get_observer(), 'sellpage' => $o, 'submit' => $submit);
			call_hooks('connect_premium', $arr);
			$o = $arr['sellpage'];
	
		}
	
		return $o;
	}
}
