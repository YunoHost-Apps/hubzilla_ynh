<?php

/**
* Name: jappixmini
* Description: Provides a Facebook-like chat using Jappix Mini
* Version: 1.0.1
* Author: leberwurscht <leberwurscht@hoegners.de>
* Maintainer: none
*/

//
// Copyright 2012 "Leberwurscht" <leberwurscht@hoegners.de>
//
// This file is dual-licensed under the MIT license (see MIT.txt) and the AGPL license (see jappix/COPYING).
//

/*

Problem:
* jabber password should not be stored on server
* jabber password should not be sent between server and browser as soon as the user is logged in
* jabber password should not be reconstructible from communication between server and browser as soon as the user is logged in

Solution:
Only store an encrypted version of the jabber password on the server. The encryption key is only available to the browser
and not to the server (at least as soon as the user is logged in). It can be stored using the jappix setDB function.

This encryption key could be the friendica password, but then this password would be stored in the browser in cleartext.
It is better to use a hash of the password.
The server should not be able to reconstruct the password, so we can't take the same hash the server stores. But we can
 use hash("some_prefix"+password). This will however not work with OpenID logins, for this type of login the password must
be queried manually.

Problem:
How to discover the jabber addresses of the friendica contacts?

Solution:
Each Friendica site with this addon provides a /jappixmini/ module page. We go through our contacts and retrieve
this information every week using a cron hook.

Problem:
We do not want to make the jabber address public.

Solution:
When two friendica users connect using DFRN, the relation gets a DFRN ID and a keypair is generated.
Using this keypair, we can provide the jabber address only to contacts:

Alice:
  signed_address = openssl_*_encrypt(alice_jabber_address)
send signed_address to Bob, who does
  trusted_address = openssl_*_decrypt(signed_address)
  save trusted_address
  encrypted_address = openssl_*_encrypt(bob_jabber_address)
reply with encrypted_address to Alice, who does
  decrypted_address = openssl_*_decrypt(encrypted_address)
  save decrypted_address

Interface for this:
GET /jappixmini/?role=%s&signed_address=%s&dfrn_id=%s

Response:
json({"status":"ok", "encrypted_address":"%s"})

*/

function jappixmini_load() {
register_hook('feature_settings', 'addon/jappixmini/jappixmini.php', 'jappixmini_settings');
register_hook('feature_settings_post', 'addon/jappixmini/jappixmini.php', 'jappixmini_settings_post');

register_hook('page_end', 'addon/jappixmini/jappixmini.php', 'jappixmini_script');
register_hook('authenticate', 'addon/jappixmini/jappixmini.php', 'jappixmini_login');

register_hook('cron', 'addon/jappixmini/jappixmini.php', 'jappixmini_cron');

// Jappix source download as required by AGPL
register_hook('about_hook', 'addon/jappixmini/jappixmini.php', 'jappixmini_download_source');

// set standard configuration
$info_text = get_config("jappixmini", "infotext");
if (!$info_text) set_config("jappixmini", "infotext",
	"To get the chat working, you need to know a BOSH host which works with your Jabber account. ".
	"An example of a BOSH server that works for all accounts is https://bind.jappix.com/, but keep ".
	"in mind that the BOSH server can read along all chat messages. If you know that your Jabber ".
	"server also provides an own BOSH server, it is much better to use this one!"
);

$bosh_proxy = get_config("jappixmini", "bosh_proxy");
if ($bosh_proxy==="") set_config("jappixmini", "bosh_proxy", "1");

// set addon version so that safe updates are possible later
$addon_version = get_config("jappixmini", "version");
if ($addon_version==="") set_config("jappixmini", "version", "1");
}


function jappixmini_unload() {
unregister_hook('feature_settings', 'addon/jappixmini/jappixmini.php', 'jappixmini_settings');
unregister_hook('feature_settings_post', 'addon/jappixmini/jappixmini.php', 'jappixmini_settings_post');

unregister_hook('page_end', 'addon/jappixmini/jappixmini.php', 'jappixmini_script');
unregister_hook('authenticate', 'addon/jappixmini/jappixmini.php', 'jappixmini_login');

unregister_hook('cron', 'addon/jappixmini/jappixmini.php', 'jappixmini_cron');

unregister_hook('about_hook', 'addon/jappixmini/jappixmini.php', 'jappixmini_download_source');
}

