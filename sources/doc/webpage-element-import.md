## <a href="#webpage-element-import"></a>Webpage element import

There are two methods of importing webpage elements: uploading a zip file or 
referencing a local cloud files folder. Both methods require that the webpage 
elements are specified using a specific folder structure. The import tool makes 
it possible to import all the elements necessary to construct an entire website
or set of websites. The goal is to accommodate external development of webpages 
as well as tools to simplify and automate deployment on a hub.

### Folder structure
Element definitions must be stored in the repo root under folders called 

	/pages/
	/blocks/
	/layouts/


Each element of these types must be defined in an individual subfolder using two files: one JSON-formatted file for the metadata and one plain text file for the element content.

### Page elements
Page element metadata is specified in a JSON-formatted file called `page.json` with the following properties:

 * title
 * pagelink
 * mimetype
 * layout
 * contentfile

**Example** 

Files: 
	
	/pages/my-page/page.json
	/pages/my-page/my-page.bbcode
	
Content of `page.json`:
	
	{
		"title": "My Page",
		"pagelink": "mypage",
		"mimetype": "text/bbcode",
		"layout": "my-layout",
		"contentfile": "my-page.bbcode"
	}
	

### Layout elements
Layout element metadata is specified in a JSON-formatted file called `layout.json` with the following properties:

 * name
 * description
 * contentfile

**Example** 

Files:

	/layouts/my-layout/layout.json
	/layouts/my-layout/my-layout.bbcode
	
Content of `layout.json`:
	
	{
		"name": "my-layout",
		"description": "Layout for my project page",
		"contentfile": "my-layout.bbcode"
	}


### Block elements
Block element metadata is specified in a JSON-formatted file called `block.json` with the following properties:

 * name
 * title
 * mimetype
 * contentfile

**Example** 

Files:
	
	/blocks/my-block/block.json
	/blocks/my-block/my-block.html

Content of `block.json`:	

	
	{
		"name": "my-block",
		"title": "",
		"mimetype": "text/html",
		"contentfile": "my-block.html"
	}
	