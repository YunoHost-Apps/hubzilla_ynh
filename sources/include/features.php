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


function feature_level($feature,$def) {
	$x = get_config('feature_level',$feature);
	if($x !== false)
		return intval($x);
	return $def;
}

function get_features($filtered = true) {

	$server_role = \Zotlabs\Lib\System::get_server_role();

	if($server_role === 'basic' && $filtered)
		return array();

	$arr = [

		// General
		'general' => [

			t('General Features'),


			[
				'multi_profiles',      
				t('Multiple Profiles'),      
				t('Ability to create multiple profiles'), 
				false, 
				get_config('feature_lock','multi_profiles'),
				feature_level('multi_profiles',3),
			],

			[
				'advanced_profiles',   
				t('Advanced Profiles'),      
				t('Additional profile sections and selections'),
				false,
				get_config('feature_lock','advanced_profiles'),
				feature_level('advanced_profiles',1),
			],

			[
				'profile_export',      
				t('Profile Import/Export'),  
				t('Save and load profile details across sites/channels'),
				false,
				get_config('feature_lock','profile_export'),
				feature_level('profile_export',3),
			],

			[
				'webpages',            
				t('Web Pages'),              
				t('Provide managed web pages on your channel'),
				false,
				get_config('feature_lock','webpages'),
				feature_level('webpages',3),
			],

			[
				'wiki',            
				t('Wiki'),              
				t('Provide a wiki for your channel'),
				false,
				get_config('feature_lock','wiki'),
				feature_level('wiki',2),
			],
/*
			[
				'hide_rating',       
				t('Hide Rating'),          
				t('Hide the rating buttons on your channel and profile pages. Note: People can still rate you somewhere else.'),
				false,
				get_config('feature_lock','hide_rating'),
				feature_level('hide_rating',3),
			],
*/			
			[
				'private_notes',       
				t('Private Notes'),          
				t('Enables a tool to store notes and reminders (note: not encrypted)'),
				false,
				get_config('feature_lock','private_notes'),
				feature_level('private_notes',1),
			],

			[
				'nav_channel_select',  
				t('Navigation Channel Select'), 
				t('Change channels directly from within the navigation dropdown menu'),
				false,
				get_config('feature_lock','nav_channel_select'),
				feature_level('nav_channel_select',3),
			],

			[
				'photo_location',       
				t('Photo Location'),          
				t('If location data is available on uploaded photos, link this to a map.'),
				false,
				get_config('feature_lock','photo_location'),
				feature_level('photo_location',2),
			],

			[
				'ajaxchat',       
				t('Access Controlled Chatrooms'),          
				t('Provide chatrooms and chat services with access control.'),
				true,
				get_config('feature_lock','ajaxchat'),
				feature_level('ajaxchat',1),
			],

			[
				'smart_birthdays',       
				t('Smart Birthdays'),          
				t('Make birthday events timezone aware in case your friends are scattered across the planet.'),
				true,
				get_config('feature_lock','smart_birthdays'),
				feature_level('smart_birthdays',2),
			],

			[ 
				'advanced_dirsearch', 
				t('Advanced Directory Search'),
				t('Allows creation of complex directory search queries'),
				false, 
				get_config('feature_lock','advanced_dirsearch'),
				feature_level('advanced_dirsearch',4),
			],

			[ 
				'advanced_theming', 
				t('Advanced Theme and Layout Settings'),
				t('Allows fine tuning of themes and page layouts'),
				false, 
				get_config('feature_lock','advanced_theming'),
				feature_level('advanced_theming',4),
			],
		],

		// Post composition
		'composition' => [

			t('Post Composition Features'),

			[
				'large_photos',   
				t('Large Photos'),              
				t('Include large (1024px) photo thumbnails in posts. If not enabled, use small (640px) photo thumbnails'),
				false,
				get_config('feature_lock','large_photos'),
				feature_level('large_photos',1),
			],

			[
				'channel_sources', 
				t('Channel Sources'),          
				t('Automatically import channel content from other channels or feeds'),
				false,
				get_config('feature_lock','channel_sources'),
				feature_level('channel_sources',3),
			],
			
			[
				'content_encrypt', 
				t('Even More Encryption'),          
				t('Allow optional encryption of content end-to-end with a shared secret key'),
				false,
				get_config('feature_lock','content_encrypt'),
				feature_level('content_encrypt',3),
			],
			
			[
				'consensus_tools', 
				t('Enable Voting Tools'),      
				t('Provide a class of post which others can vote on'),
				false,
				get_config('feature_lock','consensus_tools'),
				feature_level('consensus_tools',3),
			],

			[
				'disable_comments', 
				t('Disable Comments'),      
				t('Provide the option to disable comments for a post'),
				false,
				get_config('feature_lock','disable_comments'),
				feature_level('disable_comments',2),
			],

			[
				'delayed_posting', 
				t('Delayed Posting'),      
				t('Allow posts to be published at a later date'),
				false,
				get_config('feature_lock','delayed_posting'),
				feature_level('delayed_posting',2),
			],

			[ 	
				'content_expire',
				t('Content Expiration'),
				t('Remove posts/comments and/or private messages at a future time'), 
				false, 
				get_config('feature_lock','content_expire'),
				feature_level('content_expire',1),
			],

			[
				'suppress_duplicates', 
				t('Suppress Duplicate Posts/Comments'),  
				t('Prevent posts with identical content to be published with less than two minutes in between submissions.'),
				true,
				get_config('feature_lock','suppress_duplicates'),
				feature_level('suppress_duplicates',1),
			],

		],

		// Network Tools
		'net_module' => [

			t('Network and Stream Filtering'),

			[
				'archives',       
				t('Search by Date'),			
				t('Ability to select posts by date ranges'),
				false,
				get_config('feature_lock','archives'),
				feature_level('archives',1),
			],

			[
				'groups',    		
				t('Privacy Groups'),		
				t('Enable management and selection of privacy groups'),
				true,
				get_config('feature_lock','groups'),
				feature_level('groups',0),
			],

			[
				'savedsearch',    
				t('Saved Searches'),			
				t('Save search terms for re-use'),
				false,
				get_config('feature_lock','savedsearch'),
				feature_level('savedsearch',2),
			],

			[
				'personal_tab',   
				t('Network Personal Tab'),		
				t('Enable tab to display only Network posts that you\'ve interacted on'),
				false,
				get_config('feature_lock','personal_tab'),
				feature_level('personal_tab',1),
			],

			[
				'new_tab',   		
				t('Network New Tab'),			
				t('Enable tab to display all new Network activity'),
				false,
				get_config('feature_lock','new_tab'),
				feature_level('new_tab',2),
			],

			[
				'affinity',       
				t('Affinity Tool'),			    
				t('Filter stream activity by depth of relationships'),
				false,
				get_config('feature_lock','affinity'),
				feature_level('affinity',1),
			],

			[
				'suggest',    	
				t('Suggest Channels'),			
				t('Show friend and connection suggestions'),
				false,
				get_config('feature_lock','suggest'),
				feature_level('suggest',1),
			],

			[
				'connfilter',
				t('Connection Filtering'),
				t('Filter incoming posts from connections based on keywords/content'),
				false,
				get_config('feature_lock','connfilter'),
				feature_level('connfilter',3),
			],


		],

		// Item tools
		'tools' => [

			t('Post/Comment Tools'),

			[
				'commtag',        
				t('Community Tagging'),					
				t('Ability to tag existing posts'),
				false,
				get_config('feature_lock','commtag'),
				feature_level('commtag',1),
			],

			[
				'categories',     
				t('Post Categories'),			
				t('Add categories to your posts'),
				false,
				get_config('feature_lock','categories'),
				feature_level('categories',1),
			],

			[
				'emojis',     
				t('Emoji Reactions'),			
				t('Add emoji reaction ability to posts'),
				true,
				get_config('feature_lock','emojis'),
				feature_level('emojis',1),
			],

			[
				'filing',         
				t('Saved Folders'),				
				t('Ability to file posts under folders'),
				false,
				get_config('feature_lock','filing'),
				feature_level('filing',2),
			],

			[
				'dislike',        
				t('Dislike Posts'),				
				t('Ability to dislike posts/comments'),
				false,
				get_config('feature_lock','dislike'),
				feature_level('dislike',1),
			],

			[
				'star_posts',     
				t('Star Posts'),				
				t('Ability to mark special posts with a star indicator'),
				false,
				get_config('feature_lock','star_posts'),
				feature_level('star_posts',1),
			],

			[
				'tagadelic',      
				t('Tag Cloud'),				    
				t('Provide a personal tag cloud on your channel page'),
				false,
				get_config('feature_lock','tagadelic'),
				feature_level('tagadelic',2),
			],
		],
	];


	if($server_role === 'pro') {
		$arr['general'][] = [
			'premium_channel', 
			t('Premium Channel'), 
			t('Allows you to set restrictions and terms on those that connect with your channel'),
			false,
			get_config('feature_lock','premium_channel'),
			feature_level('premium_channel',4),
		];
	}

	$techlevel = get_account_techlevel();

	// removed any locked features and remove the entire category if this makes it empty

	if($filtered) {
		$narr = [];
		foreach($arr as $k => $x) {
			$narr[$k] = [ $arr[$k][0] ];
			$has_items = false;
			for($y = 0; $y < count($arr[$k]); $y ++) {
				$disabled = false;
				if(is_array($arr[$k][$y])) {
					if($arr[$k][$y][5] > $techlevel) {
						$disabled = true;
					}
					if($arr[$k][$y][4] !== false) { 
						$disabled = true;
					}
					if(! $disabled) {
						$has_items = true;
						$narr[$k][$y] = $arr[$k][$y];
					}
				}
			}
			if(! $has_items) {
				unset($narr[$k]);
			}
		}
	}
	else {
		$narr = $arr;
	}
	call_hooks('get_features',$narr);
	return $narr;
}
