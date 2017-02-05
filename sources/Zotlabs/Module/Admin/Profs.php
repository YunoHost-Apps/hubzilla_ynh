<?php

namespace Zotlabs\Module\Admin;


class Profs {

	function post() {
	
		if(array_key_exists('basic',$_REQUEST)) {
			$arr = explode(',',$_REQUEST['basic']);
			for($x = 0; $x < count($arr); $x ++) 
				if(trim($arr[$x]))
					$arr[$x] = trim($arr[$x]);
			set_config('system','profile_fields_basic',$arr);
	
			if(array_key_exists('advanced',$_REQUEST)) {
				$arr = explode(',',$_REQUEST['advanced']);
				for($x = 0; $x < count($arr); $x ++)
					if(trim($arr[$x]))
						$arr[$x] = trim($arr[$x]);
				set_config('system','profile_fields_advanced',$arr);
			}
			goaway(z_root() . '/admin/profs');
		}
	
	
		if(array_key_exists('field_name',$_REQUEST)) {
			if($_REQUEST['id']) {
				$r = q("update profdef set field_name = '%s', field_type = '%s', field_desc = '%s' field_help = '%s', field_inputs = '%s' where id = %d",
					dbesc($_REQUEST['field_name']),
					dbesc($_REQUEST['field_type']),
					dbesc($_REQUEST['field_desc']),
					dbesc($_REQUEST['field_help']),
					dbesc($_REQUEST['field_inputs']),
					intval($_REQUEST['id'])
				);
			}
			else {
				$r = q("insert into profdef ( field_name, field_type, field_desc, field_help, field_inputs ) values ( '%s' , '%s', '%s', '%s', '%s' )",
					dbesc($_REQUEST['field_name']),
					dbesc($_REQUEST['field_type']),
					dbesc($_REQUEST['field_desc']),
					dbesc($_REQUEST['field_help']),
					dbesc($_REQUEST['field_inputs'])
				);
			}
		}
	
	
		// add to chosen array basic or advanced
	
		goaway(z_root() . '/admin/profs');
	}
	
	function get() {
	
		if((argc() > 3) && argv(2) == 'drop' && intval(argv(3))) {
			$r = q("delete from profdef where id = %d",
				intval(argv(3))
			);
			// remove from allowed fields
	
			goaway(z_root() . '/admin/profs');	
		}
	
		if((argc() > 2) && argv(2) === 'new') {
			return replace_macros(get_markup_template('profdef_edit.tpl'),array(
				'$header' => t('New Profile Field'),
				'$field_name' => array('field_name',t('Field nickname'),$_REQUEST['field_name'],t('System name of field')),
				'$field_type' => array('field_type',t('Input type'),(($_REQUEST['field_type']) ? $_REQUEST['field_type'] : 'text'),''),
				'$field_desc' => array('field_desc',t('Field Name'),$_REQUEST['field_desc'],t('Label on profile pages')),
				'$field_help' => array('field_help',t('Help text'),$_REQUEST['field_help'],t('Additional info (optional)')),
				'$submit' => t('Save')
			));
		}
	
		if((argc() > 2) && intval(argv(2))) {
			$r = q("select * from profdef where id = %d limit 1",
				intval(argv(2))
			);
			if(! $r) {
				notice( t('Field definition not found') . EOL);
				goaway(z_root() . '/admin/profs');
			}
	
			return replace_macros(get_markup_template('profdef_edit.tpl'),array(
				'$id' => intval($r[0]['id']),
				'$header' => t('Edit Profile Field'),
				'$field_name' => array('field_name',t('Field nickname'),$r[0]['field_name'],t('System name of field')),
				'$field_type' => array('field_type',t('Input type'),$r[0]['field_type'],''),
				'$field_desc' => array('field_desc',t('Field Name'),$r[0]['field_desc'],t('Label on profile pages')),
				'$field_help' => array('field_help',t('Help text'),$r[0]['field_help'],t('Additional info (optional)')),
				'$submit' => t('Save')
			));
		}
	
		$basic = '';
		$barr = array();
		$fields = get_profile_fields_basic();
		if(! $fields)
			$fields = get_profile_fields_basic(1);
		if($fields) {
			foreach($fields as $k => $v) {
				if($basic)
					$basic .= ', ';
				$basic .= trim($k);
				$barr[] = trim($k);
			}
		}
	
		$advanced = '';
		$fields = get_profile_fields_advanced();
		if(! $fields)
			$fields = get_profile_fields_advanced(1);
		if($fields) {
			foreach($fields as $k => $v) {
				if(in_array(trim($k),$barr))
					continue;
				if($advanced)
					$advanced .= ', ';
				$advanced .= trim($k);
			}
		}
	
		$all = '';
		$fields = get_profile_fields_advanced(1);
		if($fields) {
			foreach($fields as $k => $v) {
				if($all)
					$all .= ', ';
				$all .= trim($k);
			}
		}
	
		$r = q("select * from profdef where true");
		if($r) {
			foreach($r as $rr) {
				if($all)
					$all .= ', ';
				$all .= $rr['field_name'];
			}
		}
	
		
		$o = replace_macros(get_markup_template('admin_profiles.tpl'),array(
			'$title' => t('Profile Fields'),
			'$basic' => array('basic',t('Basic Profile Fields'),$basic,''),
			'$advanced' => array('advanced',t('Advanced Profile Fields'),$advanced,t('(In addition to basic fields)')),
			'$all' => $all,
			'$all_desc' => t('All available fields'),
			'$cust_field_desc' => t('Custom Fields'),
			'$cust_fields' => $r,
			'$edit' => t('Edit'),
			'$drop' => t('Delete'),
			'$new' => t('Create Custom Field'),		
			'$submit' => t('Submit')
		));
	
		return $o;
	
	
	}





}