<?php
namespace Zotlabs\Module;

require_once('include/acl_selectors.php');
require_once('include/message.php');
require_once('include/zot.php');
require_once("include/bbcode.php");




class Mail extends \Zotlabs\Web\Controller {

	function post() {
	
		if(! local_channel())
			return;
	
		$replyto   = ((x($_REQUEST,'replyto'))      ? notags(trim($_REQUEST['replyto']))      : '');
		$subject   = ((x($_REQUEST,'subject'))      ? notags(trim($_REQUEST['subject']))      : '');
		$body      = ((x($_REQUEST,'body'))         ? escape_tags(trim($_REQUEST['body']))    : '');
		$recipient = ((x($_REQUEST,'messageto'))    ? notags(trim($_REQUEST['messageto']))    : '');
		$rstr      = ((x($_REQUEST,'messagerecip')) ? notags(trim($_REQUEST['messagerecip'])) : '');
		$preview   = ((x($_REQUEST,'preview'))      ? intval($_REQUEST['preview'])            : 0);
		$expires   = ((x($_REQUEST,'expires')) ? datetime_convert(date_default_timezone_get(),'UTC', $_REQUEST['expires']) : NULL_DATE);
	
		// If we have a raw string for a recipient which hasn't been auto-filled,
		// it means they probably aren't in our address book, hence we don't know
		// if we have permission to send them private messages.
		// finger them and find out before we try and send it.
	
		if(! $recipient) {
			$channel = \App::get_channel();
	
			$j = \Zotlabs\Zot\Finger::run($rstr,$channel);
	
			if(! $j['success']) {
				notice( t('Unable to lookup recipient.') . EOL);
				return;
			} 
	
			logger('message_post: lookup: ' . $url . ' ' . print_r($j,true));
	
			if(! $j['guid']) {
				notice( t('Unable to communicate with requested channel.'));
				return;
			}
	
			$x = import_xchan($j);
	
			if(! $x['success']) {
				notice( t('Cannot verify requested channel.'));
				return;
			}
	
			$recipient = $x['hash'];
	
			$their_perms = 0;
	
			if($j['permissions']['data']) {
				$permissions = crypto_unencapsulate($j['permissions'],$channel['channel_prvkey']);
				if($permissions)
					$permissions = json_decode($permissions, true);
				logger('decrypted permissions: ' . print_r($permissions,true), LOGGER_DATA);
			}
			else
				$permissions = $j['permissions'];
	
			if(! ($permissions['post_mail'])) {
	 			notice( t('Selected channel has private message restrictions. Send failed.'));
				// reported issue: let's still save the message and continue. We'll just tell them
				// that nothing useful is likely to happen. They might have spent hours on it.  
				//			return;
	
			}
		}
	
	//	if(feature_enabled(local_channel(),'richtext')) {
	//		$body = fix_mce_lf($body);
	//	}
	
		require_once('include/text.php');
		linkify_tags($a, $body, local_channel());
	
		if($preview) {
			$mail = [
				'mailbox' => 'outbox',
				'id' => 0,
				'mid' => 'M0',
				'from_name' => $channel['xchan_name'],
				'from_url' =>  $channel['xchan_url'],
				'from_photo' => $channel['xchan_photo_s'],
				'subject' => smilies(bbcode($subject)),
				'body' => smilies(bbcode($body)),
				'attachments' => '',
				'can_recall' => false,
				'is_recalled' => '',
				'date' => datetime_convert('UTC',date_default_timezone_get(),$message['created'], 'c')
			];
			
			echo replace_macros(get_markup_template('mail_conv.tpl'), [ '$mail' => $mail ] );
			killme();

		}
	
		if(! $recipient) {
			notice('No recipient found.');
			\App::$argc = 2;
			\App::$argv[1] = 'new';
			return;
		}
	
		// We have a local_channel, let send_message use the session channel and save a lookup
		
		$ret = send_message(0, $recipient, $body, $subject, $replyto, $expires);
	
		if($ret['success']) {
			xchan_mail_query($ret['mail']);
			build_sync_packet(0,array('conv' => array($ret['conv']),'mail' => array(encode_mail($ret['mail'],true))));
		}
		else {
			notice($ret['message']);
		}
	
		goaway(z_root() . '/mail/combined');
			
	}
	
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
	
		$tpl = get_markup_template('mail_head.tpl');
		$header = replace_macros($tpl, array(
			'$header' => t('Messages'),
		));
	
