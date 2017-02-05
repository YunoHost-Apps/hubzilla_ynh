[h1]Advanced Configurations for Administrators[/h1]
$Projectname contains many configuration options hidden from the main admin panel.

These are generally options considered too niche, confusing, or advanced for the average member.  These settings can be activated from the the top level web directory with the syntax

[code]util/config cat key value[/code] 
for a site configuration, or 

[code]util/pconfig channel_id cat key value[/code] 
for a member configuration.

This document assumes you're an administrator.
[h2]pconfig[/h2][dl terms="mb"]
  [*= system.always_my_theme ] Always use your own theme when viewing channels on the same hub.  This will break in some quite imaginative ways when viewing channels with  theme dependent Comanche.
  [*= system.blocked ] An array of xchans blocked by this channel.  Technically, this is a hidden config and does belong here, however, addons (notably  superblock) have made this available in the UI.
  [*= system.default_cipher ] Set the default cipher used for E2EE items.
  [*= system.display_friend_count ] Set the number of connections to display in the connections profile  widget.
  [*= system.do_not_track ] As the browser header.  This will break many identity based features.   You should really just set permissions that make sense.
  [*= system.forcepublicuploads ] Force uploaded photos to be public when uploaded as wall items.  It makes far more sense to just set your permissions properly in the first place.  Do that instead.
  [*= system.network_page_default ] Set default params when viewing the network page.  This should contain the same querystring as manual filtering.
  [*= system.paranoia ] Sets the security level of IP checking. If the IP address of a logged-in session changes apply this level to determine if the account should be logged out as a security breach.     
Options are:
        0 &mdash; no IP checking             
        1 &mdash; check 3 octets             
        2 &mdash; check 2 octets             
        3 &mdash; check for any difference at all

  [*= system.prevent_tag_hijacking ] Prevent foreign networks hijacking hashtags in your posts and directing them at its own resources.
  [*= system.startpage ] Another of those technically hidden configs made available by addons. Sets the default page to view when logging in.  This is exposed to the UI by the startpage addon.
  [*= system.taganyone ] Requires the config of the same name to be enabled.  Allow the @mention tagging of anyone, whether you are connected or not.  This doesn't scale.
  [*= system.user_scalable ] Determine if the app is scalable on touch screens.  Defaults to on, to  disable, set to zero - real zero, not just false.
