<?php

namespace Zotlabs\Web;


class Router {

	function __construct(&$a) {

		/**
		 *
		 * We have already parsed the server path into $a->argc and $a->argv
		 *
		 * $a->argv[0] is our module name. We will load the file mod/{$a->argv[0]}.php
		 * and use it for handling our URL request.
		 * The module file contains a few functions that we call in various circumstances
		 * and in the following order:
		 *
		 * "module"_init
		 * "module"_post (only called if there are $_POST variables)
		 * "module"_content - the string return of this function contains our page body
		 *
		 * Modules which emit other serialisations besides HTML (XML,JSON, etc.) should do
		 * so within the module init and/or post functions and then invoke killme() to terminate
		 * further processing.
		 */

		if(strlen($a->module)) {

			/**
			 *
			 * We will always have a module name.
			 * First see if we have a plugin which is masquerading as a module.
			 *
			 */

			if(is_array($a->plugins) && in_array($a->module,$a->plugins) && file_exists("addon/{$a->module}/{$a->module}.php")) {
				include_once("addon/{$a->module}/{$a->module}.php");
				if(function_exists($a->module . '_module'))
					$a->module_loaded = true;
			}

			if((strpos($a->module,'admin') === 0) && (! is_site_admin())) {
				$a->module_loaded = false;
				notice( t('Permission denied.') . EOL);
				goaway(z_root());
			}

			/**
			 * If the site has a custom module to over-ride the standard module, use it.
			 * Otherwise, look for the standard program module in the 'mod' directory
			 */

			if(! $a->module_loaded) {
				if(file_exists("mod/site/{$a->module}.php")) {
					include_once("mod/site/{$a->module}.php");
					$a->module_loaded = true;
				}
				elseif(file_exists("mod/{$a->module}.php")) {
					include_once("mod/{$a->module}.php");
					$a->module_loaded = true;
				}
			}

			/**
			 * This provides a place for plugins to register module handlers which don't otherwise exist on the system.
			 * If the plugin sets 'installed' to true we won't throw a 404 error for the specified module even if
			 * there is no specific module file or matching plugin name.
			 * The plugin should catch at least one of the module hooks for this URL. 
			 */

			$x = array('module' => $a->module, 'installed' => false);
			call_hooks('module_loaded', $x);
			if($x['installed'])
				$a->module_loaded = true;

			/**
			 * The URL provided does not resolve to a valid module.
			 *
			 * On Dreamhost sites, quite often things go wrong for no apparent reason and they send us to '/internal_error.html'.
			 * We don't like doing this, but as it occasionally accounts for 10-20% or more of all site traffic -
			 * we are going to trap this and redirect back to the requested page. As long as you don't have a critical error on your page
			 * this will often succeed and eventually do the right thing.
			 *
			 * Otherwise we are going to emit a 404 not found.
	 		 */

			if(! $a->module_loaded) {

				// Stupid browser tried to pre-fetch our Javascript img template. Don't log the event or return anything - just quietly exit.
				if((x($_SERVER, 'QUERY_STRING')) && preg_match('/{[0-9]}/', $_SERVER['QUERY_STRING']) !== 0) {
					killme();
				}

				if((x($_SERVER, 'QUERY_STRING')) && ($_SERVER['QUERY_STRING'] === 'q=internal_error.html') && $a->config['system']['dreamhost_error_hack']) {
					logger('index.php: dreamhost_error_hack invoked. Original URI =' . $_SERVER['REQUEST_URI']);
					goaway($a->get_baseurl() . $_SERVER['REQUEST_URI']);
				}

				logger('index.php: page not found: ' . $_SERVER['REQUEST_URI'] . ' ADDRESS: ' . $_SERVER['REMOTE_ADDR'] . ' QUERY: ' . $_SERVER['QUERY_STRING'], LOGGER_DEBUG);
				header($_SERVER['SERVER_PROTOCOL'] . ' 404 ' . t('Not Found'));
				$tpl = get_markup_template('404.tpl');
				$a->page['content'] = replace_macros($tpl, array(
						'$message' => t('Page not found.')
				));

				// pretend this is a module so it will initialise the theme
				$a->module = '404';
				$a->module_loaded = true;
			}
		}
	}


	function Dispatch(&$a) {

		/**
		 * Call module functions
		 */

		if($a->module_loaded) {
			$a->page['page_title'] = $a->module;
			$placeholder = '';

			/**
			 * No theme has been specified when calling the module_init functions
			 * For this reason, please restrict the use of templates to those which
			 * do not provide any presentation details - as themes will not be able
			 * to over-ride them.
			 */

			if(function_exists($a->module . '_init')) {
				$arr = array('init' => true, 'replace' => false);		
				call_hooks($a->module . '_mod_init', $arr);
				if(! $arr['replace']) {
					$func = $a->module . '_init';
					$func($a);
				}
			}

			/**
			 * Do all theme initialiasion here before calling any additional module functions.
			 * The module_init function may have changed the theme.
			 * Additionally any page with a Comanche template may alter the theme.
			 * So we'll check for those now.
			 */


			/**
			 * In case a page has overloaded a module, see if we already have a layout defined
			 * otherwise, if a PDL file exists for this module, use it
			 * The member may have also created a customised PDL that's stored in the config
			 */

			load_pdl($a);

			/**
		 	 * load current theme info
		 	 */

			$theme_info_file = 'view/theme/' . current_theme() . '/php/theme.php';
			if (file_exists($theme_info_file)){
				require_once($theme_info_file);
			}

			if(function_exists(str_replace('-', '_', current_theme()) . '_init')) {
				$func = str_replace('-', '_', current_theme()) . '_init';
				$func($a);
			}
			elseif (x($a->theme_info, 'extends') && file_exists('view/theme/' . $a->theme_info['extends'] . '/php/theme.php')) {
				require_once('view/theme/' . $a->theme_info['extends'] . '/php/theme.php');
				if(function_exists(str_replace('-', '_', $a->theme_info['extends']) . '_init')) {
					$func = str_replace('-', '_', $a->theme_info['extends']) . '_init';
					$func($a);
				}
			}

			if(($_SERVER['REQUEST_METHOD'] === 'POST') && (! $a->error)
				&& (function_exists($a->module . '_post'))
				&& (! x($_POST, 'auth-params'))) {		
				call_hooks($a->module . '_mod_post', $_POST);
				$func = $a->module . '_post';
				$func($a);
			}

			if((! $a->error) && (function_exists($a->module . '_content'))) {
				$arr = array('content' => $a->page['content'], 'replace' => false);
				call_hooks($a->module . '_mod_content', $arr);
				$a->page['content'] = $arr['content'];
				if(! $arr['replace']) {
					$func = $a->module . '_content';
					$arr = array('content' => $func($a));
				}
				call_hooks($a->module . '_mod_aftercontent', $arr);
				$a->page['content'] .= $arr['content'];
			}
		}
	}
}