<?php
/**
 * Name: More Pokes
 * Description: Additional poke options
 * Version: 1.0
 * Author: Thomas Willingham <https://kakste.com/profile/beardyunixer>
 * Maintainer: none
 */

function morepokes_load() {
	  register_hook('poke_verbs', 'addon/morepokes/morepokes.php', 'morepokes_poke_verbs');
}

function morepokes_unload() {
	  unregister_hook('poke_verbs', 'addon/morepokes/morepokes.php', 'morepokes_poke_verbs');
}

function morepokes_poke_verbs($a,&$b) {
	$b['bitchslap'] = array('bitchslapped', t('bitchslap'), t('bitchslapped'));
	$b['shag'] = array('shagged', t('shag'), t('shagged'));
	$b['patent'] = array('patented', t('patent'), t('patented'));
	$b['hug'] = array('hugged', t('hug'), t('hugged'));
	$b['murder'] = array('murdered', t('murder'), t('murdered'));
	$b['worship'] = array('worshipped', t('worship'), t('worshipped'));
	$b['kiss'] = array('kissed', t('kiss'), t('kissed'));
	$b['tempt'] = array('tempted', t('tempt'), t('tempted'));
	$b['raiseeyebrows'] = array('raised their eyebrows at', t('raise eyebrows at'), t('raised their eyebrows at'));
	$b['insult'] = array('insulted', t('insult'), t('insulted'));
	$b['praise'] = array('praised', t('praise'), t('praised'));
	$b['bedubiousof'] = array('was dubious of', t('be dubious of'), t('was dubious of'));
	$b['eat'] = array('ate', t('eat'), t('ate'));
	$b['giggleandfawn'] = array('giggled and fawned at', t('giggle and fawn at'), t('giggled and fawned at'));
	$b['doubt'] = array('doubted', t('doubt'), t('doubted'));
	$b['glare'] = array('glared at', t('glare'), t('glared at'));
	$b['fuck'] = array('fucked', t('fuck'), t('fucked'));
	$b['bonk'] = array('bonked', t('bonk'), t('bonked'));
	$b['declareundyinglove'] = array('declared undying love for', t('declare undying love for'), t('declared undying love for'));
;}
