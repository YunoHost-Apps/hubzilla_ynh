<?php

/**
 * Name: tripleAES
 * Description: triple AES encryption for Zot pro (requires zot 1.2 or greater)
 * Version: 1.0
 * ServerRoles: pro
 *
 */


function tripleaes_load() {
	\Zotlabs\Extend\Hook::register('crypto_methods','addon/tripleaes/tripleaes.php','tripleaes_crypto_methods');
	\Zotlabs\Extend\Hook::register('other_encapsulate','addon/tripleaes/tripleaes.php','tripleaes_other_encapsulate');
	\Zotlabs\Extend\Hook::register('other_unencapsulate','addon/tripleaes/tripleaes.php','tripleaes_other_unencapsulate');
}

function tripleaes_unload() {
	\Zotlabs\Extend\Hook::unregister_by_file('addon/tripleaes/tripleaes.php');
}


function tripleaes_crypto_methods(&$x) {
	array_unshift( $x , 'tripleaes' );
}

function tripleaes_other_encapsulate(&$a) {

	$x1 = aes_encapsulate($a['data'],$a['pubkey']);
	$x2 = aes_encapsulate(json_encode($x1),$a['pubkey']);
	$a['result'] = aes_encapsulate(json_encode($x2),$a['pubkey']);
	$a['result']['alg'] = 'tripleaes';
}

function tripleaes_other_unencapsulate(&$a) {

	$x1 = json_decode(aes_unencapsulate($a['data'],$a['prvkey']),true);
	$x2 = json_decode(aes_unencapsulate($x1,$a['prvkey']),true);
	$a['result'] = aes_unencapsulate($x2,$a['prvkey']);

}





