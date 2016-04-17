<?php

namespace Zotlabs\Web;


class Router {

	function __construct(&$a) {

		/**
		 *
		 * We have already parsed the server path into App::$argc and App::$argv
		 *
		 * App::$argv[0] is our module name. We will load the file mod/{App::$argv[0]}.php
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

		$module = \App::$module;

		if(strlen($module)) {

			/**
			 *
			 * We will always have a module name.
			 * First see if we have a plugin which is masquerading as a module.
			 *
			 */

			if(is_array(\App::$plugins) && in_array($module,\App::$plugins) && file_exists("addon/{$module}/{$module}.php")) {
				include_once("addon/{$module}/{$module}.php");
				if(function_exists($module . '_module'))
					\App::$module_loaded = true;
			}

			if((strpos($module,'admin') === 0) && (! is_site_admin())) {
				\App::$module_loaded = false;
				notice( t('Permission denied.') . EOL);
				goaway(z_root());
			}

			/**
			 * If the site has a custom module to over-ride the standard module, use it.
			 * Otherwise, look for the standard program module in the 'mod' directory
			 */

			if(! (\App::$module_loaded)) {
				if(file_exists("mod/site/{$module}.php")) {
					include_once("mod/site/{$module}.php");
					\App::$module_loaded = true;
				}
				elseif(file_exists("mod/{$module}.php")) {
					include_once("mod/{$module}.php");
					\App::$module_loaded = true;
				}
				else logger("mod/{$module}.php not found.");
			}


			/**
			 * This provides a place for plugins to register module handlers which don't otherwise exist on the system.
			 * If the plugin sets 'installed' to true we won't throw a 404 error for the specified module even if
			 * there is no specific module file or matching plugin name.
			 * The plugin should catch at least one of the module hooks for this URL. 
			 */

			$x = array('module' => $module, 'installed' => false);
			call_hooks('module_loaded', $x);
			if($x['installed'])
				\App::$module_loaded = true;

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

			if(! (\App::$module_loaded)) {

				// Stupid browser tried to pre-fetch our Javascript img template. Don't log the event or return anything - just quietly exit.
				if((x($_SERVER, 'QUERY_STRING')) && preg_match('/{[0-9]}/', $_SERVER['QUERY_STRING']) !== 0) {
					killme();
				}

				if((x($_SERVER, 'QUERY_STRING')) && ($_SERVER['QUERY_STRING'] === 'q=internal_error.html') && \App::$config['system']['dreamhost_error_hack']) {
					logger('index.php: dreamhost_error_hack invoked. Original URI =' . $_SERVER['REQUEST_URI']);
					goaway(z_root() . $_SERVER['REQUEST_URI']);
				}

				logger('index.php: page not found: ' . $_SERVER['REQUEST_URI'] . ' ADDRESS: ' . $_SERVER['REMOTE_ADDR'] . ' QUERY: ' . $_SERVER['QUERY_STRING'], LOGGER_DEBUG);
				header($_SERVER['SERVER_PROTOCOL'] . ' 404 ' . t('Not Found'));
				$tpl = get_markup_template('404.tpl');
				\App::$page['content'] = replace_macros($tpl, array(
						'$message' => t('Page not found.')
				));

				// pretend this is a module so it will initialise the theme
				\App::$module = '404';
				\App::$module_loaded = true;
			}
		}
	}


	function Dispatch(&$a) {

		/**
		 * Call module functions
		 */

		if(\App::$module_loaded) {
			\App::$page['page_title'] = \App::$module;
			$placeholder = '';

			/**
			 * No theme has been specified when calling the module_init functions
			 * For this reason, please restrict the use of templates to those which
			 * do not provide any presentation details - as themes will not be able
			 * to over-ride them.
			 */

			if(function_exists(\App::$module . '_init')) {
				$arr = array('init' => true, 'replace' => false);		
				call_hooks(\App::$module . '_mod_init', $arr);
				if(! $arr['replace']) {
					$func = \App::$module . '_init';
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
			elseif (x(\App::$theme_info, 'extends') && file_exists('view/theme/' . \App::$theme_info['extends'] . '/php/theme.php')) {
				require_once('view/theme/' . \App::$theme_info['extends'] . '/php/theme.php');
				if(function_exists(str_replace('-', '_', \App::$theme_info['extends']) . '_init')) {
					$func = str_replace('-', '_', \App::$theme_info['extends']) . '_init';
					$func($a);
				}
			}

			if(($_SERVER['REQUEST_METHOD'] === 'POST') && (! \App::$error)
				&& (function_exists(\App::$module . '_post'))
				&& (! x($_POST, 'auth-params'))) {		
				call_hooks(\App::$module . '_mod_post', $_POST);
				$func = \App::$module . '_post';
				$func($a);
			}

			if((! \App::$error) && (function_exists(\App::$module . '_content'))) {
				$arr = array('content' => \App::$page['content'], 'replace' => false);
				call_hooks(\App::$module . '_mod_content', $arr);
				\App::$page['content'] = $arr['content'];
				if(! $arr['replace']) {
					$func = \App::$module . '_content';
					$arr = array('content' => $func($a));
				}
				call_hooks(\App::$module . '_mod_aftercontent', $arr);
				\App::$page['content'] .= $arr['content'];
			}
		}
	}
}