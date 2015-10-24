<?php

require_once('include/items.php');
require_once('include/conversation.php');


function home_init(&$a) {

	$ret = array();

	call_hooks('home_init',$ret);

	$splash = ((argc() > 1 && argv(1) === 'splash') ? true : false);

	$channel = $a->get_channel();
	if(local_channel() && $channel && $channel['xchan_url'] && ! $splash) {
		$dest = $channel['channel_startpage'];
		if(! $dest)
			$dest = get_pconfig(local_channel(),'system','startpage');
		if(! $dest)
			$dest = get_config('system','startpage');
		if(! $dest)
			$dest = z_root() . '/network';

		goaway($dest);
	}

	if(get_account_id() && ! $splash) {
		goaway(z_root() . '/new_channel');
	}

}


function home_content(&$a, $update = 0, $load = false) {

	$o = '';


	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);
	if(x($_SESSION,'mobile_theme'))
		unset($_SESSION['mobile_theme']);

	$splash = ((argc() > 1 && argv(1) === 'splash') ? true : false);

	call_hooks('home_content',$o);
	if($o)
		return $o;

	$frontpage = get_config('system','frontpage');
	if($frontpage) {
		if(strpos($frontpage,'include:') !== false) {
			$file = trim(str_replace('include:' , '', $frontpage));
			if(file_exists($file)) {
				$a->page['template'] = 'full';
				$a->page['title'] = t('$Projectname');
				$o .= file_get_contents($file);
				return $o;
			}
		}
		if(strpos($frontpage,'http') !== 0)
			$frontpage = z_root() . '/' . $frontpage;
		if(intval(get_config('system','mirror_frontpage'))) {
			$o = '<html><head><title>' . t('$Projectname') . '</title></head><body style="margin: 0; padding: 0; border: none;" ><iframe src="' . $frontpage . '" width="100%" height="100%" style="margin: 0; padding: 0; border: none;" ></iframe></body></html>';
			echo $o;
			killme();
		}
		goaway($frontpage);
	}


	$sitename = get_config('system','sitename');
	if($sitename) 
		$o .= '<h1>' . sprintf( t("Welcome to %s") ,$sitename) . '</h1>';

	$loginbox = get_config('system','login_on_homepage');
	if(intval($loginbox) || $loginbox === false)
		$o .= login(($a->config['system']['register_policy'] == REGISTER_CLOSED) ? 0 : 1);

	return $o;

}
