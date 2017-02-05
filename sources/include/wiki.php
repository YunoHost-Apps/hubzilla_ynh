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
	if($wikis) {
		foreach($wikis as &$w) {
			$w['rawName'] = get_iconfig($w, 'wiki', 'rawName');
			$w['htmlName'] = get_iconfig($w, 'wiki', 'htmlName');
			$w['urlName'] = get_iconfig($w, 'wiki', 'urlName');
			$w['path'] = get_iconfig($w, 'wiki', 'path');
			$w['mimeType'] = get_iconfig($w, 'wiki', 'mimeType');
			$w['lock'] = (($w['allow_cid'] || $w['allow_gid'] || $w['deny_cid'] || $w['deny_gid']) ? true : false);
		}
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

	$pages[] = [
		'resource_id' => '',
		'title' => 'Home',
		'url' => 'Home',
		'link_id' => 'id_wiki_home_0'
	];

	if (is_dir($w['path']) === true) {
		$files = array_diff(scandir($w['path']), array('.', '..', '.git'));
		// TODO: Check that the files are all text files
		$i = 1;
		foreach($files as $file) {
			// strip the file extension and unwrap URL encoding to leave HTML encoded name
			$title = substr($file, 0, strrpos($file,'.'));
			if(urldecode($title) !== 'Home') {
				$pages[] = [
					'resource_id' => $resource_id,
					'title' => urldecode($title),
					'url' => $title,
					'link_id' => 'id_' . substr($resource_id, 0, 10) . '_' . $i
				];
				$i++;
			}
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
	$git = new GitRepo($channel['channel_address'], null, false, $wiki['urlName'], __DIR__ . '/../' . $path);	
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
	$item_hidden = ((intval($wiki['postVisible']) === 0) ? 1 : 0); 
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
	if (!set_iconfig($arr, 'wiki', 'mimeType', $wiki['mimeType'], true)) {
		return array('item' => null, 'success' => false);
	}
	$post = item_store($arr);
	$item_id = $post['item_id'];

	if ($item_id) {
		\Zotlabs\Daemon\Master::Summon(array('Notifier', 'activity', $item_id));
		return array('item' => $post['item'], 'success' => true);
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
		$mimeType = get_iconfig($w, 'wiki', 'mimeType');

		$path = get_iconfig($w, 'wiki', 'path');
		if (!realpath(__DIR__ . '/../' . $path)) {
			return array('wiki' => null, 'path' => null);
		}
		// Path to wiki exists
		$abs_path = realpath(__DIR__ . '/../' . $path);
		return array(
			'wiki' => $w,
			'path' => $abs_path,
			'rawName' => $rawName,
			'htmlName' => $htmlName,
			'urlName' => $urlName,
			'mimeType' => $mimeType
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

	if(local_channel() && local_channel == $owner_id) {
		return [ 'read' => true, 'write' => true, 'success' => true ];
	}

	$r = q("SELECT * FROM item WHERE uid = %d and resource_type = '%s' AND resource_id = '%s' $sql_extra LIMIT 1",
		intval($owner_id),
		dbesc(WIKI_ITEM_RESOURCE_TYPE), 
		dbesc($resource_id)
	);

	if (!$r) {
		return array('read' => false, 'write' => false, 'success' => true);
	} else {
		// TODO: Create a new permission setting for wiki analogous to webpages. Until
		// then, use webpage permissions
		$write = perm_is_allowed($owner_id, $observer_hash,'write_pages');
		return array('read' => true, 'write' => $write, 'success' => true);
	}
}

function wiki_create_page($name, $resource_id) {
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('page' => null, 'wiki' => null, 'message' => 'Wiki not found.', 'success' => false);
	}

	$page = array('rawName' => $name, 'htmlName' => escape_tags($name), 'urlName' => urlencode(escape_tags($name)), 'fileName' => urlencode(escape_tags($name)) . wiki_get_file_ext($w));
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

function wiki_rename_page($arr) {
	$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
	$pageNewName = ((array_key_exists('pageNewName',$arr)) ? $arr['pageNewName'] : '');
	$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('message' => 'Wiki not found.', 'success' => false);
	}
	$page_path_old = $w['path'] . '/' . $pageUrlName . wiki_get_file_ext($w);
	if (!is_readable($page_path_old) === true) {
		return array('message' => 'Cannot read wiki page: ' . $page_path_old, 'success' => false);
	}
	$page = array('rawName' => $pageNewName, 'htmlName' => escape_tags($pageNewName), 'urlName' => urlencode(escape_tags($pageNewName)), 'fileName' => urlencode(escape_tags($pageNewName)) . wiki_get_file_ext($w));
	$page_path_new = $w['path'] . '/' . $page['fileName'] ;
	if (is_file($page_path_new)) {
		return array('message' => 'Page already exists.', 'success' => false);
	}
	// Rename the page file in the wiki repo
	if(!rename($page_path_old, $page_path_new)) {
		return array('message' => 'Error renaming page file.', 'success' => false);
	} else {
		return array('page' => $page, 'message' => '', 'success' => true);
	}
	
}

function wiki_get_page_content($arr) {
	$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
	$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('content' => null, 'message' => 'Error reading wiki', 'success' => false);
	}
	$page_path = $w['path'] . '/' . $pageUrlName . wiki_get_file_ext($w);
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
		return array('content' => json_encode($content), 'mimeType' => $w['mimeType'], 'message' => '', 'success' => true);
	}
}

function wiki_page_history($arr) {
	$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
	$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('history' => null, 'message' => 'Error reading wiki', 'success' => false);
	}
	$page_path = $w['path'] . '/' . $pageUrlName . wiki_get_file_ext($w);
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

function wiki_prepare_content($s) {
		
	$text = preg_replace_callback('{
				(?:\n\n|\A\n?)
				(	            # $1 = the code block -- one or more lines, starting with a space/tab
				  (?>
					[ ]{'.'4'.'}  # Lines must start with a tab or a tab-width of spaces
					.*\n+
				  )+
				)
				((?=^[ ]{0,'.'4'.'}\S)|\Z)	# Lookahead for non-space at line-start, or end of doc
			}xm',
			'wiki_prepare_content_callback', $s);

	return $text;
}

