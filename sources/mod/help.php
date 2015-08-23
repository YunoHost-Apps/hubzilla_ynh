<?php

/**
 * You can create local site resources in doc/Site.md and either link to doc/Home.md for the standard resources
 * or use our include mechanism to include it on your local page.
 *
 * #include doc/Home.md;
 *
 * The syntax is somewhat strict. 
 *
 */






function load_doc_file($s) {
	$lang = get_app()->language;
	if(! isset($lang))
		$lang = 'en';
	$b = basename($s);
	$d = dirname($s);

	$c = find_doc_file("$d/$lang/$b");
	if($c) 
		return $c;
	$c = find_doc_file($s);
	if($c) 
		return $c;
	return '';
}

function find_doc_file($s) {

	// If the file was edited more recently than we've stored a copy in the database, use the file.
	// The stored database item will be searchable, the file won't be. 

	$r = q("select item.* from item left join item_id on item.id = item_id.iid where service = 'docfile' and
		sid = '%s' and item_type = %d limit 1",
		dbesc($s),
		intval(ITEM_TYPE_DOC)
	);

	if($r) {
		if(file_exists($s) && (filemtime($s) > datetime_convert('UTC','UTC',$r[0]['edited'],'U')))
			return file_get_contents($s);
		return($r[0]['body']);
 	}
	if(file_exists($s))
		return file_get_contents($s);
	return '';
}

function search_doc_files($s) {

	$a = get_app();

        $itemspage = get_pconfig(local_channel(),'system','itemspage');
        $a->set_pager_itemspage(((intval($itemspage)) ? $itemspage : 20));
        $pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval($a->pager['itemspage']), intval($a->pager['start']));

	// If the file was edited more recently than we've stored a copy in the database, use the file.
	// The stored database item will be searchable, the file won't be. 

	$regexop = db_getfunc('REGEXP');

	$r = q("select item_id.sid, item.* from item left join item_id on item.id = item_id.iid where service = 'docfile' and
		body $regexop '%s' and item_type = %d $pager_sql",
		dbesc($s),
		intval(ITEM_TYPE_DOC)
	);
	
	$r = fetch_post_tags($r,true);
	require_once('include/html2plain.php');

	for($x = 0; $x < count($r); $x ++) {

		$r[$x]['text'] = html2plain(prepare_text($r[$x]['body'],$r[$x]['mimetype'], true));

		$r[$x]['rank'] = 0;
		if($r[$x]['term']) {
			foreach($r[$x]['term'] as $t) {
				if(stristr($t['term'],$s)) {
					$r[$x]['rank'] ++;
				}
			}
		}
		if(stristr($r[$x]['sid'],$s))
			$r[$x]['rank'] ++;
		$r[$x]['rank'] += substr_count(strtolower($r[$x]['text']),strtolower($s));
	}
	usort($r,'doc_rank_sort');
	return $r;
}


function doc_rank_sort($a,$b) {
	if($a['rank'] == $b['rank'])
		return 0;
	return (($a['rank'] < $b['rank']) ? 1 : (-1));
}





function store_doc_file($s) {

	if(is_dir($s))
		return;

	$item = array();
	$sys = get_sys_channel();

	$item['aid'] = 0;
	$item['uid'] = $sys['channel_id'];


	if(strpos($s,'.md'))
		$item['mimetype'] = 'text/markdown';
	elseif(strpos($s,'.html'))
		$item['mimetype'] = 'text/html';
	else
		$item['mimetype'] = 'text/bbcode';

	
	$item['body'] = file_get_contents($s);
	$item['plink'] = z_root() . '/' . str_replace('doc','help',$s);
	$item['owner_xchan'] = $item['author_xchan'] = $sys['channel_hash'];
	$item['item_type'] = ITEM_TYPE_DOC;

	$r = q("select item.* from item left join item_id on item.id = item_id.iid where service = 'docfile' and
		sid = '%s' and item_type = %d limit 1",
		dbesc($s),
		intval(ITEM_TYPE_DOC)
	);

	if($r) {
		$item['id'] = $r[0]['id'];
		$item['mid'] = $item['parent_mid'] = $r[0]['mid'];
		$x = item_store_update($item);
	}
	else {
		$item['mid'] = $item['parent_mid'] = item_message_id();
		$x = item_store($item);
	}

	if($x['success']) {
		update_remote_id($sys,$x['item_id'],ITEM_TYPE_DOC,$s,'docfile',0,$item['mid']);
	}


}


