<?php

/**
 * @brief
 *
 * @param string $tocpath
 * @return string|unknown
 */
function get_help_content($tocpath = false) {

	global $lang;

	$doctype = 'markdown';

	$text = '';

	$path = (($tocpath !== false) ? $tocpath : '');
        
	if($tocpath === false && argc() > 1) {
		$path = '';
		for($x = 1; $x < argc(); $x ++) {
			if(strlen($path))
				$path .= '/';
			$path .= argv($x);
		}
	}

	if($path) {
		$title = basename($path);
		if(! $tocpath)
			\App::$page['title'] = t('Help:') . ' ' . ucwords(str_replace('-',' ',notags($title)));

		$text = load_doc_file('doc/' . $path . '.md');

		if(! $text) {
			$text = load_doc_file('doc/' . $path . '.bb');
			if($text)
				$doctype = 'bbcode';
		}
		if(! $text) {
			$text = load_doc_file('doc/' . $path . '.html');
			if($text)
				$doctype = 'html';
		}
	}

	if(($tocpath) && (! $text))
		return '';

	if($tocpath === false) {
		if(! $text) {
			$text = load_doc_file('doc/Site.md');
			\App::$page['title'] = t('Help');
		}
		if(! $text) {
			$doctype = 'bbcode';
			$text = load_doc_file('doc/main.bb');
                        goaway('/help/about/about_hubzilla');
			\App::$page['title'] = t('Help');
		}

		if(! $text) {
			header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
			$tpl = get_markup_template("404.tpl");
			return replace_macros($tpl, array(
				'$message' => t('Page not found.')
			));
		}
	}

	if($doctype === 'html')
                $content = parseIdentityAwareHTML($text);
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
		// bbcode retargets external content to new windows. This content is internal.
		$content = str_replace(' target="_blank"', '', $content);
	}

	$content = preg_replace_callback("/#include (.*?)\;/ism", 'preg_callback_help_include', $content);

	return translate_projectname($content);
}

function preg_callback_help_include($matches) {

	if($matches[1]) {
		$include = str_replace($matches[0],load_doc_file($matches[1]),$matches[0]);
		if(preg_match('/\.bb$/', $matches[1]) || preg_match('/\.txt$/', $matches[1])) {
			require_once('include/bbcode.php');
			$include = bbcode($include);
			$include = str_replace(' target="_blank"','',$include);
		}
		elseif(preg_match('/\.md$/', $matches[1])) {
			require_once('library/markdown.php');
			$include = Markdown($include);
		}
		return $include;
	}

}



function load_doc_file($s) {
	$lang = \App::$language;
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
	if(file_exists($s)) {
		return file_get_contents($s);
	}
	return '';
}

/**
 * @brief
 *
 * @param string $s
 * @return number|mixed|unknown|boolean
 */
function search_doc_files($s) {

	$itemspage = get_pconfig(local_channel(),'system','itemspage');
	\App::set_pager_itemspage(((intval($itemspage)) ? $itemspage : 20));
	$pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval(\App::$pager['itemspage']), intval(\App::$pager['start']));

	$regexop = db_getfunc('REGEXP');

	$r = q("select iconfig.v, item.* from item left join iconfig on item.id = iconfig.iid
		where iconfig.cat = 'system' and iconfig.k = 'docfile' and
		body $regexop '%s' and item_type = %d $pager_sql",
		dbesc($s),
		intval(ITEM_TYPE_DOC)
	);

	$r = fetch_post_tags($r, true);

	for($x = 0; $x < count($r); $x ++) {
		$position =	stripos($r[$x]['body'], $s);
		$dislen = 300;
		$start = $position-floor($dislen/2);
		if ( $start < 0) {
			$start = 0;
		}
		$r[$x]['text'] = substr($r[$x]['body'], $start, $dislen);

		$r[$x]['rank'] = 0;
		if($r[$x]['term']) {
			foreach($r[$x]['term'] as $t) {
				if(stristr($t['term'],$s)) {
					$r[$x]['rank'] ++;
				}
			}
		}
		if(stristr($r[$x]['v'], $s))
			$r[$x]['rank'] ++;
		$r[$x]['rank'] += substr_count(strtolower($r[$x]['text']), strtolower($s));
		// bias the results to the observer's native language
		if($r[$x]['lang'] === \App::$language)
			$r[$x]['rank'] = $r[$x]['rank'] + 10;

	}
	usort($r,'doc_rank_sort');

	return $r;
}


function doc_rank_sort($s1, $s2) {
	if($s1['rank'] == $s2['rank'])
		return 0;

	return (($s1['rank'] < $s2['rank']) ? 1 : (-1));
}

/**
 * @brief
 *
 * @return string
 */
function load_context_help() {

	$path = App::$cmd;
	$args = App::$argv;
	$lang = App::$language;

	if(! isset($lang) || !is_dir('doc/context/' . $lang . '/')) {
		$lang = 'en';
	}
	while($path) {
		$context_help = load_doc_file('doc/context/' . $lang . '/' . $path . '/help.html');
		if(!$context_help) {
			// Fallback to English if the translation is absent
			$context_help = load_doc_file('doc/context/en/' . $path . '/help.html');
		}
		if($context_help)
			break;

		array_pop($args);
		$path = implode($args,'/');
	}

	return $context_help;
}

/**
 * @brief
 *
 * @param string $s
 * @return void|boolean[]|number[]|string[]|unknown[]
 */
function store_doc_file($s) {

	if(is_dir($s))
		return;

	$item = array();
	$sys = get_sys_channel();

	$item['aid'] = 0;
	$item['uid'] = $sys['channel_id'];

	if(strpos($s, '.md'))
		$mimetype = 'text/markdown';
	elseif(strpos($s, '.html'))
		$mimetype = 'text/html';
	else
		$mimetype = 'text/bbcode';

	require_once('include/html2plain.php');

	$item['body'] = html2plain(prepare_text(file_get_contents($s),$mimetype, true));
	$item['mimetype'] = 'text/plain';

	$item['plink'] = z_root() . '/' . str_replace('doc','help',$s);
	$item['owner_xchan'] = $item['author_xchan'] = $sys['channel_hash'];
	$item['item_type'] = ITEM_TYPE_DOC;

	$r = q("select item.* from item left join iconfig on item.id = iconfig.iid
		where iconfig.cat = 'system' and iconfig.k = 'docfile' and
		iconfig.v = '%s' and item_type = %d limit 1",
		dbesc($s),
		intval(ITEM_TYPE_DOC)
	);

	\Zotlabs\Lib\IConfig::Set($item,'system','docfile',$s);

	if($r) {
		$item['id'] = $r[0]['id'];
		$item['mid'] = $item['parent_mid'] = $r[0]['mid'];
		$x = item_store_update($item);
	}
	else {
		$item['mid'] = $item['parent_mid'] = item_message_id();
		$x = item_store($item);
	}

	return $x;
}
