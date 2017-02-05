<?php

/**
 *
 * Name: Chess
 * Description: Hubzilla plugin for decentralized, identity-aware chess games powered by chessboard.js
 * Version: 0.8.3
 * Author: Andrew Manning <https://grid.reticu.li/channel/andrewmanning/>
 * MinVersion: 1.3.3
 * 
 */

define ( 'ACTIVITY_OBJ_CHESSGAME',   NAMESPACE_ZOT  . '/activity/chessgame' );


/**
 * @brief Return the current plugin version
 *
 * @return string Current plugin version
 */
function chess_get_version() {
    return '0.8.3';
}

function chess_load() {
    // Control the page composition by loading a custom layout
    register_hook('feature_settings', 'addon/chess/chess.php', 'chess_settings');
    register_hook('feature_settings_post', 'addon/chess/chess.php', 'chess_settings_post');
    register_hook('load_pdl', 'addon/chess/chess.php', 'chess_load_pdl');
}

function chess_unload() {
    unregister_hook('feature_settings', 'addon/chess/chess.php', 'chess_settings');
    unregister_hook('feature_settings_post', 'addon/chess/chess.php', 'chess_settings_post');
    unregister_hook('load_pdl', 'addon/chess/chess.php', 'chess_load_pdl');
}

function chess_install() {
    info('Chess plugin installed successfully');
    logger('Chess plugin installed successfully');
}

function chess_uninstall() {
    info('Chess plugin uninstalled successfully');
    logger('Chess plugin uninstalled successfully');
}

// Required in order for the plugin to return webpages at /chess as if it were 
// a subfolder in /mod
function chess_module() {}

/**
 * @brief Defines the widget for the page layout, providing the game controls
 *
 * @return string HTML content of the aside region
 */
function widget_chess_controls() {
   
    $which = null;
    $owner = false;
    if(argc() > 1) {
        $which = argv(1);
        if(local_channel()) {
            $channel = App::get_channel();
            if ($channel['channel_address'] === $which) {
                $owner = true;
            }
        }
    }
    if(! $which) {
        if(local_channel()) {
                $channel = App::get_channel();
                if($channel && $channel['channel_address'])
                $which = $channel['channel_address'];
                $owner = true;
        }
    }
    $observer = App::get_observer();
    $games = null;
    if($which) {
        $g = chess_get_games($observer, $which);
        $games = $g['games'];
    }
    $historyviewer = false;
    $gameinfo = null;
    if (argc() > 2 && argv(2) !== 'new') {
        $historyviewer = true;
        $gameinfo = chess_get_info($observer,argv(2));
    }
    $o .= replace_macros(get_markup_template('chess_controls.tpl', 'addon/chess'), array(
        '$owner' => $owner,
        '$channel' => $which, //$channel['channel_address'],
        '$games' => $games,
        '$gameinfo' => $gameinfo,
        '$historyviewer' => $historyviewer,
        '$settings' => chess_game_settings(),
        '$version' => '<a href="https://github.com/redmatrix/hubzilla-addons/">v'.chess_get_version().'</a>'
    ));
    return $o;
}

/**
 * @brief Set the layout for page composition, defining the aside region as an 
 * instance of the controls widget
 *
 * @return null 
 */
function chess_load_pdl($a, &$b) {
    if ($b['module'] === 'chess') {
        $b['layout'] = '
            [region=aside]
            [widget=chess_controls][/widget]
            [/region]
        ';
    }
}

/**
 * @brief Executes prior to page generation or $_REQUEST variables are parsed
 *
 * @return null 
 */
function chess_init($a) {}

/**
 * @brief This function provides the API endpoints, primarily called by the 
 * JavaScript functions via $.post() calls.
 *
 * @return json JSON-formatted structures with a "status" indicator for success
 * as well as other requested data
 */
