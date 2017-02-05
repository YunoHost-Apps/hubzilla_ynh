### Who is a Hubzilla developer? Should I read this?

Anyone who contributes to making Hubzilla better is a developer. There are many different and important ways you can contribute to this amazing technology, _even if you do not know how to write code_. The software itself is only a part of the Hubzilla project. You can contribute by 

* translating text to your language so that people around the world have the opportunity to use Hubzilla
* promoting Hubzilla and spreading awareness of the platform through blog posts, articles, and word-of-mouth
* creating artwork and graphics for project assets such as icons and marketing material
* supporting project infrastructure like the project website and demo servers

_Software_ developers are of course welcomed; there are so many great ideas to implement and not enough people to make them all a reality! The Hubzilla code base is an advanced and mature system, but the platform is still very flexible and responsive to new ideas. 

This document will help you get started learning and contributing to Hubzilla.

### Versioning system

The versioning system is similar to the popular semantic versioning but less stringent. Given x.y.z,  x changes yearly. y changes for "stable" monthly builds, and z increments when there are interface changes. We maintain our date and build numbers for medium grain version control (commits within a certain date range) and of course git revs for fine grained control.

### Git repository branches

There are two official branches of the Hubzilla git repo. 

* The stable version is maintained on the **master** branch. The latest commit in this branch is considered to be suitable for production hubs. 
* Experimental development occurs on the **dev** branch, which is merged into **master** when it is deemed tested and stable enough.

### Developer tools and workflows

#### Hub Snapshots

The [hub snapshots](/help/admin/hub_snapshots) page provides instructions and scripts for taking complete 
snapshots of a hub to support switching between consistent and completely known 
states. This is useful to prevent situations where the content or database schema 
might be incompatible with the code. 

### Translations

