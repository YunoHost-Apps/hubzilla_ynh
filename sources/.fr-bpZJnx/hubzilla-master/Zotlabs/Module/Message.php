<?php
namespace Zotlabs\Module;

require_once('include/acl_selectors.php');
require_once('include/message.php');
require_once('include/zot.php');
require_once("include/bbcode.php");


class Message extends \Zotlabs\Web\Controller {

	function get() {
	
		$o = '';
		nav_set_selected('messages');
	
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return login();
		}
	
		$channel = \App::get_channel();
		head_set_icon($channel['xchan_photo_s']);
	
		$cipher = get_pconfig(local_channel(),'system','default_cipher');
		if(! $cipher)
			$cipher = 'aes256';
	
	/*
		if((argc() == 3) && (argv(1) === 'dropconv')) {
			if(! intval(argv(2)))
				return;
			$cmd = argv(1);
			$r = private_messages_drop(local_channel(), argv(2), true);
			if($r)
				info( t('Conversation removed.') . EOL );
			goaway(z_root() . '/mail/combined' );
		}
	
		if(argc() == 2) {
	
			switch(argv(1)) {
				case 'combined':
					$mailbox = 'combined';
					$header = t('Conversations');
					break;
				case 'inbox':
					$mailbox = 'inbox';
					$header = t('Received Messages');
					break;
				case 'outbox':
					$mailbox = 'outbox';
					$header = t('Sent Messages');
					break;
				default:
					break;
			}
	
			// private_messages_list() can do other more complicated stuff, for now keep it simple
	
			$r = private_messages_list(local_channel(), $mailbox, \App::$pager['start'], \App::$pager['itemspage']);
	
			if(! $r) {
				info( t('No messages.') . EOL);
				return $o;
			}
	
			$messages = array();
	
			foreach($r as $rr) {
	
				$messages[] = array(
					'id'         => $rr['id'],
					'from_name'  => $rr['from']['xchan_name'],
					'from_url'   => chanlink_hash($rr['from_xchan']),
					'from_photo' => $rr['from']['xchan_photo_s'],
					'to_name'    => $rr['to']['xchan_name'],
					'to_url'     => chanlink_hash($rr['to_xchan']),
					'to_photo'   => $rr['to']['xchan_photo_s'],
					'subject'    => (($rr['seen']) ? $rr['title'] : '<strong>' . $rr['title'] . '</strong>'),
					'delete'     => t('Delete conversation'),
					'body'       => smilies(bbcode($rr['body'])),
					'date'       => datetime_convert('UTC',date_default_timezone_get(),$rr['created'], t('D, d M Y - g:i A')),
					'seen'       => $rr['seen']
				);
			}
	
	
			$tpl = get_markup_template('mail_head.tpl');
			$o = replace_macros($tpl, array(
				'$header' => $header,
				'$messages' => $messages
			));
	
	
			$o .= alt_pager($a,count($r));	
	
			return $o;
	
			return;
	
		}
	*/
	
		return;
	}
	
}
