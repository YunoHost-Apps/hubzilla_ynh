<?php

namespace Zotlabs\Module\Admin;

/**
 * @brief Admin Module for Channels.
 *
 */
class Channels {

	/**
	 * @brief Handle POST actions on channels admin page.
	 *
	 */
	function post() {

		$channels = ( x($_POST, 'channel') ? $_POST['channel'] : Array() );

		check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels');

		$xor = db_getfunc('^');

		if(x($_POST, 'page_channels_block')) {
			foreach($channels as $uid) {
				q("UPDATE channel SET channel_pageflags = ( channel_pageflags $xor %d ) where channel_id = %d",
					intval(PAGE_CENSORED),
					intval( $uid )
				);
				\Zotlabs\Daemon\Master::Summon(array('Directory', $uid, 'nopush'));
			}
			notice( sprintf( tt("%s channel censored/uncensored", "%s channels censored/uncensored", count($channels)), count($channels)) );
		}
		if(x($_POST, 'page_channels_code')) {
			foreach($channels as $uid) {
				q("UPDATE channel SET channel_pageflags = ( channel_pageflags $xor %d ) where channel_id = %d",
					intval(PAGE_ALLOWCODE),
					intval( $uid )
				);
			}
			notice( sprintf( tt("%s channel code allowed/disallowed", "%s channels code allowed/disallowed", count($channels)), count($channels)) );
		}
		if(x($_POST, 'page_channels_delete')) {
			foreach($channels as $uid) {
				channel_remove($uid, true);
			}
			notice( sprintf( tt("%s channel deleted", "%s channels deleted", count($channels)), count($channels)) );
		}

		goaway(z_root() . '/admin/channels' );
	}

	/**
	 * @brief Generate channels admin page and handle single item operations.
	 *
	 * @return string with parsed HTML
	 */
	function get() {
		if(argc() > 2) {
			$uid = argv(3);
			$channel = q("SELECT * FROM channel WHERE channel_id = %d",
				intval($uid)
			);

			if(! $channel) {
				notice( t('Channel not found') . EOL);
				goaway(z_root() . '/admin/channels' );
			}

			switch(argv(2)) {
				case "delete":{
					check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels', 't');
					// delete channel
					channel_remove($uid,true);

					notice( sprintf(t("Channel '%s' deleted"), $channel[0]['channel_name']) . EOL);
				}; break;

				case "block":{
					check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels', 't');
					$pflags = $channel[0]['channel_pageflags'] ^ PAGE_CENSORED;
					q("UPDATE channel SET channel_pageflags = %d where channel_id = %d",
						intval($pflags),
						intval( $uid )
					);
					\Zotlabs\Daemon\Master::Summon(array('Directory',$uid,'nopush'));

					notice( sprintf( (($pflags & PAGE_CENSORED) ? t("Channel '%s' censored"): t("Channel '%s' uncensored")) , $channel[0]['channel_name'] . ' (' . $channel[0]['channel_address'] . ')' ) . EOL);
				}; break;

				case "code":{
					check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels', 't');
					$pflags = $channel[0]['channel_pageflags'] ^ PAGE_ALLOWCODE;
					q("UPDATE channel SET channel_pageflags = %d where channel_id = %d",
						intval($pflags),
						intval( $uid )
					);

					notice( sprintf( (($pflags & PAGE_ALLOWCODE) ? t("Channel '%s' code allowed"): t("Channel '%s' code disallowed")) , $channel[0]['channel_name'] . ' (' . $channel[0]['channel_address'] . ')' ) . EOL);
				}; break;

				default:
					break;
			}
			goaway(z_root() . '/admin/channels' );
		}

		$key = (($_REQUEST['key']) ? dbesc($_REQUEST['key']) : 'channel_id');
		$dir = 'asc';
		if(array_key_exists('dir',$_REQUEST))
			$dir = ((intval($_REQUEST['dir'])) ? 'asc' : 'desc');

		$base = z_root() . '/admin/channels?f=';
		$odir = (($dir === 'asc') ? '0' : '1');

		/* get channels */

		$total = q("SELECT count(*) as total FROM channel where channel_removed = 0 and channel_system = 0");
		if($total) {
			\App::set_pager_total($total[0]['total']);
			\App::set_pager_itemspage(100);
		}

		$channels = q("SELECT * from channel where channel_removed = 0 and channel_system = 0 order by $key $dir limit %d offset %d ",
			intval(\App::$pager['itemspage']),
			intval(\App::$pager['start'])
		);

		if($channels) {
			for($x = 0; $x < count($channels); $x ++) {
				if($channels[$x]['channel_pageflags'] & PAGE_CENSORED)
					$channels[$x]['blocked'] = true;
				else
					$channels[$x]['blocked'] = false;

				if($channels[$x]['channel_pageflags'] & PAGE_ALLOWCODE)
					$channels[$x]['allowcode'] = true;
				else
					$channels[$x]['allowcode'] = false;
			}
		}

		$t = get_markup_template('admin_channels.tpl');
		$o = replace_macros($t, array(
			// strings //
			'$title' => t('Administration'),
			'$page' => t('Channels'),
			'$submit' => t('Submit'),
			'$select_all' => t('select all'),
			'$delete' => t('Delete'),
			'$block' => t('Censor'),
			'$unblock' => t('Uncensor'),
			'$code' => t('Allow Code'),
			'$uncode' => t('Disallow Code'),
			'$h_channels' => t('Channel'),
			'$base' => $base,
			'$odir' => $odir,
			'$th_channels' => array(
					[ t('UID'), 'channel_id' ],
					[ t('Name'), 'channel_name' ],
					[ t('Address'), 'channel_address' ]),

			'$confirm_delete_multi' => t('Selected channels will be deleted!\n\nEverything that was posted in these channels on this site will be permanently deleted!\n\nAre you sure?'),
			'$confirm_delete' => t('The channel {0} will be deleted!\n\nEverything that was posted in this channel on this site will be permanently deleted!\n\nAre you sure?'),

			'$form_security_token' => get_form_security_token('admin_channels'),

			// values //
			'$baseurl' => z_root(),
			'$channels' => $channels,
		));
		$o .= paginate($a);

		return $o;
	}

}