<?php

function diaspora_pubmsg_build($msg,$channel,$contact,$prvkey,$pubkey) {

	$a = get_app();

	logger('diaspora_pubmsg_build: ' . $msg, LOGGER_DATA, LOG_DEBUG);

    $handle = $channel['channel_address'] . '@' . App::get_hostname();


	$b64url_data = base64url_encode($msg,false);

	$data = str_replace(array("\n","\r"," ","\t"),array('','','',''),$b64url_data);

	$type = 'application/xml';
	$encoding = 'base64url';
	$alg = 'RSA-SHA256';

	$signable_data = $data  . '.' . base64url_encode($type,false) . '.'
		. base64url_encode($encoding,false) . '.' . base64url_encode($alg,false) ;

	$signature = rsa_sign($signable_data,$prvkey);
	$sig = base64url_encode($signature,false);

$magic_env = <<< EOT
<?xml version='1.0' encoding='UTF-8'?>
<diaspora xmlns="https://joindiaspora.com/protocol" xmlns:me="http://salmon-protocol.org/ns/magic-env" >
  <header>
	<author_id>$handle</author_id>
  </header>
  <me:env>
	<me:encoding>base64url</me:encoding>
	<me:alg>RSA-SHA256</me:alg>
	<me:data type="application/xml">$data</me:data>
	<me:sig>$sig</me:sig>
  </me:env>
</diaspora>
EOT;

	logger('diaspora_pubmsg_build: magic_env: ' . $magic_env, LOGGER_DATA, LOG_DEBUG);
	return $magic_env;

}




function diaspora_msg_build($msg,$channel,$contact,$prvkey,$pubkey,$public = false) {
	$a = get_app();

	if($public)
		return diaspora_pubmsg_build($msg,$channel,$contact,$prvkey,$pubkey);

	logger('diaspora_msg_build: ' . $msg, LOGGER_DATA, LOG_DEBUG);

	// without a public key nothing will work

	if(! $pubkey) {
		logger('diaspora_msg_build: pubkey missing: contact id: ' . $contact['abook_id'], LOG_ERR);
		return '';
	}

	$inner_aes_key = random_string(32);
	$b_inner_aes_key = base64_encode($inner_aes_key);
	$inner_iv = random_string(16);
	$b_inner_iv = base64_encode($inner_iv);

	$outer_aes_key = random_string(32);
	$b_outer_aes_key = base64_encode($outer_aes_key);
	$outer_iv = random_string(16);
	$b_outer_iv = base64_encode($outer_iv);

    $handle = $channel['channel_address'] . '@' . App::get_hostname();

	$padded_data = pkcs5_pad($msg,16);
	$inner_encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $padded_data, MCRYPT_MODE_CBC, $inner_iv);

	$b64_data = base64_encode($inner_encrypted);


	$b64url_data = base64url_encode($b64_data,false);
	$data = str_replace(array("\n","\r"," ","\t"),array('','','',''),$b64url_data);

	$type = 'application/xml';
	$encoding = 'base64url';
	$alg = 'RSA-SHA256';

	$signable_data = $data  . '.' . base64url_encode($type,false) . '.'
		. base64url_encode($encoding,false) . '.' . base64url_encode($alg,false) ;

	logger('diaspora_msg_build: signable_data: ' . $signable_data, LOGGER_DATA, LOG_DEBUG);

	$signature = rsa_sign($signable_data,$prvkey);
	$sig = base64url_encode($signature,false);

$decrypted_header = <<< EOT
<decrypted_header>
  <iv>$b_inner_iv</iv>
  <aes_key>$b_inner_aes_key</aes_key>
  <author_id>$handle</author_id>
</decrypted_header>
EOT;

	$decrypted_header = pkcs5_pad($decrypted_header,16);

	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $outer_aes_key, $decrypted_header, MCRYPT_MODE_CBC, $outer_iv);

	$outer_json = json_encode(array('iv' => $b_outer_iv,'key' => $b_outer_aes_key));

	$encrypted_outer_key_bundle = '';
	openssl_public_encrypt($outer_json,$encrypted_outer_key_bundle,$pubkey);

	$b64_encrypted_outer_key_bundle = base64_encode($encrypted_outer_key_bundle);

	logger('outer_bundle: ' . $b64_encrypted_outer_key_bundle . ' key: ' . $pubkey, LOGGER_DATA, LOG_DEBUG);

	$encrypted_header_json_object = json_encode(array('aes_key' => base64_encode($encrypted_outer_key_bundle), 
		'ciphertext' => base64_encode($ciphertext)));
	$cipher_json = base64_encode($encrypted_header_json_object);

	$encrypted_header = '<encrypted_header>' . $cipher_json . '</encrypted_header>';

