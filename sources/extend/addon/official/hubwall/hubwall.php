<?php

/**
 *
 * Name: Hubwall
 * Description: Send admin email message to all account holders. <b><a href="/hubwall" TARGET = "_blank">[send message now]</a></b>
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 */


function hubwall_module() {}



function hubwall_plugin_admin(&$a, &$o) {

	$o = '<div></div>&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . z_root() . '/hubwall">' . t('Send email to all members') . '</a></br/>';

}



function hubwall_post(&$a) {
	if(! is_site_admin())
		return;

	$text = trim($_REQUEST['text']);
	if(! $text)
		return;

	$sender_name  = sprintf( t('$1%s Administrator'), \Zotlabs\Lib\System::get_site_name());
	$sender_email = $_REQUEST['sender'];
	$subject      = $_REQUEST['subject'];


	$textversion = strip_tags(html_entity_decode(bbcode(stripslashes(str_replace(array("\\r", "\\n"),array( "", "\n"), $text))),ENT_QUOTES,'UTF-8'));

	$htmlversion = bbcode(stripslashes(str_replace(array("\\r","\\n"), array("","<br />\n"),$text)));

	$sql_extra = ((intval($_REQUEST['test'])) ? sprintf(" and account_email = '%s' ", get_config('system','admin_email')) : ''); 


	$recips = q("select account_email from account where account_flags = %d $sql_extra",
		intval(ACCOUNT_OK)
	);

	if(! $recips) {
		notice( t('No recipients found.') . EOL);
		return;
	}

	$total_recips = count($recips);
	$total_delivered = 0;

	foreach($recips as $recip) {


		$x = \Zotlabs\Lib\Enotify::send(array(
			'fromName'             => $sender_name,
			'fromEmail'            => $sender_email,
			'replyTo'              => $sender_email,
			'toEmail'              => $recip['account_email'],
			'messageSubject'       => $subject,
			'htmlVersion'          => $htmlversion,
			'textVersion'          => $textversion
		));
		if($x)
			$total_delivered ++;
	}

	info( sprintf( t('%1$d of %2$d messages sent.'), $total_delivered, $total_recips) . EOL );

}

function hubwall_content(&$a) {
	if(! is_site_admin())
		return;

	$title = t('Send email to all hub members.');

	$senders = [ 
		'noreply@' . \App::get_hostname() => 'noreply@' . \App::get_hostname() ,
		'postmaster@' . \App::get_hostname() => 'postmaster@' . \App::get_hostname() ,
		get_config('system','admin_email') => get_config('system','admin_email'),
	];

	$o = replace_macros(get_markup_template('hubwall_form.tpl','addon/hubwall/'),array(
		'$title' => $title,
		'$text' => htmlspecialchars($_REQUEST['text']),
		'$subject' => array('subject',t('Message subject'),$_REQUEST['subject'],''),
		'$sender' => array('sender',t('Sender Email address'), (($_REQUEST['sender']) ? $_REQUEST['sender'] : 'noreply@' . \App::get_hostname()), '' , $senders),
		'$test' => array('test',t('Test mode (only send to hub administrator)'), ((array_key_exists('test',$_REQUEST)) ? intval($_REQUEST['test']) : 0),''),
		'$submit' => t('Submit')
	));

	return $o;

}
