<?php
namespace Zotlabs\Module;


class Uexport extends \Zotlabs\Web\Controller {

	function init() {
		if(! local_channel())
			killme();
	
		if(argc() > 1) {
			$channel = \App::get_channel();
	
			require_once('include/channel.php');
	
			if(argc() > 1 && intval(argv(1)) > 1900) {
				$year = intval(argv(1));
			}
	
			if(argc() > 2 && intval(argv(2)) > 0 && intval(argv(2)) <= 12) {
				$month = intval(argv(2));
			}
	
			header('content-type: application/octet_stream');
			header('content-disposition: attachment; filename="' . $channel['channel_address'] . (($year) ? '-' . $year : '') . (($month) ? '-' . $month : '') . '.json"' );
	
			if($year) {
				echo json_encode(identity_export_year(local_channel(),$year,$month));
				killme();
			}
	
			if(argc() > 1 && argv(1) === 'basic') {
				echo json_encode(identity_basic_export(local_channel()));
				killme();
			}
	
			// FIXME - this basically doesn't work in the wild with a channel more than a few months old due to memory and execution time limits.  
			// It probably needs to be built at the CLI and offered to download as a tarball.  Maybe stored in the members dav.
	
			if(argc() > 1 && argv(1) === 'complete') {
				echo json_encode(identity_basic_export(local_channel(),true));
				killme();
			}
		}
	}
		
	function get() {
	
		$y = datetime_convert('UTC',date_default_timezone_get(),'now','Y');
	
		$yearurl = z_root() . '/uexport/' . $y;
		$janurl = z_root() . '/uexport/' . $y . '/1';
		$impurl = '/import_items';
		$o = replace_macros(get_markup_template('uexport.tpl'), array(
			'$title' => t('Export Channel'),
			'$basictitle' => t('Export Channel'),
			'$basic' => t('Export your basic channel information to a file.  This acts as a backup of your connections, permissions, profile and basic data, which can be used to import your data to a new server hub, but does not contain your content.'),
			'$fulltitle' => t('Export Content'),
			'$full' => t('Export your channel information and recent content to a JSON backup that can be restored or imported to another server hub. This backs up all of your connections, permissions, profile data and several months of posts. This file may be VERY large.  Please be patient - it may take several minutes for this download to begin.'),
			'$by_year' => t('Export your posts from a given year.'),
	
			'$extra' => t('You may also export your posts and conversations for a particular year or month. Adjust the date in your browser location bar to select other dates. If the export fails (possibly due to memory exhaustion on your server hub), please try again selecting a more limited date range.'),
			'$extra2' => sprintf( t('To select all posts for a given year, such as this year, visit <a href="%1$s">%2$s</a>'),$yearurl,$yearurl),
			'$extra3' => sprintf( t('To select all posts for a given month, such as January of this year, visit <a href="%1$s">%2$s</a>'),$janurl,$janurl),
			'$extra4' => sprintf( t('These content files may be imported or restored by visiting <a href="%1$s">%2$s</a> on any site containing your channel. For best results please import or restore these in date order (oldest first).'),$impurl,$impurl)
			
		));
	return $o;
	}
	
}
