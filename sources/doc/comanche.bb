[b]Comanche Page Description Language[/b]

Comanche is a markup language similar to bbcode with which to create elaborate and complex web pages by assembling them from a series of components - some of which are pre-built and others which can be defined on the fly. Comanche uses a Page Decription Language to create these pages.

Comanche primarily chooses what content will appear in various regions of the page. The various regions have names and these names can change depending on what layout template you choose.

[b]Page Templates[/b]
Currently there are five layout templates, unless your site provides additional layouts.

[code]
	[b]default[/b]
	The default template defines a &quot;nav&quot; region across the top, &quot;aside&quot; as a fixed width sidebar, 
	&quot;content&quot; for the main content region, and &quot;footer&quot; for a page footer.


	[b]full[/b]
	The full template defines the same as the default template with the exception that there is no &quot;aside&quot; region.


	[b]choklet[/b]
	The choklet template provides a number of fluid layout styles which can be specified by flavour:

			(default flavour) - a two column layout similar to the "default" template, but more fluid
            bannertwo - a two column layout with a banner region, compatible with the "default" template on small displays
			three - three column layout (adds a "right_aside" region to the default template)
			edgestwo - two column layout with fixed side margins
			edgesthree - three column layout with fixed side margins
			full - three column layout with fixed side margins and adds a "header" region beneath the navigation bar

	[b]redable[/b] (sic)
	A template for reading longer texts full screen (so without navigation bar). Three columns: aside, content and right_aside.
	For maximum readability it is advised to only use the middle content column.

	[b]zen[/b]
	Gives you the freedom to do everything yourself. Just a blank page with a content region.

[/code]

To choose a layout template, use the 'template' tag.

[code]
	[template]full[/template]

[/code]

To choose the "choklet" template with the "three" flavour:

[code]
	[template=three]choklet[/template]

[/code]

The default template will be used if no other template is specified. The template can use any names it desires for content regions. You will be using 'region' tags to decide what content to place in the respective regions.

Three &quot;macros&quot; have been defined for your use.
[code]
	$htmlhead - replaced with the site head content.
	$nav - replaced with the site navigation bar content.
	$content - replaced with the main page content.

[/code]

By default, $nav is placed in the &quot;nav&quot; page region and $content is placed in the &quot;content&quot; region. You only need to use these macros if you wish to re-arrange where these items appear, either to change the order or to move them to other regions.


To select a theme for your page, use the 'theme' tag.
[code]
	[theme]suckerberg[/theme]

[/code]
This will select the theme named &quot;suckerberg&quot;. By default your channel's preferred theme will be used.

[code]
	[theme=passion]suckerberg[/theme]

[/code]
This will select the theme named &quot;suckerberg&quot; and select the &quot;passion&quot; schema (theme variant). Alternatively it may be possible to use a condensed theme notation for this. 

[code]
	[theme]suckerberg:passion[/theme]

[/code]

The condensed notation isn't part of Comanche itself but is recognised by $Projectname platform as a theme specifier.

[b]Regions[/b]
Each region has a name, as noted above. You will specify the region of interest using a 'region' tag, which includes the name. Any content you wish placed in this region should be placed between the opening region tag and the closing tag.

[code]
	[region=htmlhead]....content goes here....[/region]
	[region=aside]....content goes here....[/region]
	[region=nav]....content goes here....[/region]
	[region=content]....content goes here....[/region]

[/code]

[b]CSS and Javascript[/b]
We have the possibility to include javascript and css libraries in the htmlhead region. At present we make use of jquery (js), bootstrap (css/js) and foundation (css/js).
This will overwrite the selected themes htmlhead.

[code]
	[region=htmlhead]
		[css]bootstrap[/css]
		[js]jquery[/js]
		[js]bootstrap[/js]
	[/region]

[/code]

[b]Menus and Blocks[/b]
Your webpage creation tools allow you to create menus and blocks, in addition to page content. These provide a chunk of existing content to be placed in whatever regions and whatever order you specify. Each of these has a name which you define when the menu or block is created.

