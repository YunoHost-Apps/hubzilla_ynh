<?php
/**
 * @file include/wiki.php
 * @brief Wiki related functions.
 */

use \Zotlabs\Storage\GitRepo as GitRepo;
define ( 'WIKI_ITEM_RESOURCE_TYPE', 'wiki' );

function wiki_list($channel, $observer_hash) {
	$sql_extra = item_permissions_sql($channel['channel_id'], $observer_hash);
	$wikis = q("SELECT * FROM item WHERE resource_type = '%s' AND mid = parent_mid AND uid = %d AND item_deleted = 0 $sql_extra", 
			dbesc(WIKI_ITEM_RESOURCE_TYPE),
			intval($channel['channel_id'])
	);
	foreach($wikis as &$w) {		
		$w['rawName'] = get_iconfig($w, 'wiki', 'rawName');
		$w['htmlName'] = get_iconfig($w, 'wiki', 'htmlName');
		$w['urlName'] = get_iconfig($w, 'wiki', 'urlName');
		$w['path'] = get_iconfig($w, 'wiki', 'path');
	}
	// TODO: query db for wikis the observer can access. Return with two lists, for read and write access
	return array('wikis' => $wikis);
}

function wiki_page_list($resource_id) {
	// TODO: Create item table records for pages so that metadata like title can be applied
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('pages' => null, 'wiki' => null);
	}
	$pages = array();
	if (is_dir($w['path']) === true) {
		$files = array_diff(scandir($w['path']), array('.', '..', '.git'));
		// TODO: Check that the files are all text files
		
		foreach($files as $file) {
			// strip the .md file extension and unwrap URL encoding to leave HTML encoded name
			$pages[] = array('title' => urldecode(substr($file, 0, -3)), 'url' => urlencode(substr($file, 0, -3)));
		}
	}

	return array('pages' => $pages, 'wiki' => $w);
}

function wiki_init_wiki($channel, $wiki) {
	// Store the path as a relative path, but pass absolute path to mkdir
	$path = 'store/[data]/git/'.$channel['channel_address'].'/wiki/'.$wiki['urlName'];
	if (!os_mkdir(__DIR__ . '/../' . $path, 0770, true)) {
		logger('Error creating wiki path: ' . $path);
		return null;
	}
	// Create GitRepo object 	
	$git = new GitRepo($channel['channel_address'], null, false, $name, __DIR__ . '/../' . $path);	
	if(!$git->initRepo()) {
		logger('Error creating new git repo in ' . $git->path);
		return null;
	}
	
	return array('path' => $path);
}

