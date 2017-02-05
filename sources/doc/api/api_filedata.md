API filedata
=============

Provides the ability to download a file from cloud storage in chunks

GET /api/z/1.0/filedata


Required:

	- file_id
		attach.hash of desired file ('begins with' match)


Optional:

	- start
		starting byte of returned data in file (counting from 0)

	- length
		length (prior to base64 encoding) of chunk to download 


Returns:

	attach (DB) structure with base64 encoded 'content' comprised of the desired chunk



Example:

	https://xyz.macgirvin.com/api/z/1.0/filedata?f=&file_id=9f5217770fd&start=0&length=48

Returns:

	{
	
    	"attach": {
	        "id": "107",
    	    "aid": "1",
	        "uid": "2",
    	    "hash": "9f5217770fd55d563bd77f84d534d8e119a187514bbd391714626cd9c0e60207",
	        "creator": "pgcJx1IQjuPkx8aI9qheJlBMZzJz-oTPjHy3h5pWlOVOriBO_cSiUhhqwhuZ74TYJ8_ECO3pPiRMWC0q8YPCQg",
    	    "filename": "pcxtopbm.c",
	        "filetype": "application/octet-stream",
    	    "filesize": "3934",
	        "revision": "0",
    	    "folder": "",
	        "flags": "0",
    	    "is_dir": "0",
	        "is_photo": "0",
    	    "os_storage": "1",
	        "os_path": "",
    	    "display_path": "",
	        "content": "LyogcGN4dG9wYm0uYyAtIGNvbnZlcnQgUEMgcGFpbnRicnVzaCAoLnBjeCkgZmls",
    	    "created": "2016-07-24 23:13:01",
	        "edited": "2016-07-24 23:13:01",
    	    "allow_cid": "",
	        "allow_gid": "",
    	    "deny_cid": "",
	        "deny_gid": "",
    	    "start": 0,
	        "length": 48
    	}
	
	}