function chess_post(&$a) {
    if (argc() > 1) {
        switch (argv(1)) {
            // API: /chess/settings
            // Updates game settings for the observer
            case 'settings':
                $observer = App::get_observer();
                $settings = (x($_POST,'settings') ? $_POST['settings'] : null );
                $settings = json_decode($settings, true);
                if (!isset($settings['notify_enabled'])) {
                    json_return_and_die(array('errormsg' => 'Invalid settings', 'status' => false));
                }
                $notify_enable = intval($settings['notify_enabled']);
                set_xconfig($observer['xchan_hash'],'chess','notifications',$notify_enable);
                json_return_and_die(array('status' => true));
            // API: /chess/resume
            // Resumes a game specified by "game_id" allowing further moves
            case 'resume':
                $observer = App::get_observer();
                $game_id = (x($_POST,'game_id') ? $_POST['game_id'] : '' );
                $g = chess_get_game($game_id);
                if(!$g['status']) {
                    json_return_and_die(array('errormsg' => 'Invalid game', 'status' => false));
                }
                // Verify that observer is a valid player
                $game = json_decode($g['game']['obj'], true);
                if(!in_array($observer['xchan_hash'], $game['players'])) {                    
                    json_return_and_die(array('errormsg' => 'You are not a valid player', 'status' => false));
                }                
                $success = chess_resume_game($g['game']); 
                if(!$success) {
                    json_return_and_die(array('errormsg' => 'Error resuming game', 'status' => false));
                } else {
                    json_return_and_die(array('status' => true));
                }   
            // API: /chess/end
            // Ends a game specified by "game_id" preventing further moves
            case 'end':
                $observer = App::get_observer();
                $game_id = (x($_POST,'game_id') ? $_POST['game_id'] : '' );
                $g = chess_get_game($game_id);
                if(!$g['status']) {
                    json_return_and_die(array('errormsg' => 'Invalid game', 'status' => false));
                }
                // Verify that observer is a valid player
                $game = json_decode($g['game']['obj'], true);
                if(!in_array($observer['xchan_hash'], $game['players'])) {                    
                    json_return_and_die(array('errormsg' => 'You are not a valid player', 'status' => false));
                }                
                $success = chess_end_game($g['game']); 
                if(!$success) {
                    json_return_and_die(array('errormsg' => 'Error ending game', 'status' => false));
                } else {
                    json_return_and_die(array('status' => true));
                }   
            // API: /chess/delete
            // Deletes a game specified by "game_id"
            case 'delete':
                if (!local_channel()) {
                    json_return_and_die(array('errormsg' => 'Must be local channel.', 'status' => false));
                }
                $channel = App::get_channel(); 
                $game_id = (x($_POST,'game_id') ? $_POST['game_id'] : '' );
                $d = chess_delete_game($game_id, $channel); 
                if(!$d['status']) {
                    json_return_and_die(array('errormsg' => 'Error deleting game', 'status' => false));
                } else {
                    json_return_and_die(array('status' => true));
                }   
            // API: /chess/revert
            // Reverts a game specified by "game_id" to a previous board position
            // specified by the "mid" of the child post of the original game post 
            // in the item table
            // TODO: Determine why the board position in the game item is not actually
            // being reverted
            case 'revert':
                $observer = App::get_observer();
                $game_id = $_POST['game_id'];
                $g = chess_get_game($game_id);
                if(!$g['status']) {
                    json_return_and_die(array('errormsg' => 'Invalid game', 'status' => false));
                }
                // Verify that observer is a valid player
                $game = json_decode($g['game']['obj'], true);
                if(!in_array($observer['xchan_hash'], $game['players'])) {                    
                    json_return_and_die(array('errormsg' => 'You are not a valid player', 'status' => false));
                }
                $active = ($game['active'] === $observer['xchan_hash'] ? true : false);
                if(!$active) {
                    json_return_and_die(array('errormsg' => 'It is not your turn', 'status' => false));
                }
                $r = chess_revert_position($g['game'], $observer, $_POST['mid']);
                if(!$r['status']) {
                    json_return_and_die(array('errormsg' => 'Error reverting game', 'status' => false));
                }
                json_return_and_die(array('status' => true));                
            // API: /chess/history
            // Retrieves all the board positions for a game in order to populate the
            // history viewer in the control panel
            case 'history':
                $observer = App::get_observer();
                $game_id = $_POST['game_id'];
                $g = chess_get_game($game_id);
                if(!$g['status']) {
                    json_return_and_die(array('errormsg' => 'Invalid game', 'status' => false));
                }
                // Verify that observer is a valid player
                $game = json_decode($g['game']['obj'], true);
                if(!in_array($observer['xchan_hash'], $game['players'])) {                    
                    json_return_and_die(array('errormsg' => 'You are not a valid player', 'status' => false));
                }
                $player = array_search($observer['xchan_hash'], $game['players']);
                $h = chess_get_history($g['game']);
                if(!$h['status']) {
                    json_return_and_die(array('errormsg' => 'Error retrieving game history', 'status' => false));
                }
                json_return_and_die(array('history' => $h['history'], 'status' => true));
            // API: /chess/update
            // Updates a game specified by "game_id" with a new board position specified
            // by "newPosFEN" in FEN-format
            case 'update':
                $observer = App::get_observer();
                $game_id = $_POST['game_id'];
                $g = chess_get_game($game_id);
                if(!$g['status']) {
                    json_return_and_die(array('errormsg' => 'Invalid game', 'status' => false));
                }
                // Verify that observer is a valid player
                $game = json_decode($g['game']['obj'], true);
                if(!in_array($observer['xchan_hash'], $game['players'])) {                    
                    json_return_and_die(array('errormsg' => 'You are not a valid player', 'status' => false));
                }
                $player = array_search($observer['xchan_hash'], $game['players']);
                $active = ($game['active'] === $game['players'][$player] ? true : false);
                json_return_and_die(array('position' => $game['position'], 'myturn' => $active, 'ended' => $game['ended'], 'status' => true));
            // API: /chess/move
            // Adds a new board position by creating a child post for the original 
            // game item. 
            case 'move':
                $observer = App::get_observer();
                $game_id = $_POST['game_id'];
                $newPosFEN = $_POST['newPosFEN'];
                $g = chess_get_game($game_id);
                if(!$g['status']) {                 
                    notice(t('Invalid game.') . EOL);
                    json_return_and_die(array('errormsg' => 'Invalid game ID', 'status' => false));
                }                
                // Verify that observer is a valid player
                $game = json_decode($g['game']['obj'], true);
                if(!in_array($observer['xchan_hash'], $game['players'])) {                    
                    notice(t('You are not a player in this game.') . EOL);
                    goaway('/chess');
                }
                $player = array_search($observer['xchan_hash'], $game['players']);
                $color = $game['colors'][$player];
                $active = ($game['active'] === $observer['xchan_hash'] ? true : false);
                if(!$active) {
                    json_return_and_die(array('errormsg' => 'It is not your turn', 'status' => false));
                }
                if(x($game,'ended') && intval($game['ended']) === 1) {
                    json_return_and_die(array('errormsg' => 'The game is over', 'status' => false));
                }
                $move = chess_make_move($observer, $newPosFEN, $g['game']);
                if ($move['status']) {
                    $active_xchan = ($game['players'][0] === $observer['xchan_hash'] ? $game['players'][1] :  $game['players'][0]);
                    if(chess_set_position(chess_get_game($game_id)['game'], $newPosFEN)) {
                        chess_set_active(chess_get_game($game_id)['game'], $active_xchan);
                    }                       
                    json_return_and_die(array('status' => true));
                } else {
                    json_return_and_die(array('errormsg' => 'Move failed', 'status' => false));
                }
            default:
                break;
        }
    }
    if (argc() > 2) {
        switch (argv(2)) {
            // API: /chess/[channelname]/new/
            // This endpoint handles the new game form submission and creates a new
            // game between two channels specified by the standard ACL
            case 'new':
                if (!local_channel()) {
                    notice(t('You must be a local channel to create a game.') . EOL);
                    return;
                }
                // Ensure ACL specifies exactly one other channel
                $channel = App::get_channel();
                $acl = new Zotlabs\Access\AccessList($channel);
                $acl->set_from_array($_REQUEST);
                $perms = $acl->get();
                $allow_cid = expand_acl($perms['allow_cid']);
				$valid = 0;
				if(count($allow_cid) > 1) {
					foreach($allow_cid as $allow) {
						if($allow == $channel['channel_hash'])
							continue;
						$valid ++;
					}
				}
                if ($valid != 1) {
                    notice(t('You must select one opponent that is not yourself.') . EOL);
                    return;
                } else {
                    info(t('Creating new game...') . EOL);  
                    // Get the game owner's color choice
                    $color = '';
                    if ($_POST['color'] === 'white' || $_POST['color'] === 'black') {
                        $color = $_POST['color'];
                    } else {
                        notice(t('You must select white or black.') . EOL);
                        return;
                    }
                    $game = chess_create_game($channel, $color, $acl);
                    if ($game['status']) {
                        goaway('/chess/' . $channel['channel_address'] . '/' . $game['item']['resource_id']);
                    } else {
                        notice(t('Error creating new game.') . EOL);
                    }
                    return;
                }
            default:
                break;
        }
    }
}