function wiki_create_wiki($channel, $observer_hash, $wiki, $acl) {
	$wikiinit = wiki_init_wiki($channel, $wiki);	
	if (!$wikiinit['path']) {
		notice('Error creating wiki');
		return array('item' => null, 'success' => false);
	}
	$path = $wikiinit['path'];
	// Generate unique resource_id using the same method as item_message_id()
	do {
		$dups = false;
		$resource_id = random_string();
		$r = q("SELECT mid FROM item WHERE resource_id = '%s' AND resource_type = '%s' AND uid = %d LIMIT 1", 
			dbesc($resource_id), 
			dbesc(WIKI_ITEM_RESOURCE_TYPE), 
			intval($channel['channel_id'])
		);
		if (count($r))
			$dups = true;
	} while ($dups == true);
	$ac = $acl->get();
	$mid = item_message_id();
	$arr = array();	// Initialize the array of parameters for the post
	$item_hidden = 0; // TODO: Allow form creator to send post to ACL about new game automatically
	$wiki_url = z_root() . '/wiki/' . $channel['channel_address'] . '/' . $wiki['urlName'];
	$arr['aid'] = $channel['channel_account_id'];
	$arr['uid'] = $channel['channel_id'];
	$arr['mid'] = $mid;
	$arr['parent_mid'] = $mid;
	$arr['item_hidden'] = $item_hidden;
	$arr['resource_type'] = WIKI_ITEM_RESOURCE_TYPE;
	$arr['resource_id'] = $resource_id;
	$arr['owner_xchan'] = $channel['channel_hash'];
	$arr['author_xchan'] = $observer_hash;
	$arr['plink'] = z_root() . '/channel/' . $channel['channel_address'] . '/?f=&mid=' . $arr['mid'];
	$arr['llink'] = $arr['plink'];
	$arr['title'] = $wiki['htmlName'];		 // name of new wiki;
	$arr['allow_cid'] = $ac['allow_cid'];
	$arr['allow_gid'] = $ac['allow_gid'];
	$arr['deny_cid'] = $ac['deny_cid'];
	$arr['deny_gid'] = $ac['deny_gid'];
	$arr['item_wall'] = 1;
	$arr['item_origin'] = 1;
	$arr['item_thread_top'] = 1;
	$arr['item_private'] = intval($acl->is_private());
	$arr['verb'] = ACTIVITY_CREATE;
	$arr['obj_type'] = ACTIVITY_OBJ_WIKI;
	$arr['body'] = '[table][tr][td][h1]New Wiki[/h1][/td][/tr][tr][td][zrl=' . $wiki_url . ']' . $wiki['htmlName'] . '[/zrl][/td][/tr][/table]';
	// Save the path using iconfig. The file path should not be shared with other hubs
	if (!set_iconfig($arr, 'wiki', 'path', $path, false)) {
		return array('item' => null, 'success' => false);
	}
	// Save the wiki name information using iconfig. This is shareable.
	if (!set_iconfig($arr, 'wiki', 'rawName', $wiki['rawName'], true)) {
		return array('item' => null, 'success' => false);
	}
	if (!set_iconfig($arr, 'wiki', 'htmlName', $wiki['htmlName'], true)) {
		return array('item' => null, 'success' => false);
	}
	if (!set_iconfig($arr, 'wiki', 'urlName', $wiki['urlName'], true)) {
		return array('item' => null, 'success' => false);
	}
	$post = item_store($arr);
	$item_id = $post['item_id'];

	if ($item_id) {
		proc_run('php', "include/notifier.php", "activity", $item_id);
		return array('item' => $arr, 'success' => true);
	} else {
		return array('item' => null, 'success' => false);
	}
}

function wiki_delete_wiki($resource_id) {

	$w = wiki_get_wiki($resource_id);
	$item = $w['wiki'];
	if (!$item || !$w['path']) {
		return array('item' => null, 'success' => false);
	} else {
		$drop = drop_item($item['id'], false, DROPITEM_NORMAL, true);
		$pathdel = rrmdir($w['path']);
		if ($pathdel) {
			info('Wiki files deleted successfully');
		}
		return array('item' => $item, 'success' => (($drop === 1 && $pathdel) ? true : false));
	}
}

function wiki_get_wiki($resource_id) {
	$item = q("SELECT * FROM item WHERE resource_type = '%s' AND resource_id = '%s' AND item_deleted = 0 limit 1", 
						dbesc(WIKI_ITEM_RESOURCE_TYPE), 
						dbesc($resource_id)
	);
	if (!$item) {
		return array('wiki' => null, 'path' => null);
	} else {
		$w = $item[0];	// wiki item table record
		// Get wiki metadata
		$rawName = get_iconfig($w, 'wiki', 'rawName');
		$htmlName = get_iconfig($w, 'wiki', 'htmlName');
		$urlName = get_iconfig($w, 'wiki', 'urlName');
		$path = get_iconfig($w, 'wiki', 'path');
		if (!realpath(__DIR__ . '/../' . $path)) {
			return array('wiki' => null, 'path' => null);
		}
		// Path to wiki exists
		$abs_path = realpath(__DIR__ . '/../' . $path);
		return array( 'wiki' => $w, 
									'path' => $abs_path, 
									'rawName' => $rawName, 
									'htmlName' => $htmlName, 
									'urlName' => $urlName
		);
	}
}

function wiki_exists_by_name($uid, $urlName) {
	$item = q("SELECT id,resource_id FROM item WHERE resource_type = '%s' AND title = '%s' AND uid = '%s' AND item_deleted = 0 limit 1", 
						dbesc(WIKI_ITEM_RESOURCE_TYPE), 
						dbesc(escape_tags(urldecode($urlName))), 
						dbesc($uid)
					);
	if (!$item) {
		return array('id' => null, 'resource_id' => null);
	} else {
		return array('id' => $item[0]['id'], 'resource_id' => $item[0]['resource_id']);
	}
}

