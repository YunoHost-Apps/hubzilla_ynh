<?php
namespace Zotlabs\Module;





class Pconfig extends \Zotlabs\Web\Controller {

	function post() {
	
		if(! local_channel())
			return;
	
	
	   if($_SESSION['delegate'])
	        return;
	
		check_form_security_token_redirectOnErr('/pconfig', 'pconfig');
	
		$cat = trim(escape_tags($_POST['cat']));
		$k = trim(escape_tags($_POST['k']));
		$v = trim($_POST['v']);
	
		if(in_array(argv(2),$this->disallowed_pconfig())) {
			notice( t('This setting requires special processing and editing has been blocked.') . EOL);
			return;
		}
		
		if(strpos($k,'password') !== false) {
			$v = z_obscure($v);
		}
	
		set_pconfig(local_channel(),$cat,$k,$v);
		build_sync_packet();
	
		goaway(z_root() . '/pconfig/' . $cat . '/' .  $k);
	
	}
	
	
		function get() {
	
		if(! local_channel()) {
			return login();
		}
	
		$content = '<h3>' . t('Configuration Editor') . '</h3>';
		$content .= '<div class="descriptive-paragraph">' . t('Warning: Changing some settings could render your channel inoperable. Please leave this page unless you are comfortable with and knowledgeable about how to correctly use this feature.') . '</div>' . EOL . EOL;
	
	
	
		if(argc() == 3) {
			$content .= '<a href="pconfig">pconfig[' . local_channel() . ']</a>' . EOL;
			$content .= '<a href="pconfig/' . escape_tags(argv(1)) . '">pconfig[' . local_channel() . '][' . escape_tags(argv(1)) . ']</a>' . EOL . EOL;
			$content .= '<a href="pconfig/' . escape_tags(argv(1)) . '/' . escape_tags(argv(2)) . '" >pconfig[' . local_channel() . '][' . escape_tags(argv(1)) . '][' . escape_tags(argv(2)) . ']</a> = ' . get_pconfig(local_channel(),escape_tags(argv(1)),escape_tags(argv(2))) . EOL;
	
			if(in_array(argv(2),$this->disallowed_pconfig())) {
				notice( t('This setting requires special processing and editing has been blocked.') . EOL);
				return $content;
			}
			else
				$content .= $this->pconfig_form(escape_tags(argv(1)),escape_tags(argv(2)));
		}
	
	
		if(argc() == 2) {
			$content .= '<a href="pconfig">pconfig[' . local_channel() . ']</a>' . EOL;
			load_pconfig(local_channel(),escape_tags(argv(1)));
			foreach(\App::$config[local_channel()][escape_tags(argv(1))] as $k => $x) {
				$content .= '<a href="pconfig/' . escape_tags(argv(1)) . '/' . $k . '" >pconfig[' . local_channel() . '][' . escape_tags(argv(1)) . '][' . $k . ']</a> = ' . escape_tags($x) . EOL;
			}
		}
	
		if(argc() == 1) {
	
			$r = q("select * from pconfig where uid = " . local_channel());
			if($r) {
				foreach($r as $rr) {
					$content .= '<a href="' . 'pconfig/' . escape_tags($rr['cat']) . '/' . escape_tags($rr['k']) . '" >pconfig[' . local_channel() . '][' . escape_tags($rr['cat']) . '][' . escape_tags($rr['k']) . ']</a> = ' . escape_tags($rr['v']) . EOL;
				}
			}
		}
		return $content;
	
	}
	
	
	function pconfig_form($cat,$k) {
	
		$o = '<form action="pconfig" method="post" >';
		$o .= '<input type="hidden" name="form_security_token" value="' . get_form_security_token('pconfig') . '" />';
	
		$v = get_pconfig(local_channel(),$cat,$k);
		if(strpos($k,'password') !== false) 
			$v = z_unobscure($v);
	
		$o .= '<input type="hidden" name="cat" value="' . $cat . '" />';
		$o .= '<input type="hidden" name="k" value="' . $k . '" />';
	
		if(strpos($v,"\n"))
			$o .= '<textarea name="v" >' . escape_tags($v) . '</textarea>';
	 	else
			$o .= '<input type="text" name="v" value="' . escape_tags($v) . '" />';
	
		$o .= EOL . EOL; 
		$o .= '<input type="submit" name="submit" value="' . t('Submit') . '" />';
		$o .= '</form>';
	
		return $o;
	
	}



	function disallowed_pconfig() {
		return array(
			'permissions_role'
		);
	}
	
}
