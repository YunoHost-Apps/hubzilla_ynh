<?php

namespace Zotlabs\Module\Settings;


class Oauth {


	function post() {
	
		if(x($_POST,'remove')){
			check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth');
			
			$key = $_POST['remove'];
			q("DELETE FROM tokens WHERE id='%s' AND uid=%d",
				dbesc($key),
				local_channel());
			goaway(z_root()."/settings/oauth/");
			return;			
		}
	
		if((argc() > 2) && (argv(2) === 'edit' || argv(2) === 'add') && x($_POST,'submit')) {
			
			check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth');
			
			$name   	= ((x($_POST,'name')) ? $_POST['name'] : '');
			$key		= ((x($_POST,'key')) ? $_POST['key'] : '');
			$secret		= ((x($_POST,'secret')) ? $_POST['secret'] : '');
			$redirect	= ((x($_POST,'redirect')) ? $_POST['redirect'] : '');
			$icon		= ((x($_POST,'icon')) ? $_POST['icon'] : '');
			$ok = true;
			if($name == '') {
				$ok = false;
				notice( t('Name is required') . EOL);
			}
			if($key == '' || $secret == '') {
				$ok = false;
				notice( t('Key and Secret are required') . EOL);
			}
		
			if($ok) {
				if ($_POST['submit']==t("Update")){
					$r = q("UPDATE clients SET
								client_id='%s',
								pw='%s',
								clname='%s',
								redirect_uri='%s',
								icon='%s',
								uid=%d
							WHERE client_id='%s'",
							dbesc($key),
							dbesc($secret),
							dbesc($name),
							dbesc($redirect),
							dbesc($icon),
							intval(local_channel()),
							dbesc($key));
				} else {
					$r = q("INSERT INTO clients (client_id, pw, clname, redirect_uri, icon, uid)
						VALUES ('%s','%s','%s','%s','%s',%d)",
						dbesc($key),
						dbesc($secret),
						dbesc($name),
						dbesc($redirect),
						dbesc($icon),
						intval(local_channel())
					);
					$r = q("INSERT INTO xperm (xp_client, xp_channel, xp_perm) VALUES ('%s', %d, '%s') ",
						dbesc($key),
						intval(local_channel()),
						dbesc('all')
					);
				}
			}
			goaway(z_root()."/settings/oauth/");
			return;
		}
	}

	function get() {
			
		if((argc() > 2) && (argv(2) === 'add')) {
			$tpl = get_markup_template("settings_oauth_edit.tpl");
			$o .= replace_macros($tpl, array(
				'$form_security_token' => get_form_security_token("settings_oauth"),
				'$title'	=> t('Add application'),
				'$submit'	=> t('Submit'),
				'$cancel'	=> t('Cancel'),
				'$name'		=> array('name', t('Name'), '', t('Name of application')),
				'$key'		=> array('key', t('Consumer Key'), random_string(16), t('Automatically generated - change if desired. Max length 20')),
				'$secret'	=> array('secret', t('Consumer Secret'), random_string(16), t('Automatically generated - change if desired. Max length 20')),
				'$redirect'	=> array('redirect', t('Redirect'), '', t('Redirect URI - leave blank unless your application specifically requires this')),
				'$icon'		=> array('icon', t('Icon url'), '', t('Optional')),
			));
			return $o;
		}
			
		if((argc() > 3) && (argv(2) === 'edit')) {
			$r = q("SELECT * FROM clients WHERE client_id='%s' AND uid=%d",
					dbesc(argv(3)),
					local_channel());
			
			if (!count($r)){
				notice(t('Application not found.'));
				return;
			}
			$app = $r[0];
				
			$tpl = get_markup_template("settings_oauth_edit.tpl");
			$o .= replace_macros($tpl, array(
				'$form_security_token' => get_form_security_token("settings_oauth"),
				'$title'	=> t('Add application'),
				'$submit'	=> t('Update'),
				'$cancel'	=> t('Cancel'),
				'$name'		=> array('name', t('Name'), $app['clname'] , ''),
				'$key'		=> array('key', t('Consumer Key'), $app['client_id'], ''),
				'$secret'	=> array('secret', t('Consumer Secret'), $app['pw'], ''),
				'$redirect'	=> array('redirect', t('Redirect'), $app['redirect_uri'], ''),
				'$icon'		=> array('icon', t('Icon url'), $app['icon'], ''),
			));
			return $o;
		}
			
		if((argc() > 3) && (argv(2) === 'delete')) {
			check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth', 't');
			
			$r = q("DELETE FROM clients WHERE client_id='%s' AND uid=%d",
					dbesc(argv(3)),
					local_channel());
			goaway(z_root()."/settings/oauth/");
			return;			
		}
			
			
		$r = q("SELECT clients.*, tokens.id as oauth_token, (clients.uid=%d) AS my 
				FROM clients
				LEFT JOIN tokens ON clients.client_id=tokens.client_id
				WHERE clients.uid IN (%d,0)",
				local_channel(),
				local_channel());
		
			
		$tpl = get_markup_template("settings_oauth.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_oauth"),
			'$baseurl'	=> z_root(),
			'$title'	=> t('Connected Apps'),
			'$add'		=> t('Add application'),
			'$edit'		=> t('Edit'),
			'$delete'		=> t('Delete'),
			'$consumerkey' => t('Client key starts with'),
			'$noname'	=> t('No name'),
			'$remove'	=> t('Remove authorization'),
			'$apps'		=> $r,
		));
		return $o;
			
	}

}