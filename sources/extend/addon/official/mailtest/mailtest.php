<?php

/**
 *
 * Name: Mailtest
 * Description: Send admin email message to test email functionality.
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 */


function mailtest_module() {}



function mailtest_plugin_admin(&$a, &$o) {

	$o = '<div></div>&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . z_root() . '/mailtest">' . t('Send test email') . '</a></br/>';

}



function mailtest_post(&$a) {
	if(! is_site_admin())
		return;

	$text = trim($_REQUEST['text']);
	if(! $text) {
		notice('Empty message. Discarded.');
		return;
	}

	$from_name  = $_REQUEST['from_name'];
	$from_email = $_REQUEST['from_email'];
	$reply_to   = $_REQUEST['reply_to'];
	$subject    = $_REQUEST['subject'];


	$textversion = strip_tags(html_entity_decode(bbcode(stripslashes(str_replace(array("\\r", "\\n"),array( "", "\n"), $text))),ENT_QUOTES,'UTF-8'));

	$htmlversion = bbcode(stripslashes(str_replace(array("\\r","\\n"), array("","<br />\n"),$text)));

	$recips = q("select account_email from account where account_email = '%s' ",
		dbesc(get_config('system','admin_email'))
	);

	if(! $recips) {
		notice( t('No recipients found.') . EOL);
		return;
	}

	foreach($recips as $recip) {

		$result = \Zotlabs\Lib\Enotify::send(array(
			'fromName'             => $from_name,
			'fromEmail'            => $from_email,
			'replyTo'              => $reply_to,
			'toEmail'              => $recip['account_email'],
			'messageSubject'       => $subject,
			'htmlVersion'          => $htmlversion,
			'textVersion'          => $textversion
		));
		if($result)
			notice ( t('Mail sent.') . EOL);
		else
			notice( t('Sending of mail failed.') . EOL );
	}
}

function mailtest_content(&$a) {

	if(! is_site_admin())
		return;

	$title = t('Mail Test');

	$params = [];

	$params['fromEmail'] = get_config('system','from_email');
	if(! $params['fromEmail'])
		$params['fromEmail'] = 'Administrator' . '@' . App::get_hostname();
	
	$params['fromName'] = get_config('system','from_email_name');
	if(! $params['fromName'])
		$params['fromName'] = Zotlabs\Lib\System::get_site_name();

	$params['replyTo'] = get_config('system','reply_address');
	if(! $params['replyTo'])
		$params['replyTo'] = 'noreply' . '@' . App::get_hostname();

	$o = replace_macros(get_markup_template('mailtest_form.tpl','addon/mailtest/'),array(
		'$title'      => $title,
		'$text'       => htmlspecialchars($_REQUEST['text']),
		'$subject'    => array('subject',t('Message subject'),$_REQUEST['subject'],''),
		'$from_email' => $params['fromEmail'],
		'$from_name'  => $params['fromName'],
		'$reply_to'   => $params['replyTo'],
		'$submit'     => t('Submit')
	));

	return $o;

}
