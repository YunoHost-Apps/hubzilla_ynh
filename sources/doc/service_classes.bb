[b]Service Classes[/b]

Service classes allow you to set limits on system resources. A GUI to configure this is currently under development. 

As a temporary measure, the following commandline utilities can be used:

Usage:

[code]util/service_class[/code]
list service classes

[code]util/config system default_service_class firstclass[/code]
set the default service class to 'firstclass'

[code]util/service_class firstclass[/code]
list the services that are part of 'firstclass' service class

[code]util/service_class firstclass photo_upload_limit 10000000[/code]
set firstclass total photo disk usage to 10 million bytes

[code]util/service_class --account=5 firstclass[/code]
set account id 5 to service class 'firstclass' (with confirmation)

[code]util/service_class --channel=blogchan firstclass[/code]
set the account that owns channel 'blogchan' to service class 'firstclass' (with confirmation)

[b]current limits[/b]
photo_upload_limit - maximum total bytes for photos
total_items - maximum total toplevel posts
total_pages - maximum comanche pages
total_identities - maximum number of channels owned by account
total_channels - maximum number of connections
total_feeds - maximum number of rss feed connections
attach_upload_limit - maximum file upload storage (bytes)
minimum_feedcheck_minutes - lowest setting allowed for polling rss feeds
chatrooms - maximum chatrooms
chatters_inroom - maximum chatters per room
access_tokens - maximum number of Guest Access Tokens per channel