/**
 * @brief Outputs the main content of the page, depending on the URL
 *
 * @return string HTML content
 */
function chess_content($a) {
    // Include the custom CSS and JavaScript necessary for the chess board
    head_add_css('/addon/chess/view/css/chessboard.css');
    head_add_js('/addon/chess/view/js/chessboard.js');

    // If the user is not a local channel, then they must use a URL like /chess/localchannel
    // to specify which local channel "chess host" they are visiting
    $which = null;
    if(argc() > 1) {
        $which = argv(1);
	$user = q("select channel_id from channel where channel_address = '%s' and channel_removed = 0  limit 1",
		dbesc($which)
	);

	if(!$user) {
		notice( t('Requested channel is not available.') . EOL );
		App::$error = 404;
		return;
	}
    }
    if(! $which) {
            if(local_channel()) {
                    $channel = App::get_channel();
                    if($channel && $channel['channel_address'])
                    $which = $channel['channel_address'];
            }
    }
    if(! $which) {
            notice( t('You must select a local channel /chess/channelname') . EOL );
            return;
    }
    
    if (argc() > 2) {
        switch (argv(2)) {
            case 'new':
                if (!local_channel()) {
                    notice(t('You must be logged in to see this page.') . EOL);
                    return;
                }
                $acl = new Zotlabs\Access\AccessList(App::get_channel());
                $channel_acl = $acl->get();

                require_once('include/acl_selectors.php');
                
                $channel = App::get_channel();
                $o = replace_macros(get_markup_template('chess_new.tpl', 'addon/chess'), array(
                    '$acl' => populate_acl($channel_acl, false),
		    '$allow_cid' => acl2json($channel_acl['allow_cid']),
		    '$allow_gid' => acl2json($channel_acl['allow_gid']),
		    '$deny_cid' => acl2json($channel_acl['deny_cid']),
		    '$deny_gid' => acl2json($channel_acl['deny_gid']),
                    '$channel' => $channel['channel_address']
                ));
                return $o;
            default:
                // argv(2) is the resource_id for an existing game
                // argv(1) should be the owner channel of the game
                $owner = argv(1);
                $hash = q("select channel_hash from channel where channel_address = '%s' and channel_removed = 0  limit 1",
                            dbesc($owner)
                );
                $owner_hash = $hash[0]['channel_hash'];
                $game_id = argv(2);
                $observer = App::get_observer();
                $g = chess_get_game($game_id);
                if(!$g['status'] || $g['game']['owner_xchan'] !== $owner_hash) {
                    notice(t('Invalid game.') . EOL);
                    return;
                }
                // Verify that observer is a valid player
                $game = json_decode($g['game']['obj'], true);
                if(!in_array($observer['xchan_hash'], $game['players'])) {                    
                    notice(t('You are not a player in this game.') . EOL);
                    goaway('/chess');
                }
                $player = array_search($observer['xchan_hash'], $game['players']);
                $color = $game['colors'][$player];
                $active = ($game['active'] === $game['players'][$player] ? true : false);
                $game_ended = ((!x($game,'ended') || $game['ended'] === 0) ? 0 : 1);
                $notify = intval(get_xconfig($observer['xchan_hash'],'chess','notifications'));
                logger('xconfig notifications: ' . $notify);
                $o = replace_macros(get_markup_template('chess_game.tpl', 'addon/chess'), array(
                    '$myturn' => ($active ? 'true' : 'false'),
                    '$active' => $active,
                    '$color' => $color,
                    '$game_id' => $game_id,
                    '$position' => $game['position'],
                    '$ended' => $game_ended,
                    '$notifications' => $notify
                ));
                // TODO: Create settings panel to set the board size and eventually the board theme
                // and other customizations
                return $o;
        }
    }
    // If the URL was simply /chess, then if the script reaches this point the 
    // user is a local channel, so load any games they may have as well as a board 
    // they can move pieces around on without storing the moves anywhere
    $o .= replace_macros(get_markup_template('chess.tpl', 'addon/chess'), array(
        '$color' => 'white'
    ));
    return $o;
}

