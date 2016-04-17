<?php

/**
 * Name: Who Likes me?
 * Description: Find out who your biggest fans are (who "likes" your posts)
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 */

function wholikesme_load() {}
function wholikesme_unload() {}
function wholikesme_module() {}

function wholikesme_content(&$a) {

	if(! local_channel())
		return;

	$channel = App::get_channel();

	$r = q("select count(mid) as total, author_xchan, xchan_name from item left join xchan on author_xchan = xchan_hash where uid = %d and verb = '%s' and owner_xchan = '%s' group by author_xchan order by total desc",
		intval(local_channel()),
		dbesc(ACTIVITY_LIKE),
		dbesc($channel['xchan_hash'])
	);

	if($r) {
		$o = '<h3>' . t('Who likes me?') . '</h3>';
		$o .= '<ul>';
		foreach($r as $rr) {
			$o .= '<li>' . $rr['xchan_name'] . ' ' . $rr['total'] . '</li>';
		};
		$o .= '</ul>';
	};

	return $o;
}