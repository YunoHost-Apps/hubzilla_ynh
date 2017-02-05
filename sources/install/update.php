<?php

define( 'UPDATE_VERSION' , 1185 );

/**
 *
 * update.php - automatic system update
 *
 * Automatically update database schemas and any other development changes such that
 * copying the latest files from the source code repository will always perform a clean
 * and painless upgrade.
 *
 * Each function in this file is named update_rnnnn() where nnnn is an increasing number 
 * which began counting at 1000.
 * 
 * At the top of the file "boot.php" is a define for DB_UPDATE_VERSION. Any time there is a change
 * to the database schema or one which requires an upgrade path from the existing application,
 * the DB_UPDATE_VERSION and the UPDATE_VERSION at the top of this file are incremented.
 *
 * The current DB_UPDATE_VERSION is stored in the config area of the database. If the application starts up
 * and DB_UPDATE_VERSION is greater than the last stored build number, we will process every update function 
 * in order from the currently stored value to the new DB_UPDATE_VERSION. This is expected to bring the system 
 * up to current without requiring re-installation or manual intervention.
 *
 * Once the upgrade functions have completed, the current DB_UPDATE_VERSION is stored as the current value.
 * The DB_UPDATE_VERSION will always be one greater than the last numbered script in this file. 
 *
 * If you change the database schema, the following are required:
 *    1. Update the files schema_mysql.sql and schema_postgres.sql to match the new schema.
 *       Be sure to read doc/sql_conventions.bb ($yoururl/help/sql_conventions) use only standard
 *		 SQL data types where possible to keep differences in the files to a minimum
 *    2. Update this file by adding a new function at the end with the number of the current DB_UPDATE_VERSION.
 *       This function should modify the current database schema and perform any other steps necessary
 *       to ensure that upgrade is silent and free from requiring interaction. Review to ensure that it
 *		 will run correctly on both postgres and MySQL/Mariadb. It is very difficult and messy to fix DB update
 *		 errors. Once pushed, it requires a new update which undoes any damage and performs the corrected updated.
 *    3. Increment the DB_UPDATE_VERSION in boot.php *AND* the UPDATE_VERSION in this file to match it
 *    4. TEST the upgrade prior to checkin and filing a pull request.
 *
 */

