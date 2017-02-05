<?php
namespace Zotlabs\Module;


class Pubsites extends \Zotlabs\Web\Controller {

	function get() {
		require_once('include/dir_fns.php'); 
		$dirmode = intval(get_config('system','directory_mode'));
	
		if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
			$url = z_root() . '/dirsearch';
		}
		if(! $url) {
			$directory = find_upstream_directory($dirmode);
			$url = $directory['url'] . '/dirsearch';
		}
		$url .= '/sites';

		$rating_enabled = get_config('system','rating_enabled');

		$o .= '<div class="generic-content-wrapper">';
	
		$o .= '<div class="section-title-wrapper"><h2>' . t('Public Hubs') . '</h2></div>';
	
		$o .= '<div class="section-content-tools-wrapper"><div class="descriptive-text">' . 
			t('The listed hubs allow public registration for the $Projectname network. All hubs in the network are interlinked so membership on any of them conveys membership in the network as a whole. Some hubs may require subscription or provide tiered service plans. The hub itself <strong>may</strong> provide additional details.') . '</div>' . EOL;
	
		$ret = z_fetch_url($url);
		if($ret['success']) {
			$j = json_decode($ret['body'],true);
			if($j) {
				$o .= '<table class="table table-striped table-hover"><tr><td>' . t('Hub URL') . '</td><td>' . t('Access Type') . '</td><td>' . t('Registration Policy') . '</td><td>' . t('Stats') . '</td><td>' . t('Software') . '</td>';
				if($rating_enabled)
					$o .= '<td colspan="2">' . t('Ratings') . '</td>';
				$o .= '</tr>';
				if($j['sites']) {
					foreach($j['sites'] as $jj) {
						if(! \Zotlabs\Lib\System::compatible_project($jj['project']))
							continue;
						if(strpos($jj['version'],' ')) {
							$x = explode(' ', $jj['version']);
							if($x[1])
								$jj['version'] = $x[1];
						}
						$m = parse_url($jj['url']);
						$host = strtolower(substr($jj['url'],strpos($jj['url'],'://')+3));
						$rate_links = ((local_channel()) ? '<td><a href="rate?f=&target=' . $host . '" class="btn-btn-default"><i class="fa fa-check-square-o"></i> ' . t('Rate') . '</a></td>' : '');
						$location = '';
						if(!empty($jj['location'])) { 
							$location = '<p title="' . t('Location') . '" style="margin: 5px 5px 0 0; text-align: right"><i class="fa fa-globe"></i> ' . $jj['location'] . '</p>'; 
							}
						else {
							$location = '<br />&nbsp;';
							}
						$urltext = str_replace(array('https://'), '', $jj['url']);
						$o .= '<tr><td><a href="'. (($jj['sellpage']) ? $jj['sellpage'] : $jj['url'] . '/register' ) . '" ><i class="fa fa-link"></i> ' . $urltext . '</a>' . $location . '</td><td>' . $jj['access'] . '</td><td>' . $jj['register'] . '</td><td>' . '<a target="stats" href="https://hubchart-tarine.rhcloud.com/hub.jsp?hubFqdn=' . $m['host'] . '"><i class="fa fa-area-chart"></i></a></td><td>' . ucwords($jj['project']) . (($jj['version']) ? ' ' . $jj['version'] : '') . '</td>';
						if($rating_enabled)
							$o .= '<td><a href="ratings/' . $host . '" class="btn-btn-default"><i class="fa fa-eye"></i> ' . t('View') . '</a></td>' . $rate_links ;
						$o .=  '</tr>';
					}
				}
		
				$o .= '</table>';
	
				$o .= '</div></div>';
	
			}
		}
		return $o;
	}
	
}
