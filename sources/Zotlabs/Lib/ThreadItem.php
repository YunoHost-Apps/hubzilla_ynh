<?php /** @file */

namespace Zotlabs\Lib;

require_once('include/text.php');

/**
 * A thread item
 */

class ThreadItem {

	public  $data = array();
	private $template = 'conv_item.tpl';
	private $comment_box_template = 'comment_item.tpl';
	private $commentable = false;
	// list of supported reaction emojis - a site can over-ride this via config system.reactions
	private $reactions = ['1f60a','1f44f','1f37e','1f48b','1f61e','2665','1f606','1f62e','1f634','1f61c','1f607','1f608'];
	private $toplevel = false;
	private $children = array();
	private $parent = null;
	private $conversation = null;
	private $redirect_url = null;
	private $owner_url = '';
	private $owner_photo = '';
	private $owner_name = '';
	private $wall_to_wall = false;
	private $threaded = false;
	private $visiting = false;
	private $channel = null;
	private $display_mode = 'normal';


	public function __construct($data) {
				
		$this->data = $data;
		$this->toplevel = ($this->get_id() == $this->get_data_value('parent'));

		// Prepare the children
		if(count($data['children'])) {
			foreach($data['children'] as $item) {

				/*
				 * Only add those that will be displayed
				 */

				if((! visible_activity($item)) || array_key_exists('blocked',$item)) {
					continue;
				}

				$child = new ThreadItem($item);
				$this->add_child($child);
			}
		}

		// allow a site to configure the order and content of the reaction emoji list
		if($this->toplevel) {
			$x = get_config('system','reactions');
			if($x && is_array($x) && count($x)) {
				$this->reactions = $x;
			}
		}
	}

	/**
	 * Get data in a form usable by a conversation template
	 *
	 * Returns:
	 *      _ The data requested on success
	 *      _ false on failure
	 */