function update_r1000() {
	$r = q("ALTER TABLE `channel` ADD `channel_a_delegate` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT '0', ADD INDEX ( `channel_a_delegate` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1001() {
	$r = q("CREATE TABLE if not exists `verify` (
		`id` INT(10) UNSIGNED NOT NULL ,
		`channel` INT(10) UNSIGNED NOT NULL DEFAULT '0',
		`type` CHAR( 32 ) NOT NULL DEFAULT '',
		`token` CHAR( 255 ) NOT NULL DEFAULT '',
		`meta` CHAR( 255 ) NOT NULL DEFAULT '',
		`created` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
		PRIMARY KEY ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET=utf8");

	$r2 = q("alter table `verify` add index (`channel`), add index (`type`), add index (`token`),
		add index (`meta`), add index (`created`)");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1002() {
	$r = q("ALTER TABLE `event` CHANGE `account` `aid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r2 = q("alter table `event` drop index `account`, add index (`aid`)");

	q("drop table contact");
	q("drop table deliverq");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1003() {
	$r = q("ALTER TABLE `xchan` ADD `xchan_flags` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `xchan_network` ,
ADD INDEX ( `xchan_flags` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1004() {
	$r = q("CREATE TABLE if not exists `site` (
`site_url` CHAR( 255 ) NOT NULL ,
`site_flags` INT NOT NULL DEFAULT '0',
`site_update` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
`site_directory` CHAR( 255 ) NOT NULL DEFAULT '',
PRIMARY KEY ( `site_url` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8");

	$r2 = q("alter table site add index (site_flags), add index (site_update), add index (site_directory) ");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1005() {
	q("drop table guid");
	q("drop table `notify-threads`");
	return UPDATE_SUCCESS;
}

function update_r1006() {

	$r = q("CREATE TABLE IF NOT EXISTS `xprof` (
  `xprof_hash` char(255) NOT NULL,
  `xprof_desc` char(255) NOT NULL DEFAULT '',
  `xprof_dob` char(12) NOT NULL DEFAULT '',
  `xprof_gender` char(255) NOT NULL DEFAULT '',
  `xprof_marital` char(255) NOT NULL DEFAULT '',
  `xprof_sexual` char(255) NOT NULL DEFAULT '',
  `xprof_locale` char(255) NOT NULL DEFAULT '',
  `xprof_region` char(255) NOT NULL DEFAULT '',
  `xprof_postcode` char(32) NOT NULL DEFAULT '',
  `xprof_country` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`xprof_hash`),
  KEY `xprof_desc` (`xprof_desc`),
  KEY `xprof_dob` (`xprof_dob`),
  KEY `xprof_gender` (`xprof_gender`),
  KEY `xprof_marital` (`xprof_marital`),
  KEY `xprof_sexual` (`xprof_sexual`),
  KEY `xprof_locale` (`xprof_locale`),
  KEY `xprof_region` (`xprof_region`),
  KEY `xprof_postcode` (`xprof_postcode`),
  KEY `xprof_country` (`xprof_country`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

	$r2 = q("CREATE TABLE IF NOT EXISTS `xtag` (
  `xtag_hash` char(255) NOT NULL,
  `xtag_term` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`xtag_hash`),
  KEY `xtag_term` (`xtag_term`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1007() {
	$r = q("ALTER TABLE `channel` ADD `channel_r_storage` INT UNSIGNED NOT NULL DEFAULT '128', ADD `channel_w_storage` INT UNSIGNED NOT NULL DEFAULT '128', add index ( channel_r_storage ), add index ( channel_w_storage )");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1008() {
	$r = q("alter table profile drop prv_keywords,  CHANGE `pub_keywords` `keywords` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, drop index pub_keywords");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1009() {
	$r = q("ALTER TABLE `xprof` ADD `xprof_keywords` TEXT NOT NULL");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1010() {
	$r = q("ALTER TABLE `abook` ADD `abook_dob` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `abook_connnected` ,
ADD INDEX ( `abook_dob` )");

	$r2 = q("ALTER TABLE `profile` ADD `dob_tz` CHAR( 255 ) NOT NULL DEFAULT 'UTC' AFTER `dob`");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1011() {
	$r = q("ALTER TABLE `item` ADD `expires` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `edited` ,
ADD INDEX ( `expires` )");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}
	
function update_r1012() {
	$r = q("ALTER TABLE `xchan` ADD `xchan_connurl` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `xchan_url` ,
ADD INDEX ( `xchan_connurl` )");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1013() {
	$r = q("CREATE TABLE if not exists `xlink` (
`xlink_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`xlink_xchan` CHAR( 255 ) NOT NULL DEFAULT '',
`xlink_link` CHAR( 255 ) NOT NULL DEFAULT '',
`xlink_updated` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00'
) ENGINE = MYISAM DEFAULT CHARSET=utf8");

	$r2 = q("alter table xlink add index ( xlink_xchan ), add index ( xlink_link ), add index ( xlink_updated ) ");
	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1014() {
	$r = q("ALTER TABLE `verify` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1015() {
	$r = q("ALTER TABLE `channel` ADD `channel_r_pages` INT UNSIGNED NOT NULL DEFAULT '128',
ADD `channel_w_pages` INT UNSIGNED NOT NULL DEFAULT '128'");

	$r2 = q("ALTER TABLE `channel` ADD INDEX ( `channel_r_pages` ) , ADD INDEX ( `channel_w_pages` ) ");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1016() {

	$r = q("CREATE TABLE IF NOT EXISTS `menu` (
  `menu_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_channel_id` int(10) unsigned NOT NULL DEFAULT '0',
  `menu_desc` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`menu_id`),
  KEY `menu_channel_id` (`menu_channel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");

	$r2 = q("CREATE TABLE IF NOT EXISTS `menu_item` (
  `mitem_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mitem_link` char(255) NOT NULL DEFAULT '',
  `mitem_desc` char(255) NOT NULL DEFAULT '',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `mitem_channel_id` int(10) unsigned NOT NULL,
  `mitem_menu_id` int(10) unsigned NOT NULL DEFAULT '0',
  `mitem_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`mitem_id`),
  KEY `mitem_channel_id` (`mitem_channel_id`),
  KEY `mitem_menu_id` (`mitem_menu_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");


	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1017() {
	$r = q("ALTER TABLE `event` CHANGE `cid` `event_xchan` CHAR( 255 ) NOT NULL DEFAULT '', ADD INDEX ( `event_xchan` ), drop index cid  ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1018() {
	$r = q("ALTER TABLE `event` ADD `event_hash` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `event_xchan` ,
ADD INDEX ( `event_hash` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1019() {
	$r = q("ALTER TABLE `event` DROP `message_id` ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1020() {
	$r = q("alter table photo drop `contact-id`, drop guid, drop index `resource-id`, add index ( `resource_id` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1021() {

	$r = q("ALTER TABLE `abook` CHANGE `abook_connnected` `abook_connected` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
		drop index `abook_connnected`, add index ( `abook_connected` ) ");
	
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1022() {
	$r = q("alter table attach add index ( filename ), add index ( filetype ), add index ( filesize ), add index ( created ), add index ( edited ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1023() {
	$r = q("ALTER TABLE `item` ADD `revision` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `lang` , add index ( revision ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1024() {
	$r = q("ALTER TABLE `attach` ADD `revision` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `filesize` ,
ADD INDEX ( `revision` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1025() {
	$r = q("ALTER TABLE `attach` ADD `folder` CHAR( 64 ) NOT NULL DEFAULT '' AFTER `revision` ,
ADD `flags` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `folder` , add index ( folder ), add index ( flags )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1026() {
	$r = q("ALTER TABLE `item` ADD `mimetype` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `author_xchan` ,
ADD INDEX ( `mimetype` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1027() {
	$r = q("ALTER TABLE `abook` ADD `abook_rating` INT NOT NULL DEFAULT '0' AFTER `abook_closeness` ,
ADD INDEX ( `abook_rating` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1028() {
	$r = q("ALTER TABLE `xlink` ADD `xlink_rating` INT NOT NULL DEFAULT '0' AFTER `xlink_link` ,
ADD INDEX ( `xlink_rating` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1029() {
	$r = q("ALTER TABLE `channel` ADD `channel_deleted` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `channel_pageflags` ,
ADD INDEX ( `channel_deleted` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1030() {
	$r = q("CREATE TABLE IF NOT EXISTS `issue` (
`issue_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`issue_created` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
`issue_updated` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
`issue_assigned` CHAR( 255 ) NOT NULL ,
`issue_priority` INT NOT NULL ,
`issue_status` INT NOT NULL ,
`issue_component` CHAR( 255 ) NOT NULL,
KEY `issue_created` (`issue_created`),
KEY `issue_updated` (`issue_updated`),
KEY `issue_assigned` (`issue_assigned`),
KEY `issue_priority` (`issue_priority`),
KEY `issue_status` (`issue_status`),
KEY `issue_component` (`issue_component`)
) ENGINE = MYISAM DEFAULT CHARSET=utf8");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1031() {
	$r = q("ALTER TABLE `account` ADD `account_external` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `account_email` ,
ADD INDEX ( `account_external` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1032() {
	$r = q("CREATE TABLE if not exists `xign` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`uid` INT NOT NULL DEFAULT '0',
`xchan` CHAR( 255 ) NOT NULL DEFAULT '',
KEY `uid` (`uid`),
KEY `xchan` (`xchan`)
) ENGINE = MYISAM DEFAULT CHARSET = utf8");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1033() {
	$r = q("CREATE TABLE if not exists `shares` (
`share_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`share_type` INT NOT NULL DEFAULT '0',
`share_target` INT UNSIGNED NOT NULL DEFAULT '0',
`share_xchan` CHAR( 255 ) NOT NULL DEFAULT '',
KEY `share_type` (`share_type`),
KEY `share_target` (`share_target`),
KEY `share_xchan` (`share_xchan`)
) ENGINE = MYISAM DEFAULT CHARSET = utf8");

	// if these fail don't bother reporting it

	q("drop table gcign");
	q("drop table gcontact");
	q("drop table glink");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1034() {
	$r = q("CREATE TABLE if not exists `updates` (
`ud_hash` CHAR( 128 ) NOT NULL ,
`ud_date` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
PRIMARY KEY ( `ud_hash` ),
KEY `ud_date` ( `ud_date` )
) ENGINE = MYISAM DEFAULT CHARSET = utf8");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1035() {
	$r = q("CREATE TABLE if not exists `xconfig` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`xchan` CHAR( 255 ) NOT NULL ,
`cat` CHAR( 255 ) NOT NULL ,
`k` CHAR( 255 ) NOT NULL ,
`v` MEDIUMTEXT NOT NULL,
KEY `xchan` ( `xchan` ),
KEY `cat` ( `cat` ),
KEY `k` ( `k` )
) ENGINE = MYISAM DEFAULT CHARSET = utf8");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1036() {
	$r = q("ALTER TABLE `profile` ADD `channels` TEXT NOT NULL AFTER `contact` ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}


function update_r1037() {
	$r1 = q("ALTER TABLE `item` CHANGE `uri` `mid` CHAR( 255 ) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
CHANGE `parent_uri` `parent_mid` CHAR( 255 ) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
 DROP INDEX `uri` ,
ADD INDEX `mid` ( `mid` ),
DROP INDEX `parent_uri` ,
ADD INDEX `parent_mid` ( `parent_mid` ),
 DROP INDEX `uid_uri` ,
ADD INDEX `uid_mid` ( `mid` , `uid` ) ");

	$r2 = q("ALTER TABLE `mail` CHANGE `uri` `mid` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE `parent_uri` `parent_mid` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
DROP INDEX `uri` ,
ADD INDEX `mid` ( `mid` ),
 DROP INDEX `parent_uri` ,
ADD INDEX `parent_mid` ( `parent_mid` ) ");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1038() {
	$r = q("ALTER TABLE `manage` CHANGE `mid` `xchan` CHAR( 255 ) NOT NULL DEFAULT '', drop index `mid`,  ADD INDEX ( `xchan` )");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}
 

function update_r1039() {
	$r = q("ALTER TABLE `channel` CHANGE `channel_default_gid` `channel_default_group` CHAR( 255 ) NOT NULL DEFAULT ''");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1040() {
	$r1 = q("ALTER TABLE `session` CHANGE `expire` `expire` BIGINT UNSIGNED NOT NULL ");
	$r2 = q("ALTER TABLE `tokens` CHANGE `expires` `expires` BIGINT UNSIGNED NOT NULL ");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1041() {
	$r = q("ALTER TABLE `outq` ADD `outq_driver` CHAR( 32 ) NOT NULL DEFAULT '' AFTER `outq_channel` ");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1042() {
	$r = q("ALTER TABLE `hubloc` ADD `hubloc_updated` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
ADD `hubloc_connected` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',  ADD INDEX ( `hubloc_updated` ),  ADD INDEX ( `hubloc_connected` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1043() {
	$r = q("ALTER TABLE `item` ADD `comment_policy` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `coord` ,
ADD INDEX ( `comment_policy` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1044() {
	$r = q("ALTER TABLE `term` ADD `imgurl` CHAR( 255 ) NOT NULL ,
ADD INDEX ( `imgurl` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1045() {
	$r = q("ALTER TABLE `site` ADD `site_register` INT NOT NULL DEFAULT '0',
ADD INDEX ( `site_register` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}
	
function update_r1046() {
	$r = q("ALTER TABLE `term` ADD `term_hash` CHAR( 255 ) NOT NULL DEFAULT '',
ADD INDEX ( `term_hash` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1047() {
	$r = q("ALTER TABLE `xprof` ADD `xprof_age` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `xprof_hash` ,
ADD INDEX ( `xprof_age` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1048() {
	$r = q("CREATE TABLE IF NOT EXISTS `obj` (
  `obj_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `obj_page` char(64) NOT NULL DEFAULT '',
  `obj_verb` char(255) NOT NULL DEFAULT '',
  `obj_type` int(10) unsigned NOT NULL DEFAULT '0',
  `obj_obj` char(255) NOT NULL DEFAULT '',
  `obj_channel` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`obj_id`),
  KEY `obj_verb` (`obj_verb`),
  KEY `obj_page` (`obj_page`),
  KEY `obj_type` (`obj_type`),
  KEY `obj_channel` (`obj_channel`),
  KEY `obj_obj` (`obj_obj`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1049() {
	$r = q("ALTER TABLE `term` ADD `parent_hash` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `term_hash` , ADD INDEX ( `parent_hash` ) ");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1050() {
	$r = q("ALTER TABLE `xtag` DROP PRIMARY KEY , ADD `xtag_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST , ADD INDEX ( `xtag_hash` ) ");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1051() {
	$r = q("ALTER TABLE `photo` ADD `photo_flags` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `profile` , ADD INDEX ( `photo_flags` ) ");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1052() {
	$r = q("ALTER TABLE `channel` ADD UNIQUE (`channel_address`) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1053() {
	$r = q("ALTER TABLE `profile` ADD `chandesc` TEXT NOT NULL DEFAULT '' AFTER `pdesc` ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1054() {
	$r = q("ALTER TABLE `item` CHANGE `title` `title` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1055() {
	$r = q("ALTER TABLE `mail` CHANGE `title` `title` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1056() {
	$r = q("ALTER TABLE `xchan` ADD `xchan_instance_url` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `xchan_network` ,
ADD INDEX ( `xchan_instance_url` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1057() {
	$r = q("drop table intro");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1058() {
	$r1 = q("ALTER TABLE `menu` ADD `menu_name` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `menu_channel_id` ,
ADD INDEX ( `menu_name` ) ");

	$r2 = q("ALTER TABLE `menu_item` ADD `mitem_flags` INT NOT NULL DEFAULT '0' AFTER `mitem_desc` ,
ADD INDEX ( `mitem_flags` ) ");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1059() {
	$r = q("ALTER TABLE `mail` ADD `attach` MEDIUMTEXT NOT NULL DEFAULT '' AFTER `body` ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1060() {

	$r = q("CREATE TABLE IF NOT EXISTS `vote` (
  `vote_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vote_poll` int(11) NOT NULL DEFAULT '0',
  `vote_element` int(11) NOT NULL DEFAULT '0',
  `vote_result` text NOT NULL,
  `vote_xchan` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`vote_id`),
  UNIQUE KEY `vote_vote` (`vote_poll`,`vote_element`,`vote_xchan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1061() {
	$r = q("ALTER TABLE `vote` ADD INDEX ( `vote_poll` ),  ADD INDEX ( `vote_element` ) ");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1062() {
	$r1 = q("CREATE TABLE IF NOT EXISTS `poll` (
`poll_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`poll_channel` INT UNSIGNED NOT NULL DEFAULT '0',
`poll_desc` TEXT NOT NULL DEFAULT '',
`poll_flags` INT NOT NULL DEFAULT '0',
`poll_votes` INT NOT NULL DEFAULT '0',
KEY `poll_channel` (`poll_channel`),
KEY `poll_flags` (`poll_flags`),
KEY `poll_votes` (`poll_votes`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");

	$r2 = q("CREATE TABLE IF NOT EXISTS `poll_elm` (
`pelm_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`pelm_poll` INT UNSIGNED NOT NULL DEFAULT '0',
`pelm_desc` TEXT NOT NULL DEFAULT '',
`pelm_flags` INT NOT NULL DEFAULT '0',
`pelm_result` FLOAT NOT NULL DEFAULT '0',
KEY `pelm_poll` (`pelm_poll`),
KEY `pelm_result` (`pelm_result`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1063() {
	$r = q("ALTER TABLE `xchan` ADD `xchan_follow` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `xchan_connurl` ,
ADD `xchan_connpage` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `xchan_follow` ,
ADD INDEX ( `xchan_follow` ), ADD INDEX ( `xchan_connpage`) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1064() {
	$r = q("ALTER TABLE `updates` ADD `ud_guid` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `ud_hash` ,
ADD INDEX ( `ud_guid` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1065() {
	$r = q("ALTER TABLE `item` DROP `wall`, ADD `layout_mid` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `target` ,
ADD INDEX ( `layout_mid` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1066() {
	$r = q("ALTER TABLE `site` ADD `site_access` INT NOT NULL DEFAULT '0' AFTER `site_url` ,
ADD INDEX ( `site_access` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1067() {
	$r = q("ALTER TABLE `updates` DROP PRIMARY KEY , ADD `ud_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,  ADD INDEX ( `ud_hash` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1068(){
        $r = q("ALTER TABLE `hubloc` ADD `hubloc_status` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `hubloc_flags` , ADD INDEX ( `hubloc_status` )");
        if($r)
                return UPDATE_SUCCESS;
        return UPDATE_FAILED;
}

function update_r1069() {
	$r = q("ALTER TABLE `site` ADD `site_sellpage` CHAR( 255 ) NOT NULL DEFAULT '',
ADD INDEX ( `site_sellpage` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1070() {
	$r = q("ALTER TABLE `updates` ADD `ud_flags` INT NOT NULL DEFAULT '0',
ADD INDEX ( `ud_flags` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1071() {
	$r = q("ALTER TABLE `updates` ADD `ud_addr` CHAR( 255 ) NOT NULL DEFAULT '',
ADD INDEX ( `ud_addr` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1072() {
	$r = q("ALTER TABLE `xtag` ADD `xtag_flags` INT NOT NULL DEFAULT '0',
ADD INDEX ( `xtag_flags` ) ");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1073() {
	$r1 = q("CREATE TABLE IF NOT EXISTS `source` (
`src_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`src_channel_id` INT UNSIGNED NOT NULL DEFAULT '0',
`src_channel_xchan` CHAR( 255 ) NOT NULL DEFAULT '',
`src_xchan` CHAR( 255 ) NOT NULL DEFAULT '',
`src_patt` MEDIUMTEXT NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");

	$r2 = q("ALTER TABLE `source` ADD INDEX ( `src_channel_id` ), ADD INDEX ( `src_channel_xchan` ), ADD INDEX ( `src_xchan` ) ");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1074() {
	$r1 = q("ALTER TABLE `site` ADD `site_sync` DATETIME NOT NULL AFTER `site_update` ");

	$r2 = q("ALTER TABLE `updates` ADD `ud_last` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `ud_date` ,
ADD INDEX ( `ud_last` ) ");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1075() {
	$r = q("ALTER TABLE `channel` ADD `channel_a_republish` INT UNSIGNED NOT NULL DEFAULT '128',
ADD INDEX ( `channel_a_republish` )");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1076() {
	$r = q("ALTER TABLE `item` CHANGE `inform` `sig` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1077() {
	$r = q("ALTER TABLE `item` ADD `source_xchan` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `author_xchan` ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1078() {
	$r = q("ALTER TABLE `channel` ADD `channel_dirdate` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `channel_pageflags` , ADD INDEX ( `channel_dirdate` )");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1079() {
	$r = q("ALTER TABLE `site` ADD `site_location` CHAR( 255 ) NOT NULL DEFAULT ''");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1080() {
	$r = q("ALTER TABLE `mail` ADD `expires` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
ADD INDEX ( `expires` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1081() {
	$r = q("DROP TABLE `queue` ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1082() {
	$r = q("DROP TABLE `challenge` ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1083() {
	$r = q("ALTER TABLE `notify` ADD `aid` INT NOT NULL AFTER `msg` ,
ADD INDEX ( `aid` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1084() {


	$r = q("CREATE TABLE if not exists `sys_perms` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`cat` CHAR( 255 ) NOT NULL ,
			`k` CHAR( 255 ) NOT NULL ,
			`v` MEDIUMTEXT NOT NULL,
			`public_perm` TINYINT( 1 ) UNSIGNED NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET = utf8");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1085() {
	$r1 = q("ALTER TABLE `photo` CHANGE `desc` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ");

	$r2 = q("RENAME TABLE `group` TO `groups`");

	$r3 = q("ALTER TABLE `event` CHANGE `desc` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ");

	if($r1 && $r2 && $r3)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1086() {
	$r = q("ALTER TABLE `account` ADD `account_level` INT UNSIGNED NOT NULL DEFAULT '0',
ADD INDEX ( `account_level` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1087() {
	$r = q("ALTER TABLE `xprof` ADD `xprof_about` TEXT NOT NULL DEFAULT '',
ADD `xprof_homepage` CHAR( 255 ) NOT NULL DEFAULT '',
ADD `xprof_hometown` CHAR( 255 ) NOT NULL DEFAULT '',
ADD INDEX ( `xprof_hometown` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1088() {
	$r = q("ALTER TABLE `obj` ADD `allow_cid` MEDIUMTEXT NOT NULL DEFAULT '',
ADD `allow_gid` MEDIUMTEXT NOT NULL DEFAULT '',
ADD `deny_cid` MEDIUMTEXT NOT NULL DEFAULT '',
ADD `deny_gid` MEDIUMTEXT NOT NULL DEFAULT ''");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1089() {
	$r = q("ALTER TABLE `attach` ADD `creator` CHAR( 128 ) NOT NULL DEFAULT '' AFTER `hash` ,
ADD INDEX ( `creator` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1090() {
	$r = q("ALTER TABLE `menu` ADD `menu_flags` INT NOT NULL DEFAULT '0',
ADD INDEX ( `menu_flags` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1091() {
	@os_mkdir('store/[data]/smarty3',STORAGE_DEFAULT_PERMISSIONS,true);
	@file_put_contents('store/[data]/locks','');
	return UPDATE_SUCCESS;
}

function update_r1092() {
	$r1 = q("CREATE TABLE IF NOT EXISTS `chat` (
  `chat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chat_room` int(10) unsigned NOT NULL DEFAULT '0',
  `chat_xchan` char(255) NOT NULL DEFAULT '',
  `chat_text` mediumtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`chat_id`),
  KEY `chat_room` (`chat_room`),
  KEY `chat_xchan` (`chat_xchan`),
  KEY `created` (`created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

	$r2 = q("CREATE TABLE IF NOT EXISTS `chatpresence` (
  `cp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cp_room` int(10) unsigned NOT NULL DEFAULT '0',
  `cp_xchan` char(255) NOT NULL DEFAULT '',
  `cp_last` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `cp_status` char(255) NOT NULL,
  PRIMARY KEY (`cp_id`),
  KEY `cp_room` (`cp_room`),
  KEY `cp_xchan` (`cp_xchan`),
  KEY `cp_last` (`cp_last`),
  KEY `cp_status` (`cp_status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

	$r3 = q("CREATE TABLE IF NOT EXISTS `chatroom` (
  `cr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cr_aid` int(10) unsigned NOT NULL DEFAULT '0',
  `cr_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `cr_name` char(255) NOT NULL DEFAULT '',
  `cr_created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `cr_edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`cr_id`),
  KEY `cr_aid` (`cr_aid`),
  KEY `cr_uid` (`cr_uid`),
  KEY `cr_name` (`cr_name`),
  KEY `cr_created` (`cr_created`),
  KEY `cr_edited` (`cr_edited`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");


	if($r1 && $r2 && $r3)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}




function update_r1093() {
	$r = q("ALTER TABLE `chatpresence` ADD `cp_client` CHAR( 128 ) NOT NULL DEFAULT ''");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1094() {
	$r = q("ALTER TABLE `chatroom` ADD `cr_expire` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `cr_edited` ,
ADD INDEX ( `cr_expire` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1095() {
	$r = q("ALTER TABLE `channel` ADD `channel_a_bookmark` INT UNSIGNED NOT NULL DEFAULT '128',
ADD INDEX ( `channel_a_bookmark` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1096() {
	$r = q("ALTER TABLE `account` CHANGE `account_level` `account_level` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1097() {

	// fix some mangled hublocs from a bug long ago

	$r = q("select hubloc_id, hubloc_addr from hubloc where hubloc_addr like '%%/%%'");
	if($r) {
		foreach($r as $rr) {
			q("update hubloc set hubloc_addr = '%s' where hubloc_id = %d",
				dbesc(substr($rr['hubloc_addr'],0,strpos($rr['hubloc_addr'],'/'))),
				intval($rr['hubloc_id'])
			);
		}
	}
	return UPDATE_SUCCESS;
	
}

function update_r1098() {
	$r = q("ALTER TABLE `channel` CHANGE `channel_r_stream` `channel_r_stream` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r2 = q("ALTER TABLE `channel` CHANGE `channel_r_profile` `channel_r_profile` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r3 = q("ALTER TABLE `channel` CHANGE `channel_r_photos` `channel_r_photos` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r4 = q("ALTER TABLE `channel` CHANGE `channel_r_abook` `channel_r_abook` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r4 = q("ALTER TABLE `channel` CHANGE `channel_w_stream` `channel_w_stream` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r5 = q("ALTER TABLE `channel` CHANGE `channel_w_wall` `channel_w_wall` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r6 = q("ALTER TABLE `channel` CHANGE `channel_w_tagwall` `channel_w_tagwall` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r7 = q("ALTER TABLE `channel` CHANGE `channel_w_comment` `channel_w_comment` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r8 = q("ALTER TABLE `channel` CHANGE `channel_w_mail` `channel_w_mail` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r9 = q("ALTER TABLE `channel` CHANGE `channel_w_photos` `channel_w_photos` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r10 = q("ALTER TABLE `channel` CHANGE `channel_w_chat` `channel_w_chat` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r11 = q("ALTER TABLE `channel` CHANGE `channel_a_delegate` `channel_a_delegate` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	if($r && $r2 && $r3 && $r3 && $r5 && $r6 && $r7 && $r8 && $r9 && $r9 && $r10 && $r11)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1099() {
	$r = q("CREATE TABLE IF NOT EXISTS `xchat` (
  `xchat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `xchat_url` char(255) NOT NULL DEFAULT '',
  `xchat_desc` char(255) NOT NULL DEFAULT '',
  `xchat_xchan` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`xchat_id`),
  KEY `xchat_url` (`xchat_url`),
  KEY `xchat_desc` (`xchat_desc`),
  KEY `xchat_xchan` (`xchat_xchan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1100() {
	$r = q("ALTER TABLE `xchat` ADD `xchat_edited` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
ADD INDEX ( `xchat_edited` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}
	

function update_r1101() {
	$r = q("update updates set ud_flags = 2 where ud_flags = (-1)");
	$r = q("update updates set ud_flags = 0 where ud_flags = 4096");
	return UPDATE_SUCCESS;
}

function update_r1102() {
	$r = q("update abook set abook_flags = (abook_flags - %d)
		where ( abook_flags & %d)",
		intval(ABOOK_FLAG_UNCONNECTED),
		intval(ABOOK_FLAG_UNCONNECTED)
	);
	return UPDATE_SUCCESS;
}

function update_r1103() {
	$x = curl_version();
	if(stristr($x['ssl_version'],'openssl'))
		set_config('system','curl_ssl_ciphers','ALL:!eNULL');
	return UPDATE_SUCCESS;
}

function update_r1104() {
	$r = q("ALTER TABLE `item` ADD `route` TEXT NOT NULL DEFAULT '' AFTER `postopts` ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1105() {
	$r = q("ALTER TABLE `site` ADD `site_pull` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `site_update` ,
CHANGE `site_sync` `site_sync` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00', ADD INDEX ( `site_pull` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1106() {
	$r = q("ALTER TABLE `notify` CHANGE `parent` `parent` CHAR( 255 ) NOT NULL DEFAULT ''");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1107() {
	$r = q("CREATE TABLE IF NOT EXISTS `app` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_id` char(64) NOT NULL DEFAULT '',
  `app_sig` char(255) NOT NULL DEFAULT '',
  `app_author` char(255) NOT NULL DEFAULT '',
  `app_name` char(255) NOT NULL DEFAULT '',
  `app_desc` text NOT NULL,
  `app_url` char(255) NOT NULL DEFAULT '',
  `app_photo` char(255) NOT NULL DEFAULT '',
  `app_version` char(255) NOT NULL DEFAULT '',
  `app_channel` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `app_id` (`app_id`),
  KEY `app_name` (`app_name`),
  KEY `app_url` (`app_url`),
  KEY `app_photo` (`app_photo`),
  KEY `app_version` (`app_version`),
  KEY `app_channel` (`app_channel`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1108() {
	$r = q("ALTER TABLE `app` ADD `app_addr` CHAR( 255 ) NOT NULL DEFAULT '',
ADD `app_price` CHAR( 255 ) NOT NULL DEFAULT '',
ADD `app_page` CHAR( 255 ) NOT NULL DEFAULT '',
ADD INDEX ( `app_price` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1109() {
	$r = q("ALTER TABLE `app` CHANGE `app_id` `app_id` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

// We ended up with an extra zero in the name for 1108, so do it over and ignore the result.

function update_r1110() {
	$r = q("ALTER TABLE `app` ADD `app_addr` CHAR( 255 ) NOT NULL DEFAULT '',
ADD `app_price` CHAR( 255 ) NOT NULL DEFAULT '',
ADD `app_page` CHAR( 255 ) NOT NULL DEFAULT '',
ADD INDEX ( `app_price` )");

	return UPDATE_SUCCESS;

}

function update_r1111() {
	$r = q("ALTER TABLE `app` ADD `app_requires` CHAR( 255 ) NOT NULL DEFAULT '' ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1112() {
	$r = q("CREATE TABLE IF NOT EXISTS `likes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `liker` char(128) NOT NULL DEFAULT '',
  `likee` char(128) NOT NULL DEFAULT '',
  `iid` int(11) NOT NULL DEFAULT '0',
  `verb` char(255) NOT NULL DEFAULT '',
  `target_type` char(255) NOT NULL DEFAULT '',
  `target` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `liker` (`liker`),
  KEY `likee` (`likee`),
  KEY `iid` (`iid`),
  KEY `verb` (`verb`),
  KEY `target_type` (`target_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1113() {
	$r = q("ALTER TABLE `likes` ADD `channel_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id` ,
CHANGE `iid` `iid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
ADD INDEX ( `channel_id` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1114() {
	$r = q("ALTER TABLE `likes` ADD `target_id` CHAR( 128 ) NOT NULL DEFAULT '' AFTER `target_type` ,
ADD INDEX ( `target_id` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}
	
function update_r1115() {

	// Introducing email verification. Mark all existing accounts as verified or they
	// won't be able to login.

	$r = q("update account set account_flags = (account_flags ^ 1) where (account_flags & 1) ");
	return UPDATE_SUCCESS;
}

function update_r1116() {
	@os_mkdir('store/[data]/smarty3',STORAGE_DEFAULT_PERMISSIONS,true);
	return UPDATE_SUCCESS;
} 

function update_r1117() {
	$r = q("ALTER TABLE `channel` CHANGE `channel_a_bookmark` `channel_w_like` INT( 10 ) UNSIGNED NOT NULL DEFAULT '128',
DROP INDEX `channel_a_bookmark` , ADD INDEX `channel_w_like` ( `channel_w_like` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1118() {
	$r = q("ALTER TABLE `account` ADD `account_password_changed` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
ADD INDEX ( `account_password_changed` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1119() {
	$r1 = q("CREATE TABLE IF NOT EXISTS `profdef` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `field_name` char(255) NOT NULL DEFAULT '',
  `field_type` char(16) NOT NULL DEFAULT '',
  `field_desc` char(255) NOT NULL DEFAULT '',
  `field_help` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `field_name` (`field_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

	$r2 = q("CREATE TABLE IF NOT EXISTS `profext` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0',
  `hash` char(255) NOT NULL DEFAULT '',
  `k` char(255) NOT NULL DEFAULT '',
  `v` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `channel_id` (`channel_id`),
  KEY `hash` (`hash`),
  KEY `k` (`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1120() {
	$r = q("ALTER TABLE `item` ADD `public_policy` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `coord` ,
ADD INDEX ( `public_policy` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1121() {
	$r = q("ALTER TABLE `site` ADD `site_realm` CHAR( 255 ) NOT NULL DEFAULT '',
ADD INDEX ( `site_realm` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1122() {
	$r = q("update site set site_realm = '%s' where true",
		dbesc(DIRECTORY_REALM)
	);
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1123() {
	$r1 = q("ALTER TABLE `hubloc` ADD `hubloc_network` CHAR( 32 ) NOT NULL DEFAULT '' AFTER `hubloc_addr` ,
ADD INDEX ( `hubloc_network` )");
	$r2 = q("update hubloc set hubloc_network = 'zot' where true");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1124() {
	$r1 = q("CREATE TABLE IF NOT EXISTS `sign` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `iid` int(10) unsigned NOT NULL DEFAULT '0',
  `retract_iid` int(10) unsigned NOT NULL DEFAULT '0',
  `signed_text` mediumtext NOT NULL,
  `signature` text NOT NULL,
  `signer` char(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `iid` (`iid`),
  KEY `retract_iid` (`retract_iid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ");

	$r2 = q("CREATE TABLE IF NOT EXISTS `conv` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` char(255) NOT NULL,
  `recips` mediumtext NOT NULL,
  `uid` int(11) NOT NULL,
  `creator` char(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `subject` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created` (`created`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;


}

function update_r1125() {
	$r = q("ALTER TABLE `profdef` ADD `field_inputs` MEDIUMTEXT NOT NULL DEFAULT ''");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}


function update_r1126() {
	$r = q("ALTER TABLE `mail` ADD `convid` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id` ,
ADD INDEX ( `convid` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1127() {
	$r = q("ALTER TABLE `item` ADD `comments_closed` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `changed` ,
ADD INDEX ( `comments_closed` ), ADD INDEX ( `changed` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1128() {
	$r = q("ALTER TABLE `item` ADD `diaspora_meta` MEDIUMTEXT NOT NULL DEFAULT '' AFTER `sig` ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1129() {
	$r = q("update hubloc set hubloc_network = 'zot' where hubloc_network = ''");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1130() {
	$myperms = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
		|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
		|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE;

	$r = q("select abook_channel, abook_my_perms from abook where (abook_flags & %d) and abook_my_perms != 0",
		intval(ABOOK_FLAG_SELF)
	);
	if($r) {
		foreach($r as $rr) {
			set_pconfig($rr['abook_channel'],'system','autoperms',$rr['abook_my_perms']);
		}
	}
	$r = q("update abook set abook_my_perms = %d where (abook_flags & %d) and abook_my_perms = 0",
		intval($myperms),
		intval(ABOOK_FLAG_SELF)
	);		

	return UPDATE_SUCCESS;
}

function update_r1131() {
	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) // make sure this gets skipped for anyone who hasn't run it yet, it will fail on pg
		return UPDATE_SUCCESS;
		
	$r1 = q("ALTER TABLE `abook` ADD `abook_rating_text` TEXT NOT NULL DEFAULT '' AFTER `abook_rating` ");
	$r2 = q("ALTER TABLE `xlink` ADD `xlink_rating_text` TEXT NOT NULL DEFAULT '' AFTER `xlink_rating` ");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1132() {
	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) { // correct previous failed update
		$r1 = q("ALTER TABLE abook ADD abook_rating_text TEXT NOT NULL DEFAULT '' ");
		$r2 = q("ALTER TABLE xlink ADD xlink_rating_text TEXT NOT NULL DEFAULT '' ");
		if(!$r1 || !$r2)
			return UPDATE_FAILED;
	}
	return UPDATE_SUCCESS;
}

function update_r1133() {
	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) { 
		$r1 = q("CREATE TABLE xperm (
			xp_id serial NOT NULL,
			xp_client varchar( 20 ) NOT NULL DEFAULT '',
			xp_channel bigint NOT NULL DEFAULT '0',
			xp_perm varchar( 64 ) NOT NULL DEFAULT '',
			PRIMARY KEY (xp_id) )");
		$r2 = 0;
		foreach(array('xp_client', 'xp_channel', 'xp_perm') as $fld)
			$r2 += ((q("create index $fld on xperm ($fld)") == false) ? 0 : 1);
			
		$r = (($r1 && $r2) ? true : false);
	}
	else {
		$r = q("CREATE TABLE IF NOT EXISTS `xperm` (
			`xp_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`xp_client` VARCHAR( 20 ) NOT NULL DEFAULT '',
			`xp_channel` INT UNSIGNED NOT NULL DEFAULT '0',
			`xp_perm` VARCHAR( 64 ) NOT NULL DEFAULT '',
			KEY `xp_client` (`xp_client`),
			KEY `xp_channel` (`xp_channel`),
			KEY `xp_perm` (`xp_perm`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ");
	}
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1134() {
	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) { 
		$r1 = q("ALTER TABLE xlink ADD xlink_static numeric(1) NOT NULL DEFAULT '0' ");
		$r2 = q("create index xlink_static on xlink ( xlink_static ) ");
		$r = $r1 && $r2;
	}
	else
		$r = q("ALTER TABLE xlink ADD xlink_static TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( xlink_static ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1135() {
	$r = q("ALTER TABLE xlink ADD xlink_sig TEXT NOT NULL DEFAULT ''");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1136() {
	$r1 = q("alter table item add item_unseen smallint not null default '0' ");
	$r2 = q("create index item_unseen on item ( item_unseen ) ");
	$r3 = q("update item set item_unseen = 1 where ( item_flags & 2 ) > 0 ");

	if($r1 && $r2 && $r3)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1137() {
	$r1 = q("alter table site add site_valid smallint not null default '0' ");
	$r2 = q("create index site_valid on site ( site_valid ) ");
	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1138() {
	$r1 = q("alter table outq add outq_priority smallint not null default '0' ");
	$r2 = q("create index outq_priority on outq ( outq_priority ) ");
	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1139() {
	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) { 
		$r1 = q("ALTER TABLE channel ADD channel_lastpost timestamp NOT NULL DEFAULT '0001-01-01 00:00:00'");
		$r2 = q("create index channel_lastpost on channel ( channel_lastpost ) ");
		$r = $r1 && $r2;
	}
	else
		$r = q("ALTER TABLE `channel` ADD `channel_lastpost` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `channel_dirdate` , ADD INDEX ( `channel_lastpost` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1140() {
	$r = q("select * from clients where true");
	$x = false;
	if($r) {
		foreach($r as $rr) {
			$m = q("INSERT INTO xperm (xp_client, xp_channel, xp_perm) VALUES ('%s', %d, '%s') ",
				dbesc($rr['client_id']),
				intval($rr['uid']),
				dbesc('all')
			);
			if(! $m)
				$x = true;
		}
	}
	if($x)
		return UPDATE_FAILED;
	return UPDATE_SUCCESS;
}


function update_r1141() {
		if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) { 
		$r1 = q("ALTER TABLE menu ADD menu_created timestamp NOT NULL DEFAULT '0001-01-01 00:00:00', ADD menu_edited timestamp NOT NULL DEFAULT '0001-01-01 00:00:00'");
		$r2 = q("create index menu_created on menu ( menu_created ) ");
		$r3 = q("create index menu_edited on menu ( menu_edited ) ");
		$r = $r1 && $r2;
	}
	else
		$r = q("ALTER TABLE menu ADD menu_created DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00', ADD menu_edited DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00', ADD INDEX ( menu_created ), ADD INDEX ( menu_edited ) ");

	$t = datetime_convert();
	q("update menu set menu_created = '%s', menu_edited = '%s' where true",
		dbesc($t),
		dbesc($t)
	);


	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1142() {

	$r1 = q("alter table site add site_dead smallint not null default '0' ");
	$r2 = q("create index site_dead on site ( site_dead ) ");
	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;


}

function update_r1143() {

	$r1 = q("ALTER TABLE abook ADD abook_incl TEXT NOT NULL DEFAULT ''");
	$r2 = q("ALTER TABLE abook ADD abook_excl TEXT NOT NULL DEFAULT '' ");
	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1144() {
	$r = q("select flags, id from attach where flags != 0");
	if($r) {
		foreach($r as $rr) {
			if($rr['flags'] & 1) {
				q("update attach set is_dir = 1 where id = %d",
					intval($rr['id'])
				);
			}
			if($rr['flags'] & 2) {
				q("update attach set os_storage = 1 where id = %d",
					intval($rr['id'])
				);
			}
		}
	}

	return UPDATE_SUCCESS;
}

function update_r1145() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) { 
		$r1 = q("ALTER TABLE event ADD event_status char(255) NOT NULL DEFAULT '', 
			ADD event_status_date timestamp NOT NULL DEFAULT '0001-01-01 00:00:00', 
			ADD event_percent SMALLINT NOT NULL DEFAULT '0', 
			ADD event_repeat TEXT NOT NULL DEFAULT '' ");
		$r2 = q("create index event_status on event ( event_status )");
		$r = $r1 && $r2;
	}
	else {
		$r = q("ALTER TABLE `event` ADD `event_status` CHAR( 255 ) NOT NULL DEFAULT '',
			ADD `event_status_date` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
			ADD `event_percent` SMALLINT NOT NULL DEFAULT '0',
			ADD `event_repeat` TEXT NOT NULL DEFAULT '',
			ADD INDEX ( `event_status` ) ");
 	}
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1146() {

	$r1 = q("alter table event add event_sequence smallint not null default '0' ");
	$r2 = q("create index event_sequence on event ( event_sequence ) ");
	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1147() {

    $r1 = q("alter table event add event_priority smallint not null default '0' ");
    $r2 = q("create index event_priority on event ( event_priority ) ");
    if($r1 && $r2)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;
}

function update_r1148() {
    $r1 = q("alter table likes add i_mid char(255) not null default '' ");
    $r2 = q("create index i_mid on likes ( i_mid ) ");

    if($r1 && $r2)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;

}

function update_r1149() {
	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) { 
		$r1 = q("ALTER TABLE obj ADD obj_term CHAR( 255 ) NOT NULL DEFAULT '',
			ADD obj_url CHAR( 255 ) NOT NULL DEFAULT '',
			ADD obj_imgurl CHAR( 255 ) NOT NULL DEFAULT '',
			ADD obj_created timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
			ADD obj_edited timestamp NOT NULL DEFAULT '0001-01-01 00:00:00' ");
	}
	else {
		$r1 = q("ALTER TABLE obj ADD obj_term CHAR( 255 ) NOT NULL DEFAULT '',
			ADD obj_url CHAR( 255 ) NOT NULL DEFAULT '',
			ADD obj_imgurl CHAR( 255 ) NOT NULL DEFAULT '',
			ADD obj_created DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
			ADD obj_edited DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' ");
	}

	$r2 = q("create index obj_term on obj ( obj_term ) ");
	$r3 = q("create index obj_url on obj ( obj_url ) ");
	$r4 = q("create index obj_imgurl on obj ( obj_imgurl ) ");
	$r5 = q("create index obj_created on obj ( obj_created ) ");
	$r6 = q("create index obj_edited on obj ( obj_edited ) ");
	$r = $r1 && $r2 && $r3 && $r4 && $r5 && $r6;
    if($r)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;

}

function update_r1150() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) { 
		$r1 = q("ALTER TABLE app ADD app_created timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
			ADD app_edited timestamp NOT NULL DEFAULT '0001-01-01 00:00:00' ");
	}
	else {
		$r1 = q("ALTER TABLE app ADD app_created DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
			ADD app_edited DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' ");
	}

	$r2 = q("create index app_created on app ( app_created ) ");
	$r3 = q("create index app_edited on app ( app_edited ) ");

	$r = $r1 && $r2 && $r3;
    if($r)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;

}

function update_r1151() {

	$r3 = q("select likes.*, item.mid from likes left join item on likes.iid = item.id");
	if($r3) {
		foreach($r3 as $rr) {
			q("update likes set i_mid = '%s' where id = $d",
				dbesc($rr['mid']),
				intval($rr['id'])
			);
		}
	}


	return UPDATE_SUCCESS;

}

function update_r1152() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) { 

		$r1 = q("CREATE TABLE IF NOT EXISTS \"dreport\" (
  \"dreport_id\" serial NOT NULL,
  \"dreport_channel\" int(11) NOT NULL DEFAULT '0',
  \"dreport_mid\" char(255) NOT NULL DEFAULT '',
  \"dreport_site\" char(255) NOT NULL DEFAULT '',
  \"dreport_recip\" char(255) NOT NULL DEFAULT '',
  \"dreport_result\" char(255) NOT NULL DEFAULT '',
  \"dreport_time\" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  \"dreport_xchan\" char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (\"dreport_id\") ");

	$r2 = q("create index \"dreport_mid\" on dreport (\"dreport_mid\") ");
	$r3 = q("create index \"dreport_site\" on dreport (\"dreport_site\") ");
	$r4 = q("create index \"dreport_time\" on dreport (\"dreport_time\") ");
	$r5 = q("create index \"dreport_xchan\" on dreport (\"dreport_xchan\") ");
	$r6 = q("create index \"dreport_channel\" on dreport (\"dreport_channel\") ");

	$r = $r1 && $r2 && $r3 && $r4 && $r5 && $r6;

	}
	else {
		$r = q("CREATE TABLE IF NOT EXISTS `dreport` (
  `dreport_id` int(11) NOT NULL AUTO_INCREMENT,
  `dreport_channel` int(11) NOT NULL DEFAULT '0',
  `dreport_mid` char(255) NOT NULL DEFAULT '',
  `dreport_site` char(255) NOT NULL DEFAULT '',
  `dreport_recip` char(255) NOT NULL DEFAULT '',
  `dreport_result` char(255) NOT NULL DEFAULT '',
  `dreport_time` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  `dreport_xchan` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`dreport_id`),
  KEY `dreport_mid` (`dreport_mid`),
  KEY `dreport_site` (`dreport_site`),
  KEY `dreport_time` (`dreport_time`),
  KEY `dreport_xchan` (`dreport_xchan`),
  KEY `dreport_channel` (`dreport_channel`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");

	}

    if($r)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;

}

function update_r1153() {

	$r1 = q("ALTER TABLE dreport ADD dreport_queue CHAR( 255 ) NOT NULL DEFAULT '' ");
	$r2 = q("create index dreport_queue on dreport ( dreport_queue) ");
    if($r1 && $r2)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;


}

function update_r1154() {

	$r = q("ALTER TABLE event ADD event_vdata text NOT NULL ");
    if($r)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;

}


function update_r1155() {

	$r1 = q("alter table site add site_type smallint not null default '0' ");
	$r2 = q("create index site_type on site ( site_type ) ");
	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1156() {
	$r1 = q("ALTER TABLE mail ADD conv_guid CHAR( 255 ) NOT NULL DEFAULT '' ");
	$r2 = q("create index conv_guid on mail ( conv_guid ) ");

	$r3 = q("select mail.id, mail.convid, conv.guid from mail left join conv on mail.convid = conv.id where true");
	if($r3) {
		foreach($r3 as $rr) {
			if($rr['convid']) {
				q("update mail set conv_guid = '%s' where id = %d",
					dbesc($rr['guid']),
					intval($rr['id'])
				);
			}
		}
	}
		
    if($r1 && $r2)
        return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1157() {
	$r1 = q("alter table site add site_project char(255) not null default '' ");
    $r2 = q("create index site_project on site ( site_project ) ");
    if($r1 && $r2)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;

}


function update_r1158() {
	$r = q("select attach.id, attach.data, channel_address from attach left join channel on attach.uid = channel_id where os_storage = 1 and not attach.data like '%%store%%' ");
	if($r) {
		foreach($r as $rr) {
			$has_slash = ((substr($rr['data'],0,1) === '/') ? true : false);
			q("update attach set data = '%s' where id = %d",
				dbesc('store/' . $rr['channel_address']. (($has_slash) ? '' : '/' . $rr['data'])),
				dbesc($rr['id'])
			);
		}
	}
	return UPDATE_SUCCESS;
}


function update_r1159() {
	$r = q("select attach.id, attach.data, attach.hash, channel_address from attach left join channel on attach.uid = channel_id where os_storage = 1 ");
	if($r) {
		foreach($r as $rr) {
			$x = dbunescbin($rr['data']);
			$has_slash = (($x === 'store/' . $rr['channel_address'] . '/') ? true : false); 
			if(($x === 'store/' . $rr['channel_address']) || ($has_slash)) {
				q("update attach set data = '%s' where id = %d",
					dbesc('store/' . $rr['channel_address']. (($has_slash) ? '' : '/' . $rr['hash'])),
					dbesc($rr['id'])
				);
			}
		}
	}
	return UPDATE_SUCCESS;
}


function update_r1160() {
	$r = q("alter table abook add abook_instance text not null default '' ");
	if($r)
		return UPDATE_SUCCESS;
    return UPDATE_FAILED;
}

function update_r1161() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) { 
		$r1 = q("CREATE TABLE \"iconfig\" (
  \"id\" serial NOT NULL,
  \"iid\" bigint NOT NULL DEFAULT '0',
  \"cat\" text NOT NULL DEFAULT '',
  \"k\" text NOT NULL DEFAULT '',
  \"v\" text NOT NULL DEFAULT '',
  PRIMARY_KEY(\"id\")
) ");
$r2 = q("create index \"iconfig_iid\" on iconfig (\"iid\") ");;
$r3 = q("create index \"iconfig_cat\" on iconfig (\"cat\") ");
$r4 = q("create index \"iconfig_k\" on iconfig (\"k\") ");
	$r = $r1 && $r2 && $r3 && $r4;
	}
	else {
		$r = q("CREATE TABLE IF NOT EXISTS `iconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `iid` int(11) NOT NULL DEFAULT '0',
  `cat` char(255) NOT NULL DEFAULT '',
  `k` char(255) NOT NULL DEFAULT '',
  `v` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `iid` (`iid`),
  KEY `cat` (`cat`),
  KEY `k` (`k`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ");

	}

    if($r)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;
}

function update_r1162() {
	$r1 = q("alter table iconfig add sharing int not null default '0' ");

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES)
		$r2 = q("create index \"iconfig_sharing\" on iconfig (\"sharing\") "); 
	else 
		$r2 = q("alter table iconfig add index ( sharing ) ");
    if($r1 && $r2)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;
}

function update_r1163() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r1 = q("alter table channel add channel_moved text not null default '' ");
		$r2 = q("create index \"channel_channel_moved\" on channel (\"channel_moved\") ");
	} 
	else {
		$r1 = q("alter table channel add channel_moved char(255) not null default '' ");
		$r2 = q("alter table channel add index ( channel_moved ) ");
	}
    if($r1 && $r2)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;
}

function update_r1164() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r1 = q("CREATE TABLE \"abconfig\" (
			\"id\" serial  NOT NULL,
		 	\"chan\" text NOT NULL,
			\"xchan\" text NOT NULL,
			\"cat\" text NOT NULL,
			\"k\" text NOT NULL,
			\"v\" text NOT NULL,
			PRIMARY KEY (\"id\") ");
		$r2 = q("create index \"abconfig_chan\" on abconfig (\"chan\") ");
		$r3 = q("create index \"abconfig_xchan\" on abconfig (\"xchan\") ");
		$r4 = q("create index \"abconfig_cat\" on abconfig (\"cat\") ");
		$r5 = q("create index \"abconfig_k\" on abconfig (\"k\") ");
		$r = $r1 && $r2 && $r3 && $r4 && $r5;
	}
	else {
		$r = q("CREATE TABLE IF NOT EXISTS `abconfig` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`chan` char(255) NOT NULL DEFAULT '',
			`xchan` char(255) NOT NULL DEFAULT '',
			`cat` char(255) NOT NULL DEFAULT '',
			`k` char(255) NOT NULL DEFAULT '',
			`v` mediumtext NOT NULL,
			PRIMARY KEY (`id`),
			KEY `chan` (`chan`),
			KEY `xchan` (`xchan`),
			KEY `cat` (`cat`),
			KEY `k` (`k`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ");

	}
    if($r)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;
}

function update_r1165() {

	$r1 = q("alter table hook add hook_version int not null default '0' ");

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES)
		$r2 = q("create index \"hook_version_idx\" on hook (\"hook_version\") "); 
	else 
		$r2 = q("alter table hook add index ( hook_version ) ");
    if($r1 && $r2)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;
}

function update_r1166() {

	$r = q("alter table source add src_tag text not null default '' ");
    if($r)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;
}

function update_r1167() {

	$r1 = q("alter table app add app_deleted int not null default '0' ");
	$r2 = q("alter table app add app_system int not null default '0' ");

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r3 = q("create index \"app_deleted_idx\" on app (\"app_deleted\") "); 
		$r4 = q("create index \"app_system_idx\" on app (\"app_system\") "); 
	}
	else { 
		$r3 = q("alter table app add index ( app_deleted ) ");
		$r4 = q("alter table app add index ( app_system ) ");
	}

	if($r1 && $r2 && $r3 && $r4)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1168() {

	$r1 = q("alter table obj add obj_quantity int not null default '0' ");

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r2 = q("create index \"obj_quantity_idx\" on obj (\"obj_quantity\") "); 
	}
	else { 
		$r2 = q("alter table obj add index ( obj_quantity ) ");
	}

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1169() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r1 = q("ALTER TABLE `addon` CHANGE `timestamp` `tstamp` numeric( 20 ) UNSIGNED NOT NULL DEFAULT '0' ");
		$r2 = q("ALTER TABLE `addon` CHANGE `name` `aname` text NOT NULL DEFAULT '' ");
		$r3 = q("ALTER TABLE `hook` CHANGE `function` `fn` text NOT NULL DEFAULT '' ");

	}
	else {
		$r1 = q("ALTER TABLE `addon` CHANGE `timestamp` `tstamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0' ");
		$r2 = q("ALTER TABLE `addon` CHANGE `name` `aname` CHAR(255) NOT NULL DEFAULT '' ");
		$r3 = q("ALTER TABLE `hook` CHANGE `function` `fn` CHAR(255) NOT NULL DEFAULT '' ");
	}

	if($r1 && $r2 && $r3)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1170() {

	$r1 = q("drop table fcontact");	
	$r2 = q("drop table ffinder");	
	$r3 = q("drop table fserver");	
	$r4 = q("drop table fsuggest");	
	$r5 = q("drop table spam");	

	if($r1 && $r2 && $r3 && $r4 && $r5)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1171() {

		$r1 = q("ALTER TABLE verify CHANGE `type` `vtype` varchar(32) NOT NULL DEFAULT '' ");
		$r2 = q("ALTER TABLE tokens CHANGE `scope` `auth_scope` varchar(512) NOT NULL DEFAULT '' ");
		$r3 = q("ALTER TABLE auth_codes CHANGE `scope` `auth_scope` varchar(512) NOT NULL DEFAULT '' ");
		$r4 = q("ALTER TABLE clients CHANGE `name` `clname` TEXT ");
		$r5 = q("ALTER TABLE session CHANGE `data` `sess_data` TEXT NOT NULL ");
		$r6 = q("ALTER TABLE register CHANGE `language` `lang` varchar(16) NOT NULL DEFAULT '' ");

	if($r1 && $r2 && $r3 && $r4 && $r5 && $r6)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;



}

function update_r1172() {

	$r1 = q("ALTER TABLE term CHANGE `type` `ttype` int(3) NOT NULL DEFAULT '0' ");

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r2 = q("ALTER TABLE groups CHANGE `name` `gname` TEXT NOT NULL ");
		$r3 = q("ALTER TABLE profile CHANGE `name` `fullname` TEXT NOT NULL ");
		$r4 = q("ALTER TABLE profile CHANGE `with` `partner` TEXT NOT NULL ");
		$r5 = q("ALTER TABLE profile CHANGE `work` `employment` TEXT NOT NULL ");
	}
	else {
		$r2 = q("ALTER TABLE groups CHANGE `name` `gname` char(255) NOT NULL DEFAULT '' ");
		$r3 = q("ALTER TABLE profile CHANGE `name` `fullname` char(255) NOT NULL DEFAULT '' ");
		$r4 = q("ALTER TABLE profile CHANGE `with` `partner` char(255) NOT NULL DEFAULT '' ");
		$r5 = q("ALTER TABLE profile CHANGE `work` `employment` TEXT NOT NULL ");
	}
	if($r1 && $r2 && $r3 && $r4 && $r5)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1173() {


	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r1 = q("ALTER TABLE notify CHANGE `name` `xname` TEXT NOT NULL ");
		$r2 = q("ALTER TABLE notify CHANGE `date` `created` timestamp NOT NULL DEFAULT '0001-01-01 00:00:00' ");
		$r3 = q("ALTER TABLE notify CHANGE `type` `ntype` numeric(3) NOT NULL DEFAULT '0' ");
	}
	else {
		$r1 = q("ALTER TABLE notify CHANGE `name` `xname` char(255) NOT NULL DEFAULT '' ");
		$r2 = q("ALTER TABLE notify CHANGE `date` `created` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' ");
		$r3 = q("ALTER TABLE notify CHANGE `type` `ntype` smallint(3) NOT NULL DEFAULT '0' ");
	}

	if($r1 && $r2 && $r3)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1174() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r1 = q("ALTER TABLE event CHANGE `type` `etype` varchar(255) NOT NULL DEFAULT '' ");
		$r2 = q("ALTER TABLE event CHANGE `start` `dtstart` timestamp NOT NULL DEFAULT '0001-01-01 00:00:00' ");
		$r3 = q("ALTER TABLE event CHANGE `finish` `dtend` timestamp NOT NULL DEFAULT '0001-01-01 00:00:00' ");
		$r4 = q("ALTER TABLE event CHANGE `ignore` `dismissed` numeric(1) NOT NULL DEFAULT '0' ");
		$r5 = q("ALTER TABLE attach CHANGE `data` `content` bytea NOT NULL ");
		$r6 = q("ALTER TABLE photo CHANGE `data` `content` bytea NOT NULL ");
	}
	else {
		$r1 = q("ALTER TABLE event CHANGE `type` `etype` char(255) NOT NULL DEFAULT '' ");
		$r2 = q("ALTER TABLE event CHANGE `start` `dtstart` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' ");
		$r3 = q("ALTER TABLE event CHANGE `finish` `dtend` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' ");
		$r4 = q("ALTER TABLE event CHANGE `ignore` `dismissed` tinyint(1) NOT NULL DEFAULT '0' ");
		$r5 = q("ALTER TABLE attach CHANGE `data` `content` longblob NOT NULL ");
		$r6 = q("ALTER TABLE photo CHANGE `data` `content` mediumblob NOT NULL ");
	}

	if($r1 && $r2 && $r3 && $r4 && $r5 && $r6)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}

function update_r1175() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r1 = q("ALTER TABLE item CHANGE `object` `obj` text NOT NULL");
		$r2 = q("ALTER TABLE photo CHANGE `size` `filesize` bigint NOT NULL DEFAULT '0' ");
		$r3 = q("ALTER TABLE photo CHANGE `scale` `imgscale` numeric(3) NOT NULL DEFAULT '0' ");
		$r4 = q("ALTER TABLE photo CHANGE `type` `mimetype` varchar(128) NOT NULL DEFAULT 'image/jpeg' ");

	}
	else {
		$r1 = q("ALTER TABLE item CHANGE `object` `obj` text NOT NULL");
		$r2 = q("ALTER TABLE photo CHANGE `size` `filesize` int(10) unsigned NOT NULL DEFAULT '0' ");
		$r3 = q("ALTER TABLE photo CHANGE `scale` `imgscale` tinyint(3) unsigned NOT NULL DEFAULT '0' ");
		$r4 = q("ALTER TABLE photo CHANGE `type` `mimetype` char(128) NOT NULL DEFAULT 'image/jpeg' ");

	}

	if($r1 && $r2 && $r3 && $r4)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;

}


function update_r1176() {

	$r = q("select * from item_id where true");
	if($r) {
		foreach($r as $rr) {
			\Zotlabs\Lib\IConfig::Set($rr['iid'],'system',$rr['service'],$rr['sid'],true);
		}
	}
	return UPDATE_SUCCESS;

}

function update_r1177() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r1 = q("alter table event add cal_id bigint NOT NULL DEFAULT '0'");
		$r2 = q("create index \"event_cal_idx\" on event (\"cal_id\") "); 

		$r3 = q("CREATE TABLE \"cal\" (
			\"cal_id\" serial  NOT NULL,
		 	\"cal_aid\" bigint NOT NULL DEFAULT '0',
		 	\"cal_uid\" bigint NOT NULL DEFAULT '0',
		 	\"cal_hash\" text NOT NULL,
			\"cal_name\" text NOT NULL,
			\"uri\" text NOT NULL,
			\"logname\" text NOT NULL,
			\"pass\" text NOT NULL,
			\"ctag\" text NOT NULL,
			\"synctoken\" text NOT NULL,
			\"cal_types\" text NOT NULL,
			PRIMARY KEY (\"cal_id\") ");
		$r4 = q("create index \"cal_hash_idx\" on cal (\"cal_hash\") ");
		$r5 = q("create index \"cal_name_idx\" on cal (\"cal_name\") ");
		$r6 = q("create index \"cal_types_idx\" on cal (\"cal_types\") ");
		$r7 = q("create index \"cal_aid_idx\" on cal (\"cal_aid\") ");
		$r8 = q("create index \"cal_uid_idx\" on cal (\"cal_uid\") ");
		$r = $r1 && $r2 && $r3 && $r4 && $r5 && $r6 && $r7 && $r8;
	}
	else {
		$r1 = q("alter table event add cal_id int(10) unsigned NOT NULL DEFAULT '0', 
			add index ( cal_id ) ");

		$r2 = q("CREATE TABLE IF NOT EXISTS `cal` (
			`cal_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`cal_aid` int(10) unsigned NOT NULL DEFAULT '0',
			`cal_uid` int(10) unsigned NOT NULL DEFAULT '0',
			`cal_hash` varchar(255) NOT NULL DEFAULT '',
			`cal_name` varchar(255) NOT NULL DEFAULT '',
			`uri` varchar(255) NOT NULL DEFAULT '',
			`logname` varchar(255) NOT NULL DEFAULT '',
			`pass` varchar(255) NOT NULL DEFAULT '',
			`ctag` varchar(255) NOT NULL DEFAULT '',
			`synctoken` varchar(255) NOT NULL DEFAULT '',
			`cal_types` varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY (`cal_id`),
			KEY `cal_aid` (`cal_aid`),
			KEY `cal_uid` (`cal_uid`),
			KEY `cal_hash` (`cal_hash`),
			KEY `cal_name` (`cal_name`),
			KEY `cal_types` (`cal_types`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ");

		$r = $r1 && $r2;
	}

    if($r)
        return UPDATE_SUCCESS;
    return UPDATE_FAILED;
}


function update_r1178() {

	$c2 = null;

	$c1 = q("SELECT channel_id, channel_hash from channel where true");
	if($c1) {
		$c2 = q("SELECT id, chan from abconfig where true");
		if($c2) {
			for($x = 0; $x < count($c2); $x ++) {
				foreach($c1 as $c) {
					if($c['channel_hash'] == $c2[$x]['chan']) {
						$c2[$x]['chan'] = $c['channel_id'];
						break;
					}
				}
			}
		}
	}

	$r1 = q("ALTER TABLE abconfig CHANGE chan chan int(10) unsigned NOT NULL DEFAULT '0' ");

	if($c2) {
		foreach($c2 as $c) {
			q("UPDATE abconfig SET chan = %d where id = %d",
				intval($c['chan']),
				intval($c['id'])
			);
		}
	}

	if($r1)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1179() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r1 = q("CREATE TABLE atoken (
  atoken_id serial NOT NULL,
  atoken_aid bigint NOT NULL DEFAULT 0,
  atoken_uid bigint NOT NULL DEFAULT 0,
  atoken_name varchar(255) NOT NULL DEFAULT '',
  atoken_token varchar(255) NOT NULL DEFAULT '',
  atoken_expires timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (atoken_id)) ");
	$r2 = q("create index atoken_aid on atoken (atoken_aid)");
	$r3 = q("create index atoken_uid on atoken (atoken_uid)");
	$r4 = q("create index atoken_name on atoken (atoken_name)");
	$r5 = q("create index atoken_token on atoken (atoken_token)");
	$r6 = q("create index atoken_expires on atoken (atoken_expires)");

	$r = $r1 && $r2 && $r3 && $r4 && $r5 && $r6;
 
	}
	else {
		$r = q("CREATE TABLE IF NOT EXISTS `atoken` (
  `atoken_id` int(11) NOT NULL AUTO_INCREMENT,
  `atoken_aid` int(11) NOT NULL DEFAULT 0,
  `atoken_uid` int(11) NOT NULL DEFAULT 0,
  `atoken_name` char(255) NOT NULL DEFAULT '',
  `atoken_token` char(255) NOT NULL DEFAULT '',
  `atoken_expires` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY (`atoken_id`),
  KEY `atoken_aid` (`atoken_aid`),
  KEY `atoken_uid` (`atoken_uid`),
  KEY `atoken_name` (`atoken_name`),
  KEY `atoken_token` (`atoken_token`),
  KEY `atoken_expires` (`atoken_expires`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");
	}
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
	
}

function update_r1180() {

	require_once('include/perm_upgrade.php');

	$r1 = q("select * from channel where true");
	if($r1) {
		foreach($r1 as $rr) {
			perm_limits_upgrade($rr);
			autoperms_upgrade($rr);
		}
	}

	$r2 = q("select * from abook where true");
	if($r2) {
		foreach($r2 as $rr) {
			perm_abook_upgrade($rr);
		}
	}
	
	$r = $r1 && $r2;
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1181() {
	if(\Zotlabs\Lib\System::get_server_role() == 'pro') {
		q("update account set account_level = 5 where true");
	}
	return UPDATE_SUCCESS;
}

function update_r1182() {

	$r1 = q("alter table site add site_version varchar(32) not null default '' ");

	if($r1)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1183() {

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$r1 = q("alter table hook ALTER COLUMN priority TYPE smallint");
		$r2 = q("alter table hook ALTER COLUMN priority SET NOT NULL");
		$r3 = q("alter table hook ALTER COLUMN priority SET DEFAULT '0'");
		$r1 = $r1 && $r2 && $r3;
	}
	else {
		$r1 = q("alter table hook CHANGE priority priority smallint NOT NULL DEFAULT '0' ");
	}
	$r2 = q("create index priority_idx on hook (priority)");

	if($r1 && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1184() {

	$r1 = q("alter table site add site_crypto text not null default '' ");

	if($r1)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}
