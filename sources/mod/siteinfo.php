<?php

function siteinfo_init(&$a) {

	
	if (argv(1) ===  'json') {
		$data = get_site_info();
		json_return_and_die($data);
	}
}



function siteinfo_content(&$a) {

	if(! get_config('system','hidden_version_siteinfo')) {
		$version = sprintf( t('Version %s'), RED_VERSION );
		if(@is_dir('.git') && function_exists('shell_exec')) {
			$commit = @shell_exec('git log -1 --format="%h"');
			$tag = @shell_exec('git describe --tags --abbrev=0');
		}
		if(! isset($commit) || strlen($commit) > 16)
			$commit = '';
	}
	else {
	        $version = $commit = '';
	}
	$visible_plugins = array();
	if(is_array($a->plugins) && count($a->plugins)) {
		$r = q("select * from addon where hidden = 0");
		if(count($r))
			foreach($r as $rr)
				$visible_plugins[] = $rr['name'];
	}

	$plugins_list = '';
	if(count($visible_plugins)) {
	        $plugins_text = t('Installed plugins/addons/apps:');
		$sorted = $visible_plugins;
		$s = '';
		sort($sorted);
		foreach($sorted as $p) {
			if(strlen($p)) {
				if(strlen($s)) $s .= ', ';
				$s .= $p;
			}
		}
		$plugins_list .= $s;
	}
	else
		$plugins_text = t('No installed plugins/addons/apps');

	$txt = get_config('system','admininfo');
	$admininfo = bbcode($txt);

	if(file_exists('doc/site_donate.html'))
		$donate .= file_get_contents('doc/site_donate.html');

	if(function_exists('sys_getloadavg'))
		$loadavg = sys_getloadavg();

	$o = replace_macros(get_markup_template('siteinfo.tpl'), array(
		'$title' => t('$Projectname'),
		'$description' => t('This is a hub of $Projectname - a global cooperative network of decentralized privacy enhanced websites.'),
		'$version' => $version,
		'$tag_txt' => t('Tag: '),
		'$tag' => $tag,
		'$polled' => t('Last background fetch: '),
		'$lastpoll' => get_poller_runtime(),
		'$load_average' => t('Current load average: '),
		'$loadavg_all' => $loadavg[0] . ', ' . $loadavg[1] . ', ' . $loadavg[2],		
		'$commit' => $commit,
		'$web_location' => t('Running at web location') . ' ' . z_root(),
		'$visit' => t('Please visit <a href="http://hubzilla.org">hubzilla.org</a> to learn more about $Projectname.'),
		'$bug_text' => t('Bug reports and issues: please visit'),
		'$bug_link_url' => 'https://github.com/redmatrix/hubzilla/issues',
		'$bug_link_text' => t('$projectname issues'),
		'$contact' => t('Suggestions, praise, etc. - please email "redmatrix" at librelist - dot com'),
		'$donate' => $donate,
		'$adminlabel' => t('Site Administrators'),
		'$admininfo' => $admininfo,
		'$plugins_text' => $plugins_text,
		'$plugins_list' => $plugins_list
        ));

	call_hooks('about_hook', $o); 	

	return $o;

}
