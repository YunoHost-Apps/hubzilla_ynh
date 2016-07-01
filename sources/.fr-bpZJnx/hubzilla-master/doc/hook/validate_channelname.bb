[h2]validate_channelname[/h2]

Called when creating a new channel or changing the channel name in mod/settings.php

Hook data consists of an array 

	array(
		'name' => supplied name
	);

	If the hook handler determines the name is valid, do nothing. If there is an issue with the name,
	set $hook_data['message'] to the message text which should be displayed to the member - and the name will
	not be accepted. 


	Example:
	[code]
		if(mb_strlen($hook_data['name']) < 3)
			$hook_data['message'] = t('Name too short.');
	[/code]


	