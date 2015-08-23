<?php

require_once('include/socgraph.php');
require_once('include/dir_fns.php');
require_once('include/widgets.php');
require_once('include/bbcode.php');

function directory_init(&$a) {
	$a->set_pager_itemspage(60);

	if(x($_GET,'ignore')) {
		q("insert into xign ( uid, xchan ) values ( %d, '%s' ) ",
			intval(local_channel()),
			dbesc($_GET['ignore'])
		);
		goaway(z_root() . '/directory?suggest=1');
	}

	$observer = get_observer_hash();
	$global_changed = false;
	$safe_changed = false;
	$pubforums_changed = false;

	if(array_key_exists('global',$_REQUEST)) {
		$globaldir = intval($_REQUEST['global']);
		$global_changed = true;
	}
	if($global_changed) {
		$_SESSION['globaldir'] = $globaldir;
		if($observer)
			set_xconfig($observer,'directory','globaldir',$globaldir);
	}

	if(array_key_exists('safe',$_REQUEST)) {
		$safemode = intval($_REQUEST['safe']);
		$safe_changed = true;
	}
	if($safe_changed) {
		$_SESSION['safemode'] = $safemode;
		if($observer)
			set_xconfig($observer,'directory','safemode',$safemode);
	}


	if(array_key_exists('pubforums',$_REQUEST)) {
		$pubforums = intval($_REQUEST['pubforums']);
		$pubforums_changed = true;
	}
	if($pubforums_changed) {
		$_SESSION['pubforums'] = $pubforums;
		if($observer)
			set_xconfig($observer,'directory','pubforums',$pubforums);
	}
}

