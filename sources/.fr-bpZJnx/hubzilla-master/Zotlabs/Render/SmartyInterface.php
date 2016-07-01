<?php /** @file */

namespace Zotlabs\Render;

require_once('library/Smarty/libs/Smarty.class.php');

class SmartyInterface extends \Smarty {

	public $filename;

	function __construct() {
		parent::__construct();

		$theme = Theme::current();
		$thname = $theme[0];

		// setTemplateDir can be set to an array, which Smarty will parse in order.
		// The order is thus very important here

		$template_dirs = array('theme' => "view/theme/$thname/tpl/");
		if( x(\App::$theme_info,"extends") )
			$template_dirs = $template_dirs + array('extends' => "view/theme/" . \App::$theme_info["extends"] . "/tpl/");
		$template_dirs = $template_dirs + array('base' => 'view/tpl/');
		$this->setTemplateDir($template_dirs);

        $basecompiledir = \App::$config['system']['smarty3_folder'];
        
		$this->setCompileDir($basecompiledir.'/compiled/');
		$this->setConfigDir($basecompiledir.'/config/');
		$this->setCacheDir($basecompiledir.'/cache/');

		$this->left_delimiter = \App::get_template_ldelim('smarty3');
		$this->right_delimiter = \App::get_template_rdelim('smarty3');

		// Don't report errors so verbosely
		$this->error_reporting = E_ALL & (~E_NOTICE);
	}

	function parsed($template = '') {
		if($template) {
			return $this->fetch('string:' . $template);
		}
		return $this->fetch('file:' . $this->filename);
	}
}