function jappixmini_plugin_admin(&$a, &$o) {
	// display instructions and warnings on addon settings page for admin

	if (!file_exists("addon/jappixmini.tgz")) {
		$o .= '<p><strong style="color:#fff;background-color:#f00">The source archive jappixmini.tgz does not exist. This is probably a violation of the Jappix License (AGPL).</strong></p>';
	}

	// warn if cron job has not yet been executed
	$cron_run = get_config("jappixmini", "last_cron_execution");
	if (!$cron_run) $o .= "<p><strong>Warning: The cron job has not yet been executed. If this message is still there after some time (usually 10 minutes), this means that autosubscribe and autoaccept will not work.</strong></p>";

	// bosh proxy
	$bosh_proxy = intval(get_config("jappixmini", "bosh_proxy"));
	$bosh_proxy = intval($bosh_proxy) ? ' checked="checked"' : '';
	$o .= '<label for="jappixmini-proxy">Activate BOSH proxy</label>';
	$o .= ' <input id="jappixmini-proxy" type="checkbox" name="jappixmini-proxy" value="1"'.$bosh_proxy.' /><br />';

	// bosh address
	$bosh_address = get_config("jappixmini", "bosh_address");
	$o .= '<p><label for="jappixmini-address">Adress of the default BOSH proxy. If enabled it overrides the user settings:</label><br />';
        $o .= '<input id="jappixmini-address" type="text" name="jappixmini-address" value="'.$bosh_address.'" /></p>';

	// default server address
	$default_server = get_config("jappixmini", "default_server");
	$o .= '<p><label for="jappixmini-server">Adress of the default jabber server:</label><br />';
        $o .= '<input id="jappixmini-server" type="text" name="jappixmini-server" value="'.$default_server.'" /></p>';

	// default user name to friendica nickname
	$default_user = intval(get_config("jappixmini", "default_user"));
	$default_user = intval($default_user) ? ' checked="checked"' : '';
	$o .= '<label for="jappixmini-user">Set the default username to the nickname:</label>';
	$o .= ' <input id="jappixmini-user" type="checkbox" name="jappixmini-defaultuser" value="1"'.$default_user.' /><br />';

	// info text field
	$info_text = get_config("jappixmini", "infotext");
	$o .= '<p><label for="jappixmini-infotext">Info text to help users with configuration (important if you want to provide your own BOSH host!):</label><br />';
	$o .= '<textarea id="jappixmini-infotext" name="jappixmini-infotext" rows="5" cols="50">'.htmlentities($info_text).'</textarea></p>';

	// submit button
	$o .= '<input type="submit" name="jappixmini-admin-settings" value="OK" />';
}

function jappixmini_plugin_admin_post(&$a) {
	// set info text
	$submit = $_REQUEST['jappixmini-admin-settings'];
	if ($submit) {
		$info_text = $_REQUEST['jappixmini-infotext'];
		$bosh_proxy = intval($_REQUEST['jappixmini-proxy']);
		$default_user = intval($_REQUEST['jappixmini-defaultuser']);
		$bosh_address = $_REQUEST['jappixmini-address'];
		$default_server = $_REQUEST['jappixmini-server'];
		set_config("jappixmini", "infotext", $info_text);
		set_config("jappixmini", "bosh_proxy", $bosh_proxy);
		set_config("jappixmini", "bosh_address", $bosh_address);
		set_config("jappixmini", "default_server", $default_server);
		set_config("jappixmini", "default_user", $default_user);
	}
}

