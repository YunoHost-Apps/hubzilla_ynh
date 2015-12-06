<?php

function siteinfo_json_init(&$a) {

	$data = get_site_info();
	json_return_and_die($data);

}