/**
 * @brief Create a new game by generating a new item table record as a standard 
 * post. This will propagate to the other player and provide a link to begin playing
 *
 * @return array Status and parameters of the new game post
 */
function chess_create_game($channel, $color, $acl) {

    $resource_type = 'chess';
    // Generate unique resource_id using the same method as item_message_id()
    do {
        $dups = false;
        $resource_id = random_string(5);
        $r = q("SELECT mid FROM item WHERE resource_id = '%s' AND resource_type = '%s' AND uid = %d LIMIT 1", 
                dbesc($resource_id), 
                dbesc($resource_type), 
                intval($channel['channel_id'])
        );
        if (count($r))
            $dups = true;
    } while ($dups == true);
    $ac = $acl->get();
    $mid = item_message_id(); 
    $arr = array();  // Initialize the array of parameters for the post
    $objtype = ACTIVITY_OBJ_CHESSGAME; 
    $perms = $acl->get();
    $allow_cid = expand_acl($perms['allow_cid']);
	$player2 = null;
	if(count($allow_cid)) {
		foreach($allow_cid as $allow) {
			if($allow === $channel['channel_hash'])
				continue;
			$player2 = $allow;
		}
	}


    $players = array($channel['channel_hash'], $player2);
    $object = json_encode(array(
		'id' => z_root() . '/chess/game/' . $resource_id,
        'players' => $players, 
        'colors' => array($color, ($color === 'white' ? 'black' : 'white')),
        'active' => ($color === 'white' ? $players[0] : $players[1]),
        'position' => 'start',
        'version' => chess_get_version()    // Potential compatability issues 
    ));
    $item_hidden = 0; // TODO: Allow form creator to send post to ACL about new game automatically
    $game_url = z_root() . '/chess/' . $channel['channel_address'] . '/' . $resource_id;
    $arr['aid']           = $channel['channel_account_id'];
    $arr['uid']           = $channel['channel_id'];
    $arr['mid']           = $mid;
    $arr['parent_mid']    = $mid;
    $arr['item_hidden']     = $item_hidden;
    $arr['resource_type']   = $resource_type;  
    $arr['resource_id']   = $resource_id;
    $arr['owner_xchan']     = $channel['channel_hash'];
    $arr['author_xchan']    = $channel['channel_hash'];
    // Store info about the type of chess item using the "title" field
    // Other types include 'move' for children items but may in the future include
    // additional types that will determine how the "object" field is interpreted
    $arr['title']         = 'game';     
    $arr['allow_cid']       = $ac['allow_cid'];
    $arr['item_wall']       = 1;
    $arr['item_origin']     = 1;
    $arr['item_thread_top'] = 1;
    $arr['item_private']    = intval($acl->is_private());
    $arr['verb']          = ACTIVITY_POST;
    $arr['obj_type']      = $objtype;
    $arr['obj']           = $object;
    $arr['body']          = '[table][tr][td][h1]New Chess Game[/h1][/td][/tr][tr][td][zrl='.$game_url.']Click here to play[/zrl][/td][/tr][/table]';
    
    $post = item_store($arr);
    $item_id = $post['item_id'];

    if ($item_id) {
		Zotlabs\Daemon\Master::Summon(['Notifier','activity',$item_id]);
        return array('item' => $arr, 'status' => true);
    } else {
        return array('item' => null, 'status' => false);
    }    
}

