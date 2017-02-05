<?php
namespace Zotlabs\Module;


class Manage extends \Zotlabs\Web\Controller {

	function get() {
	
		if((! get_account_id()) || ($_SESSION['delegate'])) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		require_once('include/security.php');
	
		$change_channel = ((argc() > 1) ? intval(argv(1)) : 0);
	
		if((argc() > 2) && (argv(2) === 'default')) {
			$r = q("select channel_id from channel where channel_id = %d and channel_account_id = %d limit 1",
				intval($change_channel),
				intval(get_account_id())
			);
			if($r) {
				q("update account set account_default_channel = %d where account_id = %d",
					intval($change_channel),
					intval(get_account_id())
				);
			}
			goaway(z_root() . '/manage');
		}

	
		if($change_channel) {

			$r = change_channel($change_channel);

			if((argc() > 2) && !(argv(2) === 'default')) {
				goaway(z_root() . '/' . implode('/',array_slice(\App::$argv,2))); // Go to whatever is after /manage/, but with the new channel
			}
			else {
				if($r && $r['channel_startpage'])
					goaway(z_root() . '/' . $r['channel_startpage']); // If nothing extra is specified, go to the default page
			}
			goaway(z_root());
		}
	
		$channels = null;
	
		if(local_channel()) {
			$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel.channel_account_id = %d and channel_removed = 0 order by channel_name ",
				intval(get_account_id())
			);
	
			$account = \App::get_account();
	
			if($r && count($r)) {
				$channels = $r;
				for($x = 0; $x < count($channels); $x ++) {
					$channels[$x]['link'] = 'manage/' . intval($channels[$x]['channel_id']);
					$channels[$x]['default'] = (($channels[$x]['channel_id'] == $account['account_default_channel']) ? "1" : ''); 
					$channels[$x]['default_links'] = '1';
	
	
					$c = q("SELECT id, item_wall FROM item
						WHERE item_unseen = 1 and uid = %d " . item_normal(),
						intval($channels[$x]['channel_id'])
					);
	
					if($c) {	
						foreach ($c as $it) {
							if(intval($it['item_wall']))
								$channels[$x]['home'] ++;
							else
								$channels[$x]['network'] ++;
						}
					}
	
	
					$intr = q("SELECT COUNT(abook.abook_id) AS total FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash where abook_channel = %d and abook_pending = 1 and abook_self = 0 and abook_ignored = 0 and xchan_deleted = 0 and xchan_orphan = 0 ",
						intval($channels[$x]['channel_id'])
					);
	
					if($intr)
						$channels[$x]['intros'] = intval($intr[0]['total']);
	
	
					$mails = q("SELECT count(id) as total from mail WHERE channel_id = %d AND mail_seen = 0 and from_xchan != '%s' ",
						intval($channels[$x]['channel_id']),
						dbesc($channels[$x]['channel_hash'])
					);
	
					if($mails)
						$channels[$x]['mail'] = intval($mails[0]['total']);
			
	
					$events = q("SELECT etype, dtstart, adjust FROM event
						WHERE event.uid = %d AND dtstart < '%s' AND dtstart > '%s' and dismissed = 0
						ORDER BY dtstart ASC ",
						intval($channels[$x]['channel_id']),
						dbesc(datetime_convert('UTC', date_default_timezone_get(), 'now + 7 days')),
						dbesc(datetime_convert('UTC', date_default_timezone_get(), 'now - 1 days'))
					);
	
					if($events) {
						$channels[$x]['all_events'] = count($events);
	
						if($channels[$x]['all_events']) {
							$str_now = datetime_convert('UTC', date_default_timezone_get(), 'now', 'Y-m-d');
							foreach($events as $e) {
								$bd = false;
								if($e['etype'] === 'birthday') {
									$channels[$x]['birthdays'] ++;
									$bd = true;
								}
								else {
									$channels[$x]['events'] ++;
								}
								if(datetime_convert('UTC', ((intval($e['adjust'])) ? date_default_timezone_get() : 'UTC'), $e['dtstart'], 'Y-m-d') === $str_now) {
									$channels[$x]['all_events_today'] ++;
									if($bd)
										$channels[$x]['birthdays_today'] ++;
									else
										$channels[$x]['events_today'] ++;
								}
							}
						}
					}
				}
			}
			
		    $r = q("select count(channel_id) as total from channel where channel_account_id = %d and channel_removed = 0",
				intval(get_account_id())
			);
			$limit = account_service_class_fetch(get_account_id(),'total_identities');
			if($limit !== false) {
				$channel_usage_message = sprintf( t("You have created %1$.0f of %2$.0f allowed channels."), $r[0]['total'], $limit);
			}
			else {
				$channel_usage_message = '';
	 		}
		}
	
		$create = array( 'new_channel', t('Create a new channel'), t('Create New'));
	
		$delegates = q("select * from abook left join xchan on abook_xchan = xchan_hash where 
			abook_channel = %d and abook_xchan in ( select xchan from abconfig where chan = %d and cat = 'their_perms' and k = 'delegate' and v = '1' )",
			intval(local_channel()),
			intval(local_channel())
		);
	
		if($delegates) {
			for($x = 0; $x < count($delegates); $x ++) {
				$delegates[$x]['link'] = 'magic?f=&dest=' . urlencode($delegates[$x]['xchan_url']) 
				. '&delegate=' . urlencode($delegates[$x]['xchan_addr']);
				$delegates[$x]['channel_name'] = $delegates[$x]['xchan_name'];
				$delegates[$x]['delegate'] = 1;
			}
		}
		else {
			$delegates = null;
		}
	
		$o = replace_macros(get_markup_template('channels.tpl'), array(
			'$header'           => t('Channel Manager'),
			'$msg_selected'     => t('Current Channel'),
			'$selected'         => local_channel(),
			'$desc'             => t('Switch to one of your channels by selecting it.'),
			'$msg_default'      => t('Default Channel'),
			'$msg_make_default' => t('Make Default'),
			'$create'           => $create,
			'$all_channels'     => $channels,
			'$mail_format'      => t('%d new messages'),
			'$intros_format'    => t('%d new introductions'),
			'$channel_usage_message' => $channel_usage_message,
			'$delegated_desc'   => t('Delegated Channel'),
			'$delegates'        => $delegates
		));
	
		return $o;
	
	}
	
}
