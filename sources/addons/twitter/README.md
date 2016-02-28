Twitter Plugin
==============

Original main authors Tobias Diekershoff and Michael Vogel. Adapted for the Hubzilla by Mike Macgirvin.

With this addon for the Hubzilla you can give your hub members the possibility to post
their *public* messages to Twitter. The messages will be strapped their rich
context and shortened to 140 characters length if necessary. If shortening of
the message was performed a link will be added to the tweet pointing to the
original message on your hub.

There is a similar addon for forwarding public messages to
[GNU social (formerly StatusNet)](http://gnu.io/social/).

Requirements
------------

To use this plugin you have to register an application for your Hubzilla
hub on Twitter with

* read and write access
* don't set a callback URL
* we do not intend to use Twitter for login

The registration can be done at [twitter.com/apps](https://apps.twitter.com/) and you need a Twitter
account for doing so.

After you registered the application you get an OAuth API key / secret
pair that identifies your app, you will need them for configuration.

The inclusion of a shorturl for the original posting in cases when the
message was longer than 140 characters requires it, that you have *PHP5+* and
*curl* on your server.

Where to find
-------------

In the [Hubzilla-addons git repository /twitter/](https://github.com/redmatrix/hubzilla-addons/tree/master/twitter). This directory 
contains all required PHP files (including the [Twitter OAuth library][1] by Abraham
Williams, MIT licensed and the [Slinky library][2] by Beau Lebens, BSD license),
a CSS file for styling of the user configuration and an image to _Sign in with
Twitter_.

[1]: https://github.com/abraham/twitteroauth
[2]: http://dentedreality.com.au/projects/slinky/

Configuration
=============

Global Configuration
--------------------

If you enabled an administrator account, please use the admin panel to configure
the Twitter relay. If you for any reason prefer to use the command line instead 
of the admin panels, please refer to the Alternative Configuration below. 

Activate the plugin from the plugins section of your admin panel.  When you have
done so, add your API key and API secret in the settings section of the 
plugin page.

When this is done your user can now configure their Twitter connection at
"Settings -> Connector Settings" and enable the forwarding of their *public*
messages to Twitter.

Alternative Configuration
-------------------------

* Go to the root of your Hubzilla installation and type after the prompt:

     util/config system addon

* Press enter. You get a list of active addons. To activate this addon you have to add Twitter to this list and press enter:

     util/config system addon "plugin 1, plugin 2, etc, twitter"

* Afterwards you need to add your OAuth API key / secret pair the same way (without the single quotes):

     util/config twitter consumerkey 'your API KEY here' 

     util/config twitter consumersecret 'your API SECRET here'


Connector Options for the Channel
=================================

When the OAuth API information is correctly entered into the admin configuration and a channel owner visits the "Connector Settings" page they can now connect to Twitter. To do so one has to follow the _Sign in with Twitter_
button (the page will be opened in a new browser window/tab) and get a PIN from Twitter. This PIN has to be entered on the settings page. After submitting the PIN the plugin will get OAuth credentials and this channel has the privilege to cross-post their public posts to Twitter.

After this step was successful the user now has the following config options.

* **Allow posting to Twitter** If a channel owner wants to post their _public postings_ 
   to the associated Twitter account, they need to check this box.
* **Send public postings to Twitter by default** If a channel owner wants to have _all_
  their public postings being send to the associated Twitter account they need to check
  this button as well. Otherwise they have to enable the relay of their postings
  in the ACL dialog (click the lock button) before posting an entry.
* **Clear OAuth configuration** If a channel owner wants to remove the currently associated
  Twitter account from their Hubzilla channel they have to check this box and
  then hit the submit button. The saved settings will be deleted and they have
  to reconfigure the Twitter connector to be able to relay their public
  postings to a Twitter account.

License
=======

The _Twitter Connector_ is licensed under the [3-clause BSD license][3] see 
the
LICENSE file in the addons directory.

[3]: http://opensource.org/licenses/BSD-3-Clause


