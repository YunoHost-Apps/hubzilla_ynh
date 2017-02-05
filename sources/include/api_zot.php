<?php

	function zot_api_init() {
        api_register_func('api/red/version','api_zot_version',false);
        api_register_func('api/z/1.0/version','api_zot_version',false);
		api_register_func('api/export/basic','api_export_basic', true);
		api_register_func('api/red/channel/export/basic','api_export_basic', true);
		api_register_func('api/z/1.0/channel/export/basic','api_export_basic', true);
		api_register_func('api/red/channel/stream','api_channel_stream', true);
		api_register_func('api/z/1.0/channel/stream','api_channel_stream', true);
		api_register_func('api/red/files','api_attach_list', true);
		api_register_func('api/z/1.0/files','api_attach_list', true);
		api_register_func('api/red/filemeta', 'api_file_meta', true);
		api_register_func('api/z/1.0/filemeta', 'api_file_meta', true);
		api_register_func('api/red/filedata', 'api_file_data', true);
		api_register_func('api/z/1.0/filedata', 'api_file_data', true);
		api_register_func('api/red/file/export', 'api_file_export', true);
		api_register_func('api/z/1.0/file/export', 'api_file_export', true);
		api_register_func('api/red/file', 'api_file_detail', true);
		api_register_func('api/z/1.0/file', 'api_file_detail', true);
		api_register_func('api/red/albums','api_albums', true);
		api_register_func('api/z/1.0/albums','api_albums', true);
		api_register_func('api/red/photos','api_photos', true);
		api_register_func('api/z/1.0/photos','api_photos', true);
		api_register_func('api/red/photo', 'api_photo_detail', true);
		api_register_func('api/z/1.0/photo', 'api_photo_detail', true);
		api_register_func('api/red/group_members','api_group_members', true);
		api_register_func('api/z/1.0/group_members','api_group_members', true);
		api_register_func('api/red/group','api_group', true);
		api_register_func('api/z/1.0/group','api_group', true);
		api_register_func('api/red/xchan','api_red_xchan',true);
		api_register_func('api/z/1.0/xchan','api_red_xchan',true);
		api_register_func('api/red/item/update','zot_item_update', true);
		api_register_func('api/z/1.0/item/update','zot_item_update', true);
		api_register_func('api/red/item/full','red_item', true);
		api_register_func('api/z/1.0/item/full','red_item', true);

		api_register_func('api/z/1.0/network/stream','api_network_stream', true);
		api_register_func('api/z/1.0/abook','api_zot_abook_xchan',true);
		api_register_func('api/z/1.0/abconfig','api_zot_abconfig',true);
		api_register_func('api/z/1.0/perm_allowed','api_zot_perm_allowed',true);

		return;
	}


	function api_zot_version($type) {

		if($type === 'xml') {
			header('Content-type: application/xml');
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" . '<version>' . Zotlabs\Lib\System::get_project_version() . '</version>' . "\r\n";
			killme();
		}
		elseif($type === 'json') {
			header('Content-type: application/json');
			echo '"' . Zotlabs\Lib\System::get_project_version() . '"';
			killme();
		}
	}



	/*
	 * Red basic channel export
	 */

	function api_export_basic($type) {
		if(api_user() === false) {
			logger('api_export_basic: no user');
			return false;
		}
		
		json_return_and_die(identity_basic_export(api_user(),(($_REQUEST['posts']) ? intval($_REQUEST['posts']) : 0 )));	
	}


	function api_network_stream($type) {
		if(api_user() === false) {
			logger('api_channel_stream: no user');
			return false;
		}

		$channel = channelx_by_n(api_user());
		if(! $channel)
			return false;


		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			// json_return_and_die(post_activity_item($_REQUEST));
		}
		else {
			$mindate = (($_REQUEST['mindate']) ? datetime_convert('UTC','UTC',$_REQUEST['mindate']) : '');
        	if(! $mindate)
            	$mindate = datetime_convert('UTC','UTC', 'now - 14 days');

			$arr = $_REQUEST;
			$ret = [];	
			$i = items_fetch($arr,App::get_channel(),get_observer_hash());
			if($i) {
				foreach($i as $iv) {
					$ret[] = encode_item($iv);
				}
			}

			json_return_and_die($ret);
		}
	}






	function api_channel_stream($type) {
		if(api_user() === false) {
			logger('api_channel_stream: no user');
			return false;
		}

		$channel = channelx_by_n(api_user());
		if(! $channel)
			return false;


		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			json_return_and_die(post_activity_item($_REQUEST));
		}
		else {
			$mindate = (($_REQUEST['mindate']) ? datetime_convert('UTC','UTC',$_REQUEST['mindate']) : '');
        	if(! $mindate)
            	$mindate = datetime_convert('UTC','UTC', 'now - 14 days');

			json_return_and_die(zot_feed($channel['channel_id'],$channel['channel_hash'],[ 'mindate' => $mindate ]));
		}
	}

	function api_attach_list($type) {
		if(api_user() === false) 
			return false;

		logger('api_user: ' . api_user());
		$hash     = ((array_key_exists('filehash',$_REQUEST)) ? $_REQUEST['filehash'] : '');
		$filename = ((array_key_exists('filename',$_REQUEST)) ? $_REQUEST['filename'] : '');
		$filetype = ((array_key_exists('filetype',$_REQUEST)) ? $_REQUEST['filetype'] : '');
		$start    = ((array_key_exists('start',$_REQUEST))    ? intval($_REQUEST['start'])   : 0);
		$records  = ((array_key_exists('records',$_REQUEST))  ? intval($_REQUEST['records']) : 0);

		$x = attach_list_files(api_user(),get_observer_hash(),$hash,$filename,$filetype,'created asc',$start,$records);
		if($start || $records) {
			$x['start'] = $start;
			$x['records'] = count($x['results']);
		}

		json_return_and_die($x);
	}


	function api_file_meta($type) {
		if(api_user() === false)
			return false;
		if(! $_REQUEST['file_id']) return false;
		$r = q("select * from attach where uid = %d and hash = '%s' limit 1",
			intval(api_user()),
			dbesc($_REQUEST['file_id'])
		);
		if($r) {
			unset($r[0]['content']);				
			$ret = array('attach' => $r[0]);
			json_return_and_die($ret);
		}
		killme();
	}



	function api_file_data($type) {
		if(api_user() === false)
			return false;
		if(! $_REQUEST['file_id']) return false;
		$start = (($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
		$length = (($_REQUEST['length']) ? intval($_REQUEST['length']) : 0);

		$r = q("select * from attach where uid = %d and hash like '%s' limit 1",
			intval(api_user()),
			dbesc($_REQUEST['file_id'] . '%')
		);
		if($r) {
			$ptr = $r[0];
			if($length === 0)
				$length = intval($ptr['filesize']);

			if($ptr['is_dir'])
				$ptr['content'] = '';
			elseif(! intval($r[0]['os_storage'])) {
				$ptr['start'] = $start;
				$x = substr(dbunescbin($ptr['content']),$start,$length);
				$ptr['length'] = strlen($x);
				$ptr['content'] = base64_encode($x);
			}
			else {
				$fp = fopen(dbunescbin($ptr['content']),'r');
				if($fp) {
					$seek = fseek($fp,$start,SEEK_SET);
					$x = fread($fp,$length);
					$ptr['start'] = $start;
					$ptr['length'] = strlen($x);
					$ptr['content'] = base64_encode($x);
				}
			}
				
			$ret = array('attach' => $ptr);
			json_return_and_die($ret);
		}
		killme();
	}


	function api_file_export($type) {
		if(api_user() === false)
			return false;
		if(! $_REQUEST['file_id'])
			return false;

		$ret = attach_export_data(api_user(),$_REQUEST['file_id']);
		if($ret) {
			json_return_and_die($ret);
		}
		killme();
	}


	function api_file_detail($type) {
		if(api_user() === false)
			return false;
		if(! $_REQUEST['file_id']) return false;
		$r = q("select * from attach where uid = %d and hash = '%s' limit 1",
			intval(api_user()),
			dbesc($_REQUEST['file_id'])
		);
		if($r) {
			if($r[0]['is_dir'])
				$r[0]['content'] = '';
			elseif(intval($r[0]['os_storage'])) 
				$r[0]['content'] = base64_encode(file_get_contents(dbunescbin($r[0]['content'])));
			else
				$r[0]['content'] = base64_encode(dbunescbin($r[0]['content']));
				
			$ret = array('attach' => $r[0]);
			json_return_and_die($ret);
		}
		killme();
	}



	function api_albums($type) {
		if(api_user() === false)
			return false;
		json_return_and_die(photos_albums_list(App::get_channel(),App::get_observer()));
	}

	function api_photos($type) {
		if(api_user() === false)
			return false;
		$album = $_REQUEST['album'];
		json_return_and_die(photos_list_photos(App::get_channel(),App::get_observer(),$album));
	}

	function api_photo_detail($type) {
		if(api_user() === false)
			return false;
		if(! $_REQUEST['photo_id']) return false;
		$scale = ((array_key_exists('scale',$_REQUEST)) ? intval($_REQUEST['scale']) : 0);
		$r = q("select * from photo where uid = %d and resource_id = '%s' and imgscale = %d limit 1",
			intval(local_channel()),
			dbesc($_REQUEST['photo_id']),
			intval($scale)
		);
		if($r) {
            $data = dbunescbin($r[0]['content']);
			if(array_key_exists('os_storage',$r[0]) && intval($r[0]['os_storage']))
				$data = file_get_contents($data);
			$r[0]['content'] = base64_encode($data);
			$ret = array('photo' => $r[0]);
			$i = q("select id from item where uid = %d and resource_type = 'photo' and resource_id = '%s' limit 1",
				intval(local_channel()),
				dbesc($_REQUEST['photo_id'])
			);
			if($i) {
				$ii = q("select * from item where parent = %d order by id",
					intval($i[0]['id'])
				);
				if($ii) {
					xchan_query($ii,true,0);
					$ii = fetch_post_tags($ii,true);
					if($ii) {
						$ret['item'] = array();
						foreach($ii as $iii)
							$ret['item'][] = encode_item($iii,true);
					}
				}
			}

			json_return_and_die($ret);
		}
		killme();
	}

	function api_group_members($type) {
		if(api_user() === false)
			return false;

		$r = null;

		if($_REQUEST['group_id']) {
			$r = q("select * from groups where uid = %d and id = %d limit 1",
				intval(api_user()),
				intval($_REQUEST['group_id'])
			);
		}
		elseif($_REQUEST['group_name']) {
			$r = q("select * from groups where uid = %d and gname = '%s' limit 1",
				intval(api_user()),
				dbesc($_REQUEST['group_name'])
			);
		}

		if($r) {
			$x = q("select * from group_member left join abook on abook_xchan = xchan and abook_channel = group_member.uid left join xchan on group_member.xchan = xchan.xchan_hash 
				where gid = %d",
				intval($r[0]['id'])
			);
			json_return_and_die($x);
		}

	}

	function api_group($type) {
		if(api_user() === false)
			return false;

		$r = q("select * from groups where uid = %d",
			intval(api_user())
		);
		json_return_and_die($r);
	}


	function api_red_xchan($type) {
		if(api_user() === false)
			return false;
		logger('api_xchan');
		require_once('include/hubloc.php');

		if($_SERVER['REQUEST_METHOD'] === 'POST') {
			// $r = xchan_store($_REQUEST);
		}
		$r = xchan_fetch($_REQUEST);
		json_return_and_die($r);
	};

	function api_zot_abook_xchan($type) {
		logger('api_abook_xchan');

		if(api_user() === false)
			return false;

		$sql_extra = ((array_key_exists('abook_id',$_REQUEST) && intval($_REQUEST['abook_id'])) ? ' and abook_id = ' . intval($_REQUEST['abook_id']) . ' ' : '');
		if($_SERVER['REQUEST_METHOD'] === 'POST') {
			// update
		}
		$r = q("select * from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d $sql_extra ",
			intval(api_user())
		);

		json_return_and_die($r);
	};

	function api_zot_abconfig($type) {

		if(api_user() === false)
			return false;

		$sql_extra = ((array_key_exists('abook_id',$_REQUEST) && intval($_REQUEST['abook_id'])) ? ' and abook_id = ' . intval($_REQUEST['abook_id']) . ' ' : '');
		if($_SERVER['REQUEST_METHOD'] === 'POST') {
			// update
		}
		$r = q("select abconfig.* from abconfig left join abook on abook_xchan = abconfig.xchan and abook_channel = abconfig.chan where abconfig.chan = %d $sql_extra ",
			intval(api_user())
		);

		json_return_and_die($r);

	}

	function api_zot_perm_allowed($type) {
		if(api_user() === false)
			return false;

		$perm = ((array_key_exists('perm',$_REQUEST)) ? $_REQUEST['perm'] : '');

		if(array_key_exists('abook_id',$_REQUEST) && intval($_REQUEST['abook_id'])) {
			$r = q("select abook_xchan as hash from abook where abook_id = %d and abook_channel = %d limit 1",
				intval($_REQUEST['abook_id']),
				intval(api_user())
			);
		}
		else {
			$r = xchan_fetch($_REQUEST);
		}

		$x = false;

		if($r) {
			if($perm)
				$x = [ [ 'perm' => $perm, 'allowed' => perm_is_allowed(api_user(), $r[0]['hash'], $perm)] ];
			else {
				$x = [];
				$p = get_all_perms(api_user(),$r[0]['hash']);
				if($p) {
					foreach($p as $k => $v)
						$x[] = [ 'perm' => $k, 'allowed' => $v ];
				}
			}
		}
		
		json_return_and_die($x);

	}

	function zot_item_update($type) {

		if (api_user() === false) {
			logger('api_red_item_store: no user');
			return false;
		}

		logger('api_red_item_store: REQUEST ' . print_r($_REQUEST,true));
		logger('api_red_item_store: FILES ' . print_r($_FILES,true));


		// set this so that the item_post() function is quiet and doesn't redirect or emit json

		$_REQUEST['api_source'] = true;
		$_REQUEST['profile_uid'] = api_user();

		if(x($_FILES,'media')) {
			$_FILES['userfile'] = $_FILES['media'];
			// upload the image if we have one
			$mod = new Zotlabs\Module\Wall_attach();
			$media = $mod->post();
			if($media)
				$_REQUEST['body'] .= "\n\n" . $media;
		}

		$mod = new Zotlabs\Module\Item();
		$x = $mod->post();	
		json_return_and_die($x);
	}



	function red_item($type) {

		if (api_user() === false) {
			logger('api_red_item_full: no user');
			return false;
		}

		if($_REQUEST['mid']) {
			$arr = array('mid' => $_REQUEST['mid']);
		}
		elseif($_REQUEST['item_id']) {
			$arr = array('item_id' => $_REQUEST['item_id']);
		}
		else
			json_return_and_die(array());

		$arr['start'] = 0;
		$arr['records'] = 999999;
		$arr['item_type'] = '*';

		$i = items_fetch($arr,App::get_channel(),get_observer_hash());

		if(! $i)
			json_return_and_die(array());

		$ret = array();
		$tmp = array();
		foreach($i as $ii) {
			$tmp[] = encode_item($ii,true);
		}
		$ret['item'] = $tmp;	
					 
		json_return_and_die($ret);
	}



