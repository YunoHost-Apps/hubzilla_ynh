[h2]smilie[/h2]


Called when processing translation of emoticons. It is passed an array containing two sub-arrays:

	array(
		'texts' => array('text1','text2',...),
		'icons' => array('icon1','icon2',...)
	);

	texts is the emoticon text - for example ':-)' for a traditional smile face.
	icons is the HTML used as a replacement. For example
      '&lt;img class="smiley" src="https://localhost/images/smiley-smile.gif" alt=":-)" /&gt;'

	If adding or removing an entry from either array, the corresponding element from the matching array must also 
	be added or removed. Emoticons less than three characters in length or not recommended as they get triggered 
	incorrectly quite often. Extended emoticons are indicated by convention using a preceding colon, for example 
		
		:walrus_kissing_a_baby 