function wiki_prepare_content_callback($matches) {
	$codeblock = $matches[1];

	$codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES, UTF8, false);
	return "\n\n" . $codeblock ;
}



function wiki_save_page($arr) {
	$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
	$content = ((array_key_exists('content',$arr)) ? purify_html(wiki_prepare_content($arr['content'])) : '');
	$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('message' => 'Error reading wiki', 'success' => false);
	}

	$fileName = $pageUrlName . wiki_get_file_ext($w);
	$page_path = $w['path'] . '/' . $fileName;
	if (is_writable($page_path) === true) {
		if(!file_put_contents($page_path, $content)) {
			return array('message' => 'Error writing to page file', 'success' => false);
		}
		return array('message' => '', 'filename' => $filename, 'success' => true);
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
	$page_path = $w['path'] . '/' . $pageUrlName . wiki_get_file_ext($w);
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
		return array('content' => $content, 'message' => 'No commit was provided', 'success' => false);
	}
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('content' => $content, 'message' => 'Error reading wiki', 'success' => false);
	}
	$page_path = $w['path'] . '/' . $pageUrlName . wiki_get_file_ext($w);
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
				if ($object['type'] == 'blob' && $object['file'] === $pageUrlName . wiki_get_file_ext($w)) {
						$content = $git->git->cat->blob($object['hash']);						
				}
			}
		} catch (\PHPGit\Exception\GitException $e) {
			return array('content' => $content, 'message' => 'GitRepo error thrown', 'success' => false);
		}
		return array('content' => $content, 'message' => '', 'success' => true);
	} else {
		return array('content' => $content, 'message' => 'Page file not writable', 'success' => false);
	}
}

