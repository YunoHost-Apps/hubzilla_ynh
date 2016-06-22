[h3]Directory Configuration[/h3]

Directories in $Projectname serve the purpose of searching and locating members anywhere in the network. They are also used to store and query "ratings" of members and sites. The directory services are distributed and mirrored so that a failure of one will not take down or disrupt the entire network.

[b]Standard Configuration[/b]

New sites operating as directory clients will automatically select from a hard-coded list of directory servers during their first directory access. You may examine or over-ride this decision using 

[code]
util/config system directory_server
[/code]

To set a different server,

[code]
util/config system directory_server https://newdirectory.something
[/code]


[b]Standalone configuration[/b]

Some sites may wish to operate in 'standalone' mode and not connect to any external directory services. This is useful for isolated sites ("off the gird") and test sites, but can also be useful for small organisations who do not wish to connect with other sites in the network. 

To configure this, please look in your .htconfig.php file for the following text and set the configuration accordingly.

[code]
// Configure how we communicate with directory servers.
// DIRECTORY_MODE_NORMAL     = directory client, we will find a directory
// DIRECTORY_MODE_SECONDARY  = caching directory or mirror
// DIRECTORY_MODE_PRIMARY    = main directory server
// DIRECTORY_MODE_STANDALONE = "off the grid" or private directory services

App::$config['system']['directory_mode']  = DIRECTORY_MODE_STANDALONE;
[/code]


[b]Secondary server configuration[/b]

You may also configure your site as a secondary server. This operates as a mirror of the primary directory and allows disitribution of the load amongst available servers. There is very little functional difference between a primary and secondary sever, however there may only be *one* primary directory server per realm (realms are discussed later in this document).

Before choosing to be a directory server, please be advised that you should be an active member of the network and have the resources and time available to manage these services. They don't typically require management, but the requirement is more for stability as losing a directory server can cause issues to directory clients which are reliant on it.


[b]Changing the directory server[/b]

If a directory server indicates that it is no longer a directory server, this should be detected by the software and the configuration for that server will be removed (blanked). If it goes offline permanently without warning, you will only know if site members report that directory services are unavailable. Currently this can only be repaired manually by the site administrator by selecting a new directory and performing:

[code]
util/config system directory_server https://newdirectory.something
[/code]

Eventually we hope to make this a selectable box from the site admin panel. 


[h2]Directory realms[/h2]

Large organisations may wish to use directory 'realms' rather than a single standalone directory. The standard and default realm is known as RED_GLOBAL. By creating a new realm, your organisation has the ability to create its own hierarchy of primary and secondary servers and clients. 

[code]
util/config system directory_realm MY_REALM
[/code]

Your realm *must* have a primary directory. Create this first. Then set the realm the same on all sites within your directory realm (servers and clients). 

You may also provide a "sub-realm" that operates indepently from the RED_GLOBAL realm (or any other realm) but allows cross membership and some ability to lookup members of the entire directory space. This has only undergone light testing so be prepared to help out and fix any issues that may arise. A sub-realm contains its parent realm within the realm name.

 
[code]
util/config system directory_realm RED_GLOBAL:MY_REALM
[/code]
  

[b]Realm access[/b]

You may wish that your directory servers and services are only used by members of your realm. To do this a token or password must be supplied to access the realm directory services. This token is not encrypted during transit, but is sufficient to prevent casual access to your directory servers. The following must be configured for all sites (clients and directory servers) within the realm:

[code]
util/config system realm_token my-secret-realm-password
[/code]



[h2]Directory mirrors[/h2]

Mirroring occurs with a daily transaction log of activities which are shared between directory servers. In the case of directory and profile updates, the channel address performing the update is transmitted, and the other directory servers probe that channel at its source for changes. We do not and should not trust any information given us by other directory servers. We always check the information at the source.  

Ratings are handled slightly differently - an encrypted packet (signed by the channel that created the rating) is passed between the servers. This signature needs to be verified before the rating is accepted. Ratings are always published to the primary directory server and propagated to all other directory servers from there. For this reason there can only be one primary server in a realm. If a misconfigured site claims to be a primary directory, it is ignored in the RED_GLOBAL realm. For other realms there is currently no such protection. Be aware of this when working with alternate realms.  

Newly created directory servers are not provided a "full dump", but for performance reasons and minimal disruption to the other servers in the network, they are brought online slowly. It may take up to a month for a new secondary directory server to provide a full view of the network. Please do not add any secondary servers to the hard-coded list of fallback directory servers until it has been operating as a directory for at least a month.

All channels are configured to "ping" their directory server once a month, at somewhat random times during the month. This gives the ability for the directory to discover dead channels and sites (they stop pinging). Subsequently they are marked dead or unreachable and over time will be removed from the directory results.

Channels may be configured to be "hidden" from the directory. These channels may still exist in the directory but will be un-searchable and some "sensitive" personal information will not be stored at all.

       