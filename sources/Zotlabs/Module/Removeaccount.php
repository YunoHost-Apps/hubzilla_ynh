<?php
namespace Zotlabs\Module;


class Removeaccount extends \Zotlabs\Web\Controller {

	function post() {
	
		if(! local_channel())
			return;
	
		if($_SESSION['delegate'])
			return;
	
		if((! x($_POST,'qxz_password')) || (! strlen(trim($_POST['qxz_password']))))
			return;
	
		if((! x($_POST,'verify')) || (! strlen(trim($_POST['verify']))))
			return;
	
		if($_POST['verify'] !== $_SESSION['remove_account_verify'])
			return;
	
	
		$account = \App::get_account();
		$account_id = get_account_id();
	
		$x = account_verify_password($account['account_email'],$_POST['qxz_password']);
		if(! ($x && $x['account']))
			return;
	
		if($account['account_password_changed'] > NULL_DATE) {
			$d1 = datetime_convert('UTC','UTC','now - 48 hours');
			if($account['account_password_changed'] > d1) {
				notice( t('Account removals are not allowed within 48 hours of changing the account password.') . EOL);
				return;
			}
		}
	
		$global_remove = intval($_POST['global']);
	
		account_remove($account_id, 1 - $global_remove);		
	}
		
	function get() {
	
		if(! local_channel())
			goaway(z_root());
	
		$hash = random_string();
	
		$_SESSION['remove_account_verify'] = $hash;
		$tpl = get_markup_template('removeaccount.tpl');
		$o .= replace_macros($tpl, array(
			'$basedir' => z_root(),
			'$hash' => $hash,
			'$title' => t('Remove This Account'),
			'$desc' => array(t('WARNING: '), t('This account and all its channels will be completely removed from the network. '), t('This action is permanent and can not be undone!')),
			'$passwd' => t('Please enter your password for verification:'),
			'$global' => array('global', t('Remove this account, all its channels and all its channel clones from the network'), false, t('By default only the instances of the channels located on this hub will be removed from the network')),
			'$submit' => t('Remove Account')
		));
	
		return $o;		
	
	}
	
}
