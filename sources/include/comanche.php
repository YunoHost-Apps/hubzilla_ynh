<?php /** @file */

require_once('include/security.php');
require_once('include/menu.php');
require_once('include/widgets.php');

// When editing a webpage - a dropdown is needed to select a page layout
// On submit, the pdl_select value (which is the mid of an item with item_type = ITEM_TYPE_PDL) is stored in 
// the webpage's resource_id, with resource_type 'pdl'.

// Then when displaying a webpage, we can see if it has a pdl attached. If not we'll 
// use the default site/page layout.

// If it has a pdl we'll load it as we know the mid and pass the body through comanche_parser() which will generate the 
// page layout from the given description


function pdl_selector($uid, $current="") {
	$o = '';

	$sql_extra = item_permissions_sql($uid);

	$r = q("select item_id.*, mid from item_id left join item on iid = item.id where item_id.uid = %d and item_id.uid = item.uid and service = 'PDL' $sql_extra order by sid asc",
		intval($uid)
	);

	$arr = array('channel_id' => $uid, 'current' => $current, 'entries' => $r);
	call_hooks('pdl_selector',$arr);

	$entries = $arr['entries'];
	$current = $arr['current'];

	$o .= '<select name="pdl_select" id="pdl_select" size="1">';
	$entries[] = array('title' => t('Default'), 'mid' => '');
	foreach($entries as $selection) {
		$selected = (($selection == $current) ? ' selected="selected" ' : '');
		$o .= "<option value=\"{$selection['mid']}\" $selected >{$selection['sid']}</option>";
	}

	$o .= '</select>';
	return $o;
}



function comanche_parser(&$a, $s, $pass = 0) {
	$matches = array();

	$cnt = preg_match_all("/\[comment\](.*?)\[\/comment\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0], '', $s);
		}
	}

	if($pass == 0) {
		$cnt = preg_match("/\[layout\](.*?)\[\/layout\]/ism", $s, $matches);
		if($cnt)
			$a->page['template'] = trim($matches[1]);

		$cnt = preg_match("/\[template=(.*?)\](.*?)\[\/template\]/ism", $s, $matches);
		if($cnt) {
			$a->page['template'] = trim($matches[2]);
			$a->page['template_style'] = trim($matches[2]) . '_' . $matches[1]; 
		}

		$cnt = preg_match("/\[template\](.*?)\[\/template\]/ism", $s, $matches);
		if($cnt) {
			$a->page['template'] = trim($matches[1]);
		}

		$cnt = preg_match("/\[theme=(.*?)\](.*?)\[\/theme\]/ism", $s, $matches);
		if($cnt) {
			$a->layout['schema'] = trim($matches[1]);
			$a->layout['theme'] = trim($matches[2]);
		}

		$cnt = preg_match("/\[theme\](.*?)\[\/theme\]/ism", $s, $matches);
		if($cnt)
			$a->layout['theme'] = trim($matches[1]);

		$cnt = preg_match_all("/\[webpage\](.*?)\[\/webpage\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			// only the last webpage definition is used if there is more than one
			foreach($matches as $mtch) {
				$a->layout['webpage'] = comanche_webpage($a,$mtch[1]);
			}
		}

	}
	else {
		$cnt = preg_match_all("/\[region=(.*?)\](.*?)\[\/region\]/ism", $s, $matches, PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				$a->layout['region_' . $mtch[1]] = comanche_region($a,$mtch[2]);
			}
		}

	}

}


