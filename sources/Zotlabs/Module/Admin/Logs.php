<?php

namespace Zotlabs\Module\Admin;


class Logs {


	
	/**
	 * @brief POST handler for logs admin page.
	 *
	 */

	function post() {
		if (x($_POST, 'page_logs')) {
			check_form_security_token_redirectOnErr('/admin/logs', 'admin_logs');
	
			$logfile   = ((x($_POST,'logfile'))   ? notags(trim($_POST['logfile'])) : '');
			$debugging = ((x($_POST,'debugging')) ? true : false);
			$loglevel  = ((x($_POST,'loglevel'))  ? intval(trim($_POST['loglevel'])) : 0);
	
			set_config('system','logfile', $logfile);
			set_config('system','debugging',  $debugging);
			set_config('system','loglevel', $loglevel);
		}
	
		info( t('Log settings updated.') );
		goaway(z_root() . '/admin/logs' );
	}
	
	/**
	 * @brief Logs admin page.
	 *
	 * @return string
	 */

	function get() {
	
		$log_choices = Array(
			LOGGER_NORMAL => 'Normal',
			LOGGER_TRACE => 'Trace',
			LOGGER_DEBUG => 'Debug',
			LOGGER_DATA => 'Data',
			LOGGER_ALL => 'All'
		);
	
		$t = get_markup_template('admin_logs.tpl');
	
		$f = get_config('system', 'logfile');
	
		$data = '';
	
		if(!file_exists($f)) {
			$data = t("Error trying to open <strong>$f</strong> log file.\r\n<br/>Check to see if file $f exist and is 
	readable.");
		}
		else {
			$fp = fopen($f, 'r');
			if(!$fp) {
				$data = t("Couldn't open <strong>$f</strong> log file.\r\n<br/>Check to see if file $f is readable.");
			}
			else {
				$fstat = fstat($fp);
				$size = $fstat['size'];
				if($size != 0)
				{
					if($size > 5000000 || $size < 0)
						$size = 5000000;
					$seek = fseek($fp,0-$size,SEEK_END);
					if($seek === 0) {
						$data = escape_tags(fread($fp,$size));
						while(! feof($fp))
							$data .= escape_tags(fread($fp,4096));
					}
				}
				fclose($fp);
			}
		}
	
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Logs'),
			'$submit' => t('Submit'),
			'$clear' => t('Clear'),
			'$data' => $data,
			'$baseurl' => z_root(),
			'$logname' =>  get_config('system','logfile'),
	
			// name, label, value, help string, extra data...
			'$debugging' => array('debugging', t("Debugging"),get_config('system','debugging'), ""),
			'$logfile'   => array('logfile', t("Log file"), get_config('system','logfile'), t("Must be writable by web server. Relative to your top-level webserver directory.")),
			'$loglevel'  => array('loglevel', t("Log level"), get_config('system','loglevel'), "", $log_choices),
	
			'$form_security_token' => get_form_security_token('admin_logs'),
		));
	}
	


}