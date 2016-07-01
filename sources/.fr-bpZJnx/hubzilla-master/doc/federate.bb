[h2]Creating protocol federation services[/h2]

There are three main components to writing federation plugins. These are:

[1] Channel discovery and making connections
[2] Sending posts/messages
[3] Receiving posts/messages

In addition, federation drivers must handle

[4] differences in privacy policies (and content formats)


[h3]Making connections[/h3]

The core application provides channel discovery in the following sequence:

[1] Zot channel discovery
[2] Webfinger (channel@hub) lookup
	[2.1] RFC7033 webfinger
	[2.2] XRD based webfinger (old style)
[3] URL discovery (currently only used to discover RSS feeds)
[4] If all of these fail, the network is "unknown" and we are unable to communicate with or connect with the channel. An 'xchan' record [i]may[/i] still be created [b]if[/b] there is enough information known to identify a unique channel.   

Any of the lookup services may be bound to a plugin and extended. When a channel is discovered, we create an 'xchan' record which is a platform neutral representation of the channel identity. If we need to send information to the channel, a 'hubloc' (hub location) record is also generally required. A 'follow' plugin hook is provided to bypass webfinger and this discovery sequence completely. 

The final step in gluing this together is to create an 'abook' record, which attaches an xchan in a relationship to a local channel with a given set of permissions. 

For networks which do not support nomadic identity, your plugin must also set "abook_instance" which is a comma-separated list of local URLs that the remote channel is connected with. For instance if your member was connected to my channel clone at https://example.com, the abook_instance for that connection would read 'https://example.com'. If you also connected to my clone at https://abc.example.com, the string would be changed to 'https://example.com,https://abc.example.com'. This allows local channels to differentiate which instance a given remote channel is connected with and avoid delivery failures to those channels from other clone instances. 

A federation plugin for a webfinger based system needs only to examine the webfinger or XRD results and identify a protocol stack which can be used to connect or communicate. Then create an xchan record with the given 'xchan_network' type and a hubloc record with the same 'hubloc_network' with the information given. Currently the plugin will need to create the entire record, but in the future this will be extended so that the plugin only need identify a network name and the record will be populated with all other known values.

An xchan record is always required in order to connect. To connect, create an abook record with the desired permissions. 

Additional information that your plugin requires for communication can be stored in the xconfig table and/or abconfig table if there is no convenient or appropriate table column in the xchan or hubloc tables.

When a connection is made, we generally call the notifier (include/notifier.php) to send a message to the remote channel. This is bound to the hook 'permissions_create'. Your plugin will need to handle this in order to send a "follow" or "make friends" message to the other network.

Notes: The first stage zot lookup will be replaced with a webfinger lookup. This work is in progress. A separate lookup was required initially as webfinger does not allow non-SSL connections. We will provide non-SSL zot lookups (usually test and development sites) via the "old" XRD based webfinger to avoid this limitation. 

The core application will attempt to create xchan records for projects identified as members of the "open web"; currently Hubzilla, Friendica, Diaspora, GNU-Social and Pump.io. This is so that comments can be passed amongst project sites and the network correctly identified. A federation plugin is required to fully federate with other networks, but comments may be passed to sites without such a plugin installed so that there are no unexplained holes in conversations.

The core application must also provide signing ability for Diaspora comments since they require a special signing format and must be signed by the comment author regardless of whether that channel federates with Diaspora. The owner of the conversation may federate with Diaspora so the comments must be signed. This is unfortunate but necessary.   


[h3]Sending Messages[/h3]

Whenever any message is sent (with the sole exception of directory communications), we invoke the notifier (include/notifier.php), and pass it the type of message and generally an identifier to lookup the information being sent from the database (items or conversational things, private mail, permissions updates, etc.). 

The notifier has several hooks which may be used by plugins in different ways, depending on how their delivery loop works. For different message types and complex delivery situations you may need to tie into multiple hooks. The 'permissions_create' hook was mentioned in the first section. There is also a 'permissions_update' message if permissions have changed and the other end of the link needs to be advised. Few services will provide or handle this (as their permissions are static), but it is also used for instance to send profile and profile photo update messages and you may wish to handle this.

The next plugin hook is 'notifier_process'. It is passed an array providing the complete state of the notifier and is called once per notifier invocation. It contains the complete list of recipients (with xchan_network set for each).

There is also 'notifier_hub' which like 'notifier_process' is passed the complete state of the notifier, but the difference is that it is called for each individual hub or distinct URL delivery and may be matched on the hubloc_network type. Hub delivery is much more efficient than recipient delivery but may not be suitable for all protocol stacks. 


Your plugin will be required to understand the message state and recipients and translate the sent item to the desired format. You will also be required to check privacy and block communication to anybody but the intended recipients - *if* it is a private communication. The plugin will often at this point stick the message into the queue and return the queue id to the notifier.


Queue handlers exist already for simple posted data. If you create a queue entry with 'post' type we'll open an HTTP POST request and post the data provided and acknowledge success or failure. You can create other forms of communication by providing a different outq_driver type and handling the processing of queue requests yourself. Delivery reporting is available if you wish to acknowledge different error conditions, or anything beyond success/failure. Advanced delivery reporting will also require a custom queue type. The basic 'post' type only deals with success (communication successful with the remote site) and failure.   


    
[h3]Receiving Messages[/h3]


Receiving messages from the remote network will probably require a 'receive' endpoint or module dedicated to your network communication protocol. This is a URL route that your plugin may need to register with the'module_loaded' hook. You module will then take responsibility for importing any data which arrives at that endpoint and translating it to the format required for this project and storing the resulting data. The basic structure we use is an extensible activitystream item but with slightly different field names and several optional fields. It can be easily mapped to an activitystream. Additional data can be stored in the "iconfig" table. item_store() and item_store_update() are generally used to store the data and send appropriate notifications. Similiar facilities are available for private mail and profile information.



  
