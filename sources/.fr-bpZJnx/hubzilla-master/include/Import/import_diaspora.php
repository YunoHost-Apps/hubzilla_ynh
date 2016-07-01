<?php

require_once('include/bb2diaspora.php');
require_once('include/group.php');
require_once('include/follow.php');
require_once('include/photo/photo_driver.php');

function import_diaspora($data) {

	$account = App::get_account();
	if(! $account)
		return false;

	$address = escape_tags($data['user']['username']);
	if(! $address) {
		notice( t('No username found in import file.') . EOL);
		return false;
	}

	$r = q("select * from channel where channel_address = '%s' limit 1",
		dbesc($address)
	);
	if($r) {
		// try at most ten times to generate a unique address.
		$x = 0;
		$found_unique = false;
		do {
			$tmp = $address . mt_rand(1000,9999);
			$r = q("select * from channel where channel_address = '%s' limit 1",
				dbesc($tmp)
			);
			if(! $r) {
				$address = $tmp;
				$found_unique = true;
				break;
			}
			$x ++;
		} while ($x < 10);
		if(! $found_unique) {
			logger('import_diaspora: duplicate channel address. randomisation failed.');
			notice( t('Unable to create a unique channel address. Import failed.') . EOL);
			return;
		}
	}		


	$c = create_identity(array(
		'name' => escape_tags($data['user']['name']),
		'nickname' => $address,
		'account_id' => $account['account_id'],
		'permissions_role' => 'social'
	));
	
	if(! $c['success'])
		return;

	$channel_id = $c['channel']['channel_id'];

	// Hubzilla only: Turn on the Diaspora protocol so that follow requests will be sent.

	set_pconfig($channel_id,'system','diaspora_allowed','1');

	// todo - add auto follow settings, (and strip exif in hubzilla)

	$location = escape_tags($data['user']['profile']['location']);
	if(! $location)
		$location = '';


	q("update channel set channel_location = '%s' where channel_id = %d",
		dbesc($location),
		intval($channel_id)
	);

	if($data['user']['profile']['nsfw']) { 
		q("update channel set channel_pageflags = (channel_pageflags | %d) where channel_id = %d",
				intval(PAGE_ADULT),
				intval($channel_id)
		);
	}

	if($data['user']['profile']['image_url']) {
		$p = z_fetch_url($data['user']['profile']['image_url'],true);
		if($p['success']) {
			$rawbytes = $p['body'];
			$type = guess_image_type('dummyfile',$p['header']);
			import_channel_photo($rawbytes,$type,$c['channel']['channel_account_id'],$channel_id);
		}
	}

	$gender = escape_tags($data['user']['profile']['gender']);
	$about = diaspora2bb($data['user']['profile']['bio']);
	$publish = intval($data['user']['profile']['searchable']);
	if($data['user']['profile']['birthday'])
		$dob = datetime_convert('UTC','UTC',$data['user']['profile']['birthday'],'Y-m-d');
	else
		$dob = '0000-00-00';

	// we're relying on the fact that this channel was just created and will only 
	// have the default profile currently

	$r = q("update profile set gender = '%s', about = '%s', dob = '%s', publish = %d where uid = %d",
		dbesc($gender),
		dbesc($about),
		dbesc($dob),
		dbesc($publish),
		intval($channel_id)
	);

	if($data['user']['aspects']) {
		foreach($data['user']['aspects'] as $aspect) {
			group_add($channel_id,escape_tags($aspect['name']),intval($aspect['contacts_visible']));
		}
	} 
	
	// now add connections and send friend requests


	if($data['user']['contacts']) {
		foreach($data['user']['contacts'] as $contact) {
			$result = new_contact($channel_id, $contact['person_diaspora_handle'], $c['channel']);
			if($result['success']) {
				if($contact['aspects']) {
					foreach($contact['aspects'] as $aspect) {
						group_add_member($channel_id,$aspect['name'],$result['abook']['xchan_hash']);
					}
				}
			}
		}
	}


	// Then add items - note this can't be done until Diaspora adds guids to exported 
	// items and comments



	// This will indirectly perform a refresh_all *and* update the directory

	proc_run('php', 'include/directory.php', $channel_id);

	notice( t('Import completed.') . EOL);

	change_channel($channel_id);

	goaway(z_root() . '/network' );

}