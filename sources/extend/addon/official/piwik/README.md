Piwik Plugin
============

by Tobias Diekershoff and Klaus Weidenbach

This addon allows you to embed the code necessary for the FLOSS webanalytics
tool Piwik into the red# pages.

Requirements
------------

To use this plugin you need a [Piwik](http://piwik.org/) installation.

Where to find
-------------

In the *red-addons* git repository /piwik/, this directory contains
all required files and a CSS-file for styling the opt-out notice.

Configuration
-------------

Activate the analytics addon in your admin panel and then open the
.htconfig.php file in you fav editor to add the following lines:

    App::$config['piwik']['baseurl'] = 'example.com/piwik/';
    App::$config['piwik']['sideid'] = '1';
    App::$config['piwik']['optout'] = true;
    App::$config['piwik']['async'] = false;
    App::$config['piwik']['trackjserror'] = false;
You can also use the CLI config utility:
    `$ ./util/config piwik baseurl "www.example.com/piwik/"`

Configuration fields
---------------------

* The *baseurl* points to your Piwik installation. Use the absolute path,
  remember trailing slashes but ignore the protocol (http/s) part of the URL.
* Change the *sideid* parameter to whatever ID you want to use for tracking your
  red# installation.
* The *optout* parameter (true|false) defines whether or
  not a short notice about the utilization of Piwik will be displayed on every
  page of your red# site (at the bottom of the page with some spacing to the
  other content). Part of the note is a link that allows the visitor to set an
  _opt-out_ cookie which will prevent visits from that user be tracked by piwik.
* The *async* parameter (true|false) defines whether or not to use asynchronous
  tracking so pages load (or appear to load) faster.
* The *trackjserror* parameter (true|false) defines weather or not to include
  tracking of untracked JavaScript errors in frontend. This feature requires
  Piwik >= 2.2.0

Currently the optional notice states the following:

> This website is tracked using the Piwik analytics tool. If you do not want
> that your visits are logged this way you can set a cookie to prevent Piwik
> from tracking further visits of the site (opt-out).

License
=======

The _Piwik addon_ is licensed under the [3-clause BSD license][3] see the
LICENSE file in the addons directory.

[3]: http://opensource.org/licenses/BSD-3-Clause
