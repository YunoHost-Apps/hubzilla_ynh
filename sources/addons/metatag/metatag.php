<?php

/**
 * Name: SEO MetaTags by Blablanet
 * Description: Inserts metatag in every page.    
 * Version: 1.2
 * Author: Jacob Maldonado <https://blablanet.com>
 * 
 */
/*  Seo Meta Tags Plugin for Hubzilla
 *
 *   Author: Jacob Maldonado
 *           
 *   
 *
 *   Configuration:
 *   Use hreflang only if you like to be target only from users with a specific language  
 *   The Search Engines will use hreflang language target for show in the search please Setup
 *   Your own SEO the words include here are only a Example
 *   Pleaase read Install for the lines in  your .htconfig.php file 
 *
 */


function metatag_install() {
    register_hook('page_content_top', 'addon/metatag/metatag.php', 'metatag_fetch');
}


function metatag_uninstall() {
    unregister_hook('page_content_top', 'addon/metatag/metatag.php', 'metatag_fetch');
}


function metatag_fetch($a) {
                $robots = get_config('metatag','robots');
		$hreflang = get_config('metatag','hreflang');
                $description = get_config('metatag','description');
                $keywords = get_config('metatag','keywords');

$descriptionR = get_config('metatag','descriptionR');
$descriptionL = get_config('metatag','descriptionL');
$descriptionP = get_config('metatag','descriptionP');
$descriptionA = get_config('metatag','descriptionA');
$descriptionD = get_config('metatag','descriptionD');
$descriptionN = get_config('metatag','descriptionN');

$url = $_SERVER['REQUEST_URI'];
switch($url){
case "/";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
    $a->page['htmlhead'] .= "$description" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/&JS=1";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
    $a->page['htmlhead'] .= "$description" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/register";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionR" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/register&JS=1";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionR" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/login&JS=1";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionL" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/login";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionL" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/pubsites";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionP" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/pubsub&JS=1";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionP" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;


case "/apps";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionA" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/apps&JS=1";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionA" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/directory";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionD" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/directory&JS=1";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionD" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

case "/news";
    $a->page['htmlhead'] .= "$hreflang" . "\r\n";
    $a->page['htmlhead'] .= "$robots" . "\r\n";
$a->page['htmlhead'] .= "$descriptionN" . "\r\n";
    $a->page['htmlhead'] .= "$keywords" . "\r\n";
break;

 }
   }