function wiki_get_permissions($resource_id, $owner_id, $observer_hash) {
	// TODO: For now, only the owner can edit
	$sql_extra = item_permissions_sql($owner_id, $observer_hash);
	$r = q("SELECT * FROM item WHERE resource_type = '%s' AND resource_id = '%s' $sql_extra LIMIT 1",
				dbesc(WIKI_ITEM_RESOURCE_TYPE), 
        dbesc($resource_id)
    );
	
	if (!$r) {
		return array('read' => false, 'write' => false, 'success' => true);
	} else {
		$perms = get_all_perms($owner_id, $observer_hash);
		// TODO: Create a new permission setting for wiki analogous to webpages. Until
		// then, use webpage permissions
		if (!$perms['write_pages']) {
			$write = false;
		} else {
			$write = true;
		}
		return array('read' => true, 'write' => $write, 'success' => true);
	}
}

function wiki_create_page($name, $resource_id) {
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('page' => null, 'wiki' => null, 'message' => 'Wiki not found.', 'success' => false);
	}
	$page = array('rawName' => $name, 'htmlName' => escape_tags($name), 'urlName' => urlencode(escape_tags($name)), 'fileName' => urlencode(escape_tags($name)).'.md');
	$page_path = $w['path'] . '/' . $page['fileName'];
	if (is_file($page_path)) {
		return array('page' => null, 'wiki' => null, 'message' => 'Page already exists.', 'success' => false);
	}
	// Create the page file in the wiki repo
	if(!touch($page_path)) {
		return array('page' => null, 'wiki' => null, 'message' => 'Page file cannot be created.', 'success' => false);
	} else {
		return array('page' => $page, 'wiki' => $w, 'message' => '', 'success' => true);
	}
	
}

function wiki_get_page_content($arr) {
	$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
	$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('content' => null, 'message' => 'Error reading wiki', 'success' => false);
	}
	$page_path = $w['path'].'/'.$pageUrlName.'.md';
	if (is_readable($page_path) === true) {
		if(filesize($page_path) === 0) {
			$content = '';
		} else {
			$content = file_get_contents($page_path);
			if(!$content) {
				return array('content' => null, 'message' => 'Error reading page content', 'success' => false);
			}
		}		
		// TODO: Check that the files are all text files
		return array('content' => json_encode($content), 'message' => '', 'success' => true);
	}
}

function wiki_page_history($arr) {
	$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
	$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('history' => null, 'message' => 'Error reading wiki', 'success' => false);
	}
	$page_path = $w['path'].'/'.$pageUrlName.'.md';
	if (!is_readable($page_path) === true) {
		return array('history' => null, 'message' => 'Cannot read wiki page: ' . $page_path, 'success' => false);
	}
	$reponame = ((array_key_exists('title', $w['wiki'])) ? $w['wiki']['title'] : 'repo');
	if($reponame === '') {
		$reponame = 'repo';
	}
	$git = new GitRepo('', null, false, $w['wiki']['title'], $w['path']);
	try {
		$gitlog = $git->git->log('', $page_path , array('limit' => 500));
		return array('history' => $gitlog, 'message' => '', 'success' => true);
	} catch (\PHPGit\Exception\GitException $e) {
		 return array('history' => null, 'message' => 'GitRepo error thrown', 'success' => false);
	}	
}

function wiki_save_page($arr) {
	$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
	$content = ((array_key_exists('content',$arr)) ? purify_html($arr['content']) : '');
	$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('message' => 'Error reading wiki', 'success' => false);
	}
	$page_path = $w['path'].'/'.$pageUrlName.'.md';
	if (is_writable($page_path) === true) {
		if(!file_put_contents($page_path, $content)) {
			return array('message' => 'Error writing to page file', 'success' => false);
		}
		return array('message' => '', 'success' => true);
	} else {
		return array('message' => 'Page file not writable', 'success' => false);
	}	
}

