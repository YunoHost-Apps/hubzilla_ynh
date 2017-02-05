<?php

/*
 * Name: pong
 * Description: pong
 *
 */



function pong_module() {}




function pong_content(&$a) {

$o = <<< EOT

<link rel="stylesheet" href="addon/pong/style.css" />
<div style="float: left; margin-right: 1em;">
<div id="gamediv">
	<div id="titleScreen">
		<h1>Pong!</h1>
		<p>This game is based on the HTML5 elements <b>canvas</b> and <b>audio</b>. With some simple javascript to make it work.</p>
		<p>To play this game you need a modern web browser with support for HTML5. Audio only works with Firefox 3.5+ at the moment.</p>
		<p class="vcard">Made by <a href="http://daverix.net/" class="url fn" target="_top" rel="me">David Laurell</a></p>
		<button id="playButton">Play!</button>
	</div>
	<div id="playScreen">
		<canvas width="640" height="360" id="gameCanvas">
			<p>Your browser <b>does not</b> support HTML5!</p>
			<p>Download <a href="http://firefox.com">Firefox3.6</a> for the full experience or another with good HTML5 support. The game is tested in Firefox 3.0+, Chromium 4+, Chrome 4 beta, Opera and Internet Explorer 8. To get the audio to work you are required to use Firefox for the moment.</p>
			<p>Visit the <a href="http://daverix.net/projects/pong/">project page</a> for more info.</p>
		</canvas>
		<div id="computerScore">0</div>
		<div id="playerScore">0</div>
		<div class="ingamebuttons">
			<button id="pauseButton">Pause</button>
			<button id="soundButton">Turn off sound</button>
		</div>
		<div id="pauseText">Paused</div>
	</div>
</div>
</div>
<audio id="bounceLeft" autobuffer> 
	<source src="addon/pong/ping.wav" type="audio/x-wav" /> 
	<source src="addon/pong/ping.ogg" type="audio/ogg" /> 
</audio> 
<audio id="bounceRight" autobuffer> 
	<source src="addon/pong/pong.wav" type="audio/x-wav" /> 
	<source src="addon/pong/pong.ogg" type="audio/ogg" /> 
</audio> 
<audio id="bounceWall" autobuffer> 
	<source src="addon/pong/bom.wav" type="audio/x-wav" /> 
	<source src="addon/pong/bom.ogg" type="audio/ogg" /> 
</audio> 
 
<script type="text/javascript" src="addon/pong/game.js"></script> 

EOT;

return $o;

}