Our translations are managed through Transifex. If you wish to help out translating Hubzilla to another language, sign up on transifex.com, visit [https://www.transifex.com/projects/p/red-matrix/](https://www.transifex.com/projects/p/red-matrix/) and request to join one of the existing language teams or create a new one. Notify one of the core developers when you have a translation update which requires merging, or ask about merging it yourself if you're comfortable with git and PHP. We have a string file called 'messages.po' which is gettext compliant and a handful of email templates, and from there we automatically generate the application's language files.   

#### Translation Process

The strings used in the UI of Hubzilla is translated at [Transifex][1] and then
included in the git repository at github. If you want to help with translation
for any language, be it correcting terms or translating Hubzilla to a
currently not supported language, please register an account at transifex.com
and contact the Redmatrix translation team there.

Translating Hubzilla is simple. Just use the online tool at transifex. If you
don't want to deal with git & co. that is fine, we check the status of the
translations regularly and import them into the source tree at github so that
others can use them.

We do not include every translation from transifex in the source tree to avoid
a scattered and disturbed overall experience. As an uneducated guess we have a
lower limit of 50% translated strings before we include the language. This
limit is judging only by the amount of translated strings under the assumption
that the most prominent strings for the UI will be translated first by a
translation team. If you feel your translation useable before this limit,
please contact us and we will probably include your teams work in the source
tree.

If you want to get your work into the source tree yourself, feel free to do so
and contact us with and question that arises. The process is simple and
Hubzilla ships with all the tools necessary.

The location of the translated files in the source tree is
    /view/LNG-CODE/
where LNG-CODE is the language code used, e.g. de for German or fr for French.
For the email templates (the *.tpl files) just place them into the directory
and you are done. The translated strings come as a "hmessages.po" file from
transifex which needs to be translated into the PHP file Hubzilla uses.  To do
so, place the file in the directory mentioned above and use the "po2php"
utility from the util directory of your Hubzilla installation.

Assuming you want to convert the German localization which is placed in
view/de/hmessages.po you would do the following.

1. Navigate at the command prompt to the base directory of your
   Hubzilla installation

2. Execute the po2php script, which will place the translation
   in the hstrings.php file that is used by Hubzilla.

       $> php util/po2php.php view/de/hmessages.po

   The output of the script will be placed at view/de/hstrings.php where
   froemdoca os expecting it, so you can test your translation mmediately.
                                  
3. Visit your Hubzilla page to check if it still works in the language you
   just translated. If not try to find the error, most likely PHP will give
   you a hint in the log/warnings.about the error.
                                        
   For debugging you can also try to "run" the file with PHP. This should
   not give any output if the file is ok but might give a hint for
   searching the bug in the file.

       $> php view/de/hstrings.php

4. commit the two files with a meaningful commit message to your git
   repository, push it to your fork of the Hubzilla repository at github and
   issue a pull request for that commit.

#### Utilities

Additional to the po2php script there are some more utilities for translation
in the "util" directory of the Hubzilla source tree.  If you only want to
translate Hubzilla into another language you wont need any of these tools most
likely but it gives you an idea how the translation process of Hubzilla
works.

For further information see the utils/README file.

#### Known Problems

* Hubzilla uses the language setting of the visitors browser to determain the
  language for the UI. Most of the time this works, but there are some known
  quirks.
* the early translations are based on the friendica translations, if you 
  some rough translations please let us know or fix them at Transifex.

### To-be-organized information

**Here is how you can join us.**

First, get yourself a working git package on the system where you will be
doing development.

Create your own github account.

You may fork/clone the Red repository from [https://github.com/redmatrix/hubzilla.git](https://github.com/redmatrix/hubzilla.git).

Follow the instructions provided here: [http://help.github.com/fork-a-repo/](http://help.github.com/fork-a-repo/)
to create and use your own tracking fork on github

Then go to your github page and create a "Pull request" when you are ready
to notify us to merge your work.


**Important**

Please pull in any changes from the project repository and merge them with your work **before** issuing a pull request. We reserve the right to reject any patch which results in a large number of merge conflicts. This is especially true in the case of language translations - where we may not be able to understand the subtle differences between conflicting versions.

Also - **test your changes**. Don't assume that a simple fix won't break something else. If possible get an experienced Red developer to review the code. 


**Licensing**

All code contributed to the project falls under the MIT license, unless otherwise specified. We will accept third-party code which falls under MIT, BSD and LGPL, but copyleft licensing (GPL, and AGPL) is only permitted in addons. It must be possible to completely remove the GPL (copyleft) code from the main project without breaking anything.

**Coding Style** 

In the interests of consistency we adopt the following code styling. We may accept patches using other styles, but where possible please try to provide a consistent code style. We aren't going to argue or debate the merits of this style, and it is irrelevant what project 'xyz' uses. This is not project 'xyz'. This is a baseline to try and keep the code readable now and in the future. 

* All comments should be in English.

* We use doxygen to generate documentation. This hasn't been consistently applied, but learning it and using it are highly encouraged.

* Indentation is accomplished primarily with tabs using a tab-width of 4.

* String concatenation and operators should be separated by whitespace. e.g. "$foo = $bar . 'abc';" instead of "$foo=$bar.'abc';"

* Generally speaking, we use single quotes for string variables and double quotes for SQL statements. "Here documents" should be avoided. Sometimes using double quoted strings with variable replacement is the most efficient means of creating the string. In most cases, you should be using single quotes.

* Use whitespace liberally to enhance readability. When creating arrays with many elements, we will often set one key/value pair per line, indented from the parent line appropriately. Lining up the assignment operators takes a bit more work, but also increases readability.

* Generally speaking, opening braces go on the same line as the thing which opens the brace. They are the last character on the line. Closing braces are on a line by themselves. 


**File system layout:**

[addon] optional addons/plugins

[boot.php] Every process uses this to bootstrap the application structure

[doc] Help Files

[images] core required images

[include] The "model" in MVC - (back-end functions), also contains PHP "executables" for background processing

[index.php] The front-end controller for web access

[install] Installation and upgrade files and DB schema

[library] Third party modules (must be license compatible)

[mod] Controller modules based on URL pathname (e.g. #^[url=http://sitename/foo]http://sitename/foo[/url] loads mod/foo.php)

[mod/site/] site-specific mod overrides, excluded from git

[util] translation tools, main English string database and other miscellaneous utilities

[version.inc] contains current version (auto-updated via cron for the master repository and distributed via git)

[view] theming and language files

[view/(css,js,img,php,tpl)] default theme files

[view/(en,it,es ...)] language strings and resources

[view/theme/] individual named themes containing (css,js,img,php,tpl) over-rides

**The Database:**


| Table                   | Description                                            |
|-------------------------|--------------------------------------------------------|
| abconfig                | contact table, replaces Friendica 'contact'            |
| abook                   |                                                        |
| account                 | service provider account                               |
| addon                   |                                                        |
| addressbookchanges      |                                                        |
| addressbooks            |                                                        |
| app                     |                                                        |
| atoken                  |                                                        |
| attach                  |                                                        |
| auth_codes              |                                                        |
| cache                   |                                                        |
| cal                     |                                                        |
| calendarchanges         |                                                        |
| calendarinstances       |                                                        |
| calendarobjects         |                                                        |
| calendars               |                                                        |
| calendarsubscriptions   |                                                        |
| cards                   |                                                        |
| channel                 |                                                        |
| chat                    |                                                        |
| chatpresence            |                                                        |
| chatroom                |                                                        |
| clients                 |                                                        |
| config                  |                                                        |
| conv                    |                                                        |
| dreport                 |                                                        |
| event                   |                                                        |
| group_member            |                                                        |
| groupmembers            |                                                        |
| groups                  |                                                        |
| hook                    |                                                        |
| hubloc                  |                                                        |
| iconfig                 |                                                        |
| issue                   |                                                        |
| item                    |                                                        |
| item_id                 |                                                        |
| likes                   |                                                        |
| locks                   |                                                        |
| mail                    |                                                        |
| menu                    |                                                        |
| menu_item               |                                                        |
| notify                  |                                                        |
| obj                     |                                                        |
| outq                    |                                                        |
| pconfig                 | personal (per channel) configuration storage           |
| photo                   |                                                        |
| poll                    |                                                        |
| poll_elm                |                                                        |
| principals              |                                                        |
| profdef                 |                                                        |
| profext                 |                                                        |
| profile                 |                                                        |
| profile_check           |                                                        |
| propertystorage         |                                                        |
| register                |                                                        |
| schedulingobjects       |                                                        |
| session                 |                                                        |
| shares                  |                                                        |
| sign                    |                                                        |
| site                    |                                                        |
| source                  |                                                        |
| sys_perms               |                                                        |
| term                    |                                                        |
| tokens                  |                                                        |
| updates                 |                                                        |
| users                   |                                                        |
| verify                  |                                                        |
| vote                    |                                                        |
| xchan                   |                                                        |
| xchat                   |                                                        |
| xconfig                 |                                                        |
| xign                    |                                                        |
| xlink                   |                                                        |
| xperm                   |                                                        |
| xprof                   |                                                        |
| xtag                    |                                                        |


    * abook - contact table, replaces Friendica 'contact'
    * account - service provider account
    * addon - registered plugins
    * app - peronal app data
    * attach - file attachments
    * auth_codes - OAuth usage
    * cache - OEmbed cache
    * channel - replaces Friendica 'user'
    * chat - chat room content
    * chatpresence - channel presence information for chat
    * chatroom - data for the actual chat room
    * clients - OAuth usage
    * config - main configuration storage
    * conv - Diaspora private messages
    * event - Events
    * fcontact - friend suggestion stuff
    * ffinder - friend suggestion stuff
    * fserver - obsolete
    * fsuggest - friend suggestion stuff
    * groups - privacy groups
    * group_member - privacy groups
    * hook - plugin hook registry
    * hubloc - Red location storage, ties a location to an xchan
    * item - posts
    * item_id - other identifiers on other services for posts
    * likes - likes of 'things'
    * mail - private messages
    * menu - channel menu data
    * menu_item - items uses by channel menus
    * notify - notifications
    * notify-threads - need to factor this out and use item thread info on notifications
    * obj - object data for things (x has y)
    * outq - output queue
    * pconfig - personal (per channel) configuration storage
    * photo - photo storage
    * poll - data for polls
    * poll_elm - data for poll elements
    * profdef - custom profile field definitions
    * profext - custom profile field data
    * profile - channel profiles
    * profile_check - DFRN remote auth use, may be obsolete
    * register - registrations requiring admin approval
    * session - web session storage
    * shares - shared item information
    * sign - Diaspora signatures.  To be phased out.
    * site - site table to find directory peers
    * source - channel sources data
    * spam - unfinished
    * sys_perms - extensible permissions for the sys channel
    * term - item taxonomy (categories, tags, etc.) table
    * tokens - OAuth usage
    * updates - directory sync updates
    * verify - general purpose verification structure
    * vote - vote data for polls
    * xchan - replaces 'gcontact', list of known channels in the universe
    * xchat - bookmarked chat rooms
    * xconfig - as pconfig but for channels with no local account
    * xlink - "friends of friends" linkages derived from poco
    * xprof - if this hub is a directory server, contains basic public profile info of everybody in the network
    * xtag - if this hub is a directory server, contains tags or interests of everybody in the network

    
**How to theme Hubzilla**

This is a short documentation on what I found while trying to modify Hubzilla's appearance.

First, you'll need to create a new theme. This is in /view/theme, and I chose to copy 'redbasic' since it's the only available for now. Let's assume I named it .

Oh, and don't forget to rename the _init function in /php/theme.php to be _init() instead of redbasic_init().

At that point, if you need to add javascript or css files, add them to /js or /css, and then "register" them in _init() through head_add_js('file.js') and head_add_css('file.css').

Now you'll probably want to alter a template. These can be found in in /view/tpl OR view//tpl. All you should have to do is copy whatever you want to tweak from the first place to your theme's own tpl directory.


We're pretty relaxed when it comes to developers. We don't have a lot of rules. Some of us are over-worked and if you want to help we're happy to let you help. That said, attention to a few guidelines will make the process smoother and make it easier to work together. We have developers from across the globe with different abilities and different cultural backgrounds and different levels of patience. Our primary rule is to respect others. Sometimes this is hard and sometimes we have very different opinions of how things should work, but if everybody makes an effort, we'll get along just fine.  

**Here is how you can join us.**

First, get yourself a working git package on the system where you will be
doing development.

Create your own github account.

You may fork/clone the Red repository from [url=https://github.com/redmatrix/hubzilla.git]https://github.com/redmatrix/hubzilla.git[/url]

Follow the instructions provided here: [url=http://help.github.com/fork-a-repo/]http://help.github.com/fork-a-repo/[/url]
to create and use your own tracking fork on github

Then go to your github page and create a "Pull request" when you are ready
to notify us to merge your work.

**Translations**

Our translations are managed through Transifex. If you wish to help out translating the $Projectname to another language, sign up on transifex.com, visit [url=https://www.transifex.com/projects/p/red-matrix/]https://www.transifex.com/projects/p/red-matrix/[/url] and request to join one of the existing language teams or create a new one. Notify one of the core developers when you have a translation update which requires merging, or ask about merging it yourself if you're comfortable with git and PHP. We have a string file called 'messages.po' which is gettext compliant and a handful of email templates, and from there we automatically generate the application's language files.   


**Important**

Please pull in any changes from the project repository and merge them with your work **before** issuing a pull request. We reserve the right to reject any patch which results in a large number of merge conflicts. This is especially true in the case of language translations - where we may not be able to understand the subtle differences between conflicting versions.

Also - **test your changes**. Don't assume that a simple fix won't break something else. If possible get an experienced Red developer to review the code. 

Further documentation can be found at the Github wiki pages at: [url=https://github.com/friendica/red/wiki]https://github.com/friendica/red/wiki[/url]

**Licensing**

All code contributed to the project falls under the MIT license, unless otherwise specified. We will accept third-party code which falls under MIT, BSD and LGPL, but copyleft licensing (GPL, and AGPL) is only permitted in addons. It must be possible to completely remove the GPL (copyleft) code from the main project without breaking anything.

**Concensus Building**

Code changes which fix an obvious bug are pretty straight-forward. For instance if you click "Save" and the thing you're trying to save isn't saved, it's fairly obvious what the intended behaviour should be. Often when developing feature requests, it may affect large numbers of community members and it's possible that other members of the community won't agree with the need for the feature, or with your proposed implementation. They may not see something as a bug or a desirable feature.

We encourage consensus building within the community when it comes to any feature which might be considered controversial or where there isn't unanimous decision that the proposed feature is the correct way to accomplish the task. The first place to pitch your ideas is to [url=https://zothub.com/channel/one]Channel One[/url]. Others may have some input or be able to point out facets of your concept which might be problematic in our environment. But also, you may encounter opposition to your plan. This doesn't mean you should stop and/or ignore the feature. Listen to the concerns of others and try and work through any implementation issues. 

There are places where opposition cannot be resolved. In these cases, please consider making your feature **optional** or non-default behaviour that must be specifically enabled. This technique can often be used when a feature has significant but less than unanimous support. Those who desire the feature can turn it on and those who don't want it - will leave it turned off.

If a feature uses other networks or websites and or is only seen as desirable by a small minority of the community, consider making the functionality available via an addon or plugin. Once again, those who don't desire the feature won't need to install it. Plugins are relatively easy to create and "hooks" can be easily added or modified if the current hooks do not do what is needed to allow your plugin to work.
     

**Coding Style**

In the interests of consistency we adopt the following code styling. We may accept patches using other styles, but where possible please try to provide a consistent code style. We aren't going to argue or debate the merits of this style, and it is irrelevant what project 'xyz' uses. This is not project 'xyz'. This is a baseline to try and keep the code readable now and in the future. 

*  All comments should be in English.

*  We use doxygen to generate documentation. This hasn't been consistently applied, but learning it and using it are highly encouraged.

*  Indentation is accomplished primarily with tabs using a tab-width of 4.

*  String concatenation and operators should be separated by whitespace. e.g. "$foo = $bar . 'abc';" instead of "$foo=$bar.'abc';"

*  Generally speaking, we use single quotes for string variables and double quotes for SQL statements. "Here documents" should be avoided. Sometimes using double quoted strings with variable replacement is the most efficient means of creating the string. In most cases, you should be using single quotes.

*  Use whitespace liberally to enhance readability. When creating arrays with many elements, we will often set one key/value pair per line, indented from the parent line appropriately. Lining up the assignment operators takes a bit more work, but also increases readability.

*  Generally speaking, opening braces go on the same line as the thing which opens the brace. They are the last character on the line. Closing braces are on a line by themselves. 

*  Some functions take arguments in argc/argv style like main() in C or function args in bash or Perl. Urls are broken up within a module. e.g, given "http://example.com/module/arg1/arg2", then $this->argc will be 3 (integer) and $this->argv will contain:   [0] => 'module', [1] => 'arg1',  [2] => 'arg2'. There will always be one argument. If provided a naked domain  URL, $this->argv[0] is set to "home".