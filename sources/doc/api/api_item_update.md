API item/update
===============


Usage: POST /api/z/1.0/item/update

Description: item/update posts an item (typically a conversation item or post, but can be any item) using form input.  


Required:

- body

	text/bbcode contents by default.


Optional:

- $_FILES['media']

	uploaded media file to include with post

- title

	title of post/item

- contact_allow

	array of xchan.xchan_hash allowed to view this item

- group_allow

	array of group.hash allowed to view this item

- contact_deny

	array of xchan.xchan_hash not allowed to view this item

- group_deny

	array of group.hash not allowed to view this item

- coord

	geographic coordinates

- location

	freefrom location

- expire

	datetime this post will expire or be removed

- mimetype

	mimetype if not text/bbcode

- parent

	item.id of parent to this post (makes it a comment)

- parent_mid

	alternate form of parent using message_id

- remote_xchan

	xchan.xchan_hash of this message author if not the channel owner

- consensus

	boolean set to true if this is a consensus or voting item (default false)

- nocomment

	boolean set to true if comments are to be disabled (default false)

- origin

	do not use this without reading the code

- namespace

	persistent identity for a remote network or service

- remote_id

	message_id of this resource on a remote network or service

- message_id

	message_id of this item (leave unset to generate one)

- created

	datetime of message creation

- post_id

	existing item.id if this is an edit operation

- app

	application or network name to display with item

- categories

	comma separated categories for this item

- webpage

	item.page_type if not 0

- pagetitle

	for webpage and design elements, the 'page name'

- layout_mid

	item.mid of layout for this design element

- plink

	permalink for this item if different than the default

- verb

	activitystream verb for this item/activity

- obj_type

	activitystream object type for this item/activity



Example: 

curl -u mychannel:mypassword https://xyz.macgirvin.com/api/z/1.0/item/update -d body="hello world"


Returns:


	{
	
	    "success": true,
	    "item_id": "2245",
	    "item": {
	        "id": "2245",
	        "mid": "14135cdecf6b8e3891224e4391748722114da6668eebbcb56fe4667b60b88249@xyz.macgirvin.com",
	        "aid": "1",
	        "uid": "2",
	        "parent": "2245",
	        "parent_mid": "14135cdecf6b8e3891224e4391748722114da6668eebbcb56fe4667b60b88249@xyz.macgirvin.com",
	        "thr_parent": "14135cdecf6b8e3891224e4391748722114da6668eebbcb56fe4667b60b88249@xyz.macgirvin.com",
	        "created": "2016-12-03 20:00:12",
	        "edited": "2016-12-03 20:00:12",
	        "expires": "0001-01-01 00:00:00",
	        "commented": "2016-12-03 20:00:12",
	        "received": "2016-12-03 20:00:12",
	        "changed": "2016-12-03 20:00:12",
	        "comments_closed": "0001-01-01 00:00:00",
	        "owner_xchan": "pgcJx1IQjuPkx8aI9qheJlBMZzJz-oTPjHy3h5pWlOVOriBO_cSiUhhqwhuZ74TYJ8_ECO3pPiRMWC0q8YPCQg",
	        "author_xchan": "pgcJx1IQjuPkx8aI9qheJlBMZzJz-oTPjHy3h5pWlOVOriBO_cSiUhhqwhuZ74TYJ8_ECO3pPiRMWC0q8YPCQg",
	        "source_xchan": "",
	        "mimetype": "text/bbcode",
	        "title": "",
	        "body": "hello world",
	        "html": "",
	        "app": "",
	        "lang": "",
	        "revision": "0",
	        "verb": "http://activitystrea.ms/schema/1.0/post",
	        "obj_type": "http://activitystrea.ms/schema/1.0/note",
	        "obj": "",
	        "tgt_type": "",
	        "target": "",
	        "layout_mid": "",
	        "postopts": "",
	        "route": "",
	        "llink": "https://xyz.macgirvin.com/display/14135cdecf6b8e3891224e4391748722114da6668eebbcb56fe4667b60b88249@xyz.macgirvin.com",
	        "plink": "https://xyz.macgirvin.com/channel/mychannel/?f=&mid=14135cdecf6b8e3891224e4391748722114da6668eebbcb56fe4667b60b88249@xyz.macgirvin.com",
	        "resource_id": "",
	        "resource_type": "",
	        "attach": "",
	        "sig": "sa4TOQNfHtV13HDZ1tuQGWNBpZp-nWhT2GMrZEmelXxa_IvEepD2SEsCTWOBqM8OKPJLfNy8_i-ORXjrOIIgAa_aT8cw5vka7Q0C8L9eEb_LegwQ_BtH0CXO5uT30e_8uowkwzh6kmlVg1ntD8QqrGgD5jTET_fMQOIw4gQUBh40GDG9RB4QnPp_MKsgemGrADnRk2vHO7-bR32yQ0JI-8G-eyeqGaaJmIwkHoi0vXsfjZtU7ijSLuKEBWboNjKEDU89-vQ1c5Kh1r0pmjiDk-a5JzZTYShpuhVA-vQgEcADA7wkf4lJZCYNwu3FRwHTvhSMdF0nmyv3aPFglQDky38-SAXZyQSvd7qlABHGCVVDmYrYaiq7Dh4rRENbAUf-UJFHPCVB7NRg34R8HIqmOKq1Su99bIWaoI2zuAQEVma9wLqMoFsluFhxX58KeVtlCZlro7tZ6z619-dthS_fwt0cL_2dZ3QwjG1P36Q4Y4KrCTpntn9ot5osh-HjVQ01h1I9yNCj6XPgYJ8Im3KT_G4hmMDFM7H9RUrYLl2o9XYyiS2nRrf4aJHa0UweBlAY4zcQG34bw2AMGCY53mwsSArf4Hs3rKu5GrGphuwYX0lHa7XEKMglwBWPWHI49q7-oNWr7aWwn1FnfaMfl4cQppCMtKESMNRKm_nb9Dsh5e0",
	        "diaspora_meta": "",
	        "location": "",
	        "coord": "",
	        "public_policy": "",
	        "comment_policy": "contacts",
	        "allow_cid": "",
	        "allow_gid": "",
	        "deny_cid": "",
	        "deny_gid": "",
	        "item_restrict": "0",
	        "item_flags": "0",
	        "item_private": "0",
	        "item_origin": "1",
	        "item_unseen": "0",
	        "item_starred": "0",
	        "item_uplink": "0",
	        "item_consensus": "0",
	        "item_wall": "1",
	        "item_thread_top": "1",
	        "item_notshown": "0",
	        "item_nsfw": "0",
	        "item_relay": "0",
	        "item_mentionsme": "0",
	        "item_nocomment": "0",
	        "item_obscured": "0",
	        "item_verified": "1",
	        "item_retained": "0",
	        "item_rss": "0",
	        "item_deleted": "0",
	        "item_type": "0",
	        "item_hidden": "0",
	        "item_unpublished": "0",
	        "item_delayed": "0",
	        "item_pending_remove": "0",
	        "item_blocked": "0"
	    }

	}