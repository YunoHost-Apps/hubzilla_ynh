<?php
if (! $nav_bg)
	$nav_bg = "#222";
if (! $nav_gradient_top)
	$nav_gradient_top = "#3c3c3c";
if (! $nav_gradient_bottom)
	$nav_gradient_bottom = "#222";
if (! $nav_active_gradient_top)
	$nav_active_gradient_top = "#222";
if (! $nav_active_gradient_bottom)
	$nav_active_gradient_bottom = "#282828";
if (! $nav_bd)
	$nav_bd = "#222";
if (! $nav_icon_colour)
	$nav_icon_colour = "#999";
if (! $nav_active_icon_colour)
	$nav_active_icon_colour = "#fff";
if (! $link_colour)
	$link_colour = "#337AB7";
if (! $banner_colour)
	$banner_colour = "#fff";
if (! $bgcolour)
	$bgcolour = "#fdfdfd";
if (! $background_image)
	$background_image ='';
if (! $item_colour)
	$item_colour = "rgb(238,238,238)";
if (! $comment_item_colour)
	$comment_item_colour = "rgba(254,254,254,0.4)";
if (! $comment_border_colour)
	$comment_border_colour = "transparent";
if (! $toolicon_colour)
	$toolicon_colour = '#777';
if (! $toolicon_activecolour)
	$toolicon_activecolour = '#000';
if (! $item_opacity)
	$item_opacity = "1";
if (! $font_size)
	$font_size = "0.9rem";
if (! $body_font_size)
	$body_font_size = "0.75rem";
if (! $font_colour)
	$font_colour = "#4d4d4d";
if (! $radius)
	$radius = "4";
if (! $shadow)
	$shadow = "0";
if (! $converse_width)
	$converse_width = "790";
if(! $top_photo)
	$top_photo = '48px';
if(! $comment_indent)
	$comment_indent = '0px';
if(! $reply_photo)
	$reply_photo = '32px';
if($nav_min_opacity === false || $nav_min_opacity === '') {
	$nav_float_min_opacity = 1.0;
	$nav_percent_min_opacity = 100;
}
else {
	$nav_float_min_opacity = (float) $nav_min_opacity;
	$nav_percent_min_opacity = (int) 100 * $nav_min_opacity;
}
