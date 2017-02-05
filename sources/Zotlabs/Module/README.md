Zotlabs/Module
==============


This directory contains controller modules for handling web requests. The
lowercase class name indicates the head of the URL path which this module
handles. There are other methods of attaching (routing) URL paths to
controllers, but this is the primary method used in this project.

Module controllers MUST reside in this directory and namespace to be
autoloaded (unless other specific routing methods are employed). They
typically use and extend the class definition in Zotlabs/Web/Controller 
as a template. 

Template:

	<?php

	namespace Zotlabs\Web;


	class Controller {

		function init() {}
		function post() {}
		function get() {}

	}


Typical Module declaration for the '/foo' URL route:


	<?php
	namespace Zotlabs\Module;

	class Foo extends \Zotlabs\Web\Controller {

		function init() {
			// init() handler goes here
		}

		function post() {
			// post handler goes here
		}

		function get() {
			return 'Hello world.' . EOL;
		}

	}

This model provides callbacks for public functions named init(), post(), 
and get(). init() is always called. post() is called if $_POST variables
are present, and get() is called if none of the prior functions terminated
the handler. The get() method typically retuns a string which represents 
the contents of the content region of the resulting page. Modules which emit 
json, xml or other machine-readable formats typically emit their contents
inside the init() function and call 'killme()' to terminate the Module. 

Modules are passed the URL path as argc,argv arguments. For a path such as

	https://mysite.something/foo/bar/baz

The app will typically invoke the Module class 'Foo' and pass it 

	$x = argc(); // $x = 3

	$x = argv(0); // $x = 'foo'
	$x = argv(1); // $x = 'bar'
	$x = argv(2); // $x = 'baz'

These are handled in a similar fashion to their counterparts in the Unix shell
or C/C++ languages. Do not confuse the argc(),argv() functions with the
global variables $argc,$argv which are passed to command line programs. These 
are handled separately by command line and Zotlabs/Daemon class functions. 



 