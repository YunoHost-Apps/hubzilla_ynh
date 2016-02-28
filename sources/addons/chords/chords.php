<?php

/**
 * Name: Chord Generator
 * Description: Guitar Chord Generator Application
 * Version: 1.0
 * Author: Mike Macgirvin <mike@zothub.com>
 * Maintainer: none
 */


function chords_load() {
	register_hook('app_menu', 'addon/chords/chords.php', 'chords_app_menu');
}

function chords_unload() {
	unregister_hook('app_menu', 'addon/chords/chords.php', 'chords_app_menu');

}

function chords_app_menu($a,&$b) {
	$b['app_menu'][] = '<div class="app-title"><a href="chords">Guitar Chords</a></div>'; 
}


function chords_module() {}


function chords_content($a) {


$o .=  '<h3>Guitar Chords</h3>';
$o .=  'The complete online guitar chord dictionary<br />';
$args = '';
$l = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
  if(isset($_POST['chord']) && strlen($_POST['chord']))
    $args .= escapeshellarg(ucfirst(trim($_POST['chord'])));
  if((strlen($args)) && (isset($_POST['tuning'])) && (strlen($_POST['tuning'])))
      $args .= ' '.escapeshellarg($_POST['tuning']);
  if((strlen($args)) && (isset($_POST['lefty'])))
      $args .= ' lefty';
}

if((! strlen($args)) && (! stristr(basename($_SERVER['QUERY_STRING']),'chords')) && strlen(basename($_SERVER['QUERY_STRING'])))
  $args = escapeshellarg(ucfirst(basename($_SERVER['QUERY_STRING'])));
 
$tunings = array("","openg", "opene", "dadgad");
$tnames = array("Em11 [Standard] (EADGBE)",
                "G/D [Drop D] (DGDGBD)","Open E (EBEG#BE)","Dsus4 (DADGAD)");
$t = ((isset($_POST['tuning'])) ? $_POST['tuning'] : '');
if(isset($_POST['lefty']) && $_POST['lefty']  == '1')
  $l = 'checked="checked"';

  $ch = ((isset($_POST['chord'])) ? $_POST['chord'] : '');
$o .=  <<< EOT

<form action="chords" method="post">
Chord name: (ex: Em7) <input type="text" name="chord" value="$ch" onfocus="this.select();" size="16" />
&nbsp;&nbsp;Tuning: <select name="tuning" size="5">

EOT;
  for($x = 0; $x < count($tunings); $x ++) {

    $o .=  '<option value="'.$tunings[$x].'"'.
     (($tunings[$x] == $t) ? 'selected="selected"' : '').
     '>'.$tnames[$x].'</option>';
  }

$o .=  <<< EOT
</select>
Left-Handed: <input type="checkbox" name="lefty" value="1" $l />
<br />
<input type="submit" name="submit" value="Submit" />
</form>
<br /><br />
EOT;

if(strlen($args)) {
  $o .=  '<pre>';
  $o .= shell_exec("addon/chords/chord ".$args);
  $o .=  '</pre>';
}
else {

$o .=  <<< EOT

<p>
This is a fairly comprehensive and complete guitar chord dictionary which will list most of the available ways to play a certain chord, starting from the base of the fingerboard up to a few frets beyond the twelfth fret (beyond which everything repeats). A couple of non-standard tunings are provided for the benefit of slide players, etc. 
<p />
<p>
Chord names start with a root note (A-G) and may include sharps (#) and flats (b). This software will parse most of the standard naming conventions such as maj, min, dim, sus(2 or 4), aug, with optional repeating elements.
</p>
<p>
Valid examples include  A, A7, Am7, Amaj7, Amaj9, Ammaj7, Aadd4, Asus2Add4, E7b13b11 ...
</p>
Quick Reference:<br />

EOT;

$keys = array('A','Bb','B', 'C','Db','D','Eb','E','F','Gb','G','Ab');
$o .=  '<table border="1">';
$o .=  "<tr>";
foreach($keys as $k)
  $o .=  "<td><a href=\"chords/$k\"> $k </a></td>";
$o .=  "</tr><tr>";
foreach($keys as $k)
  $o .=  "<td><a href=\"chords/{$k}m\"> {$k}m </a></td>";
$o .=  "</tr><tr>";
foreach($keys as $k)
  $o .=  "<td><a href=\"chords/{$k}7\"> {$k}7 </a></td>";
$o .=  "</tr>";
$o .=  "</table>";

}

return $o;

}










