API files
=========

List file storage (attach DB)

GET /api/z/1.0/files


Options:

	- hash
		return only entries matching hash (exactly)

	- filename
		return only entries matching filename (substring)

	- filetype
		return only entries matching filetype/mimetype (substring)

	- start
		start at record (default 0)

	- records
		number of records to return or 0 for unlimited



Example: 

curl -u mychannel:mypassword https://xyz.macgirvin.com/api/z/1.0/files -d filetype=multipart/mixed


Returns:

	{
	
	    "success": true,
	    "results": [
	        {
	            "id": "1",
	            "aid": "1",
	            "uid": "2",
	            "hash": "44ee8b2a1a7f36dea07b93b7747a2383a1bc0fdd08339e8928bfcbe45f65d939",
	            "filename": "Profile Photos",
	            "filetype": "multipart/mixed",
	            "filesize": "0",
	            "revision": "0",
	            "folder": "",
	            "os_storage": "1",
	            "is_dir": "1",
	            "is_photo": "0",
	            "flags": "0",
	            "created": "2016-01-02 21:51:17",
	            "edited": "2016-01-02 21:51:17",
	            "allow_cid": "",
	            "allow_gid": "",
	            "deny_cid": "",
	            "deny_gid": ""
	        },
	        {
	            "id": "12",
	            "aid": "1",
	            "uid": "2",
	            "hash": "71883f1fc64af33889229cbc79c5a056deeec5fc277d765f182f19073e1b2998",
	            "filename": "Cover Photos",
	            "filetype": "multipart/mixed",
	            "filesize": "0",
	            "revision": "0",
	            "folder": "",
	            "os_storage": "1",
	            "is_dir": "1",
	            "is_photo": "0",
	            "flags": "0",
	            "created": "2016-01-15 00:24:33",
	            "edited": "2016-01-15 00:24:33",
	            "allow_cid": "",
	            "allow_gid": "",
	            "deny_cid": "",
	            "deny_gid": ""
	        },
	        {
	            "id": "16",
	            "aid": "1",
	            "uid": "2",
	            "hash": "f48f7ec3278499d1dd86b72c3207beaaf4717b07df5cc9b373f14d7aad2e1bcd",
	            "filename": "2016-01",
	            "filetype": "multipart/mixed",
	            "filesize": "0",
	            "revision": "0",
	            "folder": "",
	            "os_storage": "1",
	            "is_dir": "1",
	            "is_photo": "0",
	            "flags": "0",
	            "created": "2016-01-22 03:24:55",
	            "edited": "2016-01-22 03:26:57",
	            "allow_cid": "",
	            "allow_gid": "",
	            "deny_cid": "",
	            "deny_gid": ""
	        }
		]
	}