		if((argc() == 4) && (argv(2) === 'drop')) {
			if(! intval(argv(3)))
				return;
			$cmd = argv(2);
			$mailbox = argv(1);
			$r = private_messages_drop(local_channel(), argv(3));
			if($r) {
				//info( t('Message deleted.') . EOL );
			}
			goaway(z_root() . '/mail/' . $mailbox);
		}
	
		if((argc() == 4) && (argv(2) === 'recall')) {
			if(! intval(argv(3)))
				return;
			$cmd = argv(2);
			$mailbox = argv(1);
			$r = q("update mail set mail_recalled = 1 where id = %d and channel_id = %d",
				intval(argv(3)),
				intval(local_channel())
			);
			$x = q("select * from mail where id = %d and channel_id = %d",
				intval(argv(3)),
				intval(local_channel())
			);
			if($x) {
				build_sync_packet(local_channel(),array('mail' => encode_mail($x[0],true)));
			}
	
			\Zotlabs\Daemon\Master::Summon(array('Notifier','mail',intval(argv(3))));
	
			if($r) {
					info( t('Message recalled.') . EOL );
			}
			goaway(z_root() . '/mail/' . $mailbox . '/' . argv(3));
	
		}
	
		if((argc() == 4) && (argv(2) === 'dropconv')) {
			if(! intval(argv(3)))
				return;
			$cmd = argv(2);
			$mailbox = argv(1);
			$r = private_messages_drop(local_channel(), argv(3), true);
			if($r)
				info( t('Conversation removed.') . EOL );
			goaway(z_root() . '/mail/' . $mailbox);
		}
	
		if((argc() > 1) && (argv(1) === 'new')) {
			
			$plaintext = true;
	
			$tpl = get_markup_template('msg-header.tpl');
	
			$header = replace_macros($tpl, array(
				'$baseurl' => z_root(),
				'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
				'$nickname' => $channel['channel_address'],
				'$linkurl' => t('Please enter a link URL:'),
				'$expireswhen' => t('Expires YYYY-MM-DD HH:MM')
			));
	
			\App::$page['htmlhead'] .= $header;
	
			$prename = '';
			$preid = '';
	
			if(x($_REQUEST,'hash')) {
	
				$r = q("select abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash
					where abook_channel = %d and abook_xchan = '%s' limit 1",
					intval(local_channel()),
					dbesc($_REQUEST['hash'])
				);
	
				if(!$r) {
					$r = q("select * from xchan where xchan_hash = '%s' and xchan_network = 'zot' limit 1",
						dbesc($_REQUEST['hash'])
					);
				}
	
				if($r) {
					$prename = (($r[0]['abook_id']) ? $r[0]['xchan_name'] : $r[0]['xchan_addr']);
					$preurl = $r[0]['xchan_url'];
					$preid = (($r[0]['abook_id']) ? ($r[0]['xchan_hash']) : '');
				}
				else {
					notice( t('Requested channel is not in this network') . EOL );
				}
	
			}
	
			$tpl = get_markup_template('prv_message.tpl');
			$o .= replace_macros($tpl,array(
				'$new' => true,
				'$header' => t('Send Private Message'),
				'$to' => t('To:'),
				'$prefill' => $prename,
				'$preid' => $preid,
				'$subject' => t('Subject:'),
				'$subjtxt' => ((x($_REQUEST,'subject')) ? strip_tags($_REQUEST['subject']) : ''),
				'$text' => ((x($_REQUEST,'body')) ? htmlspecialchars($_REQUEST['body'], ENT_COMPAT, 'UTF-8') : ''),
				'$yourmessage' => t('Your message:'),
				'$parent' => '',
				'$attach' => t('Attach file'),
				'$insert' => t('Insert web link'),
				'$submit' => t('Send'),
				'$defexpire' => '',
				'$feature_expire' => ((feature_enabled(local_channel(),'content_expire')) ? true : false),
				'$expires' => t('Set expiration date'),
				'$feature_encrypt' => ((feature_enabled(local_channel(),'content_encrypt')) ? true : false),
				'$encrypt' => t('Encrypt text'),
				'$cipher' => $cipher,
			));
	
			return $o;
		}
	
