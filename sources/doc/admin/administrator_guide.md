
### Overview

$Projectname is more than a simple web application. It is a
complex communications system which more closely resembles an email server
than a web server. For reliability and performance, messages are delivered in
the background and are queued for later delivery when sites are down. This
kind of functionality requires a bit more of the host system than the typical
blog. Not every PHP/MySQL hosting provider will be able to support
$Projectname. Many will but please review the requirements and confirm these
with your hosting provider prior to installation.

We've tried very hard to ensure that $Projectname will run on commodity
hosting platforms such as those used to host Wordpress blogs and Drupal
websites. It will run on most any Linux VPS system. Windows LAMP platforms
such as XAMPP and WAMP are not officially supported at this time however
we welcome patches if you manage to get it working.

### Where to find more help
If you encounter problems or have issues not addressed in this documentation, 
please let us know via the [Github issue
tracker](https://github.com/redmatrix/hubzilla/issues). Please be as clear as you
can about your operating environment and provide as much detail as possible
about any error messages you may see, so that we can prevent it from happening
in the future. Due to the large variety of operating systems and PHP platforms
in existence we may have only limited ability to debug your PHP installation or
acquire any missing modules * but we will do our best to solve any general code
issues.

### Before you begin 

#### Choose a domain name or subdomain name for your server

$Projectname can only be installed into the root of a domain or sub-domain, and can 
not be installed using alternate TCP ports.

#### Decide if you will use SSL and obtain an SSL certificate before software installation

You SHOULD use SSL. If you use SSL, you MUST use a "browser-valid" certificate.  
*You MUST NOT use self-signed certificates!*

Please test your certificate prior to installation. A web tool for testing your 
certificate is available at "http://www.digicert.com/help/". When visiting your 
site for the first time, please use the SSL ("https://") URL if SSL is available. 
This will avoid problems later. The installation routine will not allow you to 
use a non browser-valid certificate.


This restriction is incorporated because public posts from you may contain 
references to images on your own hub. Other members viewing their stream on
other hubs will get warnings if your certificate is not trusted by their web
browser. This will confuse many people because this is a decentralised network
and they will get the warning about your hub while viewing their own hub and may 
think their own hub has an issue. These warnings are very technical and scary to 
some folks, many of whom will not know how to proceed except to follow the browser
advice. This is disruptive to the community. That said, we recognise the issues
surrounding the current certificate infrastructure and agree there are many
problems, but that doesn't change the requirement. 

Free "browser-valid" certificates are available from providers such as StartSSL
and LetsEncrypt. 

If you do NOT use SSL, there may be a delay of up to a minute for the initial
install script - while we check the SSL port to see if anything responds there.
When communicating with new sites, $Projectname always attempts connection on the
SSL port first, before falling back to a less secure connection.  If you do not
use SSL, your webserver MUST NOT listen on port 443 at all.

If you use LetsEncrypt to provide certificates and create a file under 
.well-known/acme-challenge so that LetsEncrypt can verify your domain ownership, 
please remove or rename the .well-known directory as soon as the certificate is 
generated. $Projectname will provide its own handler for ".well-known" services when
it is installed, and an existing directory in this location may prevent some of 
these services from working correctly. This should not be a problem with Apache,
but may be an issue with nginx or other web server platforms.

### Deployment
There are several ways to deploy a new hub.

* Manual installation on an existing server
* Automated installation on an existing server using a shell script
* Automated deployment using an OpenShift virtual private server (VPS)

### Requirements
* Apache with mod-rewrite enabled and "AllowOverride All" so you can use a 
  local .htaccess file. Some folks have successfully used nginx and lighttpd.
  Example config scripts are available for these platforms in doc/install.
  Apache and nginx have the most support. 

* PHP 5.5 or later. 
 * Note that on some shared hosting environments, the _command line_ version of 
PHP might differ from the _webserver_ version

* PHP *command line* access with register_argc_argv set to true in the 
  php.ini file * and with no hosting provider restrictions on the use of 
  exec() and proc_open().

* curl, gd (with at least jpeg and png support), mysqli, mbstring, mcrypt, 
  and openssl extensions. The imagick extension is not required but desirable.

* xml extension is required if you want webdav to work.

* some form of email server or email gateway such that PHP mail() works.

* Mysql 5.x or MariaDB or postgres database server.

* ability to schedule jobs with cron.

* Installation into a top-level domain or sub-domain (without a 
  directory/path component in the URL) is REQUIRED.

### Manual Installation

#### Unpack the $Projectname files into the root of your web server document area
If you copy the directory tree to your webserver, make sure that you include the
hidden files like .htaccess.

If you are able to do so, we recommend using git to clone the source 
repository rather than to use a packaged tar or zip file.  This makes the 
software much easier to update. The Linux command to clone the repository 
into a directory "mywebsite" would be:

    git clone https://github.com/redmatrix/hubzilla.git mywebsite

and then you can pick up the latest changes at any time with:

    git pull

make sure folders ``store/[data]/smarty3`` and ``store`` exist and are 
writable by the webserver:

    mkdir -p "store/[data]/smarty3"
    chmod -R 777 store

    This permission (777) is very dangerous and if you have sufficient
    privilege and knowledge you should make these directories writeable
    only by the webserver and, if different, the user that will run the
    cron job (see below). In many shared hosting environments this may be
    difficult without opening a trouble ticket with your provider. The
    above permissions will allow the software to work, but are not
    optimal.

The following directories also need to be writable by the webserver in order for certain
web-based administrative tools to function:

* `addon`
* `extend`
* `view/theme`
* `widget`

#### Official addons
##### Installation
Navigate to your webThen you should clone the addon repository (separately). We'll give this repository a nickname of 'hzaddons'. You can pull in other hubzilla addon repositories by giving them different nicknames::

    cd mywebsite
    util/add_addon_repo https://github.com/redmatrix/hubzilla-addons.git hzaddons

##### Updating
For keeping the addon tree updated, you should be on your top level website directory and issue an update command for that repository::

    cd mywebsite
    util/update_addon_repo hzaddons

Create searchable representations of the online documentation. You may do this 
    any time that the documentation is updated :  
    
    cd mywebsite
    util/importdoc

### Automated installation via the .homeinstall shell script
There is a shell script in (``.homeinstall/hubzilla-setup.sh``) that will install $Projectname and its dependencies on a fresh installation of Debian 8.3 stable (Jessie). It should work on similar Linux systems but your results may vary.

#### Requirements
The installation script was originally designed for a small hardware server behind your home router. However, it has been tested on several systems running Debian 8.3:

* Home-PC (Debian-8.3.0-amd64)

  * Internet connection and router at home
  * Mini-pc connected to your router
  * USB drive for backups
  * Fresh installation of Debian on your mini-pc
  * Router with open ports 80 and 443 for your Debian

* DigitalOcean droplet (Debian 8.3 x64 / 512 MB Memory / 20 GB Disk / NYC3)

#### Overview of installation steps
1. `apt-get install git`
1. `mkdir -p /var/www/html`
1. `cd /var/www/html`
1. `git clone https://github.com/redmatrix/hubzilla.git .`
1. `nano .homeinstall/hubzilla-config.txt`
1. `cd .homeinstall/`
1. `./hubzilla-setup.sh`
1. `sed -i "s/^upload_max_filesize =.*/upload_max_filesize = 100M/g" /etc/php5/apache2/php.ini`
1. `sed -i "s/^post_max_size =.*/post_max_size = 100M/g" /etc/php5/apache2/php.ini`
1. `service apache2 reload`
1. Open your domain with a browser and step throught the initial configuration of $Projectname.

### Service Classes

Service classes allow you to set limits on system resources by limiting what individual
accounts can do, including file storage and top-level post limits. Define custom service 
classes according to your needs in the `.htconfig.php` file. For example, create 
a _standard_ and _premium_ class using the following lines:

    // Service classes
    
    App::$config['system']['default_service_class']='standard'; // this is the default service class that is attached to every new account
    
    // configuration for parent service class
    App::$config['service_class']['standard'] =
    array('photo_upload_limit'=>2097152, // total photo storage limit per channel (here 2MB)
    'total_identities' =>1, // number of channels an account can create
    'total_items' =>0, // number of top level posts a channel can create. Applies only to top level posts of the channel user, other posts and comments are unaffected
    'total_pages' =>100, // number of pages a channel can create
    'total_channels' =>100, // number of channels the user can add, other users can still add this channel, even if the limit is reached
    'attach_upload_limit' =>2097152, // total attachment storage limit per channel (here 2MB)
    'chatters_inroom' =>20);
    
    // configuration for teacher service class
    App::$config['service_class']['premium'] =
    array('photo_upload_limit'=>20000000000, // total photo storage limit per channel (here 20GB)
    'total_identities' =>20, // number of channels an account can create
    'total_items' =>20000, // number of top level posts a channel can create. Applies only to top level posts of the channel user, other posts and comments are unaffected
    'total_pages' =>400, // number of pages a channel can create
    'total_channels' =>2000, // number of channels the user can add, other users can still add this channel, even if the limit is reached
    'attach_upload_limit' =>20000000000, // total attachment storage limit per channel (here 20GB)
    'chatters_inroom' =>100);

To apply a service class to an existing account, use the command line utility from the 
web root:

`util/service_class`
list service classes

`util/config system default_service_class firstclass`
set the default service class to 'firstclass'

`util/service_class firstclass`
list the services that are part of 'firstclass' service class

`util/service_class firstclass photo_upload_limit 10000000`
set firstclass total photo disk usage to 10 million bytes

`util/service_class --account=5 firstclass`
set account id 5 to service class 'firstclass' (with confirmation)

`util/service_class --channel=blogchan firstclass`
set the account that owns channel 'blogchan' to service class 'firstclass' (with confirmation)

**Service class limit options**

* photo_upload_limit - maximum total bytes for photos
* total_items - maximum total toplevel posts
* total_pages - maximum comanche pages
* total_identities - maximum number of channels owned by account
* total_channels - maximum number of connections
* total_feeds - maximum number of rss feed connections
* attach_upload_limit - maximum file upload storage (bytes)
* minimum_feedcheck_minutes - lowest setting allowed for polling rss feeds
* chatrooms - maximum chatrooms
* chatters_inroom - maximum chatters per room
* access_tokens - maximum number of Guest Access Tokens per channel

### Theme management
#### Repo management example 
1. Navigate to your hub web root

  ```
  root@hub:/root# cd /var/www 
  ```
2. Add the theme repo and give it a name

  ```
  root@hub:/var/www# util/add_theme_repo https://github.com/DeadSuperHero/redmatrix-themes.git DeadSuperHero
  ```
3. Update the repo by using

  ```
  root@hub:/var/www#  util/update_theme_repo DeadSuperHero
  ```

### Channel Directory

#### Keywords

There is a "tag cloud" of keywords that can appear on the channel directory page. 
If you wish to hide these keywords, which are drawn from the directory server, you 
can use the *config* tool:

    util/config system disable_directory_keywords 1
    
If your hub is in the standalone mode because you do not wish to connect to the 
global grid, you may instead ensure the the _directory_server_ system option is 
empty:

    util/config system directory_server ""

### Upgrading from RedMatrix to $Projectname

#### How to migrate an individual channel from RedMatrix to $Projectname

1. Clone the channel by opening an account on a $Projectname hub and performing a basic import (not content) from the original RedMatrix hub. Give your new clone time to sync connections and settings.
1. Export individual channel content from your RedMatrix hub to a set of JSON text files using the red.hub/uexport tool. Do this in monthly increments if necessary.
1. Import the JSON data files sequentially in chronological order into the $Projectname clone using the new.hub/import_items tool.
1. Inform your Friendica and Diaspora contacts that your channel moves. They need to reconnect to your new address.  
1. After successful import (check!) delete your channel on the old RedMatrix Server.
1. On the $Projectname server visit new.hub/locs and upgrade to your channel to a primary one. And when the old Redmatrix server is still listed delete them here as well. Press "Sync" to inform all other server in the grid.

### Troubleshooting

#### Log files

The system logfile is an extremely useful resource for tracking down things that go wrong. This can be enabled in the admin/log configuration page. A loglevel setting of LOGGER_DEBUG is preferred for stable production sites. Most things that go wrong with communications or storage are listed here. A setting of LOGGER_DATA provides [b]much[/b] more detail, but may fill your disk. In either case we recommend the use of logrotate on your operating system to cycle logs and discard older entries. 

At the bottom of your .htconfig.php file are several lines (commented out) which enable PHP error logging. This reports issues with code syntax and executing the code and is the first place you should look for issues which result in a "white screen" or blank page. This is typically the result of code/syntax problems. 
Database errors are reported to the system logfile, but we've found it useful to have a file in your top-level directory called dbfail.out which [b]only[/b] collects database related issues. If the file exists and is writable, database errors will be logged to it as well as to the system logfile.

In the case of "500" errors, the issues may often be logged in your webserver logs, often /var/log/apache2/error.log or something similar. Consult your operating system documentation. 

There are three different log facilities.

**The first is the database failure log**. This is only used if you create a file called specifically 'dbfail.out' in the root folder of your website and make it write-able by the web server. If we have any database failed queries, they are all reported here. They generally indicate typos in our queries, but also occur if the database server disconnects or tables get corrupted. On rare occasions we'll see race conditions in here where two processes tried to create an xchan or cache entry with the same ID. Any other errors (especially persistent errors) should be investigated.

**The second is the PHP error log**. This is created by the language processor and only reports issues in the language environment. Again these can be syntax errors or programming errors, but these generally are fatal and result in a "white screen of death"; e.g. PHP terminates. You should probably look at this file if something goes wrong that doesn't result in a white screen of death, but it isn't uncommon for this file to be empty for days on end.

There are some lines at the bottom of the supplied .htconfig.php file; which if uncommented will enable a PHP error log (*extremely* useful for finding the source of white screen failures). This isn't done by default due to potential issues with logfile ownership and write permissions and the fact that there is no logfile rotation by default.                                                                                                          
                                                                                                                  

**The third is the "application log"**. This is used by $Projectname to report what is going on in the program and usually reports any difficulties or unexpected data we received. It also occasionally reports "heartbeat" status messages to indicate that we reached a certain point in a script. **This** is the most important log file to us, as we create it ourself for the sole purpose of reporting the status of background tasks and anything that seems weird or out of place. It may not be fatal, but maybe just unexpected. If you're performing a task and there's a problem, let us know what is in this file when the problem occurred. (Please don't send me 100M dumps you'll only piss me off). Just a few relevant lines so I can rule out a few hundred thousand lines of code and concentrate on where the problem starts showing up.

These are your site logs, not mine. We report serious issues at any log level. I highly recommend 'DEBUG' log level for most sites - which provides a bit of additional info and doesn't create huge logfiles. When there's a problem which defies all attempts to track, you might wish to use DATA log level for a short period of time to capture all the detail of what structures we were dealing with at the time. This log level will use a lot of space so is recommended only for brief periods or for developer test sites.

I recommend configuring logrotate for both the php log and the application log. I usually have a look at dbfail.out every week or two, fix any issues reported and then starting over with a fresh file. Likewise with the PHP logfile. I refer to it once in a while to see if there's something that needs fixing.

If something goes wrong, and it's not a fatal error, I look at the application logfile. Often I will
```
tail -f logfile.out 
```

While repeating an operation that has problems. Often I'll insert extra logging statements in the code if there isn't any hint what's going wrong. Even something as simple as "got here" or printing out the value of a variable that might be suspect. You can do this too - in fact I encourage you to do so. Once you've found what you need to find, you can

```
git checkout file.php
```

To immediately clear out all the extra logging stuff you added.  Use the information from this log and any detail you can provide from your investigation of the problem to file your bug report - unless your analysis points to the source of the problem. In that case, just fix it. 

##### Rotating log files

1. Enable the **logrot** addon in the official [hubzilla-addons](https://github.com/redmatrix/hubzilla-addons) repo
1. Create a directory in your web root called `log` with webserver write permissions
1. Go to the **logrot** admin settings and enter this folder name as well as the max size and number of retained log files.


#### Reporting issues

When reporting issues, please try to provide as much detail as may be necessary for developers to reproduce the issue and provide the complete text of all error messages.

We encourage you to try to the best of your abilities to use these logs combined with the source code in your possession to troubleshoot issues and find their cause. The community is often able to help, but only you have access to your site logfiles and it is considered a security risk to share them.   

If a code issue has been uncovered, please report it on the project bugtracker (https://github.com/redmatrix/hubzilla/issues). Again provide as much detail as possible to avoid us going back and forth asking questions about your configuration or how to duplicate the problem, so that we can get right to the problem and figure out what to do about it. You are also welcome to offer your own solutions and submit patches. In fact we encourage this as we are all volunteers and have little spare time available. The more people that help, the easier the workload for everybody. It's OK if your solution isn't perfect. Every little bit helps and perhaps we can improve on it. 