$magic_env = <<< EOT
<?xml version='1.0' encoding='UTF-8'?>
<diaspora xmlns="https://joindiaspora.com/protocol" xmlns:me="http://salmon-protocol.org/ns/magic-env" >
  $encrypted_header
  <me:env>
	<me:encoding>base64url</me:encoding>
	<me:alg>RSA-SHA256</me:alg>
	<me:data type="application/xml">$data</me:data>
	<me:sig>$sig</me:sig>
  </me:env>
</diaspora>
EOT;

	logger('diaspora_msg_build: magic_env: ' . $magic_env, LOGGER_DATA, LOG_DEBUG);
	return $magic_env;

}


function diaspora_share($owner,$contact) {
	$a = get_app();

	$allowed = get_pconfig($owner['channel_id'],'system','diaspora_allowed');
	if($allowed === false)
		$allowed = 1;

	if(! intval($allowed)) {
		logger('diaspora_share: disallowed for channel ' . $importer['channel_name']);
		return;
	}



	$myaddr = $owner['channel_address'] . '@' . substr(z_root(), strpos(z_root(),'://') + 3);

	if(! array_key_exists('hubloc_hash',$contact)) {
		$c = q("select * from xchan left join hubloc on xchan_hash = hubloc_hash where xchan_hash = '%s' limit 1",
			dbesc($contact['xchan_hash'])
		);
		if(! $c) {
			logger('diaspora_share: ' . $contact['hubloc_hash']  . ' not found.');
			return;
		}
		$contact = $c[0];
	}

	$theiraddr = $contact['xchan_addr'];

	$tpl = get_markup_template('diaspora_share.tpl','addon/diaspora');
	$msg = replace_macros($tpl, array(
		'$sender' => $myaddr,
		'$recipient' => $theiraddr
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'])));
	return(diaspora_queue($owner,$contact,$slap, false));
}

function diaspora_unshare($owner,$contact) {

	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' .  substr(z_root(), strpos(z_root(),'://') + 3);

	$tpl = get_markup_template('diaspora_retract.tpl','addon/diaspora');
	$msg = replace_macros($tpl, array(
		'$guid'   => $owner['channel_guid'] . str_replace('.','',App::get_hostname()),
		'$type'   => 'Person',
		'$handle' => $myaddr
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'])));

	return(diaspora_queue($owner,$contact,$slap, false));
}


function diaspora_send_status($item,$owner,$contact,$public_batch = false) {

	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' . substr(z_root(), strpos(z_root(),'://') + 3);

	if(intval($item['id']) != intval($item['parent'])) {
		logger('attempted to send a comment as a top-level post');
		return;
	}

	$images = array();

	$title = $item['title'];
	$body = bb2diaspora_itembody($item,true);

/*
	// We're trying to match Diaspora's split message/photo protocol but
	// all the photos are displayed on D* as links and not img's - even
	// though we're sending pretty much precisely what they send us when
	// doing the same operation.  
	// Commented out for now, we'll use bb2diaspora to convert photos to markdown
	// which seems to get through intact.

	$cnt = preg_match_all('|\[img\](.*?)\[\/img\]|',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$detail = array();
			$detail['str'] = $mtch[0];
			$detail['path'] = dirname($mtch[1]) . '/';
			$detail['file'] = basename($mtch[1]);
			$detail['guid'] = $item['guid'];
			$detail['handle'] = $myaddr;
			$images[] = $detail;
			$body = str_replace($detail['str'],$mtch[1],$body);
		}
	}
*/

// @TODO We need a bit more infrastructure before we can process Diaspora polls
//	if(intval($item['item_consensus'])) {
//		$poll = replace_macros(get_markup_template('diaspora_consensus.tpl','addon/diaspora'), array(
//			'$guid_q' => '10000000',
//			'$question' => t('Please choose'),
//			'$guid_y' => '00000001',
//			'$agree' => t('Agree'),
//			'$guid_n' => '0000000F',
//			'$disagree' => t('Disagree'),
//			'$guid_a' => '00000000',
//			'$abstain' => t('Abstain')
//		));
//	}
//	elseif($item['resource_type'] === 'event' && $item['resource_id']) {
//		$poll = replace_macros(get_markup_template('diaspora_consensus.tpl','addon/diaspora'), array(
//			'$guid_q' => '1000000',
//			'$question' => t('Please choose'),
//			'$guid_y' => '0000001',
//			'$agree' => t('I will attend'),
///			'$guid_n' => '000000F',
//			'$disagree' => t('I will not attend'),
//			'$guid_a' => '0000000',
//			'$abstain' => t('I may attend')
//		));
//	}
//	else
		$poll = '';

	$public = (($item['item_private']) ? 'false' : 'true');

	require_once('include/datetime.php');
	$created = datetime_convert('UTC','UTC',$item['created'],'Y-m-d H:i:s \U\T\C');

	// Detect a share element and do a reshare
	// see: https://github.com/Raven24/diaspora-federation/blob/master/lib/diaspora-federation/entities/reshare.rb
	if (!$item['item_private'] AND ($ret = diaspora_is_reshare($item["body"]))) {
		$tpl = get_markup_template('diaspora_reshare.tpl','addon/diaspora');
		$msg = replace_macros($tpl, array(
			'$root_handle' => xmlify($ret['root_handle']),
			'$root_guid' => $ret['root_guid'],
			'$guid' => $item['mid'],
			'$handle' => xmlify($myaddr),
			'$public' => $public,
			'$created' => $created,
			'$provider' => (($item['app']) ? $item['app'] : t('$projectname'))
		));
	} else {
		$tpl = get_markup_template('diaspora_post.tpl','addon/diaspora');
		$msg = replace_macros($tpl, array(
			'$body' => xmlify($body),
			'$guid' => $item['mid'],
			'$poll' => $poll,
			'$handle' => xmlify($myaddr),
			'$public' => $public,
			'$created' => $created,
			'$provider' => (($item['app']) ? $item['app'] : t('$projectname'))
		));
	}

	logger('diaspora_send_status: '.$owner['channel_name'].' -> '.$contact['xchan_name'].' base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],$public_batch)));

	$qi = array(diaspora_queue($owner,$contact,$slap,$public_batch,$item['mid']));

//	logger('diaspora_send_status: guid: '.$item['mid'].' result '.$return_code, LOGGER_DEBUG);

	if(count($images)) {
		$qim = diaspora_send_images($item,$owner,$contact,$images,$public_batch,$item['mid']);
		if($qim)
			$qi = array_merge($qi,$qim);
	}

	return $qi;
}

function diaspora_is_reshare($body) {
	
	$body = trim($body);

	// Skip if it isn't a pure repeated messages
	// Does it start with a share?
	if(strpos($body, "[share") > 0)
		return(false);

	// Does it end with a share?
	if(strlen($body) > (strrpos($body, "[/share]") + 8))
		return(false);

	$attributes = preg_replace("/\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism","$1",$body);
	// Skip if there is no shared message in there
	if ($body == $attributes)
		return(false);

	$profile = "";
	preg_match("/profile='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	preg_match('/profile="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	$ret= array();

	$ret["root_handle"] = preg_replace("=https?://(.*)/u/(.*)=ism", "$2@$1", $profile);
	if (($ret["root_handle"] == $profile) OR ($ret["root_handle"] == ""))
		return(false);

	$link = "";
	preg_match("/link='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$link = $matches[1];

	preg_match('/link="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$link = $matches[1];

	$ret["root_guid"] = preg_replace("=https?://(.*)/posts/(.*)=ism", "$2", $link);
	if (($ret["root_guid"] == $link) OR ($ret["root_guid"] == ""))
		return(false);

	return($ret);
}

function diaspora_send_images($item,$owner,$contact,$images,$public_batch = false) {
	$a = get_app();
	if(! count($images))
		return;
	$mysite = substr(z_root(),strpos(z_root(),'://') + 3) . '/photo';

	$qi = array();

	$tpl = get_markup_template('diaspora_photo.tpl','addon/diaspora');
	foreach($images as $image) {
		if(! stristr($image['path'],$mysite))
			continue;
		$resource = str_replace('.jpg','',$image['file']);
		$resource = substr($resource,0,strpos($resource,'-'));

		$r = q("select * from photo where `resource_id` = '%s' and `uid` = %d limit 1",
			dbesc($resource),
			intval($owner['uid'])
		);
		if(! $r)
			continue;
		$public = (($r[0]['allow_cid'] || $r[0]['allow_gid'] || $r[0]['deny_cid'] || $r[0]['deny_gid']) ? 'false' : 'true' );
		$msg = replace_macros($tpl,array(
			'$path' => xmlify($image['path']),
			'$filename' => xmlify($image['file']),
			'$msg_guid' => xmlify($image['guid']),
			'$guid' => xmlify($r[0]['resource_id']),
			'$handle' => xmlify($image['handle']),
			'$public' => xmlify($public),
			'$created_at' => xmlify(datetime_convert('UTC','UTC',$r[0]['created'],'Y-m-d H:i:s \U\T\C'))
		));

		logger('diaspora_send_photo: base message: ' . $msg, LOGGER_DATA);
		$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],$public_batch)));

		$qi[] = diaspora_queue($owner,$contact,$slap,$public_batch,$item['mid']);
	}

	return $qi;

}

function diaspora_send_upstream($item,$owner,$contact,$public_batch = false) {

	logger('diaspora_send_upstream');

	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' . App::get_hostname();
	$theiraddr = $contact['xchan_addr'];

	// Diaspora doesn't support threaded comments, but some
	// versions of Diaspora (i.e. Diaspora-pistos) support
	// likes on comments
	if(($item['verb'] === ACTIVITY_LIKE || $item['verb'] === ACTIVITY_DISLIKE) && $item['thr_parent']) {
		$p = q("select mid, parent_mid from item where mid = '%s' and uid = %d limit 1",
			dbesc($item['thr_parent']),
			intval($item['uid'])
		);
	}
	else {
		// The first item in the `item` table with the parent id is the parent. However, MySQL doesn't always
		// return the items ordered by `item`.`id`, in which case the wrong item is chosen as the parent.
		// The only item with `parent` and `id` as the parent id is the parent item.
		$p = q("select * from item where parent = %d and id = %d limit 1",
			intval($item['parent']),
			intval($item['parent'])
		);
	}
	if($p)
		$parent = $p[0];
	else
		return;


	if(($item['verb'] === ACTIVITY_LIKE) && ($parent['mid'] === $parent['parent_mid'])) {
		$tpl = get_markup_template('diaspora_like.tpl','addon/diaspora');
		$like = true;
		$target_type = 'Post';
		$positive = 'true';

		if(intval($item['item_deleted']))
			logger('diaspora_send_upstream: received deleted "like". Those should go to diaspora_send_retraction');
	}
	else {
		$tpl = get_markup_template('diaspora_comment.tpl','addon/diaspora');
		$like = false;
	}

	$xmlout = diaspora_fields_to_xml(get_iconfig($item,'diaspora','fields'));
	
	if($item['diaspora_meta'] && ! $like) {
		$diaspora_meta = json_decode($item['diaspora_meta'],true);
		if($diaspora_meta) {
			if(array_key_exists('iv',$diaspora_meta)) {
				$key = get_config('system','prvkey');
				$meta = json_decode(crypto_unencapsulate($diaspora_meta,$key),true);
			}
			else
				$meta = $diaspora_meta;
		}
		$signed_text = $meta['signed_text'];
		$authorsig   = $meta['signature'];
		$signer      = $meta['signer'];
		$text        = $meta['body'];
	}
	else {
		$text = bb2diaspora_itembody($item);

		// sign it

		if($like)
			$signed_text = $positive . ';' . $item['mid'] . ';' . $target_type . ';' . $parent['mid'] . ';' . $myaddr;
		else
			$signed_text = $item['mid'] . ';' . $parent['mid'] . ';' . $text . ';' . $myaddr;

		$authorsig = base64_encode(rsa_sign($signed_text,$owner['channel_prvkey'],'sha256'));

	}

	$msg = replace_macros($tpl,array(
		'$xml' => $xmlout,
		'$guid' => xmlify($item['mid']),
		'$parent_guid' => xmlify($parent['mid']),
		'$target_type' =>xmlify($target_type),
		'$authorsig' => xmlify($authorsig),
		'$body' => xmlify($text),
		'$positive' => xmlify($positive),
		'$handle' => xmlify($myaddr)
	));

	logger('diaspora_send_upstream: base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],$public_batch)));


	return(diaspora_queue($owner,$contact,$slap,$public_batch,$item['mid']));
}


function diaspora_send_downstream($item,$owner,$contact,$public_batch = false) {


	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' . App::get_hostname();

	$text = bb2diaspora_itembody($item);

	$body = $text;

	// Diaspora doesn't support threaded comments, but some
	// versions of Diaspora (i.e. Diaspora-pistos) support
	// likes on comments

	// That version is now dead so detect a "sublike" and
	// just send it as an activity. 

	$sublike = false;

	if($item['verb'] === ACTIVITY_LIKE) {
		if(($item['thr_parent']) && ($item['thr_parent'] !== $item['parent_mid'])) {
			$sublike = true;
		}
	}

	// The first item in the `item` table with the parent id is the parent. However, MySQL doesn't always
	// return the items ordered by `item`.`id`, in which case the wrong item is chosen as the parent.
	// The only item with `parent` and `id` as the parent id is the parent item.

	$p = q("select * from item where parent = %d and id = %d limit 1",
		   intval($item['parent']),
		   intval($item['parent'])
	);


	if($p)
		$parent = $p[0];
	else {
		logger('diaspora_send_downstream: no parent');
		return;
	}

	$like = false;
	$relay_retract = false;
	$sql_sign_id = 'iid';

	if( intval($item['item_deleted'])) {
		$relay_retract = true;

		$target_type = ( ($item['verb'] === ACTIVITY_LIKE && (! $sublike)) ? 'Like' : 'Comment');

		$sql_sign_id = 'retract_iid';
		$tpl = get_markup_template('diaspora_relayable_retraction.tpl','addon/diaspora');
	}
	elseif(($item['verb'] === ACTIVITY_LIKE) && (! $sublike)) {
		$like = true;

		$target_type = ( $parent['mid'] === $parent['parent_mid']  ? 'Post' : 'Comment');
//		$positive = (intval($item['item_deleted']) ? 'false' : 'true');
		$positive = 'true';

		$tpl = get_markup_template('diaspora_like_relay.tpl','addon/diaspora');
	}
	else { // item is a comment
		$tpl = get_markup_template('diaspora_comment_relay.tpl','addon/diaspora');
	}

	$diaspora_meta = (($item['diaspora_meta']) ? json_decode($item['diaspora_meta'],true) : '');
	if($diaspora_meta) {
		if(array_key_exists('iv',$diaspora_meta)) {
			$key = get_config('system','prvkey');
			$meta = json_decode(crypto_unencapsulate($diaspora_meta,$key),true);
		}
		else
			$meta = $diaspora_meta;
		$sender_signed_text = $meta['signed_text'];
		$authorsig = $meta['signature'];
		$handle = $meta['signer'];
		$text = $meta['body'];
	}
	else {
		logger('diaspora_send_downstream: original author signature not found');
	}

	/* Since the author signature is only checked by the parent, not by the relay recipients,
	 * I think it may not be necessary for us to do so much work to preserve all the original
	 * signatures. The important thing that Diaspora DOES need is the original creator's handle.
	 * Let's just generate that and forget about all the original author signature stuff.
	 *
	 * Note: this might be more of an problem if we want to support likes on comments for older
	 * versions of Diaspora (diaspora-pistos), but since there are a number of problems with
	 * doing that, let's ignore it for now.
	 *
	 *
	 */
	
	// bug - nomadic identity may/will affect diaspora_handle_from_contact

	if(! $handle)
		$handle = $owner['channel_address'] . '@' . App::get_hostname();

	if(! $sender_signed_text) {
		if($relay_retract)
			$sender_signed_text = $item['mid'] . ';' . $target_type;
		elseif($like)
			$sender_signed_text = $positive . ';' . $item['mid'] . ';' . $target_type . ';' . $parent['mid'] . ';' . $handle;
		else
			$sender_signed_text = $item['mid'] . ';' . $parent['mid'] . ';' . $text . ';' . $handle;
	}


	$xmlout = diaspora_fields_to_xml(get_iconfig($item,'diaspora','fields'));

	// The relayable may have arrived from somebody who provided no Diaspora Comment Virus. 
	// We check for this above in bb2diaspora_itembody. In that case we will have generated 
	// the body as a "wall-to-wall" post, and the author_signature will now be our own.  

	if((! $xmlout) && (! $authorsig))
		$authorsig = base64_encode(rsa_sign($sender_signed_text,$owner['channel_prvkey'],'sha256'));
		
	// Sign the relayable with the top-level owner's signature

	$parentauthorsig = base64_encode(rsa_sign($sender_signed_text,$owner['channel_prvkey'],'sha256'));

	if(! $text)
		logger('diaspora_send_downstream: no text');

	$msg = replace_macros($tpl,array(
		'$xml' => $xmlout,
		'$guid' => xmlify($item['mid']),
		'$parent_guid' => xmlify($parent['mid']),
		'$target_type' =>xmlify($target_type),
		'$authorsig' => xmlify($authorsig),
		'$parentsig' => xmlify($parentauthorsig),
		'$body' => xmlify($text),
		'$positive' => xmlify($positive),
		'$handle' => xmlify($handle)
	));

	logger('diaspora_send_downstream: base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],$public_batch)));

	return(diaspora_queue($owner,$contact,$slap,$public_batch,$item['mid']));

}



function diaspora_send_retraction($item,$owner,$contact,$public_batch = false) {

	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' .  App::get_hostname();

	// Check whether the retraction is for a top-level post or whether it's a relayable
	if( $item['mid'] !== $item['parent_mid'] ) {

		$tpl = get_markup_template('diaspora_relay_retraction.tpl','addon/diaspora');
		$target_type = (($item['verb'] === ACTIVITY_LIKE) ? 'Like' : 'Comment');
	}
	else {
		
		$tpl = get_markup_template('diaspora_signed_retract.tpl','addon/diaspora');
		$target_type = 'StatusMessage';
	}

	$signed_text = $item['mid'] . ';' . $target_type;

	$msg = replace_macros($tpl, array(
		'$guid'   => xmlify($item['mid']),
		'$type'   => xmlify($target_type),
		'$handle' => xmlify($myaddr),
		'$signature' => xmlify(base64_encode(rsa_sign($signed_text,$owner['channel_prvkey'],'sha256')))
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],$public_batch)));

	return(diaspora_queue($owner,$contact,$slap,$public_batch,$item['mid']));
}

function diaspora_send_mail($item,$owner,$contact) {

	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' .  App::get_hostname();

	$r = q("select * from conv where guid = '%s' and uid = %d limit 1",
		dbesc($item['conv_guid']),
		intval($item['channel_id'])
	);

	if(! count($r)) {
		logger('diaspora_send_mail: conversation not found.');
		return;
	}

	$z = q("select from_xchan from mail where conv_guid = '%s' and channel_id = %d and mid = parent_mid limit 1",
		dbesc($item['conv_guid']),
		intval($item['channel_id'])
	);

	$conv_owner = (($z && $z[0]['from_xchan'] === $owner['channel_hash']) ? true : false);

	$cnv = $r[0];
	$cnv['subject'] = base64url_decode(str_rot47($cnv['subject']));

	$conv = array(
		'guid' => xmlify($cnv['guid']),
		'subject' => xmlify($cnv['subject']),
		'created_at' => xmlify(datetime_convert('UTC','UTC',$cnv['created'],'Y-m-d H:i:s \U\T\C')),
		'diaspora_handle' => xmlify($cnv['creator']),
		'participant_handles' => xmlify($cnv['recips'])
	);


	if(array_key_exists('mail_obscured',$item) && intval($item['mail_obscured'])) {
		if($item['title'])
			$item['title'] = base64url_decode(str_rot47($item['title']));
		if($item['body'])
			$item['body'] = base64url_decode(str_rot47($item['body']));
	}

	
	// the parent_guid needs to be the conversation guid

	$parent_ptr = $cnv['guid'];

	$body = bb2diaspora($item['body']);
	$created = datetime_convert('UTC','UTC',$item['created'],'Y-m-d H:i:s \U\T\C');
 
	$signed_text =  $item['mid'] . ';' . $parent_ptr . ';' . $body .  ';' 
		. $created . ';' . $myaddr . ';' . $cnv['guid'];

	$sig = base64_encode(rsa_sign($signed_text,$owner['channel_prvkey'],'sha256'));

	$msg = array(
		'guid' => xmlify($item['mid']),
		'parent_guid' => xmlify($parent_ptr),
		'parent_author_signature' => (($conv_owner) ? xmlify($sig) : null),
		'author_signature' => xmlify($sig),
		'text' => xmlify($body),
		'created_at' => xmlify($created),
		'diaspora_handle' => xmlify($myaddr),
		'conversation_guid' => xmlify($cnv['guid'])
	);

	if($item['mail_isreply']) {
		$tpl = get_markup_template('diaspora_message.tpl','addon/diaspora');
		$xmsg = replace_macros($tpl, array('$msg' => $msg));
	}
	else {
		$conv['messages'] = array($msg);
		$tpl = get_markup_template('diaspora_conversation.tpl','addon/diaspora');
		$xmsg = replace_macros($tpl, array('$conv' => $conv));
	}

	logger('diaspora_conversation: ' . print_r($xmsg,true), LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($xmsg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],false)));

	return(diaspora_queue($owner,$contact,$slap,false,$item['mid']));


}


function diaspora_profile_change($channel,$recip,$public_batch = false) {


	$channel_id = $channel['channel_id'];
	
	$r = q("SELECT profile.uid AS profile_uid, profile.* , channel.* FROM profile
		left join channel on profile.uid = channel.channel_id
		WHERE channel.channel_id = %d and profile.is_default = 1 ",
		intval($channel_id)
	);

	$profile_visible = perm_is_allowed($channel_id,'','view_profile');

	if(! $r)
		return;
	$profile = $r[0];

	$handle = xmlify($channel['channel_address'] . '@' . App::get_hostname());
	$first = xmlify(((strpos($profile['channel_name'],' '))
		? trim(substr($profile['channel_name'],0,strpos($profile['channel_name'],' '))) : $profile['channel_name']));
	$last = xmlify((($first === $profile['channel_name']) ? '' : trim(substr($profile['channel_name'],strlen($first)))));
	$large = xmlify(z_root() . '/photo/profile/300/' . $profile['profile_uid'] . '.jpg');
	$medium = xmlify(z_root() . '/photo/profile/100/' . $profile['profile_uid'] . '.jpg');
	$small = xmlify(z_root() . '/photo/profile/50/'  . $profile['profile_uid'] . '.jpg');

	$searchable = xmlify((($profile_visible) ? 'true' : 'false' ));

	$nsfw = (($channel['channel_pageflags'] & (PAGE_ADULT|PAGE_CENSORED)) ? 'true' : 'false' );

	if($searchable === 'true') {
		$dob = '1000-00-00';

		if(($profile['dob']) && ($profile['dob'] != '0000-00-00'))
			$dob = ((intval($profile['dob'])) ? intval($profile['dob']) : '1000') . '-' . datetime_convert('UTC','UTC',$profile['dob'],'m-d');
		if($dob === '1000-00-00')
			$dob = '';
		$gender = xmlify($profile['gender']);
		$about = $profile['about'];
		require_once('include/bbcode.php');
		$about = xmlify(strip_tags(bbcode($about)));
		$location = '';
		if($profile['locality'])
			$location .= $profile['locality'];
		if($profile['region']) {
			if($location)
				$location .= ', ';
			$location .= $profile['region'];
		}
		if($profile['country_name']) {
			if($location)
				$location .= ', ';
			$location .= $profile['country_name'];
		}
		$location = xmlify($location);
		$tags = '';
		if($profile['keywords']) {
			$kw = str_replace(',',' ',$profile['keywords']);
			$kw = str_replace('  ',' ',$kw);
			$arr = explode(' ',$profile['keywords']);
			if(count($arr)) {
				for($x = 0; $x < 5; $x ++) {
					if(trim($arr[$x]))
						$tags .= '#' . trim($arr[$x]) . ' ';
				}
			}
		}
		$tags = xmlify(trim($tags));
	}

	$tpl = get_markup_template('diaspora_profile.tpl','addon/diaspora');

	$msg = replace_macros($tpl,array(
		'$handle' => $handle,
		'$first' => $first,
		'$last' => $last,
		'$large' => $large,
		'$medium' => $medium,
		'$small' => $small,
		'$dob' => $dob,
		'$gender' => $gender,
		'$about' => $about,
		'$location' => $location,
		'$searchable' => $searchable,
		'$nsfw' => $nsfw,
		'$tags' => $tags
	));

	logger('profile_change: ' . $msg, LOGGER_ALL, LOG_DEBUG);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$channel,$recip,$channel['channel_prvkey'],$recip['xchan_pubkey'],$public_batch)));
	return(diaspora_queue($channel,$recip,$slap,$public_batch));

}

