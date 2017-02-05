<?php

namespace Zotlabs\Module\Settings;


class Display {

		/*
		 * DISPLAY SETTINGS
		 */

	function post() {
		check_form_security_token_redirectOnErr('/settings/display', 'settings_display');

		$themespec = explode(':', \App::$channel['channel_theme']);
		$existing_theme  = $themespec[0];
		$existing_schema = $themespec[1];

		$theme             = ((x($_POST,'theme')) ? notags(trim($_POST['theme']))  : $existing_theme);

		if(! $theme)
			$theme = 'redbasic';

		$mobile_theme      = ((x($_POST,'mobile_theme')) ? notags(trim($_POST['mobile_theme']))  : '');
		$preload_images    = ((x($_POST,'preload_images')) ? intval($_POST['preload_images'])  : 0);
		$user_scalable     = ((x($_POST,'user_scalable')) ? intval($_POST['user_scalable'])  : 0);
		$nosmile           = ((x($_POST,'nosmile')) ? intval($_POST['nosmile'])  : 0); 
		$title_tosource    = ((x($_POST,'title_tosource')) ? intval($_POST['title_tosource'])  : 0);		 
		$channel_list_mode = ((x($_POST,'channel_list_mode')) ? intval($_POST['channel_list_mode']) : 0);
		$network_list_mode = ((x($_POST,'network_list_mode')) ? intval($_POST['network_list_mode']) : 0);
		$manual_update     = ((array_key_exists('manual_update',$_POST)) ? intval($_POST['manual_update']) : 0);
	
		$channel_divmore_height = ((x($_POST,'channel_divmore_height')) ? intval($_POST['channel_divmore_height']) : 400);
		if($channel_divmore_height < 50)
			$channel_divmore_height = 50;
		$network_divmore_height = ((x($_POST,'network_divmore_height')) ? intval($_POST['network_divmore_height']) : 400);
		if($network_divmore_height < 50)
			$network_divmore_height = 50;
	
		$browser_update   = ((x($_POST,'browser_update')) ? intval($_POST['browser_update']) : 0);
		$browser_update   = $browser_update * 1000;
		if($browser_update < 10000)
			$browser_update = 10000;
	
		$itemspage   = ((x($_POST,'itemspage')) ? intval($_POST['itemspage']) : 20);
		if($itemspage > 100)
			$itemspage = 100;
		
		if ($mobile_theme == "---") 
			del_pconfig(local_channel(),'system','mobile_theme');
		else {
			set_pconfig(local_channel(),'system','mobile_theme',$mobile_theme);
		}
	
		set_pconfig(local_channel(),'system','preload_images',$preload_images);
		set_pconfig(local_channel(),'system','user_scalable',$user_scalable);
		set_pconfig(local_channel(),'system','update_interval', $browser_update);
		set_pconfig(local_channel(),'system','itemspage', $itemspage);
		set_pconfig(local_channel(),'system','no_smilies',1-intval($nosmile));
		set_pconfig(local_channel(),'system','title_tosource',$title_tosource);
		set_pconfig(local_channel(),'system','channel_list_mode', $channel_list_mode);
		set_pconfig(local_channel(),'system','network_list_mode', $network_list_mode);
		set_pconfig(local_channel(),'system','channel_divmore_height', $channel_divmore_height);
		set_pconfig(local_channel(),'system','network_divmore_height', $network_divmore_height);
		set_pconfig(local_channel(),'system','manual_conversation_update', $manual_update);
	
		$newschema = '';
		if($theme == $existing_theme){
			// call theme_post only if theme has not been changed
			if( ($themeconfigfile = $this->get_theme_config_file($theme)) != null){
				require_once($themeconfigfile);
				if(class_exists('\\Zotlabs\\Theme\\' . ucfirst($theme) . 'Config')) {
					$clsname = '\\Zotlabs\\Theme\\' . ucfirst($theme) . 'Config';
					$theme_config = new $clsname();
					$schemas = $theme_config->get_schemas();
					if(array_key_exists($_POST['schema'],$schemas))
						$newschema = $_POST['schema'];
					if($newschema === '---')
						$newschema = '';	
					$theme_config->post();
				}
			}
		}

		logger('theme: ' . $theme . (($newschema) ? ':' . $newschema : ''));

		$_SESSION['theme'] = $theme . (($newschema) ? ':' . $newschema : '');
	
		$r = q("UPDATE channel SET channel_theme = '%s' WHERE channel_id = %d",
				dbesc($theme . (($newschema) ? ':' . $newschema : '')),
				intval(local_channel())
		);
		
		call_hooks('display_settings_post', $_POST);
		build_sync_packet();
		goaway(z_root() . '/settings/display' );
		return; // NOTREACHED
	}
	

