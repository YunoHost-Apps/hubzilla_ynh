<?php

function dreport_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied') . EOL);
		return;
	}

	$table = 'item';

	$channel = $a->get_channel();
	
	$mid = ((argc() > 1) ? argv(1) : '');

	if($mid === 'mail') {
		$table = 'mail';
		$mid = ((argc() > 2) ? argv(2) : '');
	}


	if(! $mid) {
		notice( t('Invalid message') . EOL);
		return;
	}

	switch($table) {
		case 'item':
			$i = q("select id from item where mid = '%s' and author_xchan = '%s' ",
				dbesc($mid),
				dbesc($channel['channel_hash'])
			);
			break;
		case 'mail':
			$i = q("select id from mail where mid = '%s' and from_xchan = '%s'",
				dbesc($mid),
				dbesc($channel['channel_hash'])
			);
			break;
		default:
			break;
	}

	if(! $i) {
		notice( t('Permission denied') . EOL);
		return;
	}
	
	$r = q("select * from dreport where dreport_xchan = '%s' and dreport_mid = '%s'",
		dbesc($channel['channel_hash']),
		dbesc($mid)
	);

	if(! $r) {
		notice( t('no results') . EOL);
		return;
	}


	$o .= '<h2>' . sprintf( t('Delivery report for %1$s'),substr($mid,0,32)) . '...' . '</h2>';
	$o .= '<table>';

	for($x = 0; $x < count($r); $x++ ) {
		$r[$x]['name'] = escape_tags(substr($r[$x]['dreport_recip'],strpos($r[$x]['dreport_recip'],' ')));

		// This has two purposes: 1. make the delivery report strings translateable, and
		// 2. assign an ordering to item delivery results so we can group them and provide
		// a readable report with more interesting events listed toward the top and lesser
		// interesting items towards the bottom

		switch($r[$x]['dreport_result']) {
			case 'channel sync processed':
				$r[$x]['gravity'] = 0;
				$r[$x]['dreport_result'] = t('channel sync processed');
				break;
			case 'queued':
				$r[$x]['gravity'] = 2;
				$r[$x]['dreport_result'] = t('queued');
				break;
			case 'posted':
				$r[$x]['gravity'] = 3;
				$r[$x]['dreport_result'] = t('posted');
				break;
			case 'accepted for delivery':
				$r[$x]['gravity'] = 4;
				$r[$x]['dreport_result'] = t('accepted for delivery');
				break;
			case 'updated':
				$r[$x]['gravity'] = 5;
				$r[$x]['dreport_result'] = t('updated');
			case 'update ignored':
				$r[$x]['gravity'] = 6;
				$r[$x]['dreport_result'] = t('update ignored');
				break;
			case 'permission denied':
				$r[$x]['dreport_result'] = t('permission denied');
				$r[$x]['gravity'] = 6;
				break;
			case 'recipient not found':
				$r[$x]['dreport_result'] = t('recipient not found');
				break;
			case 'mail recalled':
				$r[$x]['dreport_result'] = t('mail recalled');
				break;
			case 'duplicate mail received':
				$r[$x]['dreport_result'] = t('duplicate mail received');
				break;
			case 'mail delivered':
				$r[$x]['dreport_result'] = t('mail delivered');
				break;
			default:
				$r[$x]['gravity'] = 1;
				break;
		}
	}

	usort($r,'dreport_gravity_sort');
		

	foreach($r as $rr) {
		$o .= '<tr><td width="40%">' . $rr['name'] . '</td><td width="20%">' . escape_tags($rr['dreport_result']) . '</td><td width="20%">' . escape_tags($rr['dreport_time']) . '</td></tr>';
	}
	$o .= '</table>';

	return $o;



}

function dreport_gravity_sort($a,$b) {
	if($a['gravity'] == $b['gravity']) {
		if($a['name'] === $b['name'])
			return strcmp($a['dreport_time'],$b['dreport_time']);
		return strcmp($a['name'],$b['name']);
	}
	return (($a['gravity'] > $b['gravity']) ? 1 : (-1));
}