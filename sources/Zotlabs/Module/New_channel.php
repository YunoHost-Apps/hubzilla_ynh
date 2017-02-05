<?php
namespace Zotlabs\Module;

require_once('include/channel.php');
require_once('include/permissions.php');



class New_channel extends \Zotlabs\Web\Controller {

	function init() {
	
		$cmd = ((argc() > 1) ? argv(1) : '');
	
		if($cmd === 'autofill.json') {
			require_once('library/urlify/URLify.php');
			$result = array('error' => false, 'message' => '');
			$n = trim($_REQUEST['name']);
	
			$x = strtolower(\URLify::transliterate($n));
	
			$test = array();
	
			// first name
			if(strpos($x,' '))
				$test[] = legal_webbie(substr($x,0,strpos($x,' ')));
			if($test[0]) {
				// first name plus first initial of last
				$test[] = ((strpos($x,' ')) ? $test[0] . legal_webbie(trim(substr($x,strpos($x,' '),2))) : '');
				// first name plus random number
				$test[] = $test[0] . mt_rand(1000,9999);
			}
			// fullname
			$test[] = legal_webbie($x);
			// fullname plus random number
			$test[] = legal_webbie($x) . mt_rand(1000,9999);
	
			json_return_and_die(check_webbie($test));
		}
	
		if($cmd === 'checkaddr.json') {
			require_once('library/urlify/URLify.php');
			$result = array('error' => false, 'message' => '');
			$n = trim($_REQUEST['nick']);
	
			$x = strtolower(\URLify::transliterate($n));
	
			$test = array();
	
			$n = legal_webbie($x);
			if(strlen($n)) {
				$test[] = $n;
				$test[] = $n . mt_rand(1000,9999);
			}
	
			for($y = 0; $y < 100; $y ++)
				$test[] = 'id' . mt_rand(1000,9999);
	
			json_return_and_die(check_webbie($test));
		}
	
	
	}
	
	function post() {
	
		$arr = $_POST;
	
		$acc = \App::get_account();
		$arr['account_id'] = get_account_id();
	
		// prevent execution by delegated channels as well as those not logged in. 
		// get_account_id() returns the account_id from the session. But \App::$account
		// may point to the original authenticated account. 
	
		if((! $acc) || ($acc['account_id'] != $arr['account_id'])) {
			notice( t('Permission denied.') . EOL );
			return;
		}
	
		$result = create_identity($arr);
	
		if(! $result['success']) {
			notice($result['message']);
			return;
		}
	
		$newuid = $result['channel']['channel_id'];
	
		change_channel($result['channel']['channel_id']);
	
		if(! strlen($next_page = get_config('system','workflow_channel_next')))
			$next_page = 'settings';
		
		goaway(z_root() . '/' . $next_page);
	
	}
	
	function get() {
	
		$acc = \App::get_account();
	
		if((! $acc) || $acc['account_id'] != get_account_id()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
		$default_role = '';
		$aid = get_account_id();
		if($aid) {
			$r = q("select count(channel_id) as total from channel where channel_account_id = %d",
				intval($aid)
			);
			if($r && (! intval($r[0]['total']))) {
				$default_role = get_config('system','default_permissions_role');
			}
	
			$limit = account_service_class_fetch(get_account_id(),'total_identities');
	
			if($r && ($limit !== false)) {
				$channel_usage_message = sprintf( t("You have created %1$.0f of %2$.0f allowed channels."), $r[0]['total'], $limit);
			}
			else {
				$channel_usage_message = '';
			}
		}
	
		$privacy_role = ((x($_REQUEST,'permissions_role')) ? $_REQUEST['permissions_role'] :  "" );

		$perm_roles = \Zotlabs\Access\PermissionRoles::roles();
		if((get_account_techlevel() < 4) && $privacy_role !== 'custom')
			unset($perm_roles[t('Other')]);

		$name = array('name', t('Name or caption'), ((x($_REQUEST,'name')) ? $_REQUEST['name'] : ''), t('Examples: "Bob Jameson", "Lisa and her Horses", "Soccer", "Aviation Group"'), "*");
		$nickhub = '@' . \App::get_hostname();
		$nickname = array('nickname', t('Choose a short nickname'), ((x($_REQUEST,'nickname')) ? $_REQUEST['nickname'] : ''), sprintf( t('Your nickname will be used to create an easy to remember channel address e.g. nickname%s'), $nickhub), "*");
		$role = array('permissions_role' , t('Channel role and privacy'), ($privacy_role) ? $privacy_role : 'social', t('Select a channel role with your privacy requirements.') . ' <a href="help/roles" target="_blank">' . t('Read more about roles') . '</a>',$perm_roles);
	
		$o = replace_macros(get_markup_template('new_channel.tpl'), array(
			'$title'        => t('Create Channel'),
			'$desc'         => t('A channel is your identity on this network. It can represent a person, a blog, or a forum to name a few. Channels can make connections with other channels to share information with highly detailed permissions.'),
			'$label_import' => t('or <a href="import">import an existing channel</a> from another location.'),
			'$name'         => $name,
			'$role'		=> $role,
			'$default_role' => $default_role,
			'$nickname'     => $nickname,
			'$submit'       => t('Create'),
			'$channel_usage_message' => $channel_usage_message
		));
	
		return $o;
	
	}
	
	
}
