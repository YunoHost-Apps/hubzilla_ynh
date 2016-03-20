<?php
/**
 * Name: Twitter Connector
 * Description: Relay public postings to a connected Twitter account
 * Version: 1.2
 * Author: Tobias Diekershoff <https://f.diekershoff.de/profile/tobias>
 * Author: Michael Vogel <https://pirati.ca/profile/heluecht>
 * Author: Mike Macgirvin <https://zothub.com/channel/mike>
 * Maintainer: none
 *
 * Copyright (c) 2011-2013 Tobias Diekershoff, Michael Vogel
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above
 *    * copyright notice, this list of conditions and the following disclaimer in
 *      the documentation and/or other materials provided with the distribution.
 *    * Neither the name of the <organization> nor the names of its contributors
 *      may be used to endorse or promote products derived from this software
 *      without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
 
/*   Twitter Plugin for Hubzilla
 *
 *   Author: Tobias Diekershoff
 *           tobias.diekershoff@gmx.net
 *
 *   License:3-clause BSD license
 *
 *   Configuration:
 *     To use this plugin you need a OAuth Consumer key pair (key & secret)
 *     you can get it from Twitter at https://twitter.com/apps
 *
 *     Register your Hubzilla site as "Client" application with "Read & Write" access
 *     we do not need "Twitter as login". When you've registered the app you get the
 *     OAuth Consumer key and secret pair for your application/site.
 *
 *     Activate the plugin from the plugins section of your admin panel.  When you have
 *     done so, add your consumer key and consumer secret in the Plugin Features section 
 *     of the admin page. A link to this section will appear on the sidebar of the admin page
 *     called 'twitter'.
 *
 *   Alternatively: (old way - may not work any more)
 *     Add this key pair to your global .htconfig.php or use the admin panel.
 *
 *     $a->config['twitter']['consumerkey'] = 'your consumer_key here';
 *     $a->config['twitter']['consumersecret'] = 'your consumer_secret here';
 *
 *     Requirements: PHP5, curl [Slinky library]
 */

define('TWITTER_DEFAULT_POLL_INTERVAL', 5); // given in minutes

function twitter_load() {
	//  we need some hooks, for the configuration and for sending tweets
	register_hook('feature_settings', 'addon/twitter/twitter.php', 'twitter_settings'); 
	register_hook('feature_settings_post', 'addon/twitter/twitter.php', 'twitter_settings_post');
	register_hook('post_local', 'addon/twitter/twitter.php', 'twitter_post_local');
	register_hook('notifier_normal', 'addon/twitter/twitter.php', 'twitter_post_hook');
	register_hook('jot_networks', 'addon/twitter/twitter.php', 'twitter_jot_nets');

	logger("installed twitter");
}


function twitter_unload() {
	unregister_hook('feature_settings', 'addon/twitter/twitter.php', 'twitter_settings'); 
	unregister_hook('feature_settings_post', 'addon/twitter/twitter.php', 'twitter_settings_post');
	unregister_hook('post_local', 'addon/twitter/twitter.php', 'twitter_post_local');
	unregister_hook('notifier_normal', 'addon/twitter/twitter.php', 'twitter_post_hook');
	unregister_hook('jot_networks', 'addon/twitter/twitter.php', 'twitter_jot_nets');

	logger("uninstalled twitter");
}