function wiki_compare_page($arr) {
	$pageUrlName = ((array_key_exists('pageUrlName',$arr)) ? $arr['pageUrlName'] : '');
	$resource_id = ((array_key_exists('resource_id',$arr)) ? $arr['resource_id'] : '');
	$currentCommit = ((array_key_exists('currentCommit',$arr)) ? $arr['currentCommit'] : 'HEAD');
	$compareCommit = ((array_key_exists('compareCommit',$arr)) ? $arr['compareCommit'] : null);
	if (! $compareCommit) {
		return array('message' => 'No compare commit was provided', 'success' => false);
	}
	$w = wiki_get_wiki($resource_id);
	if (!$w['path']) {
		return array('message' => 'Error reading wiki', 'success' => false);
	}
	$page_path = $w['path'] . '/' . $pageUrlName . wiki_get_file_ext($w);
	if (is_readable($page_path) === true) {
		$reponame = ((array_key_exists('title', $w['wiki'])) ? urlencode($w['wiki']['title']) : 'repo');
		if($reponame === '') {
			$reponame = 'repo';
		}
		$git = new GitRepo('', null, false, $w['wiki']['title'], $w['path']);
		$compareContent = $currentContent = '';
		try {
			foreach ($git->git->tree($currentCommit) as $object) {
				if ($object['type'] == 'blob' && $object['file'] === $pageUrlName . wiki_get_file_ext($w)) {
						$currentContent = $git->git->cat->blob($object['hash']);						
				}
			}
			foreach ($git->git->tree($compareCommit) as $object) {
				if ($object['type'] == 'blob' && $object['file'] === $pageUrlName . wiki_get_file_ext($w)) {
						$compareContent = $git->git->cat->blob($object['hash']);						
				}
			}
			require_once('library/class.Diff.php');
			$diff = Diff::toTable(Diff::compare($currentContent, $compareContent));
		} catch (\PHPGit\Exception\GitException $e) {
			return array('message' => 'GitRepo error thrown', 'success' => false);
		}
		return array('diff' => $diff, 'message' => '', 'success' => true);
	} else {
		return array('message' => 'Page file not writable', 'success' => false);
	}
}

function wiki_git_commit($arr) {
	$files = ((array_key_exists('files', $arr)) ? $arr['files'] : null);
	$all = ((array_key_exists('all', $arr)) ? $arr['all'] : false);
	$commit_msg = ((array_key_exists('commit_msg', $arr)) ? $arr['commit_msg'] : 'Repo updated');
	if(array_key_exists('resource_id', $arr)) {
		$resource_id = $arr['resource_id'];
	} else {
		return array('message' => 'Wiki resource_id required for git commit', 'success' => false);
	}
	if(array_key_exists('observer', $arr)) {
		$observer = $arr['observer'];
	} else {
		return array('message' => 'Observer required for git commit', 'success' => false);
	}	
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
			$options = array('all' => $all); // git commit options\
			foreach ($files as $file) {
				if (!$git->git->add($file)) {	// add specified files to the git repo stage
					if (!$git->git->reset->hard()) {
						return array('message' => 'Error adding file to git stage: ' . $file . '. Error resetting git repo.', 'success' => false);
					}
					return array('message' => 'Error adding file to git stage: ' . $file, 'success' => false);
				}
			}
		}
		if ($git->commit($commit_msg, $options)) {
			return array('message' => 'Wiki repo commit succeeded', 'success' => true);
		} else {
			return array('message' => 'Wiki repo commit failed', 'success' => false);
		}
	} catch (\PHPGit\Exception\GitException $e) {
		return array('message' => 'GitRepo error thrown', 'success' => false);
	}
}

function wiki_convert_links($s, $wikiURL) {
	
	if (strpos($s,'[[') !== false) {
		preg_match_all("/\[\[(.*?)\]\]/", $s, $match);
		$pages = $pageURLs = array();
		foreach ($match[1] as $m) {
			// TODO: Why do we need to double urlencode for this to work?
			$pageURLs[] = urlencode(urlencode(escape_tags($m)));
			$pages[] = $m;
		}
		$idx = 0;
		while(strpos($s,'[[') !== false) {
		$replace = '<a href="'.$wikiURL.'/'.$pageURLs[$idx].'">'.$pages[$idx].'</a>';
			$s = preg_replace("/\[\[(.*?)\]\]/", $replace, $s, 1);
			$idx++;
		}
	}
	return $s;
}

/**
 * Replace the instances of the string [toc] with a list element that will be populated by
 * a table of contents by the JavaScript library
 * @param string $s
 * @return string
 */
