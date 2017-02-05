<?php

require_once('include/plugin.php');

head_add_css('library/tiptip/tipTip.css');
head_add_css('library/jgrowl/jquery.jgrowl.css');
head_add_css('library/jRange/jquery.range.css');

head_add_css('view/css/conversation.css');
head_add_css('view/css/widgets.css');
head_add_css('view/css/colorbox.css');
head_add_css('library/justifiedGallery/justifiedGallery.min.css');
head_add_css('library/Text_Highlighter/sample.css');	

head_add_js('jquery.js');
//head_add_js('jquery.migrate-3.0.0.js');
head_add_js('library/justifiedGallery/jquery.justifiedGallery.min.js');
head_add_js('library/sprintf.js/dist/sprintf.min.js');

//head_add_js('jquery-compat.js');
head_add_js('spin.js');
head_add_js('jquery.spin.js');
head_add_js('jquery.textinputs.js');
head_add_js('autocomplete.js');
head_add_js('library/jquery-textcomplete/jquery.textcomplete.js');
//head_add_js('library/colorbox/jquery.colorbox.js');
head_add_js('library/jquery.timeago.js');
head_add_js('library/readmore.js/readmore.js');
head_add_js('library/sticky-kit/sticky-kit.js');
//head_add_js('library/jquery_ac/friendica.complete.js');
//head_add_js('library/tiptip/jquery.tipTip.minified.js');
head_add_js('library/jgrowl/jquery.jgrowl_minimized.js');
//head_add_js('library/tinymce/jscripts/tiny_mce/tiny_mce.js');
head_add_js('library/cryptojs/components/core-min.js');
head_add_js('library/cryptojs/rollups/aes.js');
head_add_js('library/cryptojs/rollups/rabbit.js');
head_add_js('library/cryptojs/rollups/tripledes.js');
//head_add_js('library/stylish_select/jquery.stylish-select.js');
head_add_js('acl.js');
head_add_js('webtoolkit.base64.js');
head_add_js('main.js');
head_add_js('crypto.js');
head_add_js('library/jRange/jquery.range.js');
//head_add_js('docready.js');
head_add_js('library/colorbox/jquery.colorbox-min.js');

head_add_js('library/jquery.AreYouSure/jquery.are-you-sure.js');
head_add_js('library/tableofcontents/jquery.toc.js');
head_add_js('library/imagesloaded/imagesloaded.pkgd.min.js');
/**
 * Those who require this feature will know what to do with it.
 * Those who don't, won't.
 * Eventually this functionality needs to be provided by a module
 * such that permissions can be enforced. At the moment it's
 * more of a proof of concept; but sufficient for our immediate needs.
 */

$channel = App::get_channel();
if($channel && file_exists($channel['channel_address'] . '.js'))
	head_add_js('/' . $channel['channel_address'] . '.js');
