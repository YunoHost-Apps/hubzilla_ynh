<?php /** @file */

namespace Zotlabs\Lib;

require_once('boot.php');
require_once('include/text.php');
require_once('include/items.php');

/**
 * A list of threads
 *
 */

class ThreadStream {

	private $threads = array();
	private $mode = null;
	private $observer = null;
	private $writable = false;
	private $commentable = false;
	private $profile_owner = 0;
	private $preview = false;
	private $prepared_item = '';
	private $cipher = 'aes256';

	// $prepared_item is for use by alternate conversation structures such as photos
	// wherein we've already prepared a top level item which doesn't look anything like
	// a normal "post" item

	public function __construct($mode, $preview, $prepared_item = '') {
		$this->set_mode($mode);
		$this->preview = $preview;
		$this->prepared_item = $prepared_item;
		$c = ((local_channel()) ? get_pconfig(local_channel(),'system','default_cipher') : '');
		if($c)
			$this->cipher = $c;
	}

	/**
	 * Set the mode we'll be displayed on
	 */
	private function set_mode($mode) {
		if($this->get_mode() == $mode)
			return;

		$this->observer = \App::get_observer();
		$ob_hash = (($this->observer) ? $this->observer['xchan_hash'] : '');

		switch($mode) {
			case 'network':
				$this->profile_owner = local_channel();
				$this->writable = true;
				break;
			case 'channel':
				$this->profile_owner = \App::$profile['profile_uid'];
				$this->writable = perm_is_allowed($this->profile_owner,$ob_hash,'post_comments');
				break;
			case 'display':
				// in this mode we set profile_owner after initialisation (from conversation()) and then 
				// pull some trickery which allows us to re-invoke this function afterward
				// it's an ugly hack so FIXME
				$this->writable = perm_is_allowed($this->profile_owner,$ob_hash,'post_comments');
				break;
			case 'page':
				$this->profile_owner = \App::$profile['uid'];
				$this->writable = perm_is_allowed($this->profile_owner,$ob_hash,'post_comments');
				break;
			default:
				logger('[ERROR] Conversation::set_mode : Unhandled mode ('. $mode .').', LOGGER_DEBUG);
				return false;
				break;
		}
		$this->mode = $mode;
	}

	/**
	 * Get mode
	 */
	public function get_mode() {
		return $this->mode;
	}

	/**
	 * Check if page is writable
	 */
	public function is_writable() {
		return $this->writable;
	}

	public function is_commentable() {
		return $this->commentable;
	}

	/**
	 * Check if page is a preview
	 */
	public function is_preview() {
		return $this->preview;
	}

	/**
	 * Get profile owner
	 */
	public function get_profile_owner() {
		return $this->profile_owner;
	}

	public function set_profile_owner($uid) {
		$this->profile_owner = $uid;
		$mode = $this->get_mode();
		$this->mode = null;
		$this->set_mode($mode);
	}

	public function get_observer() {
		return $this->observer;
	}

	public function get_cipher() {
		return $this->cipher;
	}


	/**
	 * Add a thread to the conversation
	 *
	 * Returns:
	 *      _ The inserted item on success
	 *      _ false on failure
	 */
	public function add_thread($item) {
		$item_id = $item->get_id();
		if(!$item_id) {
			logger('Item has no ID!!', LOGGER_DEBUG, LOG_ERR);
			return false;
		}
		if($this->get_thread($item->get_id())) {
			logger('Thread already exists ('. $item->get_id() .').', LOGGER_DEBUG, LOG_WARNING);
			return false;
		}

		/*
		 * Only add things that will be displayed
		 */

		
		if(($item->get_data_value('id') != $item->get_data_value('parent')) && (activity_match($item->get_data_value('verb'),ACTIVITY_LIKE) || activity_match($item->get_data_value('verb'),ACTIVITY_DISLIKE))) {
			return false;
		}

		$item->set_commentable(false);
		$ob_hash = (($this->observer) ? $this->observer['xchan_hash'] : '');
		
		if(! comments_are_now_closed($item->get_data())) {
			if(($item->get_data_value('author_xchan') === $ob_hash) || ($item->get_data_value('owner_xchan') === $ob_hash))
				$item->set_commentable(true);

			if(intval($item->get_data_value('item_nocomment'))) {
				$item->set_commentable(false);
			}
			elseif(($this->observer) && (! $item->is_commentable())) {
				if((array_key_exists('owner',$item->data)) && intval($item->data['owner']['abook_self']))
					$item->set_commentable(perm_is_allowed($this->profile_owner,$this->observer['xchan_hash'],'post_comments'));
				else
					$item->set_commentable(can_comment_on_post($this->observer['xchan_hash'],$item->data));
			}
		}
		require_once('include/channel.php');

		$item->set_conversation($this);
		$this->threads[] = $item;
		return end($this->threads);
	}

	/**
	 * Get data in a form usable by a conversation template
	 *
	 * We should find a way to avoid using those arguments (at least most of them)
	 *
	 * Returns:
	 *      _ The data requested on success
	 *      _ false on failure
	 */
	public function get_template_data($conv_responses) {
		$result = array();

		foreach($this->threads as $item) {

			if(($item->get_data_value('id') == $item->get_data_value('parent')) && $this->prepared_item) {
				$item_data = $this->prepared_item;
			}
			else {
				$item_data = $item->get_template_data($conv_responses);
			}
			if(!$item_data) {
				logger('Failed to get item template data ('. $item->get_id() .').', LOGGER_DEBUG, LOG_ERR);
				return false;
			}
			$result[] = $item_data;
		}

		return $result;
	}

	/**
	 * Get a thread based on its item id
	 *
	 * Returns:
	 *      _ The found item on success
	 *      _ false on failure
	 */
	private function get_thread($id) {
		foreach($this->threads as $item) {
			if($item->get_id() == $id)
				return $item;
		}

		return false;
	}
}
