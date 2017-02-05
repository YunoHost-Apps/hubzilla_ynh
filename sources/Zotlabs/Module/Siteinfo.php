<?php
namespace Zotlabs\Module;


class Siteinfo extends \Zotlabs\Web\Controller {

	function init() {
		if (argv(1) ===  'json') {
			$data = get_site_info();
			json_return_and_die($data);
		}
	}
	
	
	
	function get() {
	
		$siteinfo = replace_macros(get_markup_template('siteinfo.tpl'),
			[ 
				'$title' => t('About this site'),
				'$sitenametxt' => t('Site Name'),
				'$sitename' => \Zotlabs\Lib\System::get_site_name(),
				'$headline' => t('Site Information'),
				'$site_about' => bbcode(get_config('system','siteinfo')),
				'$admin_headline' => t('Administrator'),
				'$admin_about' => bbcode(get_config('system','admininfo')),
				'$terms' => t('Terms of Service'),
				'$prj_header' => t('Software and Project information'),
				'$prj_name' => t('This site is powered by $Projectname'),
				'$prj_transport' => t('Federated and decentralised networking and identity services provided by Zot'),
				'$transport_link' => '<a href="https://zotlabs.com">https://zotlabs.com</a>',
				'$prj_version' => ((get_config('system','hidden_version_siteinfo')) ? '' : sprintf( t('Version %s'), \Zotlabs\Lib\System::get_project_version())),
				'$prj_linktxt' => t('Project homepage'),
				'$prj_srctxt' => t('Developer homepage'),
				'$prj_link' => \Zotlabs\Lib\System::get_project_link(),
				'$prj_src' => \Zotlabs\Lib\System::get_project_srclink(),
			]
		);

		call_hooks('about_hook', $siteinfo); 	

		return $siteinfo;

	}

	
}
