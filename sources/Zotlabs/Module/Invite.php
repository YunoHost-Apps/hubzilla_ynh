<?php
namespace Zotlabs\Module;

/**
 * module: invite.php
 *
 * send email invitations to join social network
 *
 */


class Invite extends \Zotlabs\Web\Controller {

	function post() {
	
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		check_form_security_token_redirectOnErr('/', 'send_invite');
	
		$max_invites = intval(get_config('system','max_invites'));
		if(! $max_invites)
			$max_invites = 50;
	
		$current_invites = intval(get_pconfig(local_channel(),'system','sent_invites'));
		if($current_invites > $max_invites) {
			notice( t('Total invitation limit exceeded.') . EOL);
			return;
		};
	
	
		$recips  = ((x($_POST,'recipients')) ? explode("\n",$_POST['recipients']) : array());
		$message = ((x($_POST,'message'))    ? notags(trim($_POST['message']))    : '');
	
		$total = 0;
	
		if(get_config('system','invitation_only')) {
			$invonly = true;
			$x = get_pconfig(local_channel(),'system','invites_remaining');
			if((! $x) && (! is_site_admin()))
				return;
		}
	
		foreach($recips as $recip) {
	
			$recip = trim($recip);
			if(! $recip)
				continue;
	
			if(! valid_email($recip)) {
				notice(  sprintf( t('%s : Not a valid email address.'), $recip) . EOL);
				continue;
			}
	
			else
				$nmessage = $message;
	
			$account = \App::get_account();
	
			$res = z_mail(
				[ 
				'toEmail'        => $recip,
				'fromName'       => ' ',
				'fromEmail'      => $account['account_email'],
				'messageSubject' => t('Please join us on $Projectname'),
				'textVersion'    => $nmessage,
				]
			);
	
			if($res) {
				$total ++;
				$current_invites ++;
				set_pconfig(local_channel(),'system','sent_invites',$current_invites);
				if($current_invites > $max_invites) {
					notice( t('Invitation limit exceeded. Please contact your site administrator.') . EOL);
					return;
				}
			}
			else {
				notice( sprintf( t('%s : Message delivery failed.'), $recip) . EOL);
			}
	
		}
		notice( sprintf( tt("%d message sent.", "%d messages sent.", $total) , $total) . EOL);
		return;
	}
	
	
		function get() {
	
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		$tpl = get_markup_template('invite.tpl');
		$invonly = false;
	
		if(get_config('system','invitation_only')) {
			$invonly = true;
			$x = get_pconfig(local_channel(),'system','invites_remaining');
			if((! $x) && (! is_site_admin())) {
				notice( t('You have no more invitations available') . EOL);
				return '';
			}
		}
	
			if($invonly && ($x || is_site_admin())) {
				$invite_code = autoname(8) . rand(1000,9999);
				$nmessage = str_replace('$invite_code',$invite_code,$message);
	
				$r = q("INSERT INTO register (hash,created) VALUES ('%s', '%s') ",
					dbesc($invite_code),
					dbesc(datetime_convert())
				);
	
				if(! is_site_admin()) {
					$x --;
					if($x >= 0)
						set_pconfig(local_channel(),'system','invites_remaining',$x);
					else
						return;
				}
			}
	
		$ob = \App::get_observer();
		if(! $ob)
			return $o;
	
		$channel = \App::get_channel();
	
		$o = replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("send_invite"),
			'$invite' => t('Send invitations'),
			'$addr_text' => t('Enter email addresses, one per line:'),
			'$msg_text' => t('Your message:'),
			'$default_message' => t('Please join my community on $Projectname.') . "\r\n" . "\r\n"
				. $linktxt
				. (($invonly) ? "\r\n" . "\r\n" . t('You will need to supply this invitation code:') . " " . $invite_code . "\r\n" . "\r\n" : '')
				. t('1. Register at any $Projectname location (they are all inter-connected)')
				. "\r\n" . "\r\n" . z_root() . '/register'
				. "\r\n" . "\r\n" . t('2. Enter my $Projectname network address into the site searchbar.')
				. "\r\n" . "\r\n" . $ob['xchan_addr'] . ' (' . t('or visit') . " " . z_root() . '/channel/' . $channel['channel_address'] . ')'
				. "\r\n" . "\r\n"
				. t('3. Click [Connect]')
				. "\r\n" . "\r\n"  ,
			'$submit' => t('Submit')
		));
	
		return $o;
	}
	
}