		switch(argv(1)) {
			case 'combined':
				$mailbox = 'combined';
				break;
			case 'inbox':
				$mailbox = 'inbox';
				break;
			case 'outbox':
				$mailbox = 'outbox';
				break;
			default:
				$mailbox = 'combined';
				break;
		}
	
		$last_message = private_messages_list(local_channel(), $mailbox, 0, 1);
	
		$mid = ((argc() > 2) && (intval(argv(2)))) ? argv(2) : $last_message[0]['id'];
	
		$plaintext = true;
	
	//	if( local_channel() && feature_enabled(local_channel(),'richtext') )
	//		$plaintext = false;
	
	
	
		if($mailbox == 'combined') {
			$messages = private_messages_fetch_conversation(local_channel(), $mid, true);
		}
		else {
			$messages = private_messages_fetch_message(local_channel(), $mid, true);
		}
	
		if(! $messages) {
			//info( t('Message not found.') . EOL);
			return;
		}
	
		if($messages[0]['to_xchan'] === $channel['channel_hash'])
			\App::$poi = $messages[0]['from'];
		else
			\App::$poi = $messages[0]['to'];
	
		$tpl = get_markup_template('msg-header.tpl');
		
		\App::$page['htmlhead'] .= replace_macros($tpl, array(
			'$nickname' => $channel['channel_address'],
			'$baseurl' => z_root(),
			'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
			'$linkurl' => t('Please enter a link URL:'),
			'$expireswhen' => t('Expires YYYY-MM-DD HH:MM')
		));
	
		$mails = array();
	
		$seen = 0;
		$unknown = false;
	
		foreach($messages as $message) {
	
			$s = theme_attachments($message);
	
			$mails[] = array(
				'mailbox' => $mailbox,
				'id' => $message['id'],
				'mid' => $message['mid'],
				'from_name' => $message['from']['xchan_name'],
				'from_url' =>  chanlink_hash($message['from_xchan']),
				'from_photo' => $message['from']['xchan_photo_s'],
				'to_name' => $message['to']['xchan_name'],
				'to_url' =>  chanlink_hash($message['to_xchan']),
				'to_photo' => $message['to']['xchan_photo_s'],
				'subject' => $message['title'],
				'body' => smilies(bbcode($message['body'])),
				'attachments' => $s,
				'delete' => t('Delete message'),
				'dreport' => t('Delivery report'),
				'recall' => t('Recall message'),
				'can_recall' => (($channel['channel_hash'] == $message['from_xchan'] && get_account_techlevel() > 0) ? true : false),
				'is_recalled' => (intval($message['mail_recalled']) ? t('Message has been recalled.') : ''),
				'date' => datetime_convert('UTC',date_default_timezone_get(),$message['created'], 'c'),
			);
					
			$seen = $message['seen'];
	
		}
	
		$recp = (($message['from_xchan'] === $channel['channel_hash']) ? 'to' : 'from');
	
		$tpl = get_markup_template('mail_display.tpl');
		$o = replace_macros($tpl, array(
			'$mailbox' => $mailbox,
			'$prvmsg_header' => $message['title'],
			'$thread_id' => $mid,
			'$thread_subject' => $message['title'],
			'$thread_seen' => $seen,
			'$delete' =>  t('Delete Conversation'),
			'$canreply' => (($unknown) ? false : '1'),
			'$unknown_text' => t("No secure communications available. You <strong>may</strong> be able to respond from the sender's profile page."),
			'$mails' => $mails,
				
			// reply
			'$header' => t('Send Reply'),
			'$to' => t('To:'),
			'$reply' => true,
			'$subject' => t('Subject:'),
			'$subjtxt' => $message['title'],
			'$yourmessage' => sprintf(t('Your message for %s (%s):'), $message[$recp]['xchan_name'], $message[$recp]['xchan_addr']),
			'$text' => '',
			'$parent' => $message['parent_mid'],
			'$recphash' => $message[$recp]['xchan_hash'],
			'$attach' => t('Attach file'),
			'$insert' => t('Insert web link'),
			'$submit' => t('Submit'),
			'$defexpire' => '',
			'$feature_expire' => ((feature_enabled(local_channel(),'content_expire')) ? true : false),
			'$expires' => t('Set expiration date'),
			'$feature_encrypt' => ((feature_enabled(local_channel(),'content_encrypt')) ? true : false),
			'$encrypt' => t('Encrypt text'),
			'$cipher' => $cipher,
		));
	
		return $o;
	
	}
	
}