/**
 * @brief Create a new move in the game by generating a child item for the game post
 *
 * @param $observer Authenticated observer (remote or local channel) viewing the page
 * @param $newPosFEN New board position in FEN-format
 * @param $g Game post item table record with all the game information
 * @return array Success status and array of new post data
 */
function chess_make_move($observer, $newPosFEN, $g) {
    $resource_type = 'chess';
    $resource_id = $g['resource_id'];
    $mid = item_message_id(); 
    $arr = array();  // Initialize the array of parameters for the post
    $objtype = ACTIVITY_OBJ_CHESSGAME; 
    $object = json_encode(array(
		'id' => z_root() . '/chess/game/' . $resource_id,
        'position' => $newPosFEN,    // Store the new board position in FEN notation
        'version' => chess_get_version()    // Potential compatability issues 
    ));
    $item_hidden = 0; // TODO: Allow form creator to send post to ACL about new game automatically
    $r = q("select channel_address from channel where channel_id = %d limit 1",
			intval($g['uid'])
    );
    $channel_address = '';
    if($r) {
        $channel_address = $r[0]['channel_address'] ;
    }
    
    $arr['aid']           = $g['aid'];
    $arr['uid']           = $g['uid'];
    $arr['mid']           = $mid;
    $arr['parent_mid']    = $g['mid'];
    $arr['item_hidden']     = $item_hidden;
    $arr['resource_type']   = $resource_type;  
    $arr['resource_id']   = $resource_id;           // Game ID
    $arr['owner_xchan']     = $g['owner_xchan'];    // Tracks the owner of the game
    $arr['author_xchan']    = $observer['xchan_hash'];  // Denotes which player made this move
    // Store info about the type of chess item using the "title" field
    // Other types include 'move' for children items but may in the future include
    // additional types that will determine how the "object" field is interpreted
    $arr['title']         = 'move';     
    $arr['item_wall']       = 1;
    $arr['item_origin']     = 1;
    $arr['item_thread_top'] = 0;
    $arr['item_private']    = 1;
    $arr['verb']          = ACTIVITY_POST;
    $arr['obj_type']      = $objtype;
    $arr['obj']           = $object;
    $arr['body']          = 'New position (FEN format): ' . $newPosFEN;
    
    $post = item_store($arr);
    $item_id = $post['item_id'];

    if ($item_id) {
		Zotlabs\Daemon\Master::Summon(['Notifier','activity',$item_id]);
        return array('item' => $arr, 'status' => true);
    } else {
        return array('item' => null, 'status' => false);
    }       
}

