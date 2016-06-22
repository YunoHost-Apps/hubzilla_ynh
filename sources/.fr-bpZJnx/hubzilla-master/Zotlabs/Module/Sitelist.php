<?php
namespace Zotlabs\Module; /** @file */


class Sitelist extends \Zotlabs\Web\Controller {

	function init() {
	
		$start = (($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
		$limit = ((intval($_REQUEST['limit'])) ? intval($_REQUEST['limit']) : 30);
		$order = (($_REQUEST['order']) ? $_REQUEST['order'] : 'random');
		$open = (($_REQUEST['open']) ? intval($_REQUEST['open']) : false);
	
	
		$sql_order = " order by site_url ";
		$rand = db_getfunc('rand');
		if($order == 'random')
			$sql_order = " order by $rand ";
	
		$sql_limit = " LIMIT $limit OFFSET $start ";
	
		$sql_extra = "";
		if($open)
			$sql_extra = " and site_register = " . intval(REGISTER_OPEN) . " ";
	
		$realm = get_directory_realm();
		if($realm == DIRECTORY_REALM) {
			$sql_extra .= " and ( site_realm = '" . dbesc($realm) . "' or site_realm = '') ";
		}
		else
			$sql_extra .= " and site_realm = '" . dbesc($realm) . "' ";
	
		$result = array('success' => false);
	
		$r = q("select count(site_url) as total from site where site_type = %d $sql_extra ",
			intval(SITE_TYPE_ZOT)
		);
		
		if($r)
			$result['total'] = intval($r[0]['total']);
	
		$result['start'] = $start;
		$result['limit'] = $limit;	
	
		$r = q("select * from site where site_type = %d $sql_extra $sql_order $sql_limit",
			intval(SITE_TYPE_ZOT)
		);
	
		$result['results'] = 0;
		$result['entries'] = array();
	
		if($r) {
			$result['success'] = true;		
			$result['results'] = count($r);
			
			foreach($r as $rr) {
				$result['entries'][] = array('url' => $rr['site_url']);
			}
	
		}
	
		echo json_encode($result);
		killme();
				
	
	}
}