function wiki_delete_page($arr) {
	$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
	$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('message' => 'Error reading wiki', 'success' => false);
	}
	$page_path = $w['path'].'/'.$pageUrlName.'.md';
	if (is_writable($page_path) === true) {
		if(!unlink($page_path)) {
			return array('message' => 'Error deleting page file', 'success' => false);
		}
		return array('message' => '', 'success' => true);
	} else {
		return array('message' => 'Page file not writable', 'success' => false);
	}	
}

function wiki_revert_page($arr) {
	$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
	$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
	$commitHash = ((array_key_exists('commitHash',$arr)) ? $arr['commitHash'] : null);
	if (! $commitHash) {
		return array('content' => $content, 'message' => 'No commit has provided', 'success' => false);
	}
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('content' => $content, 'message' => 'Error reading wiki', 'success' => false);
	}
	$page_path = $w['path'].'/'.$pageUrlName.'.md';
	if (is_writable($page_path) === true) {
		
		$reponame = ((array_key_exists('title', $w['wiki'])) ? urlencode($w['wiki']['title']) : 'repo');
		if($reponame === '') {
			$reponame = 'repo';
		}
		$git = new GitRepo($observer['xchan_addr'], null, false, $w['wiki']['title'], $w['path']);
		$content = null;
		try {
			$git->setIdentity($observer['xchan_name'], $observer['xchan_addr']);
			foreach ($git->git->tree($commitHash) as $object) {
				if ($object['type'] == 'blob' && $object['file'] === $pageUrlName.'.md' ) {
						$content = $git->git->cat->blob($object['hash']);						
				}
			}
		} catch (\PHPGit\Exception\GitException $e) {
			json_return_and_die(array('content' => $content, 'message' => 'GitRepo error thrown', 'success' => false));
		}
		return array('content' => $content, 'message' => '', 'success' => true);
	} else {
		return array('content' => $content, 'message' => 'Page file not writable', 'success' => false);
	}
}

function wiki_git_commit($arr) {
	$files = ((array_key_exists('files', $arr)) ? $arr['files'] : null);
	$commit_msg = ((array_key_exists('commit_msg', $arr)) ? $arr['commit_msg'] : 'Repo updated');
	$resource_id = ((array_key_exists('resource_id', $arr)) ? $arr['resource_id'] : json_return_and_die(array('message' => 'Wiki resource_id required for git commit', 'success' => false)));
	$observer = ((array_key_exists('observer', $arr)) ? $arr['observer'] : json_return_and_die(array('message' => 'Observer required for git commit', 'success' => false)));
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('message' => 'Error reading wiki', 'success' => false);
	}
	$reponame = ((array_key_exists('title', $w['wiki'])) ? urlencode($w['wiki']['title']) : 'repo');
	if($reponame === '') {
		$reponame = 'repo';
	}
	$git = new GitRepo($observer['xchan_addr'], null, false, $w['wiki']['title'], $w['path']);
	try {
		$git->setIdentity($observer['xchan_name'], $observer['xchan_addr']);
		if ($files === null) {
			$options = array('all' => true); // git commit option to include all changes
		} else {
			$options = array(); // git commit options
			foreach ($files as $file) {
				if (!$git->git->add($file)) {	// add specified files to the git repo stage
					if (!$git->git->reset->hard()) {
						json_return_and_die(array('message' => 'Error adding file to git stage: ' . $file . '. Error resetting git repo.', 'success' => false));
					}
					json_return_and_die(array('message' => 'Error adding file to git stage: ' . $file, 'success' => false));
				}
			}
		}
		if ($git->commit($commit_msg, $options)) {
			json_return_and_die(array('message' => 'Wiki repo commit succeeded', 'success' => true));
		} else {
			json_return_and_die(array('message' => 'Wiki repo commit failed', 'success' => false));
		}
	} catch (\PHPGit\Exception\GitException $e) {
		json_return_and_die(array('message' => 'GitRepo error thrown', 'success' => false));
	}
}

function wiki_generate_page_filename($name) {
	$file = urlencode(escape_tags($name));
	if( $file === '') {
		return null;
	} else {
		return $file . '.md';
	}	
}