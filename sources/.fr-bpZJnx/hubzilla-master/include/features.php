<?php /** @file */

/*
 * Features management
 */





function feature_enabled($uid,$feature) {

	$x = get_config('feature_lock',$feature);
	if($x === false) {
		$x = get_pconfig($uid,'feature',$feature);
		if($x === false) {
			$x = get_config('feature',$feature);
			if($x === false)
				$x = get_feature_default($feature);
		}
	}
	$arr = array('uid' => $uid, 'feature' => $feature, 'enabled' => $x);
	call_hooks('feature_enabled',$arr);
	return($arr['enabled']);
}

function get_feature_default($feature) {
	$f = get_features(false);
	foreach($f as $cat) {
		foreach($cat as $feat) {
			if(is_array($feat) && $feat[0] === $feature)
				return $feat[3];
		}
	}
	return false;
}


function get_features($filtered = true) {

	if(UNO && $filtered)
		return array();

	$arr = array(

		// General
		'general' => array(
			t('General Features'),
			// This is per post, and different from fixed expiration 'expire' which isn't working yet
			array('content_expire',      t('Content Expiration'),     t('Remove posts/comments and/or private messages at a future time'), false, get_config('feature_lock','content_expire')),
			array('multi_profiles',      t('Multiple Profiles'),      t('Ability to create multiple profiles'), false, get_config('feature_lock','multi_profiles')),
			array('advanced_profiles',   t('Advanced Profiles'),      t('Additional profile sections and selections'),false,get_config('feature_lock','advanced_profiles')),
			array('profile_export',      t('Profile Import/Export'),  t('Save and load profile details across sites/channels'),false,get_config('feature_lock','profile_export')),
			array('webpages',            t('Web Pages'),              t('Provide managed web pages on your channel'),false,get_config('feature_lock','webpages')),
			array('hide_rating',       t('Hide Rating'),          t('Hide the rating buttons on your channel and profile pages. Note: People can still rate you somewhere else.'),false,get_config('feature_lock','hide_rating')),			
			array('private_notes',       t('Private Notes'),          t('Enables a tool to store notes and reminders (note: not encrypted)'),false,get_config('feature_lock','private_notes')),
			array('nav_channel_select',  t('Navigation Channel Select'), t('Change channels directly from within the navigation dropdown menu'),false,get_config('feature_lock','nav_channel_select')),
			array('photo_location',       t('Photo Location'),          t('If location data is available on uploaded photos, link this to a map.'),false,get_config('feature_lock','photo_location')),
			array('ajaxchat',       t('Access Controlled Chatrooms'),          t('Provide chatrooms and chat services with access control.'),true,get_config('feature_lock','ajaxchat')),
			array('smart_birthdays',       t('Smart Birthdays'),          t('Make birthday events timezone aware in case your friends are scattered across the planet.'),true,get_config('feature_lock','smart_birthdays')),
			array('expert',       t('Expert Mode'),                 t('Enable Expert Mode to provide advanced configuration options'),false,get_config('feature_lock','expert')),
			array('premium_channel', t('Premium Channel'), t('Allows you to set restrictions and terms on those that connect with your channel'),false,get_config('feature_lock','premium_channel')),
		),

		// Post composition
		'composition' => array(
			t('Post Composition Features'),
//			array('richtext',       t('Richtext Editor'),			t('Enable richtext editor'),falseget_config('feature_lock','richtext')),
//			array('markdown',       t('Use Markdown'),              t('Allow use of "Markdown" to format posts'),false,get_config('feature_lock','markdown')),
			array('large_photos',   t('Large Photos'),              t('Include large (1024px) photo thumbnails in posts. If not enabled, use small (640px) photo thumbnails'),false,get_config('feature_lock','large_photos')),
			array('channel_sources', t('Channel Sources'),          t('Automatically import channel content from other channels or feeds'),false,get_config('feature_lock','channel_sources')),
			array('content_encrypt', t('Even More Encryption'),          t('Allow optional encryption of content end-to-end with a shared secret key'),false,get_config('feature_lock','content_encrypt')),
			array('consensus_tools', t('Enable Voting Tools'),      t('Provide a class of post which others can vote on'),false,get_config('feature_lock','consensus_tools')),
			array('delayed_posting', t('Delayed Posting'),      t('Allow posts to be published at a later date'),false,get_config('feature_lock','delayed_posting')),
			array('suppress_duplicates', t('Suppress Duplicate Posts/Comments'),  t('Prevent posts with identical content to be published with less than two minutes in between submissions.'),true,get_config('feature_lock','suppress_duplicates')),

		),

		// Network Tools
		'net_module' => array(
			t('Network and Stream Filtering'),
			array('archives',       t('Search by Date'),			t('Ability to select posts by date ranges'),false,get_config('feature_lock','archives')),
			array('groups',    		t('Privacy Groups'),		t('Enable management and selection of privacy groups'),true,get_config('feature_lock','groups')),
			array('savedsearch',    t('Saved Searches'),			t('Save search terms for re-use'),false,get_config('feature_lock','savedsearch')),
			array('personal_tab',   t('Network Personal Tab'),		t('Enable tab to display only Network posts that you\'ve interacted on'),false,get_config('feature_lock','personal_tab')),
			array('new_tab',   		t('Network New Tab'),			t('Enable tab to display all new Network activity'),false,get_config('feature_lock','new_tab')),
			array('affinity',       t('Affinity Tool'),			    t('Filter stream activity by depth of relationships'),false,get_config('feature_lock','affinity')),
			array('connfilter',     t('Connection Filtering'),      t('Filter incoming posts from connections based on keywords/content'),false,get_config('feature_lock','connfilter')),
			array('suggest',    	t('Suggest Channels'),			t('Show channel suggestions'),false,get_config('feature_lock','suggest')),
		),

		// Item tools
		'tools' => array(
			t('Post/Comment Tools'),
			array('commtag',        t('Community Tagging'),					t('Ability to tag existing posts'),false,get_config('feature_lock','commtag')),
			array('categories',     t('Post Categories'),			t('Add categories to your posts'),false,get_config('feature_lock','categories')),
			array('emojis',     t('Emoji Reactions'),			t('Add emoji reaction ability to posts'),true,get_config('feature_lock','emojis')),
			array('filing',         t('Saved Folders'),				t('Ability to file posts under folders'),false,get_config('feature_lock','filing')),
			array('dislike',        t('Dislike Posts'),				t('Ability to dislike posts/comments'),false,get_config('feature_lock','dislike')),
			array('star_posts',     t('Star Posts'),				t('Ability to mark special posts with a star indicator'),false,get_config('feature_lock','star_posts')),
			array('tagadelic',      t('Tag Cloud'),				    t('Provide a personal tag cloud on your channel page'),false,get_config('feature_lock','tagedelic')),
		),
	);

	// removed any locked features and remove the entire category if this makes it empty

	if($filtered) {
		foreach($arr as $k => $x) {
			$has_items = false;
			for($y = 0; $y < count($arr[$k]); $y ++) {	
				if(is_array($arr[$k][$y])) {
					if($arr[$k][$y][4] === false) {
						$has_items = true;
					}
					else {
						unset($arr[$k][$y]);
					}
				}
			}
			if(! $has_items) {
				unset($arr[$k]);
			}
		}
	}

	call_hooks('get_features',$arr);
	return $arr;
}