function twitter_jot_nets(&$a,&$b) {
	if(! local_channel())
		return;

	$tw_post = get_pconfig(local_channel(),'twitter','post');
	if(intval($tw_post) == 1) {
		$tw_defpost = get_pconfig(local_channel(),'twitter','post_by_default');
		$selected = ((intval($tw_defpost) == 1) ? ' checked="checked" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="twitter_enable"' . $selected . ' value="1" /> <img src="addon/twitter/twitter.png" /> ' . t('Post to Twitter') . '</div>';
	}
}

function twitter_settings_post ($a,$post) {
	if(! local_channel())
		return;
	// don't check twitter settings if twitter submit button is not clicked
	if (!x($_POST,'twitter-submit'))
		return;

	if (isset($_POST['twitter-disconnect'])) {
		/***
		 * if the twitter-disconnect checkbox is set, clear the OAuth key/secret pair
		 * from the user configuration
		 */
		del_pconfig(local_channel(), 'twitter', 'consumerkey');
		del_pconfig(local_channel(), 'twitter', 'consumersecret');
		del_pconfig(local_channel(), 'twitter', 'oauthtoken');
		del_pconfig(local_channel(), 'twitter', 'oauthsecret');
		del_pconfig(local_channel(), 'twitter', 'post');
		del_pconfig(local_channel(), 'twitter', 'post_by_default');
		del_pconfig(local_channel(), 'twitter', 'post_taglinks');
		del_pconfig(local_channel(), 'twitter', 'lastid');
		del_pconfig(local_channel(), 'twitter', 'intelligent_shortening');
		del_pconfig(local_channel(), 'twitter', 'own_id');
	} else {
	if (isset($_POST['twitter-pin'])) {
		//  if the user supplied us with a PIN from Twitter, let the magic of OAuth happen
		logger('got a Twitter PIN');
		require_once('library/twitteroauth.php');
		$ckey    = get_config('twitter', 'consumerkey');
		$csecret = get_config('twitter', 'consumersecret');
		//  the token and secret for which the PIN was generated were hidden in the settings
		//  form as token and token2, we need a new connection to Twitter using these token
		//  and secret to request a Access Token with the PIN
		$connection = new TwitterOAuth($ckey, $csecret, $_POST['twitter-token'], $_POST['twitter-token2']);
		$token   = $connection->getAccessToken( $_POST['twitter-pin'] );
		//  ok, now that we have the Access Token, save them in the user config
 		set_pconfig(local_channel(),'twitter', 'oauthtoken',  $token['oauth_token']);
		set_pconfig(local_channel(),'twitter', 'oauthsecret', $token['oauth_token_secret']);
		set_pconfig(local_channel(),'twitter', 'post', 1);
		set_pconfig(local_channel(),'twitter', 'post_taglinks', 1);
		//  reload the Addon Settings page, if we don't do it see Friendica Bug #42
        goaway($a->get_baseurl().'/settings/featured');
	} else {
		//  if no PIN is supplied in the POST variables, the user has changed the setting
		//  to post a tweet for every new __public__ posting to the wall
		set_pconfig(local_channel(),'twitter','post',intval($_POST['twitter-enable']));
		set_pconfig(local_channel(),'twitter','post_by_default',intval($_POST['twitter-default']));
		set_pconfig(local_channel(),'twitter','post_taglinks',intval($_POST['twitter-sendtaglinks']));
		set_pconfig(local_channel(),'twitter', 'mirror_posts', intval($_POST['twitter-mirror']));
		set_pconfig(local_channel(),'twitter', 'intelligent_shortening', intval($_POST['twitter-shortening']));
		set_pconfig(local_channel(),'twitter', 'import', intval($_POST['twitter-import']));
		set_pconfig(local_channel(),'twitter', 'create_user', intval($_POST['twitter-create_user']));
		info( t('Twitter settings updated.') . EOL);
	}}
}
function twitter_settings(&$a,&$s) {
        if(! local_channel())
                return;
        $a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/twitter/twitter.css' . '" media="all" />' . "\r\n";
	/***
	 * 1) Check that we have global consumer key & secret
	 * 2) If no OAuthtoken & stuff is present, generate button to get some
	 * 3) Checkbox for "Send public notices (140 chars only)
	 */
	$ckey    = get_config('twitter', 'consumerkey' );
	$csecret = get_config('twitter', 'consumersecret' );
	$otoken  = get_pconfig(local_channel(), 'twitter', 'oauthtoken'  );
	$osecret = get_pconfig(local_channel(), 'twitter', 'oauthsecret' );
	$enabled = get_pconfig(local_channel(), 'twitter', 'post');
	$checked = (($enabled) ? 1 : false);
	$defenabled = get_pconfig(local_channel(),'twitter','post_by_default');
	$defchecked = (($defenabled) ? 1 : false);
	//$shorteningenabled = get_pconfig(local_channel(),'twitter','intelligent_shortening');
	//$shorteningchecked = (($shorteningenabled) ? 1 : false);

	if ( (!$ckey) && (!$csecret) ) {
		/***
		 * no global consumer keys
		 * display warning and skip personal config
		 */
		$sc .= '<div class="section-content-danger-wrapper">';
		$sc .= t('No consumer key pair for Twitter found. Please contact your site administrator.');
		$sc .= '</div>';
	} else {
		/***
		 * ok we have a consumer key pair now look into the OAuth stuff
		 */
		if ( (!$otoken) && (!$osecret) ) {
			/***
			 * the user has not yet connected the account to twitter...
			 * get a temporary OAuth key/secret pair and display a button with
			 * which the user can request a PIN to connect the account to a
			 * account at Twitter.
			 */
			require_once('library/twitteroauth.php');
			$connection = new TwitterOAuth($ckey, $csecret);
			$request_token = $connection->getRequestToken();
			$token = $request_token['oauth_token'];
			/***
			 *  make some nice form
			 */

			$sc .= '<div class="section-content-info-wrapper">';
			$sc .= t('At this Hubzilla instance the Twitter plugin was enabled but you have not yet connected your account to your Twitter account. To do so click the button below to get a PIN from Twitter which you have to copy into the input box below and submit the form. Only your <strong>public</strong> posts will be posted to Twitter.');
			$sc .= '</div>';
			$sc .= '<a href="'.$connection->getAuthorizeURL($token).'" target="_twitter"><img src="addon/twitter/lighter.png" class="form-group" alt="'.t('Log in with Twitter').'"></a>';

			$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
				'$field'	=> array('twitter-pin', t('Copy the PIN from Twitter here'), '', '')
			));

			$sc .= '<input id="twitter-token" type="hidden" name="twitter-token" value="'.$token.'" />';
			$sc .= '<input id="twitter-token2" type="hidden" name="twitter-token2" value="'.$request_token['oauth_token_secret'].'" />';

			$sc .= '<div class=" settings-submit-wrapper">';
			$sc .= '<button type="submit" name="twitter-submit" class="btn btn-primary" value="' . t('Submit') . '">' . t('Submit') . '</button>';
			$sc .= '</div>';

		} else {
			/***
			 *  we have an OAuth key / secret pair for the user
			 *  so let's give a chance to disable the postings to Twitter
			 */
			require_once('library/twitteroauth.php');
			$connection = new TwitterOAuth($ckey,$csecret,$otoken,$osecret);
			$details = $connection->get('account/verify_credentials');
			$twitpic = $details->profile_image_url;
			if((strstr(z_root(),'https')) && (! strstr($twitpic,'https')))
				$twitpic = str_replace('http:','https:',$twitpic);

			$sc .= '<div id="twitter-info" ><img id="twitter-avatar" src="'.$twitpic.'" /><p id="twitter-info-block">'. t('Currently connected to: ') .'<a href="https://twitter.com/'.$details->screen_name.'" target="_twitter">'.$details->screen_name.'</a><br /><em>'.$details->description.'</em></p></div>';
			$sc .= '<div class="clear"></div>';
			//FIXME no hidewall in Red
			if ($a->user['hidewall']) {
				$sc .= '<div class="section-content-info-wrapper">';
				$sc .= t('<strong>Note:</strong> Due your privacy settings (<em>Hide your profile details from unknown viewers?</em>) the link potentially included in public postings relayed to Twitter will lead the visitor to a blank page informing the visitor that the access to your profile has been restricted.');
				$sc .= '</div>';
			}

			$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
				'$field'	=> array('twitter-enable', t('Allow posting to Twitter'), $checked, t('If enabled your public postings can be posted to the associated Twitter account'), array(t('No'),t('Yes')))
			));

			$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
				'$field'	=> array('twitter-default', t('Send public postings to Twitter by default'), $defchecked, t('If enabled your public postings will be posted to the associated Twitter account by default'), array(t('No'),t('Yes')))
			));

			//FIXME: Doesn't seem to work. But maybe we don't want this at all.
			//$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
			//	'$field'	=> array('twitter-shortening', t('Shortening method that optimizes the tweet'), $shorteningchecked, '', array(t('No'),t('Yes')))
			//));

			$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
				'$field'	=> array('twitter-disconnect', t('Clear OAuth configuration'), '', '', array(t('No'),t('Yes')))
			));

			$sc .= '<div class=" settings-submit-wrapper">';
			$sc .= '<button type="submit" name="twitter-submit" class="btn btn-primary" value="' . t('Submit') . '">' . t('Submit') . '</button>';
			$sc .= '</div>';
		}
	}
	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('', '<img src="addon/twitter/twitter.png" style="width:auto; height:1em; margin:-3px 5px 0px 0px;">' . t('Twitter Post Settings'), '', ''),
		'$content'	=> $sc
	));

}


