API albums
==========

Description: list photo albums

GET /api/z/1.0/albums


Output:

	text - textual name

	total - number of photos in this album

	url - web URL

	urlencode - textual name, urlencoded

	bin2hex - textual name using bin2hex (which is used in the web URL link)


Example:


	{
	
	    "success": true,
	    "albums": [
	        {
	            "text": "/",
	            "total": "2",
	            "url": "https://xyz.macgirvin.com/photos/hubzilla/album/",
	            "urlencode": "",
	            "bin2hex": ""
	        },
		        {
	            "text": "2016-01",
	            "total": "6",
	            "url": "https://xyz.macgirvin.com/photos/hubzilla/album/323031362d3031",
	            "urlencode": "2016-01",
	            "bin2hex": "323031362d3031"
	        },
	        {
	            "text": "2016-02",
	            "total": "7",
	            "url": "https://xyz.macgirvin.com/photos/hubzilla/album/323031362d3032",
	            "urlencode": "2016-02",
	            "bin2hex": "323031362d3032"
	        },
	        {
	            "text": "Cover Photos",
	            "total": "5",
	            "url": "https://xyz.macgirvin.com/photos/hubzilla/album/436f7665722050686f746f73",
	            "urlencode": "Cover+Photos",
	            "bin2hex": "436f7665722050686f746f73"
	        },
	        {
	            "text": "Profile Photos",
	            "total": "26",
	            "url": "https://xyz.macgirvin.com/photos/hubzilla/album/50726f66696c652050686f746f73",
	            "urlencode": "Profile+Photos",
	            "bin2hex": "50726f66696c652050686f746f73"
	        }
	    ]
	
	}
