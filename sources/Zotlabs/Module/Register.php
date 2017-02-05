<?php
namespace Zotlabs\Module;

require_once('include/channel.php');


class Register extends \Zotlabs\Web\Controller {

	function init() {
	
		$result = null;
		$cmd = ((argc() > 1) ? argv(1) : '');
	
		// Provide a stored request for somebody desiring a connection
		// when they first need to register someplace. Once they've
		// created a channel, we'll try to revive the connection request 
		// and process it.
	
		if($_REQUEST['connect'])
			$_SESSION['connect'] = $_REQUEST['connect'];
	
		switch($cmd) {
			case 'invite_check.json':
				$result = check_account_invite($_REQUEST['invite_code']);
				break;
			case 'email_check.json':
				$result = check_account_email($_REQUEST['email']);
				break;
			case 'password_check.json':
				$result = check_account_password($_REQUEST['password']);
				break;
			default: 
				break;
		}
		if($result) {
			json_return_and_die($result);
		}
	}
	
	
	function post() {
	
		$max_dailies = intval(get_config('system','max_daily_registrations'));
		if($max_dailies) {
			$r = q("select count(account_id) as total from account where account_created > %s - INTERVAL %s",
				db_utcnow(), db_quoteinterval('1 day')
			);
			if($r && $r[0]['total'] >= $max_dailies) {
				notice( t('Maximum daily site registrations exceeded. Please try again tomorrow.') . EOL);
				return;
			}
		}
	
		if(! x($_POST,'tos')) {
			notice( t('Please indicate acceptance of the Terms of Service. Registration failed.') . EOL);
			return;
		}
	
		$policy = get_config('system','register_policy');
	
		$email_verify = get_config('system','verify_email');
	
	
		switch($policy) {
	
			case REGISTER_OPEN:
				$flags = ACCOUNT_OK;
				break;
	
			case REGISTER_APPROVE:
				$flags = ACCOUNT_BLOCKED | ACCOUNT_PENDING;
				break;
	
			default:
			case REGISTER_CLOSED:
				if(! is_site_admin()) {
					notice( t('Permission denied.') . EOL );
					return;
				}
				$flags = ACCOUNT_BLOCKED;
				break;
		}
	
		if($email_verify && $policy == REGISTER_OPEN)
			$flags = $flags | ACCOUNT_UNVERIFIED;
			
	
		if((! $_POST['password']) || ($_POST['password'] !== $_POST['password2'])) {
			notice( t('Passwords do not match.') . EOL);
			return;
		}
	
		$arr = $_POST;
		$arr['account_flags'] = $flags;
	
		$result = create_account($arr);
	
		if(! $result['success']) {
			notice($result['message']);
			return;
		}
		require_once('include/security.php');
	
	
		if($_REQUEST['name'])
			set_aconfig($result['account']['account_id'],'register','channel_name',$_REQUEST['name']);
		if($_REQUEST['nickname'])
			set_aconfig($result['account']['account_id'],'register','channel_address',$_REQUEST['nickname']);
		if($_REQUEST['permissions_role'])
			set_aconfig($result['account']['account_id'],'register','permissions_role',$_REQUEST['permissions_role']);
	
	
	 	$using_invites = intval(get_config('system','invitation_only'));
		$num_invites   = intval(get_config('system','number_invites'));
		$invite_code   = ((x($_POST,'invite_code'))  ? notags(trim($_POST['invite_code']))  : '');
	
		if($using_invites && $invite_code) {
			q("delete * from register where hash = '%s'", dbesc($invite_code));
			// @FIXME - this also needs to be considered when using 'invites_remaining' in mod/invite.php
			set_aconfig($result['account']['account_id'],'system','invites_remaining',$num_invites);
		}
	
		if($policy == REGISTER_OPEN ) {
			if($email_verify) {
				$res = verify_email_address($result);
			}
			else {
				$res = send_register_success_email($result['email'],$result['password']);
			}
			if($res) {
				info( t('Registration successful. Please check your email for validation instructions.') . EOL ) ;
			}
		}
		elseif($policy == REGISTER_APPROVE) {
			$res = send_reg_approval_email($result);
			if($res) {
				info( t('Your registration is pending approval by the site owner.') . EOL ) ;
			}
			else {
				notice( t('Your registration can not be processed.') . EOL);
			}
			goaway(z_root());
		}
	
		if($email_verify) {
			goaway(z_root());
		}
	
		authenticate_success($result['account'],null,true,false,true);
		
		$new_channel = false;
		$next_page = 'new_channel';
	
		if(get_config('system','auto_channel_create') || get_config('system','server_role') == 'basic') {
			$new_channel = auto_channel_create($result['account']['account_id']);
			if($new_channel['success']) {
				$channel_id = $new_channel['channel']['channel_id'];
				change_channel($channel_id);
				$next_page = '~';
			}
			else
				$new_channel = false;
		}
	
		$x = get_config('system','workflow_register_next');
		if($x) {
			$next_page = $x;
			$_SESSION['workflow'] = true;
		}
	
		goaway(z_root() . '/' . $next_page);
	
	}
	
	
	
	function get() {
	
		$registration_is = '';
		$other_sites = '';
	
		if(get_config('system','register_policy') == REGISTER_CLOSED) {
			if(get_config('system','directory_mode') == DIRECTORY_MODE_STANDALONE) {
				notice( t('Registration on this hub is disabled.')  . EOL);
				return;
			}

			$mod = new Pubsites();	
			return $mod->get();
		}
	
		if(get_config('system','register_policy') == REGISTER_APPROVE) {
			$registration_is = t('Registration on this hub is by approval only.');
			$other_sites = t('<a href="pubsites">Register at another affiliated hub.</a>');
		}
	
		$max_dailies = intval(get_config('system','max_daily_registrations'));
		if($max_dailies) {
			$r = q("select count(account_id) as total from account where account_created > %s - INTERVAL %s",
				db_utcnow(), db_quoteinterval('1 day')
			);
			if($r && $r[0]['total'] >= $max_dailies) {
				logger('max daily registrations exceeded.');
				notice( t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.') . EOL);
				return;
			}
		}

		$privacy_role = ((x($_REQUEST,'permissions_role')) ? $_REQUEST['permissions_role'] : "");

		$perm_roles = \Zotlabs\Access\PermissionRoles::roles();
		if((get_account_techlevel() < 4) && $privacy_role !== 'custom')
			unset($perm_roles[t('Other')]);
	
		// Configurable terms of service link
	
		$tosurl = get_config('system','tos_url');
		if(! $tosurl)
			$tosurl = z_root() . '/help/TermsOfService';
	
		$toslink = '<a href="' . $tosurl . '" target="_blank">' . t('Terms of Service') . '</a>';
	
		// Configurable whether to restrict age or not - default is based on international legal requirements
		// This can be relaxed if you are on a restricted server that does not share with public servers
	
		if(get_config('system','no_age_restriction')) 
			$label_tos = sprintf( t('I accept the %s for this website'), $toslink);
		else
			$label_tos = sprintf( t('I am over 13 years of age and accept the %s for this website'), $toslink);
	
		$enable_tos = 1 - intval(get_config('system','no_termsofservice'));
	
		$email        = array('email', t('Your email address'), ((x($_REQUEST,'email')) ? strip_tags(trim($_REQUEST['email'])) : ""));
		$password     = array('password', t('Choose a password'), ((x($_REQUEST,'password')) ? trim($_REQUEST['password']) : ""));
		$password2    = array('password2', t('Please re-enter your password'), ((x($_REQUEST,'password2')) ? trim($_REQUEST['password2']) : ""));
		$invite_code  = array('invite_code', t('Please enter your invitation code'), ((x($_REQUEST,'invite_code')) ? strip_tags(trim($_REQUEST['invite_code'])) : ""));
		$name = array('name', t('Name or caption'), ((x($_REQUEST,'name')) ? $_REQUEST['name'] : ''), t('Examples: "Bob Jameson", "Lisa and her Horses", "Soccer", "Aviation Group"'));
		$nickhub = '@' . str_replace(array('http://','https://','/'), '', get_config('system','baseurl'));
		$nickname = array('nickname', t('Choose a short nickname'), ((x($_REQUEST,'nickname')) ? $_REQUEST['nickname'] : ''), sprintf( t('Your nickname will be used to create an easy to remember channel address e.g. nickname%s'), $nickhub));
		$role = array('permissions_role' , t('Channel role and privacy'), ($privacy_role) ? $privacy_role : 'social', t('Select a channel role with your privacy requirements.') . ' <a href="help/roles" target="_blank">' . t('Read more about roles') . '</a>',$perm_roles);
		$tos = array('tos', $label_tos, '', '', array(t('no'),t('yes')));

		$server_role = get_config('system','server_role');	


		$auto_create  = (($server_role == 'basic') || (get_config('system','auto_channel_create')) ? true : false);
		$default_role = (($server_role == 'basic') ? 'social' : get_config('system','default_permissions_role'));
	
		require_once('include/bbcode.php');
	
		$o = replace_macros(get_markup_template('register.tpl'), array(
	
			'$title'        => t('Registration'),
			'$reg_is'       => $registration_is,
			'$registertext' => bbcode(get_config('system','register_text')),
			'$other_sites'  => $other_sites,
			'$invitations'  => get_config('system','invitation_only'),
			'$invite_desc'  => t('Membership on this site is by invitation only.'),
			'$invite_code'  => $invite_code,
			'$auto_create'  => $auto_create,
			'$name'         => $name,
			'$role'         => $role,
			'$default_role' => $default_role,
			'$nickname'     => $nickname,
			'$enable_tos'	=> $enable_tos,
			'$tos'          => $tos,
			'$email'        => $email,
			'$pass1'        => $password,
			'$pass2'        => $password2,
			'$submit'       => t('Register'),
			'$verify_note'  => t('This site may require email verification after submitting this form. If you are returned to a login page, please check your email for instructions.')
		));
	
		return $o;
	
	}
	
	
}