function twitter_post_local(&$a,&$b) {

	if($b['edit'])
		return;

    if((! local_channel()) || (local_channel() != $b['uid']))
        return;

    if($b['item_private'] || ($b['mid'] != $b['parent_mid']))
        return;


	$twitter_post = intval(get_pconfig(local_channel(),'twitter','post'));
	$twitter_enable = (($twitter_post && x($_REQUEST,'twitter_enable')) ? intval($_REQUEST['twitter_enable']) : 0);

	// if API is used, default to the chosen settings
	if($_REQUEST['api_source'] && intval(get_pconfig(local_channel(),'twitter','post_by_default')))
		$twitter_enable = 1;

	if(! $twitter_enable)
		return;

	if(strlen($b['postopts']))
		$b['postopts'] .= ',';
	$b['postopts'] .= 'twitter';

}

if (! function_exists('short_link')) {
function short_link ($url) {
    require_once('library/slinky.php');
    $slinky = new Slinky( $url );
    $yourls_url = get_config('yourls','url1');
    if ($yourls_url) {
		$yourls_username = get_config('yourls','username1');
		$yourls_password = get_config('yourls', 'password1');
		$yourls_ssl = get_config('yourls', 'ssl1');
		$yourls = new Slinky_YourLS();
		$yourls->set( 'username', $yourls_username );
		$yourls->set( 'password', $yourls_password );
		$yourls->set( 'ssl', $yourls_ssl );
		$yourls->set( 'yourls-url', $yourls_url );
		$slinky->set_cascade( array( $yourls, new Slinky_UR1ca(), new Slinky_Trim(), new Slinky_IsGd(), new Slinky_TinyURL() ) );
    }
    else {
		// setup a cascade of shortening services
		// try to get a short link from these services
		// in the order ur1.ca, trim, id.gd, tinyurl
		$slinky->set_cascade( array( new Slinky_UR1ca(), new Slinky_Trim(), new Slinky_IsGd(), new Slinky_TinyURL() ) );
    }
    return $slinky->short();
} };