function comanche_menu($s, $class = '') {

	$channel_id = comanche_get_channel_id();
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

function comanche_replace_region($match) {
	$a = get_app();
	if (array_key_exists($match[1], $a->page)) {
		return $a->page[$match[1]];
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
function comanche_get_channel_id() {
	$channel_id = ((is_array(get_app()->profile)) ? get_app()->profile['profile_uid'] : 0);

	if ((! $channel_id) && (local_channel()))
		$channel_id = local_channel();

	return $channel_id;
}

function comanche_block($s, $class = '') {
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
	$channel_id = comanche_get_channel_id();

	if($channel_id) {
		$r = q("select * from item inner join item_id on iid = item.id and item_id.uid = item.uid and item.uid = %d and service = 'BUILDBLOCK' and sid = '%s' limit 1",
			intval($channel_id),
			dbesc($name)
		);

		if($r) {
			//check for eventual menus in the block and parse them
			$cnt = preg_match_all("/\[menu\](.*?)\[\/menu\]/ism", $r[0]['body'], $matches, PREG_SET_ORDER);
			if($cnt) {
				foreach($matches as $mtch) {
					$r[0]['body'] = str_replace($mtch[0], comanche_menu(trim($mtch[1])), $r[0]['body']);
				}
			}
			$cnt = preg_match_all("/\[menu=(.*?)\](.*?)\[\/menu\]/ism", $r[0]['body'], $matches, PREG_SET_ORDER);
			if($cnt) {
				foreach($matches as $mtch) {
					$r[0]['body'] = str_replace($mtch[0],comanche_menu(trim($mtch[2]),$mtch[1]),$r[0]['body']);
				}
			}

			//emit the block
			$o .= (($var['wrap'] == 'none') ? '' : '<div class="' . $class . '">');

			if($r[0]['title'] && trim($r[0]['body']) != '$content') {
				$o .= '<h3>' . $r[0]['title'] . '</h3>';
			}

			if(trim($r[0]['body']) === '$content') {
				$o .= get_app()->page['content'];
			}
			else {
				$o .= prepare_text($r[0]['body'], $r[0]['mimetype']);
			}

			$o .= (($var['wrap'] == 'none') ? '' : '</div>');
		}
	}

	return $o;
}

function comanche_js($s) {

	switch($s) {
		case 'jquery':
			$path = 'view/js/jquery.js';
			break;
		case 'bootstrap':
			$path = 'library/bootstrap/js/bootstrap.min.js';
			break;
		case 'foundation':
			$path = 'library/foundation/js/foundation.min.js';
			$init = "\r\n" . '<script>$(document).ready(function() { $(document).foundation(); });</script>';
			break;
	}

	$ret = '<script src="' . z_root() . '/' . $path . '" ></script>';
	if($init)
		$ret .= $init;

	return $ret;

}

function comanche_css($s) {

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

function comanche_webpage(&$a,$s) {
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
 * Widgets will have to get any operational arguments from the session, the
 * global app environment, or config storage until we implement argument passing
 *
 * @param string $name
 * @param string $text
 */
function comanche_widget($name, $text) {
	$vars = array();
	$matches = array();

	$cnt = preg_match_all("/\[var=(.*?)\](.*?)\[\/var\]/ism", $text, $matches, PREG_SET_ORDER);
	if ($cnt) {
		foreach ($matches as $mtch) {
			$vars[$mtch[1]] = $mtch[2];
		}
	}

	if(file_exists('widget/' . trim($name) . '.php'))
		require_once('widget/' . trim($name) . '.php');
	else {
		$theme_widget = 'widget_' . trim($name) . '.php';
		if(theme_include($theme_widget))
			require_once(theme_include($theme_widget));
	}

	$func = 'widget_' . trim($name);
	if (function_exists($func))
		return $func($vars);
}


function comanche_region(&$a, $s) {
	$matches = array();

	$cnt = preg_match_all("/\[menu\](.*?)\[\/menu\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0], comanche_menu(trim($mtch[1])), $s);
		}
	}

	// menu class e.g. [menu=horizontal]my_menu[/menu] or [menu=tabbed]my_menu[/menu]
	// allows different menu renderings to be applied

	$cnt = preg_match_all("/\[menu=(.*?)\](.*?)\[\/menu\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_menu(trim($mtch[2]),$mtch[1]),$s);
		}
	}
	$cnt = preg_match_all("/\[block\](.*?)\[\/block\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_block(trim($mtch[1])),$s);
		}
	}

	$cnt = preg_match_all("/\[block=(.*?)\](.*?)\[\/block\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_block(trim($mtch[2]),trim($mtch[1])),$s);
		}
	}

	$cnt = preg_match_all("/\[js\](.*?)\[\/js\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_js(trim($mtch[1])),$s);
		}
	}

	$cnt = preg_match_all("/\[css\](.*?)\[\/css\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_css(trim($mtch[1])),$s);
		}
	}
	// need to modify this to accept parameters

	$cnt = preg_match_all("/\[widget=(.*?)\](.*?)\[\/widget\]/ism", $s, $matches, PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0],comanche_widget(trim($mtch[1]),$mtch[2]),$s);
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
	get_app()->page_layouts[$arr['template']] = array($arr['variant']);
	return;
}