	function get() {

		$yes_no = array(t('No'),t('Yes'));

		$default_theme = get_config('system','theme');
		if(! $default_theme)
			$default_theme = 'redbasic';

		$themespec = explode(':', \App::$channel['channel_theme']);
		$existing_theme  = $themespec[0];
		$existing_schema = $themespec[1];

		$theme = (($existing_theme) ? $existing_theme : $default_theme);

		$default_mobile_theme = get_config('system','mobile_theme');
		if(! $mobile_default_theme)
			$mobile_default_theme = 'none';
	
		$allowed_themes_str = get_config('system','allowed_themes');
		$allowed_themes_raw = explode(',',$allowed_themes_str);
		$allowed_themes = array();
		if(count($allowed_themes_raw))
			foreach($allowed_themes_raw as $x) 
				if(strlen(trim($x)) && is_dir("view/theme/$x"))
					$allowed_themes[] = trim($x);

			
		$themes = array();
		$files = glob('view/theme/*');
		if($allowed_themes) {
			foreach($allowed_themes as $th) {
				$f = $th;
				$is_experimental = file_exists('view/theme/' . $th . '/experimental');
				$unsupported = file_exists('view/theme/' . $th . '/unsupported');
				$is_mobile = file_exists('view/theme/' . $th . '/mobile');
				$is_library = file_exists('view/theme/'. $th . '/library');
				$mobile_themes["---"] = t("No special theme for mobile devices");
	
				if (!$is_experimental or ($is_experimental && (get_config('experimentals','exp_themes')==1 or get_config('experimentals','exp_themes')===false))){ 
					$theme_name = (($is_experimental) ?  sprintf(t('%s - (Experimental)'), $f) : $f);
					if (! $is_library) {
						if($is_mobile) {
							$mobile_themes[$f] = $themes[$f] = $theme_name . ' (' . t('mobile') . ')';
						}
						else {
							$mobile_themes[$f] = $themes[$f] = $theme_name;
						}
					}
				}

			}
		}

		$theme_selected = ((array_key_exists('theme',$_SESSION) && $_SESSION['theme']) ? $_SESSION['theme'] : $theme);

		$mobile_theme_selected = (!x($_SESSION,'mobile_theme')? $default_mobile_theme : $_SESSION['mobile_theme']);
	
		$preload_images = get_pconfig(local_channel(),'system','preload_images');
		$preload_images = (($preload_images===false)? '0': $preload_images); // default if not set: 0
	
		$user_scalable = get_pconfig(local_channel(),'system','user_scalable');
		$user_scalable = (($user_scalable===false)? '0': $user_scalable); // default if not set: 0
			
		$browser_update = intval(get_pconfig(local_channel(), 'system','update_interval'));
		$browser_update = (($browser_update == 0) ? 80 : $browser_update / 1000); // default if not set: 40 seconds
	
		$itemspage = intval(get_pconfig(local_channel(), 'system','itemspage'));
		$itemspage = (($itemspage > 0 && $itemspage < 101) ? $itemspage : 20); // default if not set: 20 items
			
		$nosmile = get_pconfig(local_channel(),'system','no_smilies');
		$nosmile = (($nosmile===false)? '0': $nosmile); // default if not set: 0
	
		$title_tosource = get_pconfig(local_channel(),'system','title_tosource');
		$title_tosource = (($title_tosource===false)? '0': $title_tosource); // default if not set: 0
	
		$theme_config = "";
		if(($themeconfigfile = $this->get_theme_config_file($theme)) != null){
			require_once($themeconfigfile);
			if(class_exists('\\Zotlabs\\Theme\\' . ucfirst($theme) . 'Config')) {
				$clsname = '\\Zotlabs\\Theme\\' . ucfirst($theme) . 'Config';
				$thm_config = new $clsname();
				$schemas = $thm_config->get_schemas();
				$theme_config = $thm_config->get();
			}
		}

		// logger('schemas: ' . print_r($schemas,true));
			
		$tpl = get_markup_template("settings_display.tpl");
		$o = replace_macros($tpl, array(
			'$ptitle' 	=> t('Display Settings'),
			'$d_tset'       => t('Theme Settings'), 
			'$d_ctset'      => t('Custom Theme Settings'), 
			'$d_cset'       => t('Content Settings'),
			'$form_security_token' => get_form_security_token("settings_display"),
			'$submit' 	=> t('Submit'),
			'$baseurl' => z_root(),
			'$uid' => local_channel(),
			
			'$theme'	=> (($themes) ? array('theme', t('Display Theme:'), $theme_selected, '', $themes, 'preview') : false),
			'$schema'   => array('schema', t('Select scheme'), $existing_schema, '' , $schemas),

			'$mobile_theme' => (($mobile_themes) ? array('mobile_theme', t('Mobile Theme:'), $mobile_theme_selected, '', $mobile_themes, '') : false),
			'$preload_images' => array('preload_images', t("Preload images before rendering the page"), $preload_images, t("The subjective page load time will be longer but the page will be ready when displayed"), $yes_no),
			'$user_scalable' => array('user_scalable', t("Enable user zoom on mobile devices"), $user_scalable, '', $yes_no),
			'$ajaxint'   => array('browser_update',  t("Update browser every xx seconds"), $browser_update, t('Minimum of 10 seconds, no maximum')),
			'$itemspage'   => array('itemspage',  t("Maximum number of conversations to load at any time:"), $itemspage, t('Maximum of 100 items')),
			'$nosmile'	=> array('nosmile', t("Show emoticons (smilies) as images"), 1-intval($nosmile), '', $yes_no),
			'$manual_update'	=> array('manual_update', t('Manual conversation updates'), channel_manual_conv_update(local_channel()), t('Default is automatic, which may increase screen jumping'), $yes_no),
			'$title_tosource'	=> array('title_tosource', t("Link post titles to source"), $title_tosource, '', $yes_no),
			'$layout_editor' => t('System Page Layout Editor - (advanced)'),
			'$theme_config' => $theme_config,
			'$expert' => feature_enabled(local_channel(),'advanced_theming'),
			'$channel_list_mode' => array('channel_list_mode', t('Use blog/list mode on channel page'), get_pconfig(local_channel(),'system','channel_list_mode'), t('(comments displayed separately)'), $yes_no),
			'$network_list_mode' => array('network_list_mode', t('Use blog/list mode on grid page'), get_pconfig(local_channel(),'system','network_list_mode'), t('(comments displayed separately)'), $yes_no),
			'$channel_divmore_height' => array('channel_divmore_height', t('Channel page max height of content (in pixels)'), ((get_pconfig(local_channel(),'system','channel_divmore_height')) ? get_pconfig(local_channel(),'system','channel_divmore_height') : 400), t('click to expand content exceeding this height')),
			'$network_divmore_height' => array('network_divmore_height', t('Grid page max height of content (in pixels)'), ((get_pconfig(local_channel(),'system','network_divmore_height')) ? get_pconfig(local_channel(),'system','network_divmore_height') : 400) , t('click to expand content exceeding this height')),
	
	
		));

		call_hooks('display_settings',$o);			
		return $o;
	}


	function get_theme_config_file($theme){

		$base_theme = \App::$theme_info['extends'];
	
		if (file_exists("view/theme/$theme/php/config.php")){
			return "view/theme/$theme/php/config.php";
		} 
		if (file_exists("view/theme/$base_theme/php/config.php")){
			return "view/theme/$base_theme/php/config.php";
		}
		return null;
	}



			
}
