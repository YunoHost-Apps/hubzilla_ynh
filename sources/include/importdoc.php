<?php



require_once('include/cli_startup.php');


function importdoc_run($argv, $argc){

	cli_startup();

	require_once('mod/help.php');


	update_docs_dir('doc/*');

}
if (array_search(__file__,get_included_files())===0){
  importdoc_run($argv,$argc);
  killme();
}

function update_docs_dir($s) {
	$f = basename($s);
	$d = dirname($s);
	if($s === 'doc/html')
		return;
	$files = glob("$d/$f");
	if($files) {
		foreach($files as $fi) {
			if($fi === 'doc/html')
				continue;
			if(is_dir($fi))
				update_docs_dir("$fi/*");
			else
				store_doc_file($fi);
		}
	}
}


