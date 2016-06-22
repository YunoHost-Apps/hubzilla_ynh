[h2]nav[/h2]

Called when generating the main navigation bar and menu for a page

Hook data:

	array(
		'usermenu' => array( 'icon' => photo URL, 'name' => channel name )
		'nav' => array(
			'usermenu' => usermenu (photo menu) link array
				(channel home, profiles, photos, cloud, chats, webapges ...)
			'loginmenu' => login menu link array
			'network' => grid link and grid-notify
			'home' => home link and home-notify
			'intros' => intros link and intros-notify
			'notifications' => notifications link and notifications-notify
			'messages' => PM link and PM-notify
			'all_events' => events link and events notfiy
			'manage' => manage channels link
			'settings' => settings link 
			'register' => registration link
			'help' => help/doc link
			'apps' => apps link
			'search' => search link and form
			'directory' => directory link
		)

			
