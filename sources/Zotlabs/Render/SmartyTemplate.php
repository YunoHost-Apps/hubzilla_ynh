<?php /** @file */

namespace Zotlabs\Render;

class SmartyTemplate implements TemplateEngine {

	static $name ="smarty3";
	
	public function __construct(){

		// Cannot use get_config() here because it is called during installation when there is no DB.
		// FIXME: this may leak private information such as system pathnames.

        $basecompiledir = ((array_key_exists('smarty3_folder',\App::$config['system'])) 
			? \App::$config['system']['smarty3_folder'] : '');
        if (!$basecompiledir) $basecompiledir = str_replace('Zotlabs','',dirname(__dir__)) . "/" . TEMPLATE_BUILD_PATH;
        if (!is_dir($basecompiledir)) {
			@os_mkdir(TEMPLATE_BUILD_PATH, STORAGE_DEFAULT_PERMISSIONS, true);
	        if (!is_dir($basecompiledir)) {
				echo "<b>ERROR:</b> folder <tt>$basecompiledir</tt> does not exist."; killme();
			}
        }
		if(!is_writable($basecompiledir)){
			echo "<b>ERROR:</b> folder <tt>$basecompiledir</tt> must be writable by webserver."; killme();
		}
         \App::$config['system']['smarty3_folder'] = $basecompiledir;
	}
	
	// TemplateEngine interface

	public function replace_macros($s, $r) {
		$template = '';

		// these are available for use in all templates

		$r['$z_baseurl']     = z_root();
		$r['$z_server_role'] = \Zotlabs\Lib\System::get_server_role();
		$r['$z_techlevel']   = get_account_techlevel();

		if(gettype($s) === 'string') {
			$template = $s;
			$s = new SmartyInterface();
		}
		foreach($r as $key=>$value) {
			if($key[0] === '$') {
				$key = substr($key, 1);
			}
			$s->assign($key, $value);
		}
		return $s->parsed($template);		
	}
	
	public function get_markup_template($file, $root=''){
		$template_file = theme_include($file, $root);
		if($template_file) {
			$template = new SmartyInterface();
			$template->filename = $template_file;

			return $template;
		}		
		return "";
	}

	public function get_intltext_template($file, $root='') {

		$lang = \App::$language;
    
		if(file_exists("view/$lang/$file"))
        	$template_file = "view/$lang/$file";
	    elseif(file_exists("view/en/$file"))
        	$template_file = "view/en/$file";
    	else
        	$template_file = theme_include($file,$root);
		if($template_file) {
			$template = new SmartyInterface();
			$template->filename = $template_file;

			return $template;
		}		
		return "";
	}



}
