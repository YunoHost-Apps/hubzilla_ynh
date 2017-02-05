<?php

/**
 * Name: Chord Generator
 * Description: Guitar Chord Generator Application
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 */

require_once('addon/chords/Mod_Chords.php');

function chords_load() {
	Zotlabs\Extend\Hook::register('load_pdl', 'addon/chords/chords.php', '\\Chords::chords_pdl');
}

function chords_unload() {
	Zotlabs\Extend\Hook::unregister_by_file('addon/chords/chords.php');

}

class Chords {
	static public function chords_pdl(&$x) {
		if($x['module'] === 'chords')
			$x['layout'] = '[region=aside][widget=chords][/widget][/region]';
	}
}

function widget_chords($args) {

	$keys = array('A','Bb','B', 'C','Db','D','Eb','E','F','Gb','G','Ab');

	$chords = '<div class="widget"><h3>' . t('Quick Reference') . '</h3>';

	$chords .=  '<table border="1">';

	foreach($keys as $k) {	
		$chords .=  '<tr>';
		$chords .=  "<td><a href=\"chords/$k\"> $k </a></td>";
		$chords .=  "<td><a href=\"chords/{$k}m\"> {$k}m </a></td>";
		$chords .=  "<td><a href=\"chords/{$k}7\"> {$k}7 </a></td>";
		$chords .= '</tr>';
	}
	$chords .= '</table></div>';

	return $chords;

}