function wiki_generate_toc($s) {
	if (strpos($s,'[toc]') !== false) {
		//$toc_md = wiki_toc($s);	// Generate Markdown-formatted list prior to HTML render
		$toc_md = '<ul id="wiki-toc"></ul>'; // use the available jQuery plugin http://ndabas.github.io/toc/
		$s = preg_replace("/\[toc\]/", $toc_md, $s, -1);
	}
	return $s;
}

/**
 *  Converts a select set of bbcode tags. Much of the code is copied from include/bbcode.php
 * @param string $s
 * @return string
 */
function wiki_bbcode($s) {
		
		$s = str_replace(array('[baseurl]', '[sitename]'), array(z_root(), get_config('system', 'sitename')), $s);
		
		$observer = App::get_observer();
		if ($observer) {
				$s1 = '<span class="bb_observer" title="' . t('Different viewers will see this text differently') . '">';
				$s2 = '</span>';
				$obsBaseURL = $observer['xchan_connurl'];
				$obsBaseURL = preg_replace("/\/poco\/.*$/", '', $obsBaseURL);
				$s = str_replace('[observer.baseurl]', $obsBaseURL, $s);
				$s = str_replace('[observer.url]', $observer['xchan_url'], $s);
				$s = str_replace('[observer.name]', $s1 . $observer['xchan_name'] . $s2, $s);
				$s = str_replace('[observer.address]', $s1 . $observer['xchan_addr'] . $s2, $s);
				$s = str_replace('[observer.webname]', substr($observer['xchan_addr'], 0, strpos($observer['xchan_addr'], '@')), $s);
				$s = str_replace('[observer.photo]', '', $s);
		} else {
				$s = str_replace('[observer.baseurl]', '', $s);
				$s = str_replace('[observer.url]', '', $s);
				$s = str_replace('[observer.name]', '', $s);
				$s = str_replace('[observer.address]', '', $s);
				$s = str_replace('[observer.webname]', '', $s);
				$s = str_replace('[observer.photo]', '', $s);
		}

		return $s;
}

function wiki_get_file_ext($arr) {
	if($arr['mimeType'] == 'text/bbcode')
		return '.bb';
	else
		return '.md';
}

// This function is derived from 
// http://stackoverflow.com/questions/32068537/generate-table-of-contents-from-markdown-in-php
function wiki_toc($content) {
  // ensure using only "\n" as line-break
  $source = str_replace(["\r\n", "\r"], "\n", $content);

  // look for markdown TOC items
  preg_match_all(
	'/^(?:=|-|#).*$/m',
	$source,
	$matches,
	PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE
  );

  // preprocess: iterate matched lines to create an array of items
  // where each item is an array(level, text)
  $file_size = strlen($source);
  foreach ($matches[0] as $item) {
	$found_mark = substr($item[0], 0, 1);
	if ($found_mark == '#') {
	  // text is the found item
	  $item_text = $item[0];
	  $item_level = strrpos($item_text, '#') + 1;
	  $item_text = substr($item_text, $item_level);
	} else {
	  // text is the previous line (empty if <hr>)
	  $item_offset = $item[1];
	  $prev_line_offset = strrpos($source, "\n", -($file_size - $item_offset + 2));
	  $item_text =
		substr($source, $prev_line_offset, $item_offset - $prev_line_offset - 1);
	  $item_text = trim($item_text);
	  $item_level = $found_mark == '=' ? 1 : 2;
	}
	if (!trim($item_text) OR strpos($item_text, '|') !== FALSE) {
	  // item is an horizontal separator or a table header, don't mind
	  continue;
	}
	$raw_toc[] = ['level' => $item_level, 'text' => trim($item_text)];
  }
	$o = '';
	foreach($raw_toc as $t) {
		$level = intval($t['level']);
		$text = $t['text'];
		switch ($level) {
			case 1:
				$li = '* ';
				break;
			case 2:
				$li = '  * ';
				break;
			case 3:
				$li = '    * ';
				break;
			case 4:
				$li = '      * ';
				break;
			default:
				$li = '* ';
				break;
		}
		$o .= $li . $text . "\n";
	}
  return $o;
}
