[h2]module_mod_content[/h2]

The hook data for this call consists of an array 

	$arr['content']

This element contains the HTML content before calling the module_content() function. It is invoked before the content region has been populated. This may  or may not be empty as there may be other processes or addons generating content prior to your hook handler is run. Be certain to preserve any current content. Typically anything you add here will be placed at the top of the content region of the page, but in any event prior to the main content region being generated. 

	The current module may be determined by lookin at App::$module  

