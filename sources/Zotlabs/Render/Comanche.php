<?php /** @file */

namespace Zotlabs\Render;

require_once('include/security.php');
require_once('include/menu.php');
require_once('include/widgets.php');



class Comanche {


	function parse($s, $pass = 0) {
		$matches = array();

		$cnt = preg_match_all("/\[comment\](.*?)\[\/comment\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$s = str_replace($mtch[0], '', $s);
			}
		}

		$cnt = preg_match_all("/\[if (.*?)\](.*?)\[else\](.*?)\[\/if\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				if($this->test_condition($mtch[1])) {
					$s = str_replace($mtch[0], $mtch[2], $s);
				}
				else {
					$s = str_replace($mtch[0], $mtch[3], $s);
				}
			}
		}
		else {
			$cnt = preg_match_all("/\[if (.*?)\](.*?)\[\/if\]/ism", $s, $matches, PREG_SET_ORDER);
			if($cnt) {
				foreach($matches as $mtch) {
					if($this->test_condition($mtch[1])) {
						$s = str_replace($mtch[0], $mtch[2], $s);
					}
					else {
						$s = str_replace($mtch[0], '', $s);
					}
				}
			}
		}
		if($pass == 0)
			$this->parse_pass0($s);
		else
			$this->parse_pass1($s);

	}

	function parse_pass0($s) {

		$matches = null;

		$cnt = preg_match("/\[layout\](.*?)\[\/layout\]/ism", $s, $matches);
		if($cnt)
			\App::$page['template'] = trim($matches[1]);

		$cnt = preg_match("/\[template=(.*?)\](.*?)\[\/template\]/ism", $s, $matches);
		if($cnt) {
			\App::$page['template'] = trim($matches[2]);
			\App::$page['template_style'] = trim($matches[2]) . '_' . $matches[1]; 
		}

		$cnt = preg_match("/\[template\](.*?)\[\/template\]/ism", $s, $matches);
		if($cnt) {
			\App::$page['template'] = trim($matches[1]);
		}

		$cnt = preg_match("/\[theme=(.*?)\](.*?)\[\/theme\]/ism", $s, $matches);
		if($cnt) {
			\App::$layout['schema'] = trim($matches[1]);
			\App::$layout['theme'] = trim($matches[2]);
		}

		$cnt = preg_match("/\[theme\](.*?)\[\/theme\]/ism", $s, $matches);
		if($cnt)
			\App::$layout['theme'] = trim($matches[1]);

		$cnt = preg_match_all("/\[webpage\](.*?)\[\/webpage\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			// only the last webpage definition is used if there is more than one
			foreach($matches as $mtch) {
				\App::$layout['webpage'] = $this->webpage($a,$mtch[1]);
			}
		}
	}

	function parse_pass1($s) {
		$cnt = preg_match_all("/\[region=(.*?)\](.*?)\[\/region\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				\App::$layout['region_' . $mtch[1]] = $this->region($mtch[2],$mtch[1]);
			}
		}
	}

	function get_condition_var($v) {
		if($v) {
			$x = explode('.',$v);
			if($x[0] == 'config')
				return get_config($x[1],$x[2]);
			elseif($x[0] === 'observer') {
				if(count($x) > 1) {
					$y = \App::get_observer();
					if(! $y)
						return false;
					if($x[1] == 'address')
						return $y['xchan_addr'];
					elseif($x[1] == 'name')
						return $y['xchan_name'];
					return false;
				}
				return get_observer_hash();
			}
			else
				return false;
		}
		return false;
	}

	function test_condition($s) {
		// This is extensible. The first version of variable testing supports tests of the forms:
		// [if $config.system.foo == baz] which will check if get_config('system','foo') is the string 'baz';
		// [if $config.system.foo != baz] which will check if get_config('system','foo') is not the string 'baz';
		// You may check numeric entries, but these checks are evaluated as strings. 
		// [if $config.system.foo {} baz] which will check if 'baz' is an array element in get_config('system','foo')
		// [if $config.system.foo {*} baz] which will check if 'baz' is an array key in get_config('system','foo')
		// [if $config.system.foo] which will check for a return of a true condition for get_config('system','foo');
		// The values 0, '', an empty array, and an unset value will all evaluate to false.

		if(preg_match('/[\$](.*?)\s\=\=\s(.*?)$/',$s,$matches)) {
			$x = $this->get_condition_var($matches[1]);
			if($x == trim($matches[2]))
				return true;
			return false;
		}
		if(preg_match('/[\$](.*?)\s\!\=\s(.*?)$/',$s,$matches)) {
			$x = $this->get_condition_var($matches[1]);
			if($x != trim($matches[2]))
				return true;
			return false;
		}

		if(preg_match('/[\$](.*?)\s\{\}\s(.*?)$/',$s,$matches)) {
			$x = $this->get_condition_var($matches[1]);
			if(is_array($x) && in_array(trim($matches[2]),$x))
				return true;
			return false;
		}

		if(preg_match('/[\$](.*?)\s\{\*\}\s(.*?)$/',$s,$matches)) {
			$x = $this->get_condition_var($matches[1]);
			if(is_array($x) && array_key_exists(trim($matches[2]),$x))
				return true;
			return false;
		}

		if(preg_match('/[\$](.*?)$/',$s,$matches)) {
			$x = $this->get_condition_var($matches[1]);
			if($x)
				return true;
			return false;
		}
		return false;

	}


	function menu($s, $class = '') {

		$channel_id = $this->get_channel_id();
		$name = $s;

		$cnt = preg_match_all("/\[var=(.*?)\](.*?)\[\/var\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$var[$mtch[1]] = $mtch[2];
				$name = str_replace($mtch[0], '', $name);
			}
		}

		if($channel_id) {
			$m = menu_fetch($name,$channel_id, get_observer_hash());
			return menu_render($m, $class, $edit = false, $var);
		}
	}


	function replace_region($match) {
		if (array_key_exists($match[1], \App::$page)) {
			return \App::$page[$match[1]];
		}
	}

	/**
	 * @brief Returns the channel_id of the profile owner of the page.
	 *
	 * Returns the channel_id of the profile owner of the page, or the local_channel
	 * if there is no profile owner. Otherwise returns 0.
	 *
	 * @return channel_id
	 */

	function get_channel_id() {
		$channel_id = ((is_array(\App::$profile)) ? \App::$profile['profile_uid'] : 0);

		if ((! $channel_id) && (local_channel()))
			$channel_id = local_channel();

		return $channel_id;
	}

	function block($s, $class = '') {
		$var = array();
		$matches = array();
		$name = $s;
		$class = (($class) ? $class : 'bblock widget');

		$cnt = preg_match_all("/\[var=(.*?)\](.*?)\[\/var\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$var[$mtch[1]] = $mtch[2];
				$name = str_replace($mtch[0], '', $name);
			}
		}

		$o = '';
		$channel_id = $this->get_channel_id();

		if($channel_id) {
			$r = q("select * from item inner join iconfig on iconfig.iid = item.id and item.uid = %d 
				and iconfig.cat = 'system' and iconfig.k = 'BUILDBLOCK' and iconfig.v = '%s' limit 1",
				intval($channel_id),
				dbesc($name)
			);

			if($r) {
				//check for eventual menus in the block and parse them
				$cnt = preg_match_all("/\[menu\](.*?)\[\/menu\]/ism", $r[0]['body'], $matches, PREG_SET_ORDER);
				if($cnt) {
					foreach($matches as $mtch) {
						$r[0]['body'] = str_replace($mtch[0], $this->menu(trim($mtch[1])), $r[0]['body']);
					}
				}
				$cnt = preg_match_all("/\[menu=(.*?)\](.*?)\[\/menu\]/ism", $r[0]['body'], $matches, PREG_SET_ORDER);
				if($cnt) {
					foreach($matches as $mtch) {
						$r[0]['body'] = str_replace($mtch[0],$this->menu(trim($mtch[2]),$mtch[1]),$r[0]['body']);
					}
				}

				//emit the block
				$o .= (($var['wrap'] == 'none') ? '' : '<div class="' . $class . '">');

				if($r[0]['title'] && trim($r[0]['body']) != '$content') {
					$o .= '<h3>' . $r[0]['title'] . '</h3>';
				}

				if(trim($r[0]['body']) === '$content') {
					$o .= \App::$page['content'];
				}
				else {
					$o .= prepare_text($r[0]['body'], $r[0]['mimetype']);
				}

				$o .= (($var['wrap'] == 'none') ? '' : '</div>');
			}
		}

		return $o;
	}

	function js($s) {

		switch($s) {
			case 'jquery':
				$path = 'view/js/jquery.js';
				break;
			case 'bootstrap':
				$path = 'library/bootstrap/js/bootstrap.min.js';
				break;
			case 'foundation':
				$path = 'library/foundation/js/foundation.js';
				$init = "\r\n" . '<script>$(document).ready(function() { $(document).foundation(); });</script>';
				break;
		}

		$ret = '<script src="' . z_root() . '/' . $path . '" ></script>';
		if($init)
			$ret .= $init;

		return $ret;

	}

	function css($s) {

		switch($s) {
			case 'bootstrap':
				$path = 'library/bootstrap/css/bootstrap.min.css';
				break;
			case 'foundation':
				$path = 'library/foundation/css/foundation.min.css';
				break;
		}

		$ret = '<link rel="stylesheet" href="' . z_root() . '/' . $path . '" type="text/css" media="screen">';

		return $ret;

	}

	// This doesn't really belong in Comanche, but it could also be argued that it is the perfect place.
	// We need to be able to select what kind of template and decoration to use for the webpage at the heart of our content.
	// For now we'll allow an '[authored]' element which defaults to name and date, or 'none' to remove these, and perhaps
	// 'full' to provide a social network style profile photo.
	// But leave it open to have richer templating options and perhaps ultimately discard this one, once we have a better idea
	// of what template and webpage options we might desire. 

	function webpage(&$a,$s) {
		$ret = array();
		$matches = array();

		$cnt = preg_match_all("/\[authored\](.*?)\[\/authored\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$ret['authored'] = $mtch[1];
			}
		}
		return $ret;
	}


	/**
	 * Render a widget
	 *
	 * @param string $name
	 * @param string $text
	 */

	function widget($name, $text) {
		$vars = array();
		$matches = array();


		$cnt = preg_match_all("/\[var=(.*?)\](.*?)\[\/var\]/ism", $text, $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				$vars[$mtch[1]] = $mtch[2];
			}
		}

		$func = 'widget_' . trim($name);

		if(! function_exists($func)) {
			if(file_exists('widget/' . trim($name) . '.php'))
				require_once('widget/' . trim($name) . '.php');
			elseif(file_exists('widget/' . trim($name) . '/' . trim($name) . '.php'))
				require_once('widget/' . trim($name) . '/' . trim($name) . '.php');
		}
		else {
			$theme_widget = $func . '.php';
			if((! function_exists($func)) && theme_include($theme_widget))
				require_once(theme_include($theme_widget));
		}

		if(function_exists($func))
			return $func($vars);
	}


	function region($s,$region_name) {

		$s = str_replace('$region',$region_name,$s);

		$matches = array();

		$cnt = preg_match_all("/\[menu\](.*?)\[\/menu\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$s = str_replace($mtch[0], $this->menu(trim($mtch[1])), $s);
			}
		}

		// menu class e.g. [menu=horizontal]my_menu[/menu] or [menu=tabbed]my_menu[/menu]
		// allows different menu renderings to be applied

		$cnt = preg_match_all("/\[menu=(.*?)\](.*?)\[\/menu\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$s = str_replace($mtch[0],$this->menu(trim($mtch[2]),$mtch[1]),$s);
			}
		}
		$cnt = preg_match_all("/\[block\](.*?)\[\/block\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$s = str_replace($mtch[0],$this->block(trim($mtch[1])),$s);
			}
		}

		$cnt = preg_match_all("/\[block=(.*?)\](.*?)\[\/block\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$s = str_replace($mtch[0],$this->block(trim($mtch[2]),trim($mtch[1])),$s);
			}
		}

		$cnt = preg_match_all("/\[js\](.*?)\[\/js\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$s = str_replace($mtch[0],$this->js(trim($mtch[1])),$s);
			}
		}

		$cnt = preg_match_all("/\[css\](.*?)\[\/css\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$s = str_replace($mtch[0],$this->css(trim($mtch[1])),$s);
			}
		}
		// need to modify this to accept parameters

		$cnt = preg_match_all("/\[widget=(.*?)\](.*?)\[\/widget\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$s = str_replace($mtch[0],$this->widget(trim($mtch[1]),$mtch[2]),$s);
			}
		}

		return $s;
	}


	/*
	 * @function register_page_template($arr)
	 *   Registers a page template/variant for use by Comanche selectors
	 * @param array $arr
	 *    'template' => template name
	 *    'variant' => array(
	 *           'name' => variant name
	 *           'desc' => text description
	 *           'regions' => array(
	 *               'name' => name
	 *               'desc' => text description
	 *           )
	 *    )
	 */


	function register_page_template($arr) {
		\App::$page_layouts[$arr['template']] = array($arr['variant']);
		return;
	}

}