function directory_content(&$a) {

	if((get_config('system','block_public')) && (! local_channel()) && (! remote_channel())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	$observer = get_observer_hash();

	$globaldir = get_directory_setting($observer, 'globaldir');
	// override your personal global search pref if we're doing a navbar search of the directory
	if(intval($_REQUEST['navsearch']))
		$globaldir = 1;

	$safe_mode = get_directory_setting($observer, 'safemode');

	$pubforums = get_directory_setting($observer, 'pubforums');

	$o = '';
	nav_set_selected('directory');

	if(x($_POST,'search'))
		$search = notags(trim($_POST['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');


	if(strpos($search,'=') && local_channel() && get_pconfig(local_channel(),'feature','expert'))
		$advanced = $search;


	$keywords = (($_GET['keywords']) ? $_GET['keywords'] : '');

	// Suggest channels if no search terms or keywords are given
	$suggest = (local_channel() && x($_REQUEST,'suggest')) ? $_REQUEST['suggest'] : '';

	if($suggest) {

		$r = suggestion_query(local_channel(),get_observer_hash());

		// Remember in which order the suggestions were
		$addresses = array();
		$common = array();
		$index = 0;
		foreach($r as $rr) {
			$common[$rr['xchan_addr']] = $rr['total'];
			$addresses[$rr['xchan_addr']] = $index++;
		}

		// Build query to get info about suggested people
		$advanced = '';
		foreach(array_keys($addresses) as $address) {
			$advanced .= "address=\"$address\" ";
		}
		// Remove last space in the advanced query
		$advanced = rtrim($advanced);

	}

	$tpl = get_markup_template('directory_header.tpl');

	$dirmode = intval(get_config('system','directory_mode'));

	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
		$url = z_root() . '/dirsearch';
	}
	if(! $url) {
		$directory = find_upstream_directory($dirmode);
		$url = $directory['url'] . '/dirsearch';
	}

	$token = get_config('system','realm_token');


	logger('mod_directory: URL = ' . $url, LOGGER_DEBUG);

	$contacts = array();

	if(local_channel()) {
		$x = q("select abook_xchan from abook where abook_channel = %d",
			intval(local_channel())
		);
		if($x) {
			foreach($x as $xx)
				$contacts[] = $xx['abook_xchan'];
		}
	}

	if($url) {
		// We might want to make the tagadelic count (&kw=) configurable or turn it off completely.

		$numtags = get_config('system','directorytags');

		$kw = ((intval($numtags)) ? $numtags : 50);
		$query = $url . '?f=&kw=' . $kw . (($safe_mode != 1) ? '&safe=' . $safe_mode : '');

		if($token)
			$query .= '&t=' . $token;

		if(! $globaldir)
			$query .= '&hub=' . get_app()->get_hostname();

		if($search)
			$query .= '&name=' . urlencode($search) . '&keywords=' . urlencode($search);
		if(strpos($search,'@'))
			$query .= '&address=' . urlencode($search);
		if($keywords)
			$query .= '&keywords=' . urlencode($keywords);
		if($advanced)
			$query .= '&query=' . urlencode($advanced);
		if(! is_null($pubforums))
			$query .= '&pubforums=' . intval($pubforums);

		$directory_sort_order = get_config('system','directory_sort_order');
		if(! $directory_sort_order)
			$directory_sort_order = 'date';

		$sort_order  = ((x($_REQUEST,'order')) ? $_REQUEST['order'] : $directory_sort_order);

		if($sort_order)
			$query .= '&order=' . urlencode($sort_order);
			
		if($a->pager['page'] != 1)
			$query .= '&p=' . $a->pager['page'];

		logger('mod_directory: query: ' . $query);

		$x = z_fetch_url($query);
		logger('directory: return from upstream: ' . print_r($x,true), LOGGER_DATA);

		if($x['success']) {
			$t = 0;
			$j = json_decode($x['body'],true);
			if($j) {

				if($j['results']) {

					$entries = array();

					$photo = 'thumb';

					foreach($j['results'] as $rr) {

						$profile_link = chanlink_url($rr['url']);
		
						$pdesc = (($rr['description']) ? $rr['description'] . '<br />' : '');
						$connect_link = ((local_channel()) ? z_root() . '/follow?f=&url=' . urlencode($rr['address']) : ''); 		

						// Checking status is disabled ATM until someone checks the performance impact more carefully
						//$online = remote_online_status($rr['address']);
						$online = '';

						if(in_array($rr['hash'],$contacts))
							$connect_link = '';

						$location = '';
						if(strlen($rr['locale']))
							$location .= $rr['locale'];
						if(strlen($rr['region'])) {
							if(strlen($rr['locale']))
								$location .= ', ';
							$location .= $rr['region'];
						}
						if(strlen($rr['country'])) {
							if(strlen($location))
								$location .= ', ';
							$location .= $rr['country'];
						}

						$age = '';
						if(strlen($rr['birthday'])) {
							if(($years = age($rr['birthday'],'UTC','')) != 0)
								$age = $years;
						}

						$page_type = '';

						if($rr['total_ratings'])
							$total_ratings = sprintf( tt("%d rating", "%d ratings", $rr['total_ratings']), $rr['total_ratings']);
						else
							$total_ratings = '';

						$profile = $rr;

						if ((x($profile,'locale') == 1)
							|| (x($profile,'region') == 1)
							|| (x($profile,'postcode') == 1)
							|| (x($profile,'country') == 1))

						$gender = ((x($profile,'gender') == 1) ? t('Gender: ') . $profile['gender']: False);
	
						$marital = ((x($profile,'marital') == 1) ?  t('Status: ') . $profile['marital']: False);
		
						$homepage = ((x($profile,'homepage') == 1) ?  t('Homepage: ') : False);
						$homepageurl = ((x($profile,'homepage') == 1) ?  $profile['homepage'] : ''); 

						$hometown = ((x($profile,'hometown') == 1) ? $profile['hometown']  : False);

						$about = ((x($profile,'about') == 1) ? bbcode($profile['about']) : False);

						$keywords = ((x($profile,'keywords')) ? $profile['keywords'] : '');

						$out = '';

						if($keywords) {
							$keywords = str_replace(',',' ', $keywords);
							$keywords = str_replace('  ',' ', $keywords);
							$karr = explode(' ', $keywords);

							if($karr) {
								if(local_channel()) {
									$r = q("select keywords from profile where uid = %d and is_default = 1 limit 1",
										intval(local_channel())
									);
									if($r) {
										$keywords = str_replace(',',' ', $r[0]['keywords']);
										$keywords = str_replace('  ',' ', $keywords);
										$marr = explode(' ', $keywords);
									}
								}
								foreach($karr as $k) {
									if(strlen($out))
										$out .= ', ';
									if($marr && in_arrayi($k,$marr))
										$out .= '<strong>' . $k . '</strong>';
									else
										$out .= $k;
								}
							}
			
						}

						$entry = array(
							'id' => ++$t,
							'profile_link' => $profile_link,
							'public_forum' => $rr['public_forum'],
							'photo' => $rr['photo'],
							'hash' => $rr['hash'],
							'alttext' => $rr['name'] . ((local_channel() || remote_channel()) ? ' ' . $rr['address'] : ''),
							'name' => $rr['name'],
							'age' => $age,
							'age_label' => t('Age:'),
							'profile' => $profile,
							'address' =>  $rr['address'],
							'nickname' => substr($rr['address'],0,strpos($rr['address'],'@')),
							'location' => $location,
							'location_label' => t('Location:'),
							'gender'   => $gender,
							'total_ratings' => $total_ratings,
							'viewrate' => true,
							'canrate' => ((local_channel()) ? true : false),
							'pdesc'	=> $pdesc,
							'pdesc_label' => t('Description:'),
							'marital'  => $marital,
							'homepage' => $homepage,
							'homepageurl' => linkify($homepageurl),
							'hometown' => $hometown,
							'hometown_label' => t('Hometown:'),
							'about' => $about,
							'about_label' => t('About:'),
							'conn_label' => t('Connect'),
							'forum_label' => t('Public Forum:'), 
							'connect' => $connect_link,
							'online' => $online,
							'kw' => (($out) ? t('Keywords: ') : ''),
							'keywords' => $out,
							'ignlink' => $suggest ? $a->get_baseurl() . '/directory?ignore=' . $rr['hash'] : '',
							'ignore_label' => t('Don\'t suggest'),
							'common_friends' => (($common[$rr['address']]) ? intval($common[$rr['address']]) : ''),
							'common_label' => t('Common connections:'),
							'common_count' => intval($common[$rr['address']]),
							'safe' => $safe_mode
						);

						$arr = array('contact' => $rr, 'entry' => $entry);

						call_hooks('directory_item', $arr);

						unset($profile);
						unset($location);

						if(! $arr['entry']) {
							continue;
						}			
						
						if($sort_order == '' && $suggest) {
							$entries[$addresses[$rr['address']]] = $arr['entry']; // Use the same indexes as originally to get the best suggestion first
						}

						else {
							$entries[] = $arr['entry'];
						}
					}

					ksort($entries); // Sort array by key so that foreach-constructs work as expected

					if($j['keywords']) {
						$a->data['directory_keywords'] = $j['keywords'];
					}

					logger('mod_directory: entries: ' . print_r($entries,true), LOGGER_DATA);


					if($_REQUEST['aj']) {
						if($entries) {
							$o = replace_macros(get_markup_template('directajax.tpl'),array(
								'$entries' => $entries
							));
						}
						else {
							$o = '<div id="content-complete"></div>';
						}
						echo $o;
						killme();
					}
					else {
						$maxheight = 94;

						$dirtitle = (($globaldir) ? t('Global Directory') : t('Local Directory'));

						$o .= "<script> var page_query = '" . $_GET['q'] . "'; var extra_args = '" . extra_query_args() . "' ; divmore_height = " . intval($maxheight) . ";  </script>";
						$o .= replace_macros($tpl, array(
							'$search' => $search,
							'$desc' => t('Find'),
							'$finddsc' => t('Finding:'),
							'$safetxt' => htmlspecialchars($search,ENT_QUOTES,'UTF-8'),
							'$entries' => $entries,
							'$dirlbl' => $suggest ? t('Channel Suggestions') : $dirtitle,
							'$submit' => t('Find'),
							'$next' => alt_pager($a,$j['records'], t('next page'), t('previous page')),
							'$sort' => t('Sort options'),
							'$normal' => t('Alphabetic'),
							'$reverse' => t('Reverse Alphabetic'),
							'$date' => t('Newest to Oldest'),
							'$reversedate' => t('Oldest to Newest'),
							'$suggest' => $suggest ? '&suggest=1' : ''
						));


					}

				}
				else {
					if($_REQUEST['aj']) {
						$o = '<div id="content-complete"></div>';
						echo $o;
						killme();
					}
					if($a->pager['page'] == 1 && $j['records'] == 0 && strpos($search,'@')) {
						goaway(z_root() . '/chanview/?f=&address=' . $search);
					}
					info( t("No entries (some entries may be hidden).") . EOL);
				}
			}
		}
	}
	return $o;
}