[code]
	[menu]mymenu[/menu]

[/code]
This places the menu called &quot;mymenu&quot; at this location on the page, which must be inside a region. 

[code]
	[menu=horizontal]mymenu[/menu]

[/code]
This places the menu called &quot;mymenu&quot; at this location on the page, which must be inside a region. Additionally it applies the "horizontal" class to the menu. "horizontal" is defined in the redbasic theme. It may or may not be available in other themes. 

[code]
	[menu][var=wrap]none[/var]mymenu[/menu]

[/code]
The variable [var=wrap]none[/var] in a block removes the wrapping div element from the menu.

[code]
	[block]contributors[/block]
[/code]
This places a block named &quot;contributors&quot; in this region.

[code]
	[block=someclass]contributors[/block]

[/code]
This places a block named &quot;contributors&quot; in this region. Additionally it applies the &quot;someclass&quot; class to the block. This replaces the default block classes &quot;bblock widget&quot;.

[code]
	[block][var=wrap]none[/var]contributors[/block]

[/code]
The variable [var=wrap]none[/var] in a block removes the wrapping div element from the block.

[b]Widgets[/b]
Widgets are executable apps provided by the system which you can place on your page. Some widgets take arguments which allows you to tailor the widget to your purpose. (TODO: list available widgets and arguments). The base system provides

[code]
	profile - widget which duplicates the profile sidebar of your channel page. This widget takes no arguments
	tagcloud - provides a tag cloud of categories
		count - maximum number of category tags to list	

[/code]

Widgets and arguments are specified with the 'widget' and 'var' tags.
[code]
	[widget=recent_visitors][var=count]24[/var][/widget]

[/code]

This loads the &quot;recent_visitors&quot; widget and supplies it with the argument &quot;count&quot; set to &quot;24&quot;. 
 
[b]Comments[/b]
The 'comment' tag is used to delimit comments. These comments will not appear on the rendered page.

[code]
	[comment]This is a comment[/comment]

[/code]

[b]Conditional Execution[/b]
You can use an 'if' construct to make decisions. These are currently based on system configuration variable or the current observer.

[code]
	[if $config.system.foo]
		... the configuration variable system.foo evaluates to 'true'.
	[else]
		... the configuration variable system.foo evaluates to 'false'.
 	[/if]

	[if $observer]
		... this content will only be show to authenticated viewers
	[/if]

[/code]

	The 'else' clause is optional. 

	Several tests are supported besides boolean evaluation.

[code]
	[if $config.system.foo == bar]
		... the configuration variable system.foo is equal to the string 'bar'
	[/if]
	[if $config.system.foo != bar]
		... the configuration variable system.foo is not equal to the string 'bar'
	[/if]
	[if $config.system.foo {} bar ]
		... the configuration variable system.foo is a simple array containing a value 'bar'
	[/if]
	[if $config.system.foo {*} bar]
		... the configuration variable system.foo is a simple array containing a key named 'bar'
	[/if]
[/code]

[b]Complex Example[/b]
[code]
	[comment]use an existing page template which provides a banner region plus 3 columns beneath it[/comment]

	[template]3-column-with-header[/template]

	[comment]Use the &quot;darknight&quot; theme[/comment]

	[theme]darkknight[/theme]

	[comment]Use the existing site navigation menu[/comment]

	[region=nav]$nav[/region]

	[region=side]

		[comment]Use my chosen menu and a couple of widgets[/comment]

		[menu]myfavouritemenu[/menu]

		[widget=recent_visitors]
			[var=count]24[/var]
			[var=names_only]1[/var]
		[/widget]

		[widget=tagcloud][/widget]
		[block]donate[/block]

	[/region]



	[region=middle]

		[comment]Show the normal page content[/comment]

		$content

	[/region]



	[region=right]

		[comment]Show my condensed channel &quot;wall&quot; feed and allow interaction if the observer is allowed to interact[/comment]

		[widget]channel[/widget]

	[/region]

[/code]

#include doc/macros/main_footer.bb;