/**
 * @brief Change the game item to specify which player should take the next turn
 *
 * @param $xchan Unique hash associated with which channel should take the next turn
 * @param $g Game post item table record with all the game information
 * @return boolean Success of game item update
 */
function chess_set_active($g, $xchan) {    
    $game = json_decode($g['obj'], true);
    $game['active'] = $xchan;

	if(! $game['id'])
		$game['id'] = $game['resource_id'];

    $gameobj = json_encode($game);
    $r = q("UPDATE item set obj = '%s' WHERE mid = '%s' AND resource_type = '%s'", 
                        dbesc($gameobj), 
                        dbesc($g['mid']),
                        dbesc('chess')
                );   
    return $r;
}

/**
 * @brief Updates the game item with the latest board position
 *
 * @param $position New board position in FEN-format
 * @param $g Game post item table record with all the game information
 * @return array Success of game item update
 */
function chess_set_position($g, $position) {    
    $game = json_decode($g['obj'], true);
    $game['position'] = $position;

	if(! $game['id'])
		$game['id'] = $game['resource_id'];

    $gameobj = json_encode($game);
    $r = q("UPDATE item set obj = '%s' WHERE mid = '%s' AND resource_type = '%s'", 
                        dbesc($gameobj), 
                        dbesc($g['mid']),
                        dbesc('chess')
                );   
    return $r;
}

/**
 * @brief Retrieve the game item data structure
 *
 * @param $game_id Unique game ID string
 * @return array Success of retrieval and game item 
 */
function chess_get_game($game_id) {
    $g = q("SELECT * FROM item WHERE resource_id = '%s' AND resource_type = '%s' and mid = parent_mid AND item_deleted = 0 LIMIT 1", 
            dbesc($game_id), 
            dbesc('chess')
    );
    if (!$g) {
        return array('game' => null, 'status' => false);
    } else {
        return array('game' => $g[0], 'status' => true);
    }
}

/**
 * @brief Retrieve all board positions of a game
 *
 * @param $g Game post item table record with all the game information
 * @return array Success of retrieval and game history
 */
function chess_get_history($g) {
    $parentmid = $g['mid'];
    $moves = q("SELECT mid,obj,author_xchan FROM item WHERE resource_type = '%s' AND resource_id = '%s' AND parent_mid = '%s' AND mid != parent_mid order by id",  
            dbesc('chess'),
            dbesc($g['resource_id']),
            dbesc($parentmid)
    );
    if (!$moves) {
        return array('history' => null, 'status' => false);
    } else {
        return array('history' => $moves, 'status' => true);
    }
}

/**
 * @brief Revert the game to a previous board position
 *
 * @param $g Game post item table record with all the game information
 * @param $observer Authenticated observer (remote or local channel) viewing the page
 * @return array Success of board position reversion
 */
