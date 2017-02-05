[h2]change_channel[/h2]

Called when entering a logged in state in a channel context (as opposed to an account context).
The hook array provides two arguments, 'channel_id' and 'chanx'. 'chanx' is a union of the channel 
and xchan records for the now active channel.

Use this to capture what would traditionally be known as 'login events'.  In this platform, login is
a separate authentication activity and doesn't necessarily require "connecting to an identity", which
is what the change_channel activity represents. 


