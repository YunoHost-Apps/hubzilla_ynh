[h2]Server Roles[/h2]

$Projectname can be configured in many different ways. One of the configurations available at installation is to select a 'server role'. There are currently three server roles. We highly recommend that you use 'standard' unless you have special needs. 


[h3]Basic[/h3]

The 'basic' server role is designed to be relatively simple, and it doesn't present options or complicated features. The hub admin may configure additional features at a site level. This role is designed for simple social networking and social network federation. Many features which do not federate easily have been removed, including (and this is somewhat important) "nomadic identity". You may move a channel to or from a basic server, but you may not clone it. Once moved, it becomes read-only on the origination site. No updates of any kind will be provided. It is recommended that after the move, the original channel be deleted - as it is no longer useable. The data remains only in case there are issues making a copy of the data. 

This role is supported by the hubzilla community.

[h3]Standard[/h3]


The 'standard' server role is recommended for most sites. All features of the software are available to everybody unless locked by the hub administrator. Some features will not federate easily with other networks, so there is often an increased support burden explaining why sharing events with Diaspora (for instance) presents a different experience on that network. Additionally any member can enable "advanced" or "expert" features, and these may be beyond their technical skill level. This may also result in an increased support burden.

This role is supported by the hubzilla community.

[h3]Pro[/h3]

The 'pro' server role is primarily designed for communities which want to present a uniform experience and be relieved of many federation support issues. In this role there is [b]no federation with other networks[/b]. Additional features [b]may[/b] be provided, such as channel ratings, premium channels, and e-commerce. 

By default a channel may set a "techlevel" appropriate to their technical skill. Higher levels provide more features. Everybody starts with techlevel 0 (similar to the 'basic' role) where all complicated features are hidden from view. Increasing the techlevel provides more advanced or complex features.

The hub admin may also lock individual channels or their entire site at a defined techlevel which provides an installation with a selection of advanced features consistent with the perceived technical skills of the members. For instance, a community for horse racing might have a different techlevel than a community for Linux kernel developers. 

This role is not supported by the hubzilla community. 