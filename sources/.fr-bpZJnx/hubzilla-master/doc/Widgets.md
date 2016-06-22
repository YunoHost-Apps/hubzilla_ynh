Core Widgets
============

Some/many of these widgets have restrictions which may restrict the type of page where they may appear or may require login


* clock - displays the current time
    * args: military (1 or 0) - use 24 hour time as opposed to AM/PM
<br />&nbsp;<br />

* profile - displays a profile sidebar on pages which load profiles (pages with nickname in the URL)

* tagcloud - display a tagcloud of webpage items

    * args: count - number of items to return (default 24)
<br />&nbsp;<br />

* collections - privacy group selector for the current logged in channel

    * args: mode - one of "conversation", "group", "abook" depending on module
<br />&nbsp;<br />

* suggestions - friend suggestions for the current logged on channel

* follow - presents a text box for following another channel

* notes - private notes area for the current logged in channel if private_notes feature is enabled

* savedsearch - network/matrix search with save - must be logged in and savedsearch feature enabled

* filer - select filed items from network/matrix stream - must be logged in

* archive - date range selector for network and channel pages
    * args: 'wall' - 1 or 0, limit to wall posts or network/matrix posts (default)
<br />&nbsp;<br />

* fullprofile - same as profile currently

* categories - categories filter (channel page)

* tagcloud_wall - tagcloud for channel page only
    * args: 'limit' - number of tags to return (default 50)
<br />&nbsp;<br />

* catcloud_wall - tagcloud for channel page categories
    * args: 'limit' - number of categories to return (default 50)
<br />&nbsp;<br />

* affinity - affinity slider for network page - must be logged in

* settings_menu - sidebar menu for settings page, must be logged in

* mailmenu - sidebar menu for private message page - must be logged in

* design_tools - design tools menu for webpage building pages, must be logged in

* findpeople - tools to find other channels

* photo_albums - list photo albums of the current page owner with a selector menu

* vcard - mini profile sidebar for the person of interest (page owner, whatever)

* dirsafemode - directory selection tool - only on directory pages

* dirsort - directory selection tool - only on directory pages

* dirtags - directory tool - only on directory pages

* menu_preview - preview a menu - only on menu edit pages

* chatroom_list - list of chatrooms for the page owner

* bookmarkedchats - list of bookmarked chatrooms collected on this site for the current observer

* suggestedchats - "interesting" chatrooms chosen for the current observer

* item - displays a single webpage item by mid or page title
    * args:
	* channel_id - channel that owns the content, defualt is the profile_uid 
	* mid - message_id of webpage to display (must be webpage, not a conversation item)
	* title - URL page title of webpage (must provide one of either title or mid)
<br />&nbsp;<br />

* photo - display a single photo
    * args: 
    * url - URL of photo, must be http or https
    * zrl - use zid authenticated link
    * style - CSS style string
<br />&nbsp;<br />

* cover_photo - display the cover photo for the selected channel
    * args:
	* channel_id - channel to use, default is the profile_uid 
    * style - CSS style string (default is dynamically resized to width of region)
<br />&nbsp;<br />


* photo_rand - display a random photo from one of your photo albums. Photo permissions are honoured
    * args: 
    * album - album name (very strongly recommended if you have lots of photos)
    * scale - typically 0 (original size), 1 (1024px), 2, (640px), or 3 (320px)
    * style - CSS style string
	* channel_id - if not your own
<br />&nbsp;<br />

* random_block - display a random block element from your webpage design tools collection. Permissions are honoured.
    * args: 
    * contains - only return blocks which include the contains string in the block name
    * channel_id - if not your own
<br />&nbsp;<br />

* tasklist - provide a task or to-do list for the currently logged-in channel.
	* args:
	* all - display completed tasks if all is non-zero.
<br />&nbsp;<br />

* forums - provide a list of connected public forums with unseen counts for the current logged-in channel.
<br />&nbsp;<br />


* album - provides a widget containing a complete photo album from albums belonging to the page owner; this may be too large to present in a sidebar region as is best implemented as a content region widget. 
	* args:
	* album - album name
	* title - optional title, album name is used if not present
<br />&nbsp;<br />
 

Creating New Widgets
====================

If you want a widget named 'slugfish', create widget/slugfish.php containing


    <?php
    
    function widget_slugfish($args) {
    
    .. widget code goes here
    
    }


#include doc/macros/main_footer.bb;
