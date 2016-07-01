<?php

function nodeinfo_content(&$a) {


	// We have to lie and say we're redmatrix because the schema was defined a bit too rigidly and

	if(argc() > 1 && argv(1) === '1.0') {
		$arr = array(

			'version' => '1.0',
			'software' => array('name' => 'redmatrix','version' => Zotlabs\project\System::get_project_version()),
			'protocols' => array('inbound' => array('zot'), 'outbound' => array('zot')),
			'services' => array(),
			'openRegistrations' => ((get_config('system','register_policy') === REGISTER_OPEN) ? true : false),
			'usage' => array(
				'users' => array(
					'total' => intval(get_config('statistics_json','total_users')),
					'activeHalfyear' => intval(get_config('statistics_json','active_users_halfyear')),
					'activeMonth' => intval(get_config('statistics_json','active_users_monthly')),
				),
				'localPosts' => intval(get_config('statistics_json','local_posts')),
				'localComments' => intval(get_config('statistics_json','local_comments')),
			)
		);

		if(in_array('diaspora',App::$plugins)) {
			$arr['protocols']['inbound'][] = 'diaspora';
			$arr['protocols']['outbound'][] = 'diaspora';
		}

		if(in_array('gnusoc',App::$plugins)) {
			$arr['protocols']['inbound'][] = 'gnusocial';
			$arr['protocols']['outbound'][] = 'gnusocial';
		}

		if(in_array('friendica',App::$plugins)) {
			$arr['protocols']['inbound'][] = 'friendica';
			$arr['protocols']['outbound'][] = 'friendica';
		}

		$services = array();
		$iservices = array();

		if(in_array('diaspost',App::$plugins))
			$services[] = 'diaspora';
		if(in_array('dwpost',App::$plugins))
			$services[] = 'dreamwidth';
		if(in_array('statusnet',App::$plugins))
			$services[] = 'gnusocial';
		if(in_array('rtof',App::$plugins))
			$services[] = 'friendica';
		if(in_array('gpluspost',App::$plugins))
			$services[] = 'google';
		if(in_array('ijpost',App::$plugins))
			$services[] = 'insanejournal';
		if(in_array('libertree',App::$plugins))
			$services[] = 'libertree';
		if(in_array('pumpio',App::$plugins))
			$services[] = 'pumpio';
		if(in_array('redred',App::$plugins))
			$services[] = 'redmatrix';
		if(in_array('twitter',App::$plugins))
			$services[] = 'twitter';
		if(in_array('wppost',App::$plugins)) {
			$services[] = 'wordpress';
			$iservices[] = 'wordpress';
		}
		if(in_array('xmpp',App::$plugins)) {
			$services[] = 'xmpp';
			$iservices[] = 'xmpp';
		}

		if($services)
			$arr['services']['outbound'] = $services;
		if($iservices)
			$arr['services']['inbound'] = $iservices;



	}


	header('Content-type: application/json');
	echo json_encode($arr);
	killme();


}