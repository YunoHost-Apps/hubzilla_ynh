<?php

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
	if(file_exists($s))
		return file_get_contents($s);
	return '';
}

function search_doc_files($s) {

	$a = get_app();

	$itemspage = get_pconfig(local_channel(),'system','itemspage');
	\App::set_pager_itemspage(((intval($itemspage)) ? $itemspage : 20));
	$pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval(\App::$pager['itemspage']), intval(\App::$pager['start']));

	$regexop = db_getfunc('REGEXP');

	$r = q("select item_id.sid, item.* from item left join item_id on item.id = item_id.iid where service = 'docfile' and
		body $regexop '%s' and item_type = %d $pager_sql",
		dbesc($s),
		intval(ITEM_TYPE_DOC)
	);
	
	$r = fetch_post_tags($r,true);

	for($x = 0; $x < count($r); $x ++) {

		$r[$x]['text'] = $r[$x]['body'];

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
		// bias the results to the observer's native language
		if($r[$x]['lang'] === \App::$language)
			$r[$x]['rank'] = $r[$x]['rank'] + 10;

	}
	usort($r,'doc_rank_sort');
	return $r;
}


function doc_rank_sort($s1,$s2) {
	if($s1['rank'] == $s2['rank'])
		return 0;
	return (($s1['rank'] < $s2['rank']) ? 1 : (-1));
}


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


function store_doc_file($s) {

	if(is_dir($s))
		return;

	$item = array();
	$sys = get_sys_channel();

	$item['aid'] = 0;
	$item['uid'] = $sys['channel_id'];


	if(strpos($s,'.md'))
		$mimetype = 'text/markdown';
	elseif(strpos($s,'.html'))
		$mimetype = 'text/html';
	else
		$mimetype = 'text/bbcode';

	require_once('include/html2plain.php');

	$item['body'] = html2plain(prepare_text(file_get_contents($s),$mimetype, true));
	$item['mimetype'] = 'text/plain';
	
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

