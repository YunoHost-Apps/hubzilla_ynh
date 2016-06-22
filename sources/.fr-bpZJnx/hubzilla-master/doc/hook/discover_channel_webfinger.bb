[h2]discover_channel_webfinger[/h2]

Called after performing channel discovery using RFC7033 webfinger and where the channel is not recognised as zot. 

Passed an array:

	address: URL or address that is being discovered
	success: set to true if the plugin discovers something
	webfinger: array of webfinger links (output of webfinger_rfc7033())


	if your plugin indicates success you are expected to generate and populate an xchan (and hubloc) record prior to returning. 

	