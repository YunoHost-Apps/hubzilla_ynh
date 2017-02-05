<?php
namespace Zotlabs\Module;

require_once('include/help.php');

/**
 * You can create local site resources in doc/Site.md and either link to doc/Home.md for the standard resources
 * or use our include mechanism to include it on your local page.
 *@code
 * #include doc/Home.md;
 *@endcode
 *
 * The syntax is somewhat strict.
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
					$dirname = dirname($rr['v']);
					$fname = basename($rr['v']);
					$fname = substr($fname, 0, strrpos($fname, '.'));
					$path = trim(substr($dirname, 4), '/');

					$o .= '<li><a href="help/' . (($path) ? $path . '/' : '') . $fname . '" >' . ucwords(str_replace('_',' ',notags($fname))) . '</a><br>'
						. '<b><i>' . 'help/' . (($path) ? $path . '/' : '') . $fname . '</i></b><br>'
						. '...' . str_replace('$Projectname', \Zotlabs\Lib\System::get_platform_name(), $rr['text']) . '...<br><br></li>';
				}
				$o .= '</ul>';
				$o .= '</div>';
				$o .= '</div>';
			}

			return $o;
		}
                
                
                if(argc() > 2 && argv(argc()-2) === 'assets') {
                        $path = '';
                        for($x = 1; $x < argc(); $x ++) {
                                if(strlen($path))
                                        $path .= '/';
                                $path .= argv($x);
                        }
                        $realpath = 'doc/' . $path;
                         //Set the content-type header as appropriate
                        $imageInfo = getimagesize($realpath);
                        switch ($imageInfo[2]) {
                            case IMAGETYPE_JPEG:
                                header("Content-Type: image/jpeg");
                                break;
                            case IMAGETYPE_GIF:
                                header("Content-Type: image/gif");
                                break;
                            case IMAGETYPE_PNG:
                                header("Content-Type: image/png");
                                break;
                           default:
                                break;
                        }
                        header("Content-Length: " . filesize($realpath));

                        // dump the picture and stop the script
                        readfile($realpath);
                        killme();
                }

		$headings = [
			'about' => t('About'),
			'member' => t('Members'),
			'admin' => t('Administrators'),
			'developer' => t('Developers'),
			'tutorials' => t('Tutorials')
		];

		if(array_key_exists(argv(1), $headings))
			$heading = $headings[argv(1)];

                $content =  get_help_content();

		return replace_macros(get_markup_template('help.tpl'), array(
			'$title' => t('$Projectname Documentation'),
			'$tocHeading' => t('Contents'),
			'$content' => $content,
			'$heading' => $heading
		));
	}

}
