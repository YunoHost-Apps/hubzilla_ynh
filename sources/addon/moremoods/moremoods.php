<?php
/**
 * Name: More Moods
 * Description: Additional mood options
 * Version: 1.0
 * Author: Macgirvin
 * Maintainer: none
 */

function moremoods_load() {
	  register_hook('mood_verbs', 'addon/moremoods/moremoods.php', 'moremoods_moods');
}

function moremoods_unload() {
	  unregister_hook('mood_verbs', 'addon/moremoods/moremoods.php', 'moremoods_moods');
}

function moremoods_moods($a,&$b) {
	$b['lonely'] = t('lonely');
	$b['drunk'] = t('drunk');
	$b['horny'] = t('horny');
	$b['stoned'] = t('stoned');
	$b['fucked up'] = t('fucked up');
	$b['clusterfucked'] = t('clusterfucked');
	$b['crazy'] = t('crazy');
	$b['hurt'] = t('hurt');
	$b['sleepy'] = t('sleepy');
	$b['grumpy'] = t('grumpy');
	$b['high'] = t('high');
	$b['semi-conscious'] = t('semi-conscious');
	$b['in love'] = t('in love');
	$b['in lust'] = t('in lust');
	$b['naked'] = t('naked');
	$b['stinky'] = t('stinky');
	$b['sweaty'] = t('sweaty');
	$b['bleeding out'] = t('bleeding out');
	$b['victorious'] = t('victorious');
	$b['defeated'] = t('defeated');
	$b['envious'] = t('envious');
	$b['jealous'] = t('jealous');
	
}