function help_content(&$a) {
	nav_set_selected('help');

	if($_REQUEST['search']) {
		$r = search_doc_files($_REQUEST['search']);
		if($r) {
			$o .= '<ul>';
			foreach($r as $rr) {
				$dirname = dirname($rr['sid']);
				$fname = basename($rr['sid']);
				$fname = substr($fname,0,strrpos($fname,'.'));
				$path = trim(substr($dirname,4),'/');

				$o .= '<li><a href="help/' . (($path) ? $path . '/' : '') . $fname . '" >' . ucwords(str_replace('_',' ',notags($fname))) . '</a><br />' . 
				str_replace('$Projectname',PLATFORM_NAME,substr($rr['text'],0,200)) . '...<br /><br /></li>';

			}
			$o .= '</ul>';
		}
		return $o;
	}


	global $lang;

	$doctype = 'markdown';

	$text = '';

	if(argc() > 1) {
		$path = '';
		for($x = 1; $x < argc(); $x ++) {
			if(strlen($path))
				$path .= '/';
			$path .= argv($x);
		}
		$title = basename($path);

		$text = load_doc_file('doc/' . $path . '.md');
		$a->page['title'] = t('Help:') . ' ' . ucwords(str_replace('-',' ',notags($title)));

		if(! $text) {
			$text = load_doc_file('doc/' . $path . '.bb');
			if($text)
				$doctype = 'bbcode';
			$a->page['title'] = t('Help:') . ' ' . ucwords(str_replace('_',' ',notags($title)));
		}
		if(! $text) {
			$text = load_doc_file('doc/' . $path . '.html');
			if($text)
				$doctype = 'html';
			$a->page['title'] = t('Help:') . ' ' . ucwords(str_replace('-',' ',notags($title)));
		}
	}

	if(! $text) {
		$text = load_doc_file('doc/Site.md');
		$a->page['title'] = t('Help');
	}
	if(! $text) {
		$doctype = 'bbcode';
		$text = load_doc_file('doc/main.bb');
		$a->page['title'] = t('Help');
	}
	
	if(! strlen($text)) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
		$tpl = get_markup_template("404.tpl");
		return replace_macros($tpl, array(
			'$message' =>  t('Page not found.' )
		));
	}

	if($doctype === 'html')
		$content = $text;
	if($doctype === 'markdown')	{
		require_once('library/markdown.php');
		# escape #include tags
		$text = preg_replace('/#include/ism', '%%include', $text);
		$content = Markdown($text);
		$content = preg_replace('/%%include/ism', '#include', $content);
	}
	if($doctype === 'bbcode') {
		require_once('include/bbcode.php');
		$content = bbcode($text);
	} 

	$content = preg_replace_callback("/#include (.*?)\;/ism", 'preg_callback_help_include', $content);

	return replace_macros(get_markup_template("help.tpl"), array(
		'$title' => t('$Projectname Documentation'),
		'$content' => translate_projectname($content)
	));

}


function preg_callback_help_include($matches) {

	if($matches[1]) {
		$include = str_replace($matches[0],load_doc_file($matches[1]),$matches[0]);
		if(preg_match('/\.bb$/', $matches[1]) || preg_match('/\.txt$/', $matches[1])) {
			require_once('include/bbcode.php');
			$include = bbcode($include);
		} elseif(preg_match('/\.md$/', $matches[1])) {
			require_once('library/markdown.php');
			$include = Markdown($include);
		}
		return $include;
	}

}

