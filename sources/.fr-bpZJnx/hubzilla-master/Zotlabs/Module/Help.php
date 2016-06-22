<?php
namespace Zotlabs\Module;


require_once('include/help.php');

/**
 * You can create local site resources in doc/Site.md and either link to doc/Home.md for the standard resources
 * or use our include mechanism to include it on your local page.
 *
 * #include doc/Home.md;
 *
 * The syntax is somewhat strict. 
 *
 */

class Help extends \Zotlabs\Web\Controller {

	function get() {
		nav_set_selected('help');
	
		if($_REQUEST['search']) {
		
			$o .= '<div id="help-content" class="generic-content-wrapper">';
			$o .= '<div class="section-title-wrapper">';
			$o .= '<h2>' . t('Documentation Search') . ' - ' . htmlspecialchars($_REQUEST['search']) . '</h2>';
			$o .= '</div>';
			$o .= '<div class="section-content-wrapper">';
	
			$r = search_doc_files($_REQUEST['search']);
			if($r) {
				$o .= '<ul class="help-searchlist">';
				foreach($r as $rr) {
					$dirname = dirname($rr['sid']);
					$fname = basename($rr['sid']);
					$fname = substr($fname,0,strrpos($fname,'.'));
					$path = trim(substr($dirname,4),'/');
	
					$o .= '<li><a href="help/' . (($path) ? $path . '/' : '') . $fname . '" >' . ucwords(str_replace('_',' ',notags($fname))) . '</a><br />' . 
					str_replace('$Projectname',\Zotlabs\Lib\System::get_platform_name(),substr($rr['text'],0,200)) . '...<br /><br /></li>';
	
				}
				$o .= '</ul>';
				$o .= '</div>';
				$o .= '</div>';
			}
			return $o;
		}
	
	
		global $lang;
	
		$doctype = 'markdown';
	
		$text = '';
	
		if(argc() > 1) {
			$path = '';
			for($x = 1; $x < argc(); $x ++) {
				if(strlen($path))
					$path .= '/';
				$path .= argv($x);
			}
			$title = basename($path);
	
			$text = load_doc_file('doc/' . $path . '.md');
			\App::$page['title'] = t('Help:') . ' ' . ucwords(str_replace('-',' ',notags($title)));
	
			if(! $text) {
				$text = load_doc_file('doc/' . $path . '.bb');
				if($text)
					$doctype = 'bbcode';
				\App::$page['title'] = t('Help:') . ' ' . ucwords(str_replace('_',' ',notags($title)));
			}
			if(! $text) {
				$text = load_doc_file('doc/' . $path . '.html');
				if($text)
					$doctype = 'html';
				\App::$page['title'] = t('Help:') . ' ' . ucwords(str_replace('-',' ',notags($title)));
			}
		}
	
		if(! $text) {
			$text = load_doc_file('doc/Site.md');
			\App::$page['title'] = t('Help');
		}
		if(! $text) {
			$doctype = 'bbcode';
			$text = load_doc_file('doc/main.bb');
			\App::$page['title'] = t('Help');
		}
		
		if(! strlen($text)) {
			header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
			$tpl = get_markup_template("404.tpl");
			return replace_macros($tpl, array(
				'$message' =>  t('Page not found.' )
			));
		}
	
		if($doctype === 'html')
			$content = $text;
		if($doctype === 'markdown')	{
			require_once('library/markdown.php');
			# escape #include tags
			$text = preg_replace('/#include/ism', '%%include', $text);
			$content = Markdown($text);
			$content = preg_replace('/%%include/ism', '#include', $content);
		}
		if($doctype === 'bbcode') {
			require_once('include/bbcode.php');
			$content = bbcode($text);
			// bbcode retargets external content to new windows. This content is internal.
			$content = str_replace(' target="_blank"','',$content);		
		} 
	
		$content = preg_replace_callback("/#include (.*?)\;/ism", 'self::preg_callback_help_include', $content);
	
		return replace_macros(get_markup_template("help.tpl"), array(
			'$title' => t('$Projectname Documentation'),
			'$content' => translate_projectname($content)
		));
	
	}
	
	
	private static function preg_callback_help_include($matches) {
	
		if($matches[1]) {
			$include = str_replace($matches[0],load_doc_file($matches[1]),$matches[0]);
			if(preg_match('/\.bb$/', $matches[1]) || preg_match('/\.txt$/', $matches[1])) {
				require_once('include/bbcode.php');
				$include = bbcode($include);
				$include = str_replace(' target="_blank"','',$include);		
			} 
			elseif(preg_match('/\.md$/', $matches[1])) {
				require_once('library/markdown.php');
				$include = Markdown($include);
			}
			return $include;
		}
	
	}
	
	
}
