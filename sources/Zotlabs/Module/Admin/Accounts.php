<?php

namespace Zotlabs\Module\Admin;



class Accounts {
	
	/**
	 * @brief Handle POST actions on accounts admin page.
	 *
	 * This function is called when on the admin user/account page the form was
	 * submitted to handle multiple operations at once. If one of the icons next
	 * to an entry are pressed the function admin_page_accounts() will handle this.
	 *
	 */

	function post() {
		$pending = ( x($_POST, 'pending') ? $_POST['pending'] : array() );
		$users   = ( x($_POST, 'user')    ? $_POST['user']    : array() );
		$blocked = ( x($_POST, 'blocked') ? $_POST['blocked'] : array() );
	
		check_form_security_token_redirectOnErr('/admin/accounts', 'admin_accounts');
	
		// change to switch structure?
		// account block/unblock button was submitted
		if (x($_POST, 'page_users_block')) {
			for ($i = 0; $i < count($users); $i++) {
				// if account is blocked remove blocked bit-flag, otherwise add blocked bit-flag
				$op = ($blocked[$i]) ? '& ~' : '| ';
				q("UPDATE account SET account_flags = (account_flags $op%d) WHERE account_id = %d",
					intval(ACCOUNT_BLOCKED),
					intval($users[$i])
				);
			}
			notice( sprintf( tt("%s account blocked/unblocked", "%s account blocked/unblocked", count($users)), count($users)) );
		}
		// account delete button was submitted
		if (x($_POST, 'page_accounts_delete')) {
			foreach ($users as $uid){
				account_remove($uid, true, false);
			}
			notice( sprintf( tt("%s account deleted", "%s accounts deleted", count($users)), count($users)) );
		}
		// registration approved button was submitted
		if (x($_POST, 'page_users_approve')) {
			foreach ($pending as $hash) {
				account_allow($hash);
			}
		}
		// registration deny button was submitted
		if (x($_POST, 'page_users_deny')) {
			foreach ($pending as $hash) {
				account_deny($hash);
			}
		}
	
		goaway(z_root() . '/admin/accounts' );
	}

	/**
	 * @brief Generate accounts admin page and handle single item operations.
	 *
	 * This function generates the accounts/account admin page and handles the actions
	 * if an icon next to an entry was clicked. If several items were selected and
	 * the form was submitted it is handled by the function admin_page_accounts_post().
	 *
	 * @return string
	 */

	function get(){
		if (argc() > 2) {
			$uid = argv(3);
			$account = q("SELECT * FROM account WHERE account_id = %d",
				intval($uid)
			);
	
			if (! $account) {
				notice( t('Account not found') . EOL);
				goaway(z_root() . '/admin/accounts' );
			}
	
			check_form_security_token_redirectOnErr('/admin/accounts', 'admin_accounts', 't');
	
			switch (argv(2)){
				case 'delete':
					// delete user
					account_remove($uid,true,false);
	
					notice( sprintf(t("Account '%s' deleted"), $account[0]['account_email']) . EOL);
					break;
				case 'block':
					q("UPDATE account SET account_flags = ( account_flags | %d ) WHERE account_id = %d",
						intval(ACCOUNT_BLOCKED),
						intval($uid)
					);
	
					notice( sprintf( t("Account '%s' blocked") , $account[0]['account_email']) . EOL);
					break;
				case 'unblock':
					q("UPDATE account SET account_flags = ( account_flags & ~%d ) WHERE account_id = %d",
							intval(ACCOUNT_BLOCKED),
							intval($uid)
					);
	
					notice( sprintf( t("Account '%s' unblocked"), $account[0]['account_email']) . EOL);
					break;
			}
	
			goaway(z_root() . '/admin/accounts' );
		}
	
		/* get pending */
		$pending = q("SELECT account.*, register.hash from account left join register on account_id = register.uid where (account_flags & %d )>0 ",
			intval(ACCOUNT_PENDING)
		);
	
		/* get accounts */
	
		$total = q("SELECT count(*) as total FROM account");
		if (count($total)) {
			\App::set_pager_total($total[0]['total']);
			\App::set_pager_itemspage(100);
		}
	
		$serviceclass = (($_REQUEST['class']) ? " and account_service_class = '" . dbesc($_REQUEST['class']) . "' " : '');

		$key = (($_REQUEST['key']) ? dbesc($_REQUEST['key']) : 'account_id');
		$dir = 'asc';
		if(array_key_exists('dir',$_REQUEST))
			$dir = ((intval($_REQUEST['dir'])) ? 'asc' : 'desc');

		$base = z_root() . '/admin/accounts?f=';
		$odir = (($dir === 'asc') ? '0' : '1');
	
		$users = q("SELECT account_id , account_email, account_lastlog, account_created, account_expires, account_service_class, ( account_flags & %d ) > 0 as blocked, 
			(SELECT %s FROM channel as ch WHERE ch.channel_account_id = ac.account_id and ch.channel_removed = 0 ) as channels FROM account as ac 
			where true $serviceclass order by $key $dir limit %d offset %d ",
			intval(ACCOUNT_BLOCKED),
			db_concat('ch.channel_address', ' '),
			intval(\App::$pager['itemspage']),
			intval(\App::$pager['start'])
		);
	
	//	function _setup_users($e){
	//		$accounts = Array(
	//			t('Normal Account'), 
	//			t('Soapbox Account'),
	//			t('Community/Celebrity Account'),
	//			t('Automatic Friend Account')
	//		);
	
	//		$e['page_flags'] = $accounts[$e['page-flags']];
	//		$e['register_date'] = relative_date($e['register_date']);
	//		$e['login_date'] = relative_date($e['login_date']);
	//		$e['lastitem_date'] = relative_date($e['lastitem_date']);
	//		return $e;
	//	}
	//	$users = array_map("_setup_users", $users);
	
		$t = get_markup_template('admin_accounts.tpl');
		$o = replace_macros($t, array(
			// strings //
			'$title' => t('Administration'),
			'$page' => t('Accounts'),
			'$submit' => t('Submit'),
			'$select_all' => t('select all'),
			'$h_pending' => t('Registrations waiting for confirm'),
			'$th_pending' => array( t('Request date'), t('Email') ),
			'$no_pending' =>  t('No registrations.'),
			'$approve' => t('Approve'),
			'$deny' => t('Deny'),
			'$delete' => t('Delete'),
			'$block' => t('Block'),
			'$unblock' => t('Unblock'),
			'$odir' => $odir,
			'$base' => $base,
			'$h_users' => t('Accounts'),
			'$th_users' => array( 
				[ t('ID'), 'account_id' ],
				[ t('Email'), 'account_email' ],
				[ t('All Channels'), 'channels' ],
				[ t('Register date'), 'account_created' ],
				[ t('Last login'), 'account_lastlog' ],
				[ t('Expires'), 'account_expires' ],
				[ t('Service Class'), 'account_service_class'] ),
	
			'$confirm_delete_multi' => t('Selected accounts will be deleted!\n\nEverything these accounts had posted on this site will be permanently deleted!\n\nAre you sure?'),
			'$confirm_delete' => t('The account {0} will be deleted!\n\nEverything this account has posted on this site will be permanently deleted!\n\nAre you sure?'),
	
			'$form_security_token' => get_form_security_token("admin_accounts"),
	
			// values //
			'$baseurl' => z_root(),
	
			'$pending' => $pending,
			'$users' => $users,
		));
		$o .= paginate($a);
	
		return $o;
	}
	

}