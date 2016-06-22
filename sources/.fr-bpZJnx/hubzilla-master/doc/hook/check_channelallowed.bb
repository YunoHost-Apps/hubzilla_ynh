[h2]check_channelallowed[/h2]

Called when checking the channel (xchan) black and white lists to see if a channel is blocked.

Hook data 

	array('hash' => xchan_hash of xchan to check);

	create and set array element 'allowed' to true or false to override the system checks