[/dl]
[h2]Site config[/h2][dl terms="mb"]
  [*= randprofile.check ] When requesting a random profile, check that it actually exists first
  [*= randprofile.retry ] Number of times to retry getting a random profile
  [*= system.admin_email ] Specifies the administrator's email for this site.  This is initially set during install.
  [*= system.authlog ] Logfile to use for logging auth errors.  Used to plug in to server side software such as fail2ban.  Auth failures are still logged to the main logs as well.
  [*= system.auto_channel_create ] Add the necessary form elements to create the first channel on the account registration page, and create it (possibly following email validation or administrator approval). This precludes the ability to import a channel from another site as the first channel created on this site for a new account.  Use with system.default_permissions_role to streamline registration. 
  [*= system.auto_follow ] Make the first channel of an account auto-follow channels listed here - comma separated list of webbies (member@hub addresses).
  [*= system.blacklisted_sites ] An array of specific hubs to block from this hub completely.
  [*= system.block_public_search ] Similar to block_public, except only blocks public access to  search features.  Useful for sites that want to be public, but keep getting hammered by search engines.
  [*= system.cron_hour ] Specify an hour in which to run cron_daily.  By default with no config, this will run at midnight UTC.
  [*= system.default_permissions_role ] If set to a valid permissions role name, use that role for the first channel created by a new account and don't ask for the "Channel Type" on the channel creation form. Examples of valid names are: 'social', 'social_restricted', 'social_private',  'forum', 'forum_restricted' and 'forum_private'.  Read more about permissions roles [zrl=[baseurl]/help/roles]here[/zrl].
  [*= system.default_profile_photo ] Set the profile photo that new channels start with. This should contain the name of a directory located under [font=courier]images/default_profile_photos/[/font], or be left unset. If not set then 'rainbow_man' is assumed.
  [*= system.directorytags ] Set the number of keyword tags displayed on the directory page. Default is 50 unless set to a  positive integer.
  [*= system.disable_directory_keywords ] If '1', do not show directory keywords. If the hub is a directory server, prevent returning tags to any directory clients. Please do not set this for directory servers in the RED_GLOBAL realm. 
  [*= system.disable_discover_tab ] This allows you to completely disable the ability to discover public content from external sites.
  [*= system.disable_dreport ] If '1', don't store or link to delivery reports
  [*= system.dlogfile ] Logfile to use for logging development errors.  Exactly the same as logger otherwise.  This isn't magic, and requires your own logging statements.  Developer tool.
  [*= system.email_notify_icon_url ] URL of image (32x32) to display in email notifications (HTML bodies).
  [*= system.expire_delivery_reports ] Expiration in days for delivery reports - default 10
  [*= system.expire_limit ] Don't expire any more than this number of posts per channel per expiration run to keep from exhausting memory. Default 5000.
  [*= system.hidden_version_siteinfo ] If true, do not report the software version on siteinfo pages (system.hide_version also hides the version on these pages, this setting *only* hides the version on siteinfo pages).
  [*= system.hide_help ] Don't display help documentation link in nav bar
  [*= system.hide_in_statistics ] Tell the red statistics servers to completely hide this hub in hub lists.
  [*= system.hide_version ] If true, do not report the software version on webpages and tools. (*) Must be set in .htconfig.php
  [*= system.ignore_imagick ] Ignore imagick and use GD, even if imagick is installed on the server. Prevents some issues with PNG files in older versions of imagick.
  [*= system.max_daily_registrations ] Set the maximum number of new registrations allowed on any day. Useful to prevent oversubscription after a bout of publicity for the project.
  [*= system.max_import_size ] If configured, the maximum length of an imported text message. This is normally left at 200Kbytes  or more to accomodate Friendica private photos, which are embedded.
  [*= system.max_tagged_forums ] Spam prevention. Limits the number of tagged forums which are recognised in any post. Default is 2. Only the first 'n' tags will be delivered as forums, the others will not cause any delivery. 
  [*= system.minimum_feedcheck_minutes ] The minimum interval between polling RSS feeds.  If this is lower than the cron interval, feeds will be polled with each cronjob. Defaults to 60 if not set. The site setting can also be over-ridden on a channel by channel basis by a service class setting aptly named 'minimum_feedcheck_minutes'.
  [*= system.no_age_restriction ] Do not restrict registration to people over the age of 13. This carries legal responsibilities in  many countries to require that age be provided and to block all personal information from minors,  so please check your local laws before changing.  
  [*= system.openssl_conf_file ] Specify a file containing OpenSSL configuration. Needed in some Windows installations to  locate the openssl configuration file on the system.  Read the code first. If you can't read the code, don't play with it.
  [*= system.openssl_encrypt ] Use openssl encryption engine, default is false (uses mcrypt for AES encryption)
  [*= system.optimize_items ] Runs optimise_table during some tasks to keep your database nice and  defragmented.  This comes at a performance cost while the operations are running, but also keeps things a bit faster while it's not.   There also exist CLI utilities for performing this operation, which you may prefer, especially if you're a large site.
  [*= system.override_poll_lockfile ] Ignore the lock file in the poller process to allow more than one process to run at a time.
  [*= system.paranoia ] As the pconfig, but on a site-wide basis.  Can be overwritten by member settings.
  [*= system.photo_cache_time ] How long to cache photos, in seconds. Default is 86400 (1 day). Longer time increases performance, but it also means it takes longer for changed permissions to apply.
  [*= system.platform_name ] What to report as the platform name in webpages and statistics. (*) Must be set in .htconfig.php
  [*= system.rating_enabled ] Distributed reputation reporting and data collection. This feature is currently being re-worked.
  [*= system.poke_basic ] Reduce the number of poke verbs to exactly 1 ("poke"). Disable other verbs. 
  [*= system.proc_run_use_exec ] If 1, use the exec system call in proc_run to run background tasks. By default we use proc_open and proc_close. On some (currently rare) systems this does not work well.
  [*= system.projecthome ] Display the project page on your home page for logged out viewers.
  [*= system.projecthome ] Set the project homepage as the homepage of your hub. (Obsolete)
  [*= system.register_link ] path to direct to from the "register" link on the login form. On closed sites this will direct to  'pubsites'. For open sites it will normally redirect to 'register' but you may change this to a  custom site page offering subscriptions or whatever. 
  [*= system.reserved_channels ] Don't allow members to register channels with this comma separated list of names (no spaces)
  [*= system.sellpage ] A URL shown in the public sites list to sell your hub - display service classes, etc.
  [*= system.startpage ] Set the default page to be taken to after a login for all channels at this website.  Can be overwritten by user settings.
  [*= system.sys_expire_days ] How many days to keep discovered public content from other sites
  [*= system.taganyone ] Allow the @mention tagging of anyone whether you are connected or not.
  [*= system.tempdir ] Place to store temporary files (currently unused), default is defined in the PHP configuration  
  [*= system.tos_url ] Set an alternative link for the ToS location.
  [*= system.transport_security_header ] if non-zero and SSL is being used, include a strict-transport-security header on webpages
  [*= system.uploaddir ] Location to upload files (default is system.tempdir, currently used only by js_upload plugin)
  [*= system.workflow_channel_next ] The page to direct new members to immediately after creating a channel.
  [*= system.workflow_register_next ] The page to direct members to immediately after creating an account (only when auto_channel_create or UNO is enabled).
[/dl]
[h2]Directory config[/h2]
[h3]Directory search defaults[/h3][dl terms="mb"]
  [*= directory.globaldir ] 0 or 1. Default 0.  If you visit the directory on a site you'll just see the members of that site by default. You have to go through an extra step to see the people in the rest of the network; and by doing so there's a clear delineation that these people *aren't* members of that site but of a larger network.
  [*= directory.pubforums ] 0 or 1. Public forums [i]should[/i] be default 0.
  [*= directory.safemode ] 0 or 1.  
[/dl]
[h3]Directory server configuration[/h3][i](see [zrl=[baseurl]/help/directories]help/directories[/zrl])[/i]

[dl terms="mb"]
  [*= system.directory_mode ]
  [*= system.directory_primary ]
  [*= system.directory_realm ]
  [*= system.directory_server ]
  [*= system.realm_token ]
[/dl]

#include doc/macros/main_footer.bb;