function twitter_shortenmsg($b, $shortlink = false) {
	require_once("include/api.php");
	require_once("include/bbcode.php");
	require_once("include/html2plain.php");

	$max_char = 140;

//	$b['body'] = bb_CleanPictureLinks($b['body']);

	// Looking for the first image
//	$cleaned_body = api_clean_plain_items($b['body']);
	$cleaned_body = $b['body'];
	$image = '';
	if(preg_match("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/is",$cleaned_body,$matches))
		$image = $matches[3];

	if ($image == '')
		if(preg_match("/\[img\](.*?)\[\/img\]/is",$cleaned_body,$matches))
			$image = $matches[1];

	$multipleimages = (strpos($cleaned_body, "[img") != strrpos($cleaned_body, "[img"));

	// When saved into the database the content is sent through htmlspecialchars
	// That means that we have to decode all image-urls
	$image = htmlspecialchars_decode($image);

	$body = $b["body"];
	if ($b["title"] != "")
		$body = $b["title"]."\n\n".$body;

	if (strpos($body, "[bookmark") !== false) {
		// splitting the text in two parts:
		// before and after the bookmark
		$pos = strpos($body, "[bookmark");
		$body1 = substr($body, 0, $pos);
		$body2 = substr($body, $pos);

		// Removing all quotes after the bookmark
		// they are mostly only the content after the bookmark.
		$body2 = preg_replace("/\[quote\=([^\]]*)\](.*?)\[\/quote\]/ism",'',$body2);
		$body2 = preg_replace("/\[quote\](.*?)\[\/quote\]/ism",'',$body2);
		$body = $body1.$body2;
	}

	// Add some newlines so that the message could be cut better
	$body = str_replace(array("[quote", "[bookmark", "[/bookmark]", "[/quote]"),
			array("\n[quote", "\n[bookmark", "[/bookmark]\n", "[/quote]\n"), $body);

	// remove the recycle signs and the names since they aren't helpful on twitter
	// recycle 1
	$recycle = html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8');
	$body = preg_replace( '/'.$recycle.'\[url\=(\w+.*?)\](\w+.*?)\[\/url\]/i', "\n", $body);
	// recycle 2 (Test)
	$recycle = html_entity_decode("&#x25CC; ", ENT_QUOTES, 'UTF-8');
	$body = preg_replace( '/'.$recycle.'\[url\=(\w+.*?)\](\w+.*?)\[\/url\]/i', "\n", $body);

	// remove the share element
	//$body = preg_replace("/\[share(.*?)\](.*?)\[\/share\]/ism","\n\n$2\n\n",$body);

	// At first convert the text to html
	$html = bbcode($body, false, false);

	// Then convert it to plain text
	$msg = trim(html2plain($html, 0, true));
	$msg = html_entity_decode($msg,ENT_QUOTES,'UTF-8');

	// Removing multiple newlines
	while (strpos($msg, "\n\n\n") !== false)
		$msg = str_replace("\n\n\n", "\n\n", $msg);

	// Removing multiple spaces
	while (strpos($msg, "  ") !== false)
		$msg = str_replace("  ", " ", $msg);

	$origmsg = trim($msg);

	// Removing URLs
	$msg = preg_replace('/(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/i', "", $msg);

	$msg = trim($msg);

	$link = '';
	// look for bookmark-bbcode and handle it with priority
	if(preg_match("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/is",$b['body'],$matches))
		$link = $matches[1];

	$multiplelinks = (strpos($b['body'], "[bookmark") != strrpos($b['body'], "[bookmark"));

	// If there is no bookmark element then take the first link
//	if ($link == '') {
//		$links = collecturls($html);

//		foreach($links AS $singlelink) {
//			$img_str = fetch_url($singlelink);

//			$tempfile = tempnam(get_config("system","temppath"), "cache");
//			file_put_contents($tempfile, $img_str);
//			$mime = image_type_to_mime_type(exif_imagetype($tempfile));
//			unlink($tempfile);

//			if (substr($mime, 0, 6) == "image/") {
//				$image = $singlelink;
//				unset($links[$singlelink]);
//			}
//		}

//		if (sizeof($links) > 0) {
//			reset($links);
//			$link = current($links);
//		}
//		$multiplelinks = (sizeof($links) > 1);
//	}

	$msglink = "";
	if ($multiplelinks)
		$msglink = $b["plink"];
	else if ($link != "")
		$msglink = $link;
	else if ($multipleimages)
		$msglink = $b["plink"];
	else if ($image != "")
		$msglink = $image;

	if (($msglink == "") and strlen($msg) > $max_char)
		$msglink = $b["plink"];

	// If the message is short enough then don't modify it.
	if ((strlen($origmsg) <= $max_char) AND ($msglink == ""))
		return(array("msg"=>$origmsg, "image"=>""));

	// If the message is short enough and contains a picture then post the picture as well
	if ((strlen($origmsg) <= ($max_char - 23)) AND strpos($origmsg, $msglink))
		return(array("msg"=>$origmsg, "image"=>$image));

	// If the message is short enough and the link exists in the original message don't modify it as well
	// -3 because of the bad shortener of twitter
	if ((strlen($origmsg) <= ($max_char - 3)) AND strpos($origmsg, $msglink))
		return(array("msg"=>$origmsg, "image"=>""));

	// Preserve the unshortened link
	$orig_link = $msglink;

	// Just replace the message link with a 22 character long string
	// Twitter calculates with this length
	if (trim($msglink) <> '')
		$msglink = "1234567890123456789012";

	if (strlen(trim($msg." ".$msglink)) > ($max_char)) {
		$msg = substr($msg, 0, ($max_char) - (strlen($msglink)));
		$lastchar = substr($msg, -1);
		$msg = substr($msg, 0, -1);
		$pos = strrpos($msg, "\n");
		if ($pos > 0)
			$msg = substr($msg, 0, $pos);
		else if ($lastchar != "\n")
			$msg = substr($msg, 0, -3)."...";

		// if the post contains a picture and a link then the system tries to cut the post earlier.
		// So the link and the picture can be posted.
		if (($image != "") AND ($orig_link != $image)) {
			$msg2 = substr($msg, 0, ($max_char - 20) - (strlen($msglink)));
			$lastchar = substr($msg2, -1);
			$msg2 = substr($msg2, 0, -1);
			$pos = strrpos($msg2, "\n");
			if ($pos > 0)
				$msg = substr($msg2, 0, $pos);
			else if ($lastchar == "\n")
				$msg = trim($msg2);
		}

	}
	// Removing multiple spaces - again
	while (strpos($msg, "  ") !== false)
		$msg = str_replace("  ", " ", $msg);

	$msg = trim($msg);

	// Removing multiple newlines
	//while (strpos($msg, "\n\n") !== false)
	//	$msg = str_replace("\n\n", "\n", $msg);

	// Looking if the link points to an image
	$img_str = fetch_url($orig_link);

//	$tempfile = tempnam(get_config("system","temppath"), "cache");
//	file_put_contents($tempfile, $img_str);
//	$mime = image_type_to_mime_type(exif_imagetype($tempfile));
//	unlink($tempfile);

	if (($image == $orig_link) OR (substr($mime, 0, 6) == "image/"))
		return(array("msg"=>$msg, "image"=>$orig_link));
	else if (($image != $orig_link) AND ($image != "") AND (strlen($msg." ".$msglink) <= ($max_char - 23))) {
		if ($shortlink)
			$orig_link = short_link($orig_link);

		return(array("msg"=>$msg." ".$orig_link, "image"=>$image));
	} else {
		if ($shortlink)
			$orig_link = short_link($orig_link);

		return(array("msg"=>$msg." ".$orig_link, "image"=>""));
	}
}