function chess_revert_position($g, $observer, $mid) {    
    $m = q("SELECT obj FROM item WHERE resource_type = '%s' AND resource_id = '%s' AND mid = '%s' LIMIT 1",  
            dbesc('chess'),
            dbesc($g['resource_id']),
            dbesc($mid)
    );
    if (!$m) {
        return array('status' => false);
    } else {
        $gameobj = json_decode($g['obj'], true);
        $moveobj = json_decode($m[0]['obj'], true);
        $move = chess_make_move($observer, $moveobj['position'], $g);
        if ($move['status']) {         
            if(chess_set_position($g, $moveobj['position'])) {
                $active_xchan = ($gameobj['players'][0] === $observer['xchan_hash'] ? $gameobj['players'][1] :  $gameobj['players'][0]);
                chess_set_active($g, $active_xchan);
                return array('status' => true);
            } else {
                return array('status' => false);
            }
        } else {
            return array('status' => false);
        }
    }
}

/**
 * @brief Retrieve a list of games in which the observer is a participant, separating
 * lists of those owned and those not owned
 *
 * @param $owner_address The channel name taken from the URL /chess/[channelname]
 * @param $observer Authenticated observer (remote or local channel) viewing the page
 * @return array Success of games retrieval and the games data
 */
function chess_get_games($observer, $owner_address) {
    $g = [];
    $g['owner_active'] = $g['player_active'] = $g['owner_ended'] = $g['player_ended'] = [];    
    
    $hash = q("select channel_hash from channel where channel_address = '%s' and channel_removed = 0  limit 1",
                            dbesc($owner_address)
                );
    if (!$hash) {        
        $g['owner_active'] = $g['player_active'] = $g['owner_ended'] = $g['player_ended'] = null;
        return array('games' => $g, 'status' => false);
    }
    $owner_hash = $hash[0]['channel_hash'];
    // This is a potentially expensive query if there are many chess games
    $games = q("SELECT * FROM item WHERE resource_type = '%s' AND title = '%s' AND owner_xchan = '%s' AND obj LIKE '%s' AND item_deleted = 0 order by id desc",  
            dbesc('chess'),
            dbesc('game'),
            dbesc($owner_hash),
            dbesc('%' . $observer['xchan_hash'] . '%')
    );
    if (!$games) {
        $g['owner_active'] = $g['player_active'] = $g['owner_ended'] = $g['player_ended'] = null;
        return array('games' => $g, 'status' => false);
    }
    foreach($games as $game) {
        $gameobj = json_decode($game['obj'], true);
        // Get the names of the players
        $info = chess_get_info($observer, $game['resource_id']);
        // Determine opponent's name
        $opponent_name = (($observer['xchan_hash'] === $gameobj['players'][0]) ? $info['players'][1] : $info['players'][0]);
        $active = (($observer['xchan_hash'] === $gameobj['active']) ? true : false);
        $date = array_shift(explode(' ',$game['created']));
        if($game['owner_xchan'] === $observer['xchan_hash']) {
            if(!x($gameobj,'ended') || $gameobj['ended'] === 0) {
                $g['owner_active'][] = array('plink' => $game['plink'], 'game_id' => $game['resource_id'], 'date' => $date, 'opponent' => $opponent_name, 'active' => $active, 'obj' => $gameobj);
            } else {
                $g['owner_ended'][] = array('plink' => $game['plink'], 'game_id' => $game['resource_id'], 'date' => $date, 'opponent' => $opponent_name, 'active' => $active,  'obj' => $gameobj);
            }
        } elseif (in_array($observer['xchan_hash'], $gameobj['players'])) { 
            if(!x($gameobj,'ended') || $gameobj['ended'] === 0) {
                $g['player_active'][] = array('plink' => $game['plink'], 'game_id' => $game['resource_id'], 'date' => $date, 'opponent' => $opponent_name, 'active' => $active,  'obj' => $gameobj);
            } else {
                $g['player_ended'][] = array('plink' => $game['plink'], 'game_id' => $game['resource_id'], 'date' => $date, 'opponent' => $opponent_name, 'active' => $active,  'obj' => $gameobj);
            }
        }
    }
    $g['owner_active'] = ((empty($g['owner_active'])) ? null : $g['owner_active']);
    $g['owner_ended'] = ((empty($g['owner_ended'])) ? null : $g['owner_ended']);
    $g['player_active'] = ((empty($g['player_active'])) ? null : $g['player_active']);
    $g['player_ended'] = ((empty($g['player_ended'])) ? null : $g['player_ended']);
    
    return array('games' => $g, 'status' => true);
    
    
}

/**
 * @brief Delete a chess game using the standard drop_item method for posts in the
 * item table
 *
 * @param $game_id unique ID of the game to be deleted
 * @param $channel The authenticated local channel requesting the deletion
 * @return array Success of deletion and item that was deleted
 */
