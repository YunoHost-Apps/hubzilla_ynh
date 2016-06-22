[h2]module_mod_aftercontent[/h2]



The hook data for this call consists of an array 

	$arr['content']

This element contains the HTML content which was prepared for this page by calling the module_content() function. It is invoked after the content has been created. It does not contain the result of AJAX or asynchronous page load calls.

	The current module may be determined by lookin at App::$module  