function twitter_action($a, $uid, $pid, $action) {

	$ckey    = get_config('twitter', 'consumerkey');
	$csecret = get_config('twitter', 'consumersecret');
	$otoken  = get_pconfig($uid, 'twitter', 'oauthtoken');
	$osecret = get_pconfig($uid, 'twitter', 'oauthsecret');

	require_once("addon/twitter/codebird.php");

	$cb = \Codebird\Codebird::getInstance();
	$cb->setConsumerKey($ckey, $csecret);
	$cb->setToken($otoken, $osecret);

	$post = array('id' => $pid);

	logger("twitter_action '".$action."' ID: ".$pid." data: " . print_r($post, true), LOGGER_DATA);

	switch ($action) {
		case "delete":
			$result = $cb->statuses_destroy($post);
			break;
		case "like":
			$result = $cb->favorites_create($post);
			break;
		case "unlike":
			$result = $cb->favorites_destroy($post);
			break;
	}
	logger("twitter_action '".$action."' send, result: " . print_r($result, true), LOGGER_DEBUG);
}

function twitter_post_hook(&$a,&$b) {

	/**
	 * Post to Twitter
	 */

    if((! is_item_normal($b)) || $b['item_private'] || ($b['created'] !== $b['edited']))
        return;

    if(! perm_is_allowed($b['uid'],'','view_stream'))
        return;

    if(! strstr($b['postopts'],'twitter'))
        return;

    if($b['parent'] != $b['id'])
        return;


	logger('twitter post invoked');


	load_pconfig($b['uid'], 'twitter');

	$ckey    = get_config('twitter', 'consumerkey');
	$csecret = get_config('twitter', 'consumersecret');
	$otoken  = get_pconfig($b['uid'], 'twitter', 'oauthtoken');
	$osecret = get_pconfig($b['uid'], 'twitter', 'oauthsecret');
	$intelligent_shortening = get_pconfig($b['uid'], 'twitter', 'intelligent_shortening');

	// Global setting overrides this
	if (get_config('twitter','intelligent_shortening'))
                $intelligent_shortening = get_config('twitter','intelligent_shortening');

	if($ckey && $csecret && $otoken && $osecret) {
		logger('twitter: we have customer key and oauth stuff, going to send.', LOGGER_DEBUG);

		// If it's a repeated message from twitter then do a native retweet and exit
//		if (twitter_is_retweet($a, $b['uid'], $b['body']))
//			return;

		require_once('library/twitteroauth.php');
		require_once('include/bbcode.php');
		$tweet = new TwitterOAuth($ckey,$csecret,$otoken,$osecret);

                // in theory max char is 140 but T. uses t.co to make links 
                // longer so we give them 10 characters extra
		if (!$intelligent_shortening) {
			$max_char = 130; // max. length for a tweet
	                // we will only work with up to two times the length of the dent 
	                // we can later send to Twitter. This way we can "gain" some 
	                // information during shortening of potential links but do not 
	                // shorten all the links in a 200000 character long essay.
	                if (! $b['title']=='') {
	                    $tmp = $b['title'] . ' : '. $b['body'];
	//                    $tmp = substr($tmp, 0, 4*$max_char);
	                } else {
	                    $tmp = $b['body']; // substr($b['body'], 0, 3*$max_char);
	                }
	                // if [url=bla][img]blub.png[/img][/url] get blub.png
	                $tmp = preg_replace( '/\[url\=(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)\]\[img\](\\w+.*?)\\[\\/img\]\\[\\/url\]/i', '$2', $tmp);
	                $tmp = preg_replace( '/\[zrl\=(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)\]\[zmg\](\\w+.*?)\\[\\/zmg\]\\[\\/zrl\]/i', '$2', $tmp);
	                // preserve links to images, videos and audios
	                $tmp = preg_replace( '/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism', '$3', $tmp);
	                $tmp = preg_replace( '/\[\\/?img(\\s+.*?\]|\])/i', '', $tmp);
	                $tmp = preg_replace( '/\[zmg\=([0-9]*)x([0-9]*)\](.*?)\[\/zmg\]/ism', '$3', $tmp);
	                $tmp = preg_replace( '/\[\\/?zmg(\\s+.*?\]|\])/i', '', $tmp);
	                $tmp = preg_replace( '/\[\\/?video(\\s+.*?\]|\])/i', '', $tmp);
	                $tmp = preg_replace( '/\[\\/?youtube(\\s+.*?\]|\])/i', '', $tmp);
	                $tmp = preg_replace( '/\[\\/?vimeo(\\s+.*?\]|\])/i', '', $tmp);
	                $tmp = preg_replace( '/\[\\/?audio(\\s+.*?\]|\])/i', '', $tmp);
	                $linksenabled = get_pconfig($b['uid'],'twitter','post_taglinks');
	                // if a #tag is linked, don't send the [url] over to SN
	                // that is, don't send if the option is not set in the
	                // connector settings
	                if ($linksenabled=='0') {
				// #-tags
				$tmp = preg_replace( '/#\[url\=(\w+.*?)\](\w+.*?)\[\/url\]/i', '#$2', $tmp);
				// @-mentions
				$tmp = preg_replace( '/@\[url\=(\w+.*?)\](\w+.*?)\[\/url\]/i', '@$2', $tmp);
				$tmp = preg_replace( '/#\[zrl\=(\w+.*?)\](\w+.*?)\[\/zrl\]/i', '#$2', $tmp);
				// @-mentions
				$tmp = preg_replace( '/@\[zrl\=(\w+.*?)\](\w+.*?)\[\/zrl\]/i', '@$2', $tmp);
				// recycle 1
	                }
	                $tmp = preg_replace( '/\[url\=(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)\](\w+.*?)\[\/url\]/i', '$2 $1', $tmp);

	                // find all http or https links in the body of the entry and
	                // apply the shortener if the link is longer then 20 characters
	                if (( strlen($tmp)>$max_char ) && ( $max_char > 0 )) {
	                    preg_match_all ( '/(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/i', $tmp, $allurls  );
	                    foreach ($allurls as $url) {
	                        foreach ($url as $u) {
	                            if (strlen($u)>20) {
	                                $sl = short_link($u);
	                                $tmp = str_replace( $u, $sl, $tmp );
	                            }
	                        }
	                    }
	                }
	                // ok, all the links we want to send out are save, now strip 
	                // away the remaining bbcode
			//$msg = strip_tags(bbcode($tmp, false, false));
			$msg = bbcode($tmp, false, false);
			$msg = str_replace(array('<br>','<br />'),"\n",$msg);
			$msg = strip_tags($msg);

			// quotes not working - let's try this
			$msg = html_entity_decode($msg);
			if (( strlen($msg) > $max_char) && $max_char > 0) {
				$shortlink = short_link( $b['plink'] );
				// the new message will be shortened such that "... $shortlink"
				// will fit into the character limit
				$msg = nl2br(substr($msg, 0, $max_char-strlen($shortlink)-4));
        	                $msg = str_replace(array('<br>','<br />'),' ',$msg);
	                        $e = explode(' ', $msg);
	                        //  remove the last word from the cut down message to 
	                        //  avoid sending cut words to the MicroBlog
	                        array_pop($e);
	                        $msg = implode(' ', $e);
				$msg .= '... ' . $shortlink;
			}

			$msg = trim($msg);
			$image = "";
		} else {
			$msgarr = twitter_shortenmsg($b);
                        $msg = $msgarr["msg"];
                        $image = $msgarr["image"];
		}

		// and now tweet it :-)
//		if(strlen($msg) and ($image != "")) {
//			$img_str = z_fetch_url($image);

//			$tempfile = tempnam(get_config("system","temppath"), "cache");
//			file_put_contents($tempfile, $img_str);

			// Twitter had changed something so that the old library doesn't work anymore
			// so we are using a new library for twitter
			// To-Do:
			// Switching completely to this library with all functions
		        require_once("addon/twitter/codebird.php");

//			$cb = \Codebird\Codebird::getInstance();
//			$cb->setConsumerKey($ckey, $csecret);
//			$cb->setToken($otoken, $osecret);

//			$post = array('status' => $msg, 'media[]' => $tempfile);

//			if ($iscomment)
//				$post["in_reply_to_status_id"] = substr($orig_post["uri"], 9);

//			$result = $cb->statuses_updateWithMedia($post);
//			unlink($tempfile);

//			logger('twitter_post_with_media send, result: ' . print_r($result, true), LOGGER_DEBUG);
//			if ($result->errors OR $result->error) {
//				logger('Send to Twitter failed: "' . print_r($result->errors, true) . '"');

				// Workaround: Remove the picture link so that the post can be reposted without it
//				$msg .= " ".$image;
//				$image = "";
//			} elseif ($iscomment) {
//				logger('twitter_post: Update extid '.$result->id_str." for post id ".$b['id']);
//				q("UPDATE `item` SET `extid` = '%s', `body` = '%s' WHERE `id` = %d",
//					dbesc("twitter::".$result->id_str),
//					dbesc($result->text),
//					intval($b['id'])
//				);
//			}
//		}

		if(strlen($msg) and ($image == "")) {
			$url = 'statuses/update';
			$post = array('status' => $msg);

			if ($iscomment)
				$post["in_reply_to_status_id"] = substr($orig_post["uri"], 9);

			$result = $tweet->post($url, $post);
			logger('twitter_post send, result: ' . print_r($result, true), LOGGER_DEBUG);
			if ($result->errors) {
				logger('Send to Twitter failed: "' . print_r($result->errors, true) . '"');

			} 
//		elseif ($iscomment) {
//				logger('twitter_post: Update extid '.$result->id_str." for post id ".$b['id']);
//				q("UPDATE `item` SET `extid` = '%s' WHERE `id` = %d",
//					dbesc("twitter::".$result->id_str),
//					intval($b['id'])
//				);
				//q("UPDATE `item` SET `extid` = '%s', `body` = '%s' WHERE `id` = %d",
				//	dbesc("twitter::".$result->id_str),
				//	dbesc($result->text),
				//	intval($b['id'])
				//);
//			}
		}
	}
}