function chess_delete_game($game_id, $channel) {
    $items = q("SELECT id FROM item WHERE resource_type = '%s' AND resource_id = '%s' AND uid = %d AND item_deleted = 0 limit 1",
            dbesc('chess'),
            dbesc($game_id),
            intval($channel['channel_id'])
    );
    if (!$items) {
        return array('items' => null, 'status' => false);   
    } else {
        $drop = drop_item($items[0]['id'],false,DROPITEM_NORMAL,true);
        return array('items' => $items, 'status' => (($drop === 1) ? true : false));   
    }
}

/**
 * @brief Ends a chess game by setting a game item object property. Assumes the 
 * permissions to perform this action are already verified
 *
 * @param $g Game post item table record with all the game information
 * @return array Success of ending game
 */
function chess_end_game($g) {  
    $game = json_decode($g['obj'], true);
    $game['ended'] = 1; // An active game will have ended = 0 or will not have "ended" at all
    $gameobj = json_encode($game);
    $r = q("UPDATE item set obj = '%s' WHERE mid = '%s' AND resource_type = '%s'", 
                        dbesc($gameobj), 
                        dbesc($g['mid']),
                        dbesc('chess')
                );   
    return $r;
}

/**
 * @brief Resumes a chess game by setting a game item object property. Assumes the 
 * permissions to perform this action are already verified
 *
 * @param $g Game post item table record with all the game information
 * @return array Success of resuming game
 * @todo Combine this with chess_end_game() with a 0/1 input parameter
 */
function chess_resume_game($g) {  
    $game = json_decode($g['obj'], true);
    $game['ended'] = 0; // An active game will have ended = 0 or will not have "ended" at all
    $gameobj = json_encode($game);
    $r = q("UPDATE item set obj = '%s' WHERE mid = '%s' AND resource_type = '%s'", 
                        dbesc($gameobj), 
                        dbesc($g['mid']),
                        dbesc('chess')
                );   
    return $r;
}

/**
 * @brief Retrieve various info about a game, including the players' names and the 
 * permanent link to the game conversation
 *
 * @param $game_id unique ID of the game to be deleted
 * @param $observer Authenticated observer (remote or local channel) viewing the page
 * @return array Success of retrieval and the game info
 */
function chess_get_info($observer, $game_id) {
    // Get the game by game_id and 

    $g = chess_get_game($game_id);
    if (!$g) {
        return array('players' => null, 'status' => false);   
    } 
    $game = json_decode($g['game']['obj'], true);
    // If the observer is a player in the game, get the names of the players
    if(in_array($observer['xchan_hash'], $game['players'])) { 
        $player_names = [];
        foreach($game['players'] as $xchan_hash) {
            $p = q("select xchan_name from xchan where xchan_hash = '%s' limit 1",
                    dbesc($xchan_hash)
            );
            if (!$p) {
                return array('players' => null, 'status' => false);  
            } 
            $player_names[] = $p[0]['xchan_name']; 
        }
        return array('players' => $player_names, 'plink' => $g['game']['plink'], 'status' => true);   
    } else {        
        return array('players' => null, 'plink' => null, 'status' => false);  
    }
    
}
/*
function chess_settings_post(&$a,&$b) {
    $observer = App::get_observer();
    if($_POST['chess-submit']) {
        set_xconfig($observer['xchan_hash'],'chess','notifications',intval($_POST['notifications']));
        info( t('Chess settings updated.') . EOL);
    }
}


function chess_settings(&$a,&$s) {    
    if(! local_channel())
            return;

    $observer = App::get_observer();
    $notifications = get_xconfig($observer['xchan_hash'],'chess','notifications');

    $sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
            '$field'	=> array('notifications', t('Enable notifications'), $notifications, '', $yes_no),
    ));

    $s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
            '$addon' 	=> array('chess', '<img src="addon/chess/chess.png" style="width:auto; height:1em; margin:-3px 5px 0px 0px;">' . t('Chess Settings'), '', t('Submit')),
            '$content'	=> $sc
    ));

    return;
}
*/
function chess_game_settings() {  
    $observer = App::get_observer();
    $notifications = get_xconfig($observer['xchan_hash'],'chess','notifications');

    $sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
            '$field'	=> array('chess_notify_enable', t('Enable notifications'), $notifications, '', $yes_no),
    ));

    return $sc;
}
