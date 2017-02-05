<?php

/**
 * Name: testdrive
 * Description: Sample Hubzilla plugin/addon for creating a test drive site with automatic account expiration.
 * Version: 1.1
 * Author: Mike Macgirvin <https://macgirvin.com/channel/mike>
 * Maintainer: none
 */




function testdrive_install() {

	register_hook('register_account', 'addon/testdrive/testdrive.php', 'testdrive_register_account');
	register_hook('cron_daily', 'addon/testdrive/testdrive.php', 'testdrive_cron');
	register_hook('enotify','addon/testdrive/testdrive.php', 'testdrive_enotify');

}


function testdrive_uninstall() {

	unregister_hook('register_account', 'addon/testdrive/testdrive.php', 'testdrive_register_account');
	unregister_hook('cron_daily', 'addon/testdrive/testdrive.php', 'testdrive_cron');
	unregister_hook('enotify','addon/testdrive/testdrive.php', 'testdrive_enotify');

}

function testdrive_register_account($a,$b) {

	$aid = $b['account_id'];

	$days = get_config('testdrive','expiredays');
	if(! $days)
		return;

	$r = q("UPDATE account set account_expires_on = '%s' where account_id = %d",
		dbesc(datetime_convert('UTC','UTC','now +' . $days . ' days')),
		intval($aid)
	);

};


function testdrive_cron($a,$b) {

	$r = q("select * from account where account_expires_on < %s + INTERVAL %s and
		account_expire_notified <= '%s' ",
		db_utcnow(), 
		db_quoteinterval('5 DAY'),
		dbesc(NULL_DATE)	
	);


	if($r) {
		foreach($r as $rr) {

			$uid  = $rr['account_default_channel']; 
			if(! $uid)
				continue;

			$x = q("select * from channel where channel_id = %d limit 1",
				intval($uid)
			);

			if(! $x)
				continue;

			\Zotlabs\Lib\Enotify::submit(array(
				'type' => NOTIFY_SYSTEM,
				'system_type'  => 'testdrive_expire',
				'from_xchan'   => $x[0]['channel_hash'],
				'to_xchan'     => $x[0]['channel_hash'],
			));

			q("update account set account_expire_notified = '%s' where account_id = %d",
				dbesc(datetime_convert()),
				intval($rr['account_id'])
			);

		}
	}

	// give them a 5 day grace period. Then nuke the account. 

	$r = q("select * from account where account_expired = 1 and account_expires < %s - INTERVAL %s",
		db_utcnow(),
		db_quoteinterval('5 DAY')
	);

	if($r) {
		foreach($r as $rr)
			account_remove($rr['account_id']);
	}

}		

function testdrive_enotify(&$a, &$b) {
    if (x($b, 'params') && $b['params']['type'] == NOTIFY_SYSTEM 
		&& x($b['params'], 'system_type') && $b['params']['system_type'] === 'testdrive_expire') {
        $b['itemlink'] = z_root();
        $b['epreamble'] = $b['preamble'] = sprintf( t('Your account on %s will expire in a few days.'), get_config('system','sitename'));
        $b['subject'] = t('Your $Productname test account is about to expire.');
        $b['body'] = sprintf( t("Hi %1\$s,\n\nYour test account on %2\$s will expire in less than five days. We hope you enjoyed this test drive and use this opportunity to find or install a permanent hub and migrate your account to it. A list of public hubs is available at https://hubzilla.org/pubsites - and for more information on setting up your own $Projectname hub please see the project website at https://github.com/redmatrix/hubzilla ."), $b['recipient']['xchan_name'], "[url=" . z_root() . "]" . $b['sitename'] . "[/url]");
    }
}