function twitter_plugin_admin_post(&$a){
	$consumerkey	=	((x($_POST,'consumerkey'))		? notags(trim($_POST['consumerkey']))	: '');
	$consumersecret	=	((x($_POST,'consumersecret'))	? notags(trim($_POST['consumersecret'])): '');
	set_config('twitter','consumerkey',$consumerkey);
	set_config('twitter','consumersecret',$consumersecret);
	info( t('Settings updated.'). EOL );
}
function twitter_plugin_admin(&$a, &$o){
logger('Twitter admin');
	$t = get_markup_template( "admin.tpl", "addon/twitter/" );

	$o = replace_macros($t, array(
		'$submit' => t('Submit Settings'),
								// name, label, value, help, [extra values]
		'$consumerkey' => array('consumerkey', t('Consumer Key'),  get_config('twitter', 'consumerkey' ), ''),
                '$consumersecret' => array('consumersecret', t('Consumer Secret'),  get_config('twitter', 'consumersecret' ), '')
	));
}



function twitter_expand_entities($a, $body, $item, $no_tags = false, $dontincludemedia) {
	require_once("include/oembed.php");

	$tags = "";

	if (isset($item->entities->urls)) {
		$type = "";
		$footerurl = "";
		$footerlink = "";
		$footer = "";

		foreach ($item->entities->urls AS $url) {
			if ($url->url AND $url->expanded_url AND $url->display_url) {

				$expanded_url = twitter_original_url($url->expanded_url);

				$oembed_data = oembed_fetch_url($expanded_url);

				// Quickfix: Workaround for URL with "[" and "]" in it
				if (strpos($expanded_url, "[") OR strpos($expanded_url, "]"))
					$expanded_url = $url->url;

				if ($type == "")
					$type = $oembed_data->type;

				if ($oembed_data->type == "video") {
					$body = str_replace($url->url,
							"[video]".$expanded_url."[/video]", $body);
					$dontincludemedia = true;
				} elseif (($oembed_data->type == "photo") AND isset($oembed_data->url) AND !$dontincludemedia) {
					$body = str_replace($url->url,
							"[url=".$expanded_url."][img]".$oembed_data->url."[/img][/url]",
							$body);
					$dontincludemedia = true;
				} elseif ($oembed_data->type != "link")
					$body = str_replace($url->url,
							"[url=".$expanded_url."]".$expanded_url."[/url]",
							$body);
							//"[url=".$expanded_url."]".$url->display_url."[/url]",
				else {
					$img_str = fetch_url($expanded_url, true, $redirects, 4);

					$tempfile = tempnam(get_config("system","temppath"), "cache");
					file_put_contents($tempfile, $img_str);
					$mime = image_type_to_mime_type(exif_imagetype($tempfile));
					unlink($tempfile);

					if (substr($mime, 0, 6) == "image/") {
						$type = "photo";
						$body = str_replace($url->url, "[img]".$expanded_url."[/img]", $body);
						$dontincludemedia = true;
					} else {
						$type = $oembed_data->type;
						$footerurl = $expanded_url;
						$footerlink = "[url=".$expanded_url."]".$expanded_url."[/url]";
						//$footerlink = "[url=".$expanded_url."]".$url->display_url."[/url]";

						$body = str_replace($url->url, $footerlink, $body);
					}
				}
			}
		}

		if ($footerurl != "")
			$footer = twitter_siteinfo($footerurl, $dontincludemedia);

		if (($footerlink != "") AND (trim($footer) != "")) {
			$removedlink = trim(str_replace($footerlink, "", $body));

			if (strstr($body, $removedlink))
				$body = $removedlink;

			$body .= "\n\n[class=type-".$type."]".$footer."[/class]";
		}

		if ($no_tags)
			return(array("body" => $body, "tags" => ""));

		$tags_arr = array();

		foreach ($item->entities->hashtags AS $hashtag) {
			$url = "#[url=".$a->get_baseurl()."/search?tag=".rawurlencode($hashtag->text)."]".$hashtag->text."[/url]";
			$tags_arr["#".$hashtag->text] = $url;
			$body = str_replace("#".$hashtag->text, $url, $body);
		}

		foreach ($item->entities->user_mentions AS $mention) {
			$url = "@[url=https://twitter.com/".rawurlencode($mention->screen_name)."]".$mention->screen_name."[/url]";
			$tags_arr["@".$mention->screen_name] = $url;
			$body = str_replace("@".$mention->screen_name, $url, $body);
		}

		// it seems as if the entities aren't always covering all mentions. So the rest will be checked here
	        $tags = get_tags($body);

        	if(count($tags)) {
			foreach($tags as $tag) {
				if (strstr(trim($tag), " "))
					continue;

	                        if(strpos($tag,'#') === 0) {
        	                        if(strpos($tag,'[url='))
                	                        continue;

					// don't link tags that are already embedded in links

					if(preg_match('/\[(.*?)' . preg_quote($tag,'/') . '(.*?)\]/',$body))
						continue;
					if(preg_match('/\[(.*?)\]\((.*?)' . preg_quote($tag,'/') . '(.*?)\)/',$body))
						continue;

					$basetag = str_replace('_',' ',substr($tag,1));
					$url = '#[url='.$a->get_baseurl().'/search?tag='.rawurlencode($basetag).']'.$basetag.'[/url]';
					$body = str_replace($tag,$url,$body);
					$tags_arr["#".$basetag] = $url;
					continue;
				} elseif(strpos($tag,'@') === 0) {
        	                        if(strpos($tag,'[url='))
                	                        continue;

					$basetag = substr($tag,1);
					$url = '@[url=https://twitter.com/'.rawurlencode($basetag).']'.$basetag.'[/url]';
					$body = str_replace($tag,$url,$body);
					$tags_arr["@".$basetag] = $url;
				}
			}
		}


		$tags = implode($tags_arr, ",");

	}
	return(array("body" => $body, "tags" => $tags));
}

