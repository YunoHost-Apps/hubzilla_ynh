Daemon (background) Processes
=============================


This directory provides background tasks which are executed by a 
command-line process and detached from normal web processing.

Background tasks are invoked by calling


	Zotlabs\Daemon\Master::Summon([ $cmd, $arg1, $argn... ]); 

The Master class loads the desired command file and passes the arguments.


To create a background task 'Foo' use the following template.

	<?php
	
	namespace Zotlabs\Daemon;
	
	class Foo {
	
		static public function run($argc,$argv) {
			// do something
		}
	}


The Master class "summons" the command by creating an executable script
from the provided arguments, then it invokes "Release" to execute the script
detached from web processing. This process calls the static::run() function
with any command line arguments using the traditional argc, argv format. 

Please note: These are *real* $argc, $argv variables passed from the command
line, and not the parsed argc() and argv() functions/variables which were 
obtained from parsing path components of the request URL by web processes.

Background processes do not emit displayable output except through logs. They 
should also not make any assumptions about their HTML and web environment 
(as they do not have a web environment), particularly with respect to global
variables such as $_SERVER, $_REQUEST, $_GET, $_POST, $_COOKIES, and $_SESSION.

