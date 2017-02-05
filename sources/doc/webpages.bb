[b]Creating Web Pages[/b]

Hubzilla enables users to create static webpages.  To activate this feature, enable the web pages feature in your Additional Features section.

Once enabled, a new tab will appear on your channel page labelled &quot;Webpages&quot;.  Clicking this link will take you to the webpage editor.
Pages will be accessible at mydomain/page/username/pagelinktitle

The &quot;page link title&quot; box allows a user to specify the &quot;pagelinktitle&quot; of this URL.  If no page link title is set, we will set one for you automatically, using the message ID of the item.  

Beneath the page creation box, a list of existing pages will appear with an &quot;edit&quot; link.  Clicking this will take you to an editor, similar to that of the post editor, where you can make changes to your webpages.

See also: 

[zrl=[baseurl]/help/webpage-element-import]Webpage element import tool[/zrl]

[b]Using Blocks[/b]

Blocks can be parts of webpages. The basic HTML of a block looks like this
[code]
	<div>
		Block Content
	</div>

[/code]

If a block has text/html content type it can also contain menu elements. Sample content of
[code]
	<p>HTML block content</p> 
	[menu]menuname[/menu]

[/code]
will produce HTML like this
[code]
	<div>
		<p>HTML block content</p>
		<div>
			<ul>
				<li><a href="#">Link 1</a></li>
				<li><a href="#">Link 2</a></li>
				<li><a href="#">Link 3</a></li>
			</ul>
		</div>
	</div>

[/code]

Via the $content macro a block can also contain the actual webpage content. For this create a block with only
[code]
	$content

[/code]as content.

To make a block appear in the webpage it must be defined in the page layout inside a region.
[code]
	[region=aside]
		[block]blockname[/block]
	[/region]

[/code]

The block appearance can be manipulated in the page layout.

Custom classes can be assigned
[code]
	[region=aside]
		[block=myclass]blockname[/block]
	[/region]

[/code]
will produce this HTML
[code]
	<div class="myclass">
		Block Content
	</div>

[/code]

Via the wrap variable a block can be stripped off its wrapping <div></div> tag
[code]
	[region=aside]
		[block][var=wrap]none[/var]blockname[/block]
	[/region]

[/code]
will produce this HTML
[code]
	Block Content

[/code]


#include doc/macros/main_footer.bb;