function jappixmini_module() {}
function jappixmini_init(&$a) {


	// module page where other Friendica sites can submit Jabber addresses to and also 
	// can query Jabber addresses of local users

	$address = base64url_decode($_REQUEST['address']);
	$requestor = $_REQUEST['requestor'];
	$requestee = $_REQUEST['requestee'];

	if(! $address || ! $requestor || ! $requestee)
		killme();

	$channel = channelx_by_hash($requestee);
	if (! $channel) 
		killme();

	$r = q("select * from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d
		and abook_self = 0 and xchan_hash = '%s' limit 1",
		intval($channel['channel_id']),
		dbesc($requestor)
	);
	if(! $r)
		killme();

	$req = $r[0];

	// save the Jabber address we received
	try {

		$trusted_address = "";
		openssl_public_decrypt($address, $trusted_address, $req['xchan_pubkey']);

		$now = intval(time());
		set_pconfig($channel['channel_id'], "jappixmini", "id:$requestor", "$now:$trusted_address");
	} catch (Exception $e) {

	}

	// do not return an address if user deactivated plugin
	$activated = get_pconfig($channel['channel_id'], 'jappixmini', 'activate');
	if (!$activated) killme();

	if(! perm_is_allowed($channel['channel_id'],$req['xchan_hash'],'chat'))
		killme();


	// return the requested Jabber address
	try {
		$username = get_pconfig($channel['channel_id'], 'jappixmini', 'username');
		$server = get_pconfig($channel['channel_id'], 'jappixmini', 'server');
		$address = "$username@$server";

		$encrypted_address = "";
		openssl_private_encrypt($address, $encrypted_address, $channel['channel_prvkey']);

		$encoded = base64url_encode($encrypted_address);

		$answer = Array(
			"status"=>"ok",
			"address"=>$encoded
		);

		$answer_json = json_encode($answer);
		echo $answer_json;
		killme();
	} catch (Exception $e) {
		killme();
	}

}