	public function get_template_data($conv_responses, $thread_level=1) {
	
		$result = array();

		$item     = $this->get_data();

		$commentww = '';
		$sparkle = '';
		$buttons = '';
		$dropping = false;
		$star = false;
		$isstarred = "unstarred fa-star-o";
		$indent = '';
		$osparkle = '';
		$total_children = $this->count_descendants();
		$unseen_comments = (($item['real_uid']) ? 0 : $this->count_unseen_descendants());

		$conv = $this->get_conversation();
		$observer = $conv->get_observer();

		$lock = ((($item['item_private'] == 1) || (($item['uid'] == local_channel()) && (strlen($item['allow_cid']) || strlen($item['allow_gid']) 
			|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
			? t('Private Message')
			: false);
		$shareable = ((($conv->get_profile_owner() == local_channel() && local_channel()) && ($item['item_private'] != 1)) ? true : false);

		// allow an exemption for sharing stuff from your private feeds
		if($item['author']['xchan_network'] === 'rss')
			$shareable = true;

		$mode = $conv->get_mode();

		if(local_channel() && $observer['xchan_hash'] === $item['author_xchan'])
			$edpost = array(z_root()."/editpost/".$item['id'], t("Edit"));
		else
			$edpost = false;


		if($observer['xchan_hash'] == $this->get_data_value('author_xchan') 
			|| $observer['xchan_hash'] == $this->get_data_value('owner_xchan') 
			|| $this->get_data_value('uid') == local_channel())
			$dropping = true;


		if(array_key_exists('real_uid',$item)) {
			$edpost = false;
			$dropping = false;
		}


		if($dropping) {
			$drop = array(
				'dropping' => $dropping,
				'delete' => t('Delete'),
			);
		}		
// FIXME
		if($observer_is_pageowner) {		
			$multidrop = array(
				'select' => t('Select'), 
			);
		}

		$filer = ((($conv->get_profile_owner() == local_channel()) && (! array_key_exists('real_uid',$item))) ? t("Save to Folder") : false);

		$profile_avatar = $item['author']['xchan_photo_m'];
		$profile_link   = chanlink_url($item['author']['xchan_url']);
		$profile_name   = $item['author']['xchan_name'];

		$location = format_location($item);
		$isevent = false;
		$attend = null;
		$canvote = false;

		// process action responses - e.g. like/dislike/attend/agree/whatever
		$response_verbs = array('like');
		if(feature_enabled($conv->get_profile_owner(),'dislike'))
			$response_verbs[] = 'dislike';
		if($item['obj_type'] === ACTIVITY_OBJ_EVENT) {
			$response_verbs[] = 'attendyes';
			$response_verbs[] = 'attendno';
			$response_verbs[] = 'attendmaybe';
			if($this->is_commentable()) {
				$isevent = true;
				$attend = array( t('I will attend'), t('I will not attend'), t('I might attend'));
			}
		}

		$consensus = (intval($item['item_consensus']) ? true : false);
		if($consensus) {
			$response_verbs[] = 'agree';
			$response_verbs[] = 'disagree';
			$response_verbs[] = 'abstain';
			if($this->is_commentable()) {
				$conlabels = array( t('I agree'), t('I disagree'), t('I abstain'));
				$canvote = true;
			}
		}

		if(! feature_enabled($conv->get_profile_owner(),'dislike'))
			unset($conv_responses['dislike']);
  
		$responses = get_responses($conv_responses,$response_verbs,$this,$item);

		$my_responses = [];
		foreach($response_verbs as $v) {
			$my_responses[$v] = (($conv_responses[$v][$item['mid'] . '-m']) ? 1 : 0);
		}

		$like_count = ((x($conv_responses['like'],$item['mid'])) ? $conv_responses['like'][$item['mid']] : '');
		$like_list = ((x($conv_responses['like'],$item['mid'])) ? $conv_responses['like'][$item['mid'] . '-l'] : '');
		if (count($like_list) > MAX_LIKERS) {
			$like_list_part = array_slice($like_list, 0, MAX_LIKERS);
			array_push($like_list_part, '<a href="#" data-toggle="modal" data-target="#likeModal-' . $this->get_id() . '"><b>' . t('View all') . '</b></a>');
		} else {
			$like_list_part = '';
		}
		$like_button_label = tt('Like','Likes',$like_count,'noun');

		if (feature_enabled($conv->get_profile_owner(),'dislike')) {
			$dislike_count = ((x($conv_responses['dislike'],$item['mid'])) ? $conv_responses['dislike'][$item['mid']] : '');
			$dislike_list = ((x($conv_responses['dislike'],$item['mid'])) ? $conv_responses['dislike'][$item['mid'] . '-l'] : '');
			$dislike_button_label = tt('Dislike','Dislikes',$dislike_count,'noun');
			if (count($dislike_list) > MAX_LIKERS) {
				$dislike_list_part = array_slice($dislike_list, 0, MAX_LIKERS);
				array_push($dislike_list_part, '<a href="#" data-toggle="modal" data-target="#dislikeModal-' . $this->get_id() . '"><b>' . t('View all') . '</b></a>');
			} else {
				$dislike_list_part = '';
			}
		}

		$showlike    = ((x($conv_responses['like'],$item['mid'])) ? format_like($conv_responses['like'][$item['mid']],$conv_responses['like'][$item['mid'] . '-l'],'like',$item['mid']) : '');
		$showdislike = ((x($conv_responses['dislike'],$item['mid']) && feature_enabled($conv->get_profile_owner(),'dislike'))  
				? format_like($conv_responses['dislike'][$item['mid']],$conv_responses['dislike'][$item['mid'] . '-l'],'dislike',$item['mid']) : '');

		/*
		 * We should avoid doing this all the time, but it depends on the conversation mode
		 * And the conv mode may change when we change the conv, or it changes its mode
		 * Maybe we should establish a way to be notified about conversation changes
		 */

		$this->check_wall_to_wall();
		
		if($this->is_toplevel()) {
			// FIXME check this permission
			if(($conv->get_profile_owner() == local_channel()) && (! array_key_exists('real_uid',$item))) {

// FIXME we don't need all this stuff, some can be done in the template

				$star = array(
					'do' => t("Add Star"),
					'undo' => t("Remove Star"),
					'toggle' => t("Toggle Star Status"),
					'classdo' => (intval($item['item_starred']) ? "hidden" : ""),
					'classundo' => (intval($item['item_starred']) ? "" : "hidden"),
					'isstarred' => (intval($item['item_starred']) ? "starred fa-star" : "unstarred fa-star-o"),
					'starred' =>  t('starred'),
				);

			}
		} 
		else {
			$indent = 'comment';
		}


		$verified = (intval($item['item_verified']) ? t('Message signature validated') : '');
		$forged = ((($item['sig']) && (! intval($item['item_verified']))) ? t('Message signature incorrect') : '');
		$unverified = '' ; // (($this->is_wall_to_wall() && (! intval($item['item_verified']))) ? t('Message cannot be verified') : '');



		// FIXME - check this permission
		if($conv->get_profile_owner() == local_channel()) {
			$tagger = array(
				'tagit' => t("Add Tag"),
				'classtagger' => "",
			);
		}

		$server_role = get_config('system','server_role');

		$has_bookmarks = false;
		if(is_array($item['term'])) {
			foreach($item['term'] as $t) {
				if((get_account_techlevel() > 0) && ($t['ttype'] == TERM_BOOKMARK))
					$has_bookmarks = true;
			}
		}

		$has_event = false;
		if(($item['obj_type'] === ACTIVITY_OBJ_EVENT) && $conv->get_profile_owner() == local_channel())
			$has_event = true;

		if($this->is_commentable()) {
			$like = array( t("I like this \x28toggle\x29"), t("like"));
			$dislike = array( t("I don't like this \x28toggle\x29"), t("dislike"));
		}

		if ($shareable)
			$share = array( t('Share This'), t('share'));

		$dreport = '';

		$keep_reports = intval(get_config('system','expire_delivery_reports'));
		if($keep_reports === 0)
			$keep_reports = 30;

		if((! get_config('system','disable_dreport')) && strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC',"now - $keep_reports days")) > 0)
			$dreport = t('Delivery Report');

		if(strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC','now - 12 hours')) > 0)
			$indent .= ' shiny';


		localize_item($item);

		$body = prepare_body($item,true);

		// $viewthread (below) is only valid in list mode. If this is a channel page, build the thread viewing link
		// since we can't depend on llink or plink pointing to the right local location.
 
		$owner_address = substr($item['owner']['xchan_addr'],0,strpos($item['owner']['xchan_addr'],'@'));
		$viewthread = $item['llink'];
		if($conv->get_mode() === 'channel')
			$viewthread = z_root() . '/channel/' . $owner_address . '?f=&mid=' . $item['mid'];

		$comment_count_txt = sprintf( tt('%d comment','%d comments',$total_children),$total_children );
		$list_unseen_txt = (($unseen_comments) ? sprintf('%d unseen',$unseen_comments) : '');
		


		

		$children = $this->get_children();

		$has_tags = (($body['tags'] || $body['categories'] || $body['mentions'] || $body['attachments'] || $body['folders']) ? true : false);

		$tmp_item = array(
			'template' => $this->get_template(),
			'mode' => $mode,			
			'type' => implode("",array_slice(explode("/",$item['verb']),-1)),
			'body' => $body['html'],
			'tags' => $body['tags'],
			'categories' => $body['categories'],
			'mentions' => $body['mentions'],
			'attachments' => $body['attachments'],
			'folders' => $body['folders'],
			'text' => strip_tags($body['html']),
			'id' => $this->get_id(),
			'mid' => $item['mid'],
			'isevent' => $isevent,
			'attend' => $attend,
			'consensus' => $consensus,
			'conlabels' => $conlabels,
			'canvote' => $canvote,
			'linktitle' => sprintf( t('View %s\'s profile - %s'), $profile_name, $item['author']['xchan_addr']),
			'olinktitle' => sprintf( t('View %s\'s profile - %s'), $this->get_owner_name(), $item['owner']['xchan_addr']),
			'llink' => $item['llink'],
			'viewthread' => $viewthread,
			'to' => t('to'),
			'via' => t('via'),
			'wall' => t('Wall-to-Wall'),
			'vwall' => t('via Wall-To-Wall:'),
			'profile_url' => $profile_link,
			'item_photo_menu' => item_photo_menu($item),
			'dreport' => $dreport,
			'name' => $profile_name,
			'thumb' => $profile_avatar,
			'osparkle' => $osparkle,
			'sparkle' => $sparkle,
			'title' => $item['title'],
			'title_tosource' => get_pconfig($conv->get_profile_owner(),'system','title_tosource'),
			'ago' => relative_date($item['created']),
			'app' => $item['app'],
			'str_app' => sprintf( t('from %s'), $item['app']),
			'isotime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'c'),
			'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
			'editedtime' => (($item['edited'] != $item['created']) ? sprintf( t('last edited: %s'), datetime_convert('UTC', date_default_timezone_get(), $item['edited'], 'r')) : ''),
			'expiretime' => (($item['expires'] > NULL_DATE) ? sprintf( t('Expires: %s'), datetime_convert('UTC', date_default_timezone_get(), $item['expires'], 'r')):''),
			'lock' => $lock,
			'verified' => $verified,
			'unverified' => $unverified,
			'forged' => $forged,
			'location' => $location,
			'attend_label' => t('Attend'),
			'attend_title' => t('Attendance Options'),
			'vote_label' => t('Vote'),
			'vote_title' => t('Voting Options'),
			'indent' => $indent,
			'owner_url' => $this->get_owner_url(),
			'owner_photo' => $this->get_owner_photo(),
			'owner_name' => $this->get_owner_name(),
			'photo' => $body['photo'],
			'event' => $body['event'],
			'has_tags' => $has_tags,
			'reactions' => $this->reactions,
// Item toolbar buttons
			'emojis'   => (($this->is_toplevel() && $this->is_commentable() && feature_enabled($conv->get_profile_owner(),'emojis')) ? '1' : ''),
			'like'      => $like,
			'dislike'   => ((feature_enabled($conv->get_profile_owner(),'dislike')) ? $dislike : ''),
			'share'     => $share,
			'rawmid'	=> $item['mid'],
			'plink'     => get_plink($item),
			'edpost'    => $edpost, // ((feature_enabled($conv->get_profile_owner(),'edit_posts')) ? $edpost : ''),
			'star'      => ((feature_enabled($conv->get_profile_owner(),'star_posts')) ? $star : ''),
			'tagger'    => ((feature_enabled($conv->get_profile_owner(),'commtag')) ? $tagger : ''),
			'filer'     => ((feature_enabled($conv->get_profile_owner(),'filing')) ? $filer : ''),
			'bookmark'  => (($conv->get_profile_owner() == local_channel() && local_channel() && $has_bookmarks) ? t('Save Bookmarks') : ''),
			'addtocal'  => (($has_event) ? t('Add to Calendar') : ''),
			'drop'      => $drop,
			'multidrop' => ((feature_enabled($conv->get_profile_owner(),'multi_delete')) ? $multidrop : ''),
// end toolbar buttons

			'unseen_comments' => $unseen_comments,
			'comment_count' => $total_children,
			'comment_count_txt' => $comment_count_txt,
			'list_unseen_txt' => $list_unseen_txt,
			'markseen' => t('Mark all seen'),
			'responses' => $responses,
			'my_responses' => $my_responses,
			'like_count' => $like_count,
			'like_list' => $like_list,
			'like_list_part' => $like_list_part,
			'like_button_label' => $like_button_label,
			'like_modal_title' => t('Likes','noun'),
			'dislike_modal_title' => t('Dislikes','noun'),
			'dislike_count' => ((feature_enabled($conv->get_profile_owner(),'dislike')) ? $dislike_count : ''),
			'dislike_list' => ((feature_enabled($conv->get_profile_owner(),'dislike')) ? $dislike_list : ''),
			'dislike_list_part' => ((feature_enabled($conv->get_profile_owner(),'dislike')) ? $dislike_list_part : ''),
			'dislike_button_label' => ((feature_enabled($conv->get_profile_owner(),'dislike')) ? $dislike_button_label : ''),
			'modal_dismiss' => t('Close'),
			'showlike' => $showlike,
			'showdislike' => $showdislike,
			'comment' => $this->get_comment_box($indent),
			'previewing' => ($conv->is_preview() ? ' preview ' : ''),
			'wait' => t('Please wait'),
			'submid' => substr($item['mid'],0,32),
			'thread_level' => $thread_level
		);

		$arr = array('item' => $item, 'output' => $tmp_item);
		call_hooks('display_item', $arr);

		$result = $arr['output'];

		$result['children'] = array();
		$nb_children = count($children);

		$visible_comments = get_config('system','expanded_comments');
		if($visible_comments === false)
			$visible_comments = 3;

//		needed for scroll to comment from notification but needs more work
//		as we do not want to open all comments unless there is actually an #item_xx anchor
//		and the url fragment is not sent to the server. 
//		if(in_array(\App::$module,['display','update_display'])) 
//			$visible_comments = 99999;

		if(($this->get_display_mode() === 'normal') && ($nb_children > 0)) {
			foreach($children as $child) {
				$result['children'][] = $child->get_template_data($conv_responses, $thread_level + 1);
			}
			// Collapse
			if(($nb_children > $visible_comments) || ($thread_level > 1)) {
				$result['children'][0]['comment_firstcollapsed'] = true;
				$result['children'][0]['num_comments'] = $comment_count_txt;
				$result['children'][0]['hide_text'] = sprintf( t('%s show all'), '<i class="fa fa-chevron-down"></i>');
				if($thread_level > 1) {
					$result['children'][$nb_children - 1]['comment_lastcollapsed'] = true;
				}
				else {
					$result['children'][$nb_children - ($visible_comments + 1)]['comment_lastcollapsed'] = true;
				}
			}
		}
		
		$result['private'] = $item['item_private'];
		$result['toplevel'] = ($this->is_toplevel() ? 'toplevel_item' : '');

		if($this->is_threaded()) {
			$result['flatten'] = false;
			$result['threaded'] = true;
		}
		else {
			$result['flatten'] = true;
			$result['threaded'] = false;
		}

		return $result;
	}
	
	public function get_id() {
		return $this->get_data_value('id');
	}

	public function get_display_mode() {
		return $this->display_mode;
	}

	public function set_display_mode($mode) {
		$this->display_mode = $mode;
	}

	public function is_threaded() {
		return $this->threaded;
	}

	public function set_commentable($val) {
		$this->commentable = $val;
		foreach($this->get_children() as $child)
			$child->set_commentable($val);
	}

	public function is_commentable() {
		return $this->commentable;
	}

	/**
	 * Add a child item
	 */
	public function add_child($item) {
		$item_id = $item->get_id();
		if(!$item_id) {
			logger('[ERROR] Item::add_child : Item has no ID!!', LOGGER_DEBUG);
			return false;
		}
		if($this->get_child($item->get_id())) {
			logger('[WARN] Item::add_child : Item already exists ('. $item->get_id() .').', LOGGER_DEBUG);
			return false;
		}
		/*
		 * Only add what will be displayed
		 */

		if(activity_match($item->get_data_value('verb'),ACTIVITY_LIKE) || activity_match($item->get_data_value('verb'),ACTIVITY_DISLIKE)) {
			return false;
		}
		
		$item->set_parent($this);
		$this->children[] = $item;
		return end($this->children);
	}

	/**
	 * Get a child by its ID
	 */
	public function get_child($id) {
		foreach($this->get_children() as $child) {
			if($child->get_id() == $id)
				return $child;
		}
		return null;
	}

	/**
	 * Get all our children
	 */
	public function get_children() {
		return $this->children;
	}

	/**
	 * Set our parent
	 */
	protected function set_parent($item) {
		$parent = $this->get_parent();
		if($parent) {
			$parent->remove_child($this);
		}
		$this->parent = $item;
		$this->set_conversation($item->get_conversation());
	}

	/**
	 * Remove our parent
	 */
	protected function remove_parent() {
		$this->parent = null;
		$this->conversation = null;
	}

	/**
	 * Remove a child
	 */
	public function remove_child($item) {
		$id = $item->get_id();
		foreach($this->get_children() as $key => $child) {
			if($child->get_id() == $id) {
				$child->remove_parent();
				unset($this->children[$key]);
				// Reindex the array, in order to make sure there won't be any trouble on loops using count()
				$this->children = array_values($this->children);
				return true;
			}
		}
		logger('[WARN] Item::remove_child : Item is not a child ('. $id .').', LOGGER_DEBUG);
		return false;
	}

	/**
	 * Get parent item
	 */
	protected function get_parent() {
		return $this->parent;
	}

	/**
	 * set conversation
	 */
	public function set_conversation($conv) {
		$previous_mode = ($this->conversation ? $this->conversation->get_mode() : '');
		
		$this->conversation = $conv;

		// Set it on our children too
		foreach($this->get_children() as $child)
			$child->set_conversation($conv);
	}

	/**
	 * get conversation
	 */
	public function get_conversation() {
		return $this->conversation;
	}

	/**
	 * Get raw data
	 *
	 * We shouldn't need this
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get a data value
	 *
	 * Returns:
	 *      _ value on success
	 *      _ false on failure
	 */
	public function get_data_value($name) {
		if(!isset($this->data[$name])) {
//			logger('[ERROR] Item::get_data_value : Item has no value name "'. $name .'".', LOGGER_DEBUG);
			return false;
		}

		return $this->data[$name];
	}

	/**
	 * Get template
	 */
	public function get_template() {
		return $this->template;
	}


	public function set_template($t) {
		$this->template = $t;
	}

	/**
	 * Check if this is a toplevel post
	 */
	private function is_toplevel() {
		return $this->toplevel;
	}

	/**
	 * Count the total of our descendants
	 */
	private function count_descendants() {
		$children = $this->get_children();
		$total = count($children);
		if($total > 0) {
			foreach($children as $child) {
				$total += $child->count_descendants();
			}
		}
		return $total;
	}

	private function count_unseen_descendants() {
		$children = $this->get_children();
		$total = count($children);
		if($total > 0) {
			$total = 0;
			foreach($children as $child) {
				if((! visible_activity($child->data)) || array_key_exists('author_blocked',$child->data)) {
					continue;
				}
				if(intval($child->data['item_unseen']))
					$total ++;
			}
		}
		return $total;
	}


	/**
	 * Get the template for the comment box
	 */
	private function get_comment_box_template() {
		return $this->comment_box_template;
	}

	/**
	 * Get the comment box
	 *
	 * Returns:
	 *      _ The comment box string (empty if no comment box)
	 *      _ false on failure
	 */
	private function get_comment_box($indent) {

		if(!$this->is_toplevel() && !get_config('system','thread_allow')) {
			return '';
		}
		
		$comment_box = '';
		$conv = $this->get_conversation();

//		logger('Commentable conv: ' . $conv->is_commentable());

		if(! $this->is_commentable())
			return;

		$template = get_markup_template($this->get_comment_box_template());

		$observer = $conv->get_observer();

		$qc = ((local_channel()) ? get_pconfig(local_channel(),'system','qcomment') : null);
		$qcomment = (($qc) ? explode("\n",$qc) : null);

		$arr = array('comment_buttons' => '','id' => $this->get_id());
		call_hooks('comment_buttons',$arr);
		$comment_buttons = $arr['comment_buttons'];


		$comment_box = replace_macros($template,array(
			'$return_path' => '',
			'$threaded' => $this->is_threaded(),
			'$jsreload' => '', //(($conv->get_mode() === 'display') ? $_SESSION['return_url'] : ''),
			'$type' => (($conv->get_mode() === 'channel') ? 'wall-comment' : 'net-comment'),
			'$id' => $this->get_id(),
			'$parent' => $this->get_id(),
			'$qcomment' => $qcomment,
			'$comment_buttons' => $comment_buttons,
			'$profile_uid' =>  $conv->get_profile_owner(),
			'$mylink' => $observer['xchan_url'],
			'$mytitle' => t('This is you'),
			'$myphoto' => $observer['xchan_photo_s'],
			'$comment' => t('Comment'),
			'$submit' => t('Submit'),
			'$edbold' => t('Bold'),
			'$editalic' => t('Italic'),
			'$eduline' => t('Underline'),
			'$edquote' => t('Quote'),
			'$edcode' => t('Code'),
			'$edimg' => t('Image'),
			'$edurl' => t('Insert Link'),
			'$edvideo' => t('Video'),
			'$preview' => t('Preview'), // ((feature_enabled($conv->get_profile_owner(),'preview')) ? t('Preview') : ''),
			'$indent' => $indent,
			'$feature_encrypt' => ((feature_enabled($conv->get_profile_owner(),'content_encrypt')) ? true : false),
			'$encrypt' => t('Encrypt text'),
			'$cipher' => $conv->get_cipher(),
			'$sourceapp' => \App::$sourcename

		));

		return $comment_box;
	}

	private function get_redirect_url() {
		return $this->redirect_url;
	}

	/**
	 * Check if we are a wall to wall item and set the relevant properties
	 */
	protected function check_wall_to_wall() {
		$conv = $this->get_conversation();
		$this->wall_to_wall = false;
		$this->owner_url = '';
		$this->owner_photo = '';
		$this->owner_name = '';

		if($conv->get_mode() === 'channel')
			return;
		
		if($this->is_toplevel() && ($this->get_data_value('author_xchan') != $this->get_data_value('owner_xchan'))) {
			$this->owner_url = chanlink_url($this->data['owner']['xchan_url']);
			$this->owner_photo = $this->data['owner']['xchan_photo_m'];
			$this->owner_name = $this->data['owner']['xchan_name'];
			$this->wall_to_wall = true;
		}
	}

	private function is_wall_to_wall() {
		return $this->wall_to_wall;
	}

	private function get_owner_url() {
		return $this->owner_url;
	}

	private function get_owner_photo() {
		return $this->owner_photo;
	}

	private function get_owner_name() {
		return $this->owner_name;
	}

	private function is_visiting() {
		return $this->visiting;
	}




}

