<?php

/**
 * Name: Donate
 * Description: Support the Redmatrix/Hubzilla projects
 * Version: 1.0
 * Author: Macgirvin
 * Maintainer: none
 *
 */

function load(){}
function unload(){}
function donate_module(){}

function donate_content(&$a) {

/* Format - array( display name, paypal id, description of services or skills you provide to the matrix) */

$contributors = array(
array('The Redmatrix/Hubzilla Projects', 'max@macgirvin.com', t('Project Servers and Resources')),
array('Mike Macgirvin','mike@macgirvin.com',t('Project Creator and Tech Lead')),
array('Thomas Willingham','beardyunixer@beardyunixer.com',t('Admin, developer, directorymin, support bloke')),
/* Developers and public hubs - add your donatable resource here */

);


$sponsors = array(
'Leila',
'Rose',
'Pierre',
'Jared',
'Erik',
'Chris',
'DuckDuckGo',
'Nicholas',
'Michael',
'Troilus',
'Steve',
'Jakob Jensen'
);



call_hooks('donate_contributors',$contributors);

call_hooks('donate_sponsors',$sponsors);

$sponsors[] = t('And the hundreds of other people and organisations who helped make the Hubzilla possible.');


$text .= '<p>' . t('The Redmatrix/Hubzilla projects are provided primarily by volunteers giving their time and expertise - and often paying out of pocket for services they share with others.') . '</p>';
$text .= '<p>' . t('There is no corporate funding and no ads, and we do not collect and sell your personal information. (We don\'t control your personal information - <strong>you do</strong>.)') . '</p>';
$text .= '<p>' . t('Help support our ground-breaking work in decentralisation, web identity, and privacy.') . '</p>';

$text .= '<p>' . t('Your donations keep servers and services running and also helps us to provide innovative new features and continued development.') . '</p>';

$o = replace_macros(get_markup_template('donate.tpl','addon/donate'),array(
	'$header' => t('Donate'),
	'$text' => $text,
	'$choice' => t('Choose a project, developer, or public hub to support with a one-time donation'),
	'$onetime' => t('Donate Now'),
	'$repeat' => t('<strong><em>Or</em></strong> become a project sponsor (Hubzilla Project only)'),
	'$note' => t('Please indicate if you would like your first name or full name (or nothing) to appear in our sponsor listing'),
	'$subscribe' => t('Sponsor'),
	'$contributors' => $contributors,
	'$sponsors' => $sponsors,
	'$thanks' => t('Special thanks to: '),
));

call_hooks('donate_plugin',$o);

return $o;

}
