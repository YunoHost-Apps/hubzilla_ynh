<?php
/* Name: Flattr Widget
 * Description: Add a Flattr Button to the left/right aside are to allow the flattring of one thing (e.g. the for a blog)
 * Version: 0.1
 * Screenshot: img/red-flattr-widget.png
 * Depends: Core
 * Recommends: None
 * Category: Widget, flattr, Payment
 * Author: Tobias Diekershoff <https://diekershoff.de/channel/bavatar>
 * Maintainer: Tobias Diekershoff <https://diekershoff.de/channel/bavatar>
 */

function flattrwidget_load() {
	register_hook('construct_page', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_construct_page');
	register_hook('feature_settings', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_settings');
	register_hook('feature_settings_post', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_settings_post');
}

function flattrwidget_unload() {
	unregister_hook('construct_page', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_construct_page');
	unregister_hook('feature_settings', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_settings');
	unregister_hook('feature_settings_post', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_settings_post');
}

function flattrwidget_construct_page(&$a,&$b) {
    if (! $b['module']=='channel')
	return;
    $id = App::$profile['profile_uid'];
    $enable = intval(get_pconfig($id,'flattrwidget','enable'));
    if (! $enable)
	return;
    App::$page['htmlhead'] .= '<link rel="stylesheet" href="'.z_root().'/addon/flattrwidget/style.css'.'" media="all" />';
    //  get alignment and static/dynamic from the settings
    //  align is either "aside" or "right_aside"
    //  sd is either static or dynamic
    $lr = get_pconfig( $id, 'flattrwidget', 'align');
    $sd = get_pconfig( $id, 'flattrwidget', 'sd');
    //  title of the thing for the things page on flattr
    $ftitle = get_pconfig( $id, 'flattrwidget', 'title');
    //  URL of the thing
    $thing = get_pconfig( $id, 'flattrwidget', 'thing');
    //  flattr user the thing belongs to
    $user = get_pconfig( $id, 'flattrwidget', 'user');
    //  title for the flattr button itself
    $title = t('Flattr this!');
    //  construct the link for the button
    $link = 'https://flattr.com/submit/auto?user_id='.$user.'&url=' . rawurlencode($thing).'&title='.rawurlencode($ftitle);
    if ($sd == 'static') {
	//  static button graphic from the img folder
	$img = z_root() .'/addon/flattrwidget/img/flattr-badge-large.png';
	$code = '<a href="'.$link.'" target="_blank"><img src="'.$img.'" alt="'.$title.'" title="'.$title.'" border="0"></a>';
    } else {
	$code = '<script id=\'fbdu5zs\'>(function(i){var f,s=document.getElementById(i);f=document.createElement(\'iframe\');f.src=\'//api.flattr.com/button/view/?uid='.$user.'&url='.rawurlencode($thing).'&title='.rawurlencode($ftitle).'\';f.title=\''.$title.'\';f.height=72;f.width=65;f.style.borderWidth=0;s.parentNode.insertBefore(f,s);})(\'fbdu5zs\');</script>';
	//  dynamic button from flattr API
    }
    //  put the widget content together
    $flattrwidget = '<div id="flattr-widget">'.$code.'</div>';
    //  place the widget into the selected aside area
    if ($lr=='right_aside') {
	$b['layout']['region_right_aside'] = $flattrwidget . $b['layout']['region_right_aside'];
    } else {
	$b['layout']['region_aside'] = $flattrwidget . $b['layout']['region_aside'];
    }
}
function flattrwidget_settings_post($a,$s) {
    if(! local_channel() || (! x($_POST,'flattrwidget-submit')))
	return;
    $c = App::get_channel();
    set_pconfig( local_channel(), 'flattrwidget', 'align', $_POST['flattrwidget-align'] );
    set_pconfig( local_channel(), 'flattrwidget', 'sd', $_POST['flattrwidget-static'] );
    $thing = $_POST['flattrwidget-thing'];
    if ($thing == '') {
	$thing = z_root().'/channel/'.$c['channel_address'];
    }
    set_pconfig( local_channel(), 'flattrwidget', 'thing', $thing);
    set_pconfig( local_channel(), 'flattrwidget', 'user', $_POST['flattrwidget-user']);
    $ftitle = $_POST['flattrwidget-thingtitle'];
    if ($ftitle == '') {
	$ftitle = $c['channel_name'].' on The Hubzilla';
    }
    set_pconfig( local_channel(), 'flattrwidget', 'title', $ftitle);
    set_pconfig( local_channel(), 'flattrwidget', 'enable', intval($_POST['flattrwidget-enable']));
    info(t('Flattr widget settings updated.').EOL);
}
function flattrwidget_settings(&$a,&$s) {
	$id = local_channel();
	if (! $id)
		return;

	//App::$page['htmlhead'] .= '<link rel="stylesheet" href="'.z_root().'/addon/flattrwidget/style.css'.'" media="all" />';
	$lr = get_pconfig( $id, 'flattrwidget', 'align');
	$sd = get_pconfig( $id, 'flattrwidget', 'sd');
	$thing = get_pconfig( $id, 'flattrwidget', 'thing');
	$user = get_pconfig( $id, 'flattrwidget', 'user');
	$ftitle = get_pconfig( $id, 'flattrwidget', 'title');
	$enable = intval(get_pconfig(local_channel(),'flattrwidget','enable'));
	$enable_checked = (($enable) ? 1 : false);

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('flattrwidget-user', t('Flattr user'), $user, '')
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('flattrwidget-thing', t('URL of the Thing to flattr'), $thing, t('If empty channel URL is used'))
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('flattrwidget-thingtitle', t('Title of the Thing to flattr'), $ftitle, t('If empty "channel name on The Hubzilla" will be used'))
	));

	$sc .= replace_macros(get_markup_template('field_select.tpl'), array(
		'$field'	=> array('flattrwidget-static', t('Static or dynamic flattr button'), $sd, '', array('static'=>t('static'), 'dynamic'=>t('dynamic')))
	));

	$sc .= replace_macros(get_markup_template('field_select.tpl'), array(
		'$field'	=> array('flattrwidget-align', t('Alignment of the widget'), $lr, '', array('aside'=>t('left'), 'right_aside'=>t('right')))
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('flattrwidget-enable', t('Enable Flattr widget'), $enable_checked, '', array(t('No'),t('Yes'))),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('flattrwidget',t('Flattr Widget Settings'), '', t('Submit')),
		'$content'	=> $sc
	));


}
