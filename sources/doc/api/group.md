API group
=========

GET /api/z/1.0/group


Description: list privacy groups


Returns: DB tables of all privacy groups. 

To use with API group_members, provide either 'group_id' from the id element returned in this call, or 'group_name' from the gname returned in this call.


	[
	
	    {
	        "id": "1",
	        "hash": "966c946394f3e2627bbb8a55026b5725e582407098415c02f85232de3f3fde76Friends",
	        "uid": "2",
	        "visible": "0",
	        "deleted": "0",
	        "gname": "Friends"
	    },
	    {
	        "id": "2",
	        "hash": "852ebc17f8c3ed4866f2162e384ded0f9b9d1048f93822c0c84196745f6eec66Family",
	        "uid": "2",
	        "visible": "1",
	        "deleted": "0",
	        "gname": "Family"
	    },
	    {
	        "id": "3",
	        "hash": "cc3cb5a7f9818effd7c7c80a58b09a189b62efa698a74319117babe33ee30ab9Co-workers",
	        "uid": "2",
	        "visible": "0",
	        "deleted": "0",
	        "gname": "Co-workers"
	    }
	]