function jappixmini_settings(&$a, &$s) {

	//head_add_css('addon/jappixmini/jappixmini.css');

	// addon settings for a user

	$activate = get_pconfig(local_channel(),'jappixmini','activate');
	$activate = ((intval($activate)) ? 1 : false);

	$dontinsertchat = get_pconfig(local_channel(),'jappixmini','dontinsertchat');
	$insertchat = ((intval($dontinsertchat)) ? 1 : false);

	$defaultbosh = get_config("jappixmini", "bosh_address");

	if ($defaultbosh != "")
		set_pconfig(local_channel(),'jappixmini','bosh', $defaultbosh);

	$username = get_pconfig(local_channel(),'jappixmini','username');
	$username = htmlentities($username);
	$server = get_pconfig(local_channel(),'jappixmini','server');
	$server = htmlentities($server);
	$bosh = get_pconfig(local_channel(),'jappixmini','bosh');
	$bosh = htmlentities($bosh);
	$password = get_pconfig(local_channel(),'jappixmini','password');
	$autosubscribe = get_pconfig(local_channel(),'jappixmini','autosubscribe');
	$autosubscribe = intval($autosubscribe) ? 1 : false;
	$autoapprove = get_pconfig(local_channel(),'jappixmini','autoapprove');
	$autoapprove = intval($autoapprove) ? 1 : false;
	$encrypt = intval(get_pconfig(local_channel(),'jappixmini','encrypt'));
	$encrypt_checked = $encrypt ? 1 : false;
	$encrypt_disabled = $encrypt ? '' : ' disabled="disabled"';

	if ($server == "")
		$server = get_config("jappixmini", "default_server");

	if (($username == "") and get_config("jappixmini", "default_user"))
		$username = App::$user["nickname"];

	$info_text = get_config("jappixmini", "infotext");
	$info_text = htmlentities($info_text);
	$info_text = str_replace("\n", "<br />", $info_text);

	// count contacts
	$r = q("SELECT COUNT(1) as `cnt` FROM `pconfig` WHERE `uid`=%d AND `cat`='jappixmini' AND `k` LIKE 'id:%%'", local_channel());
	if (count($r))
		$contact_cnt = $r[0]["cnt"];
	else
		$contact_cnt = 0;

	// count jabber addresses
	$r = q("SELECT COUNT(1) as `cnt` FROM `pconfig` WHERE `uid`=%d AND `cat`='jappixmini' AND `k` LIKE 'id:%%' AND `v` LIKE '%%@%%'", local_channel());

	if (count($r))
		$address_cnt = $r[0]["cnt"];
	else
		$address_cnt = 0;

	if (!$activate) {
		// load scripts if not yet activated so that password can be saved
		App::$page['htmlhead'] .= '<script type="text/javascript" src="' . z_root() . '/addon/jappixmini/jappix/php/get.php?t=js&amp;g=mini.xml"></script>'."\r\n";
		App::$page['htmlhead'] .= '<script type="text/javascript" src="' . z_root() . '/addon/jappixmini/jappix/php/get.php?t=js&amp;f=presence.js~caps.js~name.js~roster.js"></script>'."\r\n";
		App::$page['htmlhead'] .= '<script type="text/javascript" src="' . z_root() . '/addon/jappixmini/lib.js"></script>'."\r\n";
	}

	$sc .= '<div class="section-content-info-wrapper form-group">';
	$sc .= '<strong>' . t('Status:') . '</strong> Addon knows ' . $address_cnt . ' Jabber addresses of ' . $contact_cnt . ' Hubzilla contacts (takes some time, usually 10 minutes, to update).';
	$sc .= '</div>';

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('jappixmini-activate', t('Activate addon'), $activate, '', array(t('No'),t('Yes')))
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('jappixmini-dont-insertchat', t('Hide Jappixmini Chat-Widget from the webinterface'), $insertchat, '', array(t('No'),t('Yes')))
	));


	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('jappixmini-username', t('Jabber username'), $username, '')
	));



	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('jappixmini-server', t('Jabber server'), $server, '')
	));


	if ($defaultbosh == "") {
		$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
			'$field'	=> array('jappixmini-bosh', t('Jabber BOSH host URL'), $bosh, '')
		));
	}

	$sc .= ' <input type="hidden" id="jappixmini-password" name="jappixmini-encrypted-password" value="'.$password.'" />';

	$sc .= replace_macros(get_markup_template('field_password.tpl'), array(
		'$field'	=> array('jappixmini-password-field', t('Jabber password'), '', '', '', 'onchange="jappixmini_set_password();"')
	));

	$onchange = "document.getElementById('id_jappixmini-friendica-password').disabled = !this.checked;jappixmini_set_password();";

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('jappixmini-encrypt', t('Encrypt Jabber password with Hubzilla password'), $encrypt_checked, t('Recommended'), array(t('No'),t('Yes')), 'onchange="' . $onchange . '"')
	));

	$sc .= replace_macros(get_markup_template('field_password.tpl'), array(
		'$field'	=> array('jappixmini-friendica-password', t('Hubzilla password'), '', '', '', $encrypt_disabled . ' onchange="jappixmini_set_password();"')
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('jappixmini-autoapprove', t('Approve subscription requests from Hubzilla contacts automatically'), $autoapprove, '', array(t('No'),t('Yes')))
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('jappixmini-autosubscribe', t('Approve subscription requests from Hubzilla contacts automatically'), $autosubscribe, '', array(t('No'),t('Yes')))
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('jappixmini-purge', t('Purge internal list of jabber addresses of contacts'), '', '', array(t('No'),t('Yes')))
	));

	if ($info_text) {
		$sc .= '<div class="section-content-warning-wrapper">';
		$sc .= '<strong>' . t('Configuration Help') . '</strong><br>' . $info_text;
		$sc .= '</div>';
	}

	$sc .= ' <button class="btn btn-success pull-right" type="button" onclick="jappixmini_addon_subscribe();">' . t('Add Contact') . '</button>';

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('jappixmini', t('Jappix Mini Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

	App::$page['htmlhead'] .= "<script type=\"text/javascript\">
		function jappixmini_set_password() {
			encrypt = document.getElementById('id_jappixmini-encrypt').checked;
			password = document.getElementById('jappixmini-password');
			clear_password = document.getElementById('id_jappixmini-password-field');
			if (encrypt) {
				friendica_password = document.getElementById('id_jappixmini-friendica-password');

				if (friendica_password) {
					jappixmini_addon_set_client_secret(friendica_password.value);
					jappixmini_addon_encrypt_password(clear_password.value, function(encrypted_password){
						password.value = encrypted_password;
					});
				}
			}
			else {
				password.value = clear_password.value;
			}
		}

		jQuery(document).ready(function() {
			encrypt = document.getElementById('id_jappixmini-encrypt').checked;
			password = document.getElementById('jappixmini-password');
			clear_password = document.getElementById('id_jappixmini-password-field');
			if (encrypt) {
				jappixmini_addon_decrypt_password(password.value, function(decrypted_password){
					clear_password.value = decrypted_password;
				});
			}
			else {
				clear_password.value = password.value;
			}
		});
	</script>";

}

function jappixmini_settings_post(&$a,&$b) {
	// save addon settings for a user

	if(! local_channel())
		return;

	$uid = local_channel();

	$account_id = get_account_id();

	if(! $account_id)
		return;

	if($_POST['jappixmini-submit']) {
		$encrypt = intval($b['jappixmini-encrypt']);
		if ($encrypt) {
			$valid = false;
			// check that Jabber password was encrypted with correct Friendica password
			$friendica_password = trim($b['jappixmini-friendica-password']);

			$x = q("select * from account where account_id = %d limit 1",
				intval($account_id)
			);

			if($x) {
				foreach($x as $record) {
					if(($record['account_flags'] == ACCOUNT_OK) || ($record['account_flags'] == ACCOUNT_UNVERIFIED) && (hash('whirlpool',$record['account_salt'] . $friendica_password) === $record['account_password'])) {
						$valid = true;
						break;
					}
				}
			}

			if (! $valid) {
				info("Hubzilla password not valid.");
				return;
			}
		}

		$purge = intval($b['jappixmini-purge']);

		$username = trim($b['jappixmini-username']);
		$old_username = get_pconfig($uid,'jappixmini','username');
		if ($username!=$old_username)
			$purge = 1;

		$server = trim($b['jappixmini-server']);
		$old_server = get_pconfig($uid,'jappixmini','server');

		if ($server!=$old_server)
			$purge = 1;

		set_pconfig($uid,'jappixmini','username',$username);
		set_pconfig($uid,'jappixmini','server',$server);
		set_pconfig($uid,'jappixmini','bosh',trim($b['jappixmini-bosh']));
		set_pconfig($uid,'jappixmini','password',trim($b['jappixmini-encrypted-password']));
		set_pconfig($uid,'jappixmini','autosubscribe',intval($b['jappixmini-autosubscribe']));
		set_pconfig($uid,'jappixmini','autoapprove',intval($b['jappixmini-autoapprove']));
		set_pconfig($uid,'jappixmini','activate',intval($b['jappixmini-activate']));
		set_pconfig($uid,'jappixmini','dontinsertchat',intval($b['jappixmini-dont-insertchat']));
		set_pconfig($uid,'jappixmini','encrypt',$encrypt);
		info( 'Jappix Mini settings saved.' );

		if ($purge) {
			q("DELETE FROM `pconfig` WHERE `uid`=$uid AND `cat`='jappixmini' AND `k` LIKE 'id:%%'");
			info( 'List of addresses purged.' );
		}
	}
}

function jappixmini_script(&$a,&$s) {
    // adds the script to the page header which starts Jappix Mini

    if(! local_channel()) return;

    $activate = get_pconfig(local_channel(),'jappixmini','activate');
    $dontinsertchat = get_pconfig(local_channel(), 'jappixmini','dontinsertchat');
	if (!$activate or $dontinsertchat) {
		return;
	}
    App::$page['htmlhead'] .= '<script type="text/javascript" src="' . z_root() . '/addon/jappixmini/jappix/php/get.php?t=js&amp;g=mini.xml"></script>'."\r\n";
    App::$page['htmlhead'] .= '<script type="text/javascript" src="' . z_root() . '/addon/jappixmini/jappix/php/get.php?t=js&amp;f=presence.js~caps.js~name.js~roster.js"></script>'."\r\n";

    App::$page['htmlhead'] .= '<script type="text/javascript" src="' . z_root() . '/addon/jappixmini/lib.js"></script>'."\r\n";

    $username = get_pconfig(local_channel(),'jappixmini','username');
    $username = str_replace("'", "\\'", $username);
    $server = get_pconfig(local_channel(),'jappixmini','server');
    $server = str_replace("'", "\\'", $server);
    $bosh = get_pconfig(local_channel(),'jappixmini','bosh');
    $bosh = str_replace("'", "\\'", $bosh);
    $encrypt = get_pconfig(local_channel(),'jappixmini','encrypt');
    $encrypt = intval($encrypt);
    $password = get_pconfig(local_channel(),'jappixmini','password');
    $password = str_replace("'", "\\'", $password);

    $autoapprove = get_pconfig(local_channel(),'jappixmini','autoapprove');
    $autoapprove = intval($autoapprove);
    $autosubscribe = get_pconfig(local_channel(),'jappixmini','autosubscribe');
    $autosubscribe = intval($autosubscribe);

    // set proxy if necessary
    $use_proxy = get_config('jappixmini','bosh_proxy');
    if ($use_proxy) {
        $proxy = z_root().'/addon/jappixmini/proxy.php';
    }
    else {
        $proxy = "";
    }

    // get a list of jabber accounts of the contacts
    $contacts = Array();
    $uid = local_channel();
    $rows = q("SELECT * FROM `pconfig` WHERE `uid`=$uid AND `cat`='jappixmini' AND `k` LIKE 'id:%%'");
    foreach ($rows as $row) {
        $key = $row['k'];
		$pos = strpos($key, ":");
		$dfrn_id = substr($key, $pos+1);
        $r = q("SELECT xchan_name FROM xchan WHERE xchan_hash = '%s' limit 1",
			dbesc($dfrn_id)
		);
		$name = $r[0]["xchan_name"];

        $value = $row['v'];
        $pos = strpos($value, ":");
        $address = substr($value, $pos+1);
		if (!$address) continue;
		if (!$name) $name = $address;

		$contacts[$address] = $name;
    }

    $contacts_json = json_encode($contacts);
    $contacts_hash = sha1($contacts_json);

    // get nickname
    $r = q("SELECT `channel_address` FROM `channel` WHERE `channel_id`=$uid");
    $nickname = json_encode($r[0]["channel_address"]);
    $groupchats = get_config('jappixmini','groupchats');
    //if $groupchats has no value jappix_addon_start will produce a syntax error
    if(empty($groupchats)){
    	$groupchats = "{}";
    }

    // add javascript to start Jappix Mini
    App::$page['htmlhead'] .= "<script type=\"text/javascript\">
        jQuery(document).ready(function() {
           jappixmini_addon_start('$server', '$username', '$proxy', '$bosh', $encrypt, '$password', $nickname, $contacts_json, '$contacts_hash', $autoapprove, $autosubscribe, $groupchats);
        });
    </script>";

    return;
}

function jappixmini_login(&$a, &$o) {
    // create client secret on login to be able to encrypt jabber passwords

    // for setDB and str_sha1, needed by jappixmini_addon_set_client_secret
    App::$page['htmlhead'] .= '<script type="text/javascript" src="' . z_root() . '/addon/jappixmini/jappix/php/get.php?t=js&amp;f=datastore.js~jsjac.js"></script>'."\r\n";

    // for jappixmini_addon_set_client_secret
    App::$page['htmlhead'] .= '<script type="text/javascript" src="' . z_root() . '/addon/jappixmini/lib.js"></script>'."\r\n";

    // save hash of password
    $o = str_replace("<form ", "<form onsubmit=\"jappixmini_addon_set_client_secret(this.elements['id_password'].value);return true;\" ", $o);
}

function jappixmini_cron(&$a, $d) {


	// For autosubscribe/autoapprove, we need to maintain a list of jabber addresses of our contacts.

	set_config("jappixmini", "last_cron_execution", $d);

	// go through list of users with jabber enabled

	$users = q("SELECT uid FROM pconfig WHERE cat = 'jappixmini' AND ( k = 'autosubscribe' OR k = 'autoapprove') AND v = '1' group by uid ");

	logger("jappixmini: Update list of contacts' jabber accounts for ".count($users)." users.");

	if(! count($users))
		return;

	foreach ($users as $row) {
		$uid = $row["uid"];

		// for each user, go through list of contacts
		$rand = db_getfunc('rand');
		$contacts = q("SELECT * FROM `abook` left join xchan on abook_xchan = xchan_hash WHERE `abook_channel`=%d AND abook_self = 0 order by $rand",
			intval($uid)
		);

		$channel = channelx_by_n($uid);
		if((! $channel) || (! $contacts))
			continue;

		foreach ($contacts as $contact_row) {

			$xchan_hash = $contact_row["abook_xchan"];
			$pubkey = $contact_row["xchan_pubkey"];
	

			// check if jabber address already present
			$present = get_pconfig($uid, "jappixmini", "id:" . $xchan_hash);
			$now = intval(time());
			if ($present) {
				// $present has format "timestamp:jabber_address"
				$p = strpos($present, ":");
				$timestamp = intval(substr($present, 0, $p));

				// do not re-retrieve jabber address if last retrieval
				// is not older than a week
				if ($now-$timestamp<3600*24*7)
					continue;
			}

			logger('jappixmini: checking ' . $contact_row['xchan_name'] . ' for channel ' . $channel['channel_name']);

			// construct base retrieval address
			$pos = strpos($contact_row['xchan_connurl'], "/poco/");
			if($pos===false) 
				continue;

			$url = substr($contact_row['xchan_connurl'], 0, $pos)."/jappixmini?f=";

			// construct own address
			$username = get_pconfig($uid, 'jappixmini', 'username');
			if (!$username) continue;
			$server = get_pconfig($uid, 'jappixmini', 'server');
			if (!$server) continue;

			$address = $username."@".$server;

			// sign address
			$signed_address = "";
			openssl_private_encrypt($address, $signed_address, $channel['channel_prvkey']);

			// construct request url
			$signed_address_hex = base64url_encode($signed_address);
			
			$postvars = array(
				'address' => $signed_address,
				'requestor' => $channel['xchan_hash'],
				'requestee' => $contact_row['xchan_hash']
			);


			try {
				// send request
				$answer_json = z_post_url($url,$postvars);
logger('jappixmini: url response: ' . print_r($answer_json,true));
				if(! $answer_json['success']) {
					logger('jappixmini: failed z_post_url ' . $url);
					throw new Exception();
				}
				if($answer_json['return_code'] == 404) {
					logger('jappixmini: failed z_post_url (404)' . $url);
					throw new Exception();
				}

				// parse answer
				$answer = json_decode($answer_json['body'],true);
				if ($answer['status'] != "ok") 
					throw new Exception();

				$address = base64url_decode($answer['address']);
				if (! $address)
					throw new Exception();

				// decrypt address
				$decrypted_address = "";
				openssl_public_decrypt($address, $decrypted_address, $pubkey);
				if (!$decrypted_address) 
					throw new Exception();
			} catch (Exception $e) {
				$decrypted_address = "";
			}
			
			// save address
			set_pconfig($uid, "jappixmini", "id:" . $xchan_hash, "$now:$decrypted_address");
		}
	}
}

function jappixmini_download_source(&$a,&$b) {
	// Jappix Mini source download link on About page

	$b .= '<h1>Jappix Mini</h1>';
	$b .= '<p>This site uses the jappixmini addon, which includes Jappix Mini by the <a href="'.z_root().'/addon/jappixmini/jappix/AUTHORS">Jappix authors</a> and is distributed under the terms of the <a href="'.z_root().'/addon/jappixmini/jappix/COPYING">GNU Affero General Public License</a>.</p>';
	$b .= '<p>You can download the <a href="'.z_root().'/addon/jappixmini.tgz">source code of the addon</a>. The rest of Hubzilla is distributed under compatible licenses and can be retrieved from <a href="https://github.com/friendica/red">https://github.com/friendica/red</a> and <a href="https://github.com/friendica/red-addons">https://github.com/friendica/red-addons</a></p>';
}
