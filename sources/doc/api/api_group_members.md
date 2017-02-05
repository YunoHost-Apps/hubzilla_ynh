API group_members
=================

GET /api/z/1.0/group_members



Required:

	group_id or group_name


Returns:

	group_member+abook+xchan (DB join) for each member of the privacy group 


	[
	
	    {
	        "id": "1",
	        "uid": "2",
	        "gid": "1",
	        "xchan": "pgcJx1IQjuPkx8aI9qheJlBMZzJz-oTPjHy3h5pWlOVOriBO_cSiUhhqwhuZ74TYJ8_ECO3pPiRMWC0q8YPCQg",
	        "abook_id": "2",
	        "abook_account": "1",
	        "abook_channel": "2",
	        "abook_xchan": "pgcJx1IQjuPkx8aI9qheJlBMZzJz-oTPjHy3h5pWlOVOriBO_cSiUhhqwhuZ74TYJ8_ECO3pPiRMWC0q8YPCQg",
	        "abook_my_perms": "218555",
	        "abook_their_perms": "0",
	        "abook_closeness": "0",
	        "abook_created": "2016-01-02 21:16:26",
	        "abook_updated": "2016-01-02 21:16:26",
	        "abook_connected": "0000-00-00 00:00:00",
	        "abook_dob": "0000-00-00 00:00:00",
	        "abook_flags": "0",
	        "abook_blocked": "0",
	        "abook_ignored": "0",
	        "abook_hidden": "0",
	        "abook_archived": "0",
	        "abook_pending": "0",
	        "abook_unconnected": "0",
	        "abook_self": "1",
	        "abook_feed": "0",
	        "abook_profile": "",
	        "abook_incl": "",
	        "abook_excl": "",
	        "abook_instance": "",
	        "xchan_hash": "pgcJx1IQjuPkx8aI9qheJlBMZzJz-oTPjHy3h5pWlOVOriBO_cSiUhhqwhuZ74TYJ8_ECO3pPiRMWC0q8YPCQg",
	        "xchan_guid": "lql-1VnxtiO4-WF0h72wLX1Fu8szzHDOXgQaTbELwXW77k8AKFfh-hYr70vqMrc3SSvWN-Flrc5HFhRTWB7ICw",
	        "xchan_guid_sig": "PafvEL0VpKfxATxlCqDjfOeSIMdmpr3iU7X-Sysa1h5LzDpjSXsjO37tYZL-accb1M5itLlfnW5epkTa5I4flsW21zSY1A2jCuBQUTLLGV7rNyyBy7lgqJUFvAMRx0TfXzP9lcaPqlM9T1tA6jfWOsOmkdzwofGeXBnsjGfjsO2xdGYe6vwjOU0DSavukvzDMnOayB9DekpvDnaNBTxeGLM45Skzr7ZEMcNF7TeXMbnvpfLaALYEKeQs9bGH-UgAG8fBWgzVAzeBfx_XSR1rdixjyiZGP0kq0h35SlmMPcEjliodOBFwMXqpXFB7Ibp4F6o6te2p2ErViJccQVG8VNKB6SbKNXY6bhP5zVcVsJ-vR-p4xXoYJJvzTN7yTDsGAXHOLF4ZrXbo5yi5gFAlIrTLAF2EdWQwxSGyLRWKxG8PrDkzEzX6cJJ0VRcLh5z6OI5QqQNdeghPZbshMFMJSc_ApCPi9_hI4ZfctCIOi3T6bdgTNKryLm5fhy_eqjwLAZTGP-aUBgLZpb1mf2UojBn6Ey9cCyq-0T2RWyk-FcIcbV4qJ-p_8oODqw13Qs5FYkjLr1bGBq82SuolkYrXEwQClxnrfKa4KYc2_eHAXPL01iS9zVnI1ySOCNJshB97Odpooc4wk7Nb2Fo-Q6THU9zuu0uK_-JbK7IIl6go2qA",
	        "xchan_pubkey": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA18JB76lyP4zzL/y7BCej\neJnfZIWZNtM3MZvI1zEVMWmmwOS+u/yH8oPwyaDk4Y/tnj8GzMPj1lCGVRcd8EJa\nNrCMd50HODA5EsJtxpsOzRcILYjOcTtIAG1K4LtKqELi9ICAaFp0fNfa+Jf0eCek\nvPusx2/ORhy+o23hFoSMhL86o2gmaiRnmnA3Vz4ZMG92ieJEDMXt9IA1EkIqS4y5\nBPZfVPLD1pv8iivj+dtN1XjwplgjUbtxmU0/Ej808nHppscRIqx/XJ0XZU90oNGw\n/wYoK2EzJlPbRsAkwNqoFrAYlr5HPpn4BJ2ebFYQgWBUraD7HwS5atsQEaxGfO21\nlUP0+lDg9t3CXvudDj0UG1jiEKbVIGA+4aG0GN2DSC5AyRq/GRxqyay5W2vQbAZH\nyvxPGrZFO24I65g3pjhpjEsLqZ4ilTLQoLMs0drCIcRm5RxMUo4s/LMg16lT4cEk\n1qRtk2X0Sb1AMQQ2uRXiVtWz77QHMONEYkf6OW4SHbwcv5umvlv69NYEGfCcbgq0\nAV7U4/BWztUz/SWj4r194CG43I9I8dmaEx9CFA/XMePIAXQUuABfe1QMOR6IxLpq\nTHG1peZgHQKeGz4aSGrhQkZNNoOVNaZoIfcvopxcHDTZLigseEIaPPha4WFYoKPi\nUPbZ5o8gTLc750uzrnb2jwcCAwEAAQ==\n-----END PUBLIC KEY-----\n",
	        "xchan_photo_mimetype": "image/png",
	        "xchan_photo_l": "https://xyz.macgirvin.com/photo/profile/l/2",
	        "xchan_photo_m": "https://xyz.macgirvin.com/photo/profile/m/2",
	        "xchan_photo_s": "https://xyz.macgirvin.com/photo/profile/s/2",
	        "xchan_addr": "teller@xyz.macgirvin.com",
	        "xchan_url": "https://xyz.macgirvin.com/channel/teller",
	        "xchan_connurl": "https://xyz.macgirvin.com/poco/teller",
	        "xchan_follow": "https://xyz.macgirvin.com/follow?f=&url=%s",
	        "xchan_connpage": "",
	        "xchan_name": "Teller",
	        "xchan_network": "zot",
	        "xchan_instance_url": "",
	        "xchan_flags": "0",
	        "xchan_photo_date": "2016-10-19 01:26:50",
	        "xchan_name_date": "2016-01-02 21:16:26",
	        "xchan_hidden": "0",
	        "xchan_orphan": "0",
	        "xchan_censored": "0",
	        "xchan_selfcensored": "0",
	        "xchan_system": "0",
	        "xchan_pubforum": "0",
	        "xchan_deleted": "0"
	    },
	    {
	        "id": "12",
	        "uid": "2",
	        "gid": "1",
	        "xchan": "xuSMUYxw1djBB97qXsbrBN1nzJH_gFwQL6pS4zIy8fuusOfBxNlMiVb4h_q5tOEvpE7tYf1EsryjNciMuPIj5w",
	        "abook_id": "24",
	        "abook_account": "1",
	        "abook_channel": "2",
	        "abook_xchan": "xuSMUYxw1djBB97qXsbrBN1nzJH_gFwQL6pS4zIy8fuusOfBxNlMiVb4h_q5tOEvpE7tYf1EsryjNciMuPIj5w",
	        "abook_my_perms": "218555",
	        "abook_their_perms": "218555",
	        "abook_closeness": "80",
	        "abook_created": "2016-01-27 00:48:43",
	        "abook_updated": "2016-12-04 17:16:58",
	        "abook_connected": "2016-12-04 17:16:58",
	        "abook_dob": "0001-01-01 00:00:00",
	        "abook_flags": "0",
	        "abook_blocked": "0",
	        "abook_ignored": "0",
	        "abook_hidden": "0",
	        "abook_archived": "0",
	        "abook_pending": "0",
	        "abook_unconnected": "0",
	        "abook_self": "0",
	        "abook_feed": "0",
	        "abook_profile": "debb5236efb1626cfbad33ccb49892801e5f844aa04bf81f580cfa7d13204819",
	        "abook_incl": "",
	        "abook_excl": "",
	        "abook_instance": "",
	        "xchan_hash": "xuSMUYxw1djBB97qXsbrBN1nzJH_gFwQL6pS4zIy8fuusOfBxNlMiVb4h_q5tOEvpE7tYf1EsryjNciMuPIj5w",
	        "xchan_guid": "d5EMLlt1tHHZ0dANoA7B5Wq9UgXoWcFS9-gXOkL_AAejcPApoQRyxfHTuu8DoTbUaO-bYmX5HPuWuK9PHyqNmA",
	        "xchan_guid_sig": "CVWEMRPtzI1YcHfnnWHTuv3H964OAmSElgUfxMoX6RdQdxNpqb_POirpVuyP8s3W17mVCfO5V9IAjkg5iKcqCk6YcvOD_egmMy-AnM9TC1kKndQHw55CunD82Q8K_xBNSXkSROizcNkKh9DVLjJPFjW1AqtI4njkZ3EMgrWqnbFRM1qPToUoCY9zM3tEMHoAD9YX1zP90wl40LzfN-dtcNWpSBbiz9owou62uzLbN7mrCwKOMlXLjwwGswRnxIsEnb3O-FXOs8hs0mArKe9snq1-BKeD16LyzxgwlpVLElzIJZGEZGtMdIJgeRzKuBvPjsOIpQ1yAkuOpFJ3nGCM-IPOIIjAmyVl5zD3xPVcxxpZlJRn5fG1Y-gnqTgsrEQCA7M6XPWQdrdHU4akZfyUyFJDhv3uM-jon9VzrYTBw68R0WA-1Z8WafEHA4qh5OWAj85lUarwhr7iTiEckH51ypPCPs6VbT6Pw7yMaxfjFOcipashQagx0tfOlDhE5dQANOXKASFtH1J9-CZY2MQdLPQ6u54d5whuHKMGaJ0V68pnmZ2rOn7g344Ah2WCJrm17jj60QsRMorqRFj7GMdPIA1XB8Wrk88MuYOe3Dhyuu6ZWKI7YTWJS690ZVkKUqAiNHqj0W86DtaiPUc_mmGR0fHl4Gksnko3WmCFv9q2X2E",
	        "xchan_pubkey": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAoj2xCJktBA8Ww7Hp+ZNL\nrNuQpo8UB/bfvRkIy+yua3xpF1TuXcnAH61kyRz8vXgOu/l2CyxQbIoaGslCV5Sy\n8JKeNXe+IilUdSSEjMIwCPfSPsYnMHsSnHWmPmclvJwEtQUKOZmW5mMuVBvXy7D2\njomFwc69AYphdyys6eQ7Dcn6+FRBiQbyMprZ5lxyVW+O4DuXVNa3ej2ebx0gCJZ4\ntTIlBoKwEey91dY+FyKVFjdwfNczpmL7LgmZXqcVx+MG3mYgibwdVMiXVj5X06cs\nV9hJ5Xi+Aklsv/UWJtjw9FVt7y9TLptnhh4Ra6T/MDmnBBIAkOR7P/X8cRv078MT\nl0IMsP0RJcDEtTLtwHFVtDs6p52KDFqclKWbqmxmxqV3OTPVYtArRGIzgnJi/5ur\nHRr5G6Cif7QY3UowsIOf78Qvy28LwSbdymgBAWwPPKIviXWxGO+9kMWdmPSUQrWy\nK0+7YA9P9fBUFfn9Hc+p8SJQmQ6OAqLwrDGiPSOlGaNrbEqwqLGgIpXwK+lEFcFJ\n3SPOjJRWdR2whlMxvpwX+39+H7dWN3vSa3Al4/Sq7qW8yW2rYwf+eGyp4Z0lRR+8\nJxFMCwZkSw5g14YdlikAPojv5V1c6KuA5ieg8G1hwyONV7A4JHPyEdPt0W0TZi6C\nCOVkPaC3xGrguETZpJfVpwUCAwEAAQ==\n-----END PUBLIC KEY-----\n",
	        "xchan_photo_mimetype": "image/png",
	        "xchan_photo_l": "https://xyz.macgirvin.com/photo/9da63aa910ea14e1501ee1a749d181a6-4",
	        "xchan_photo_m": "https://xyz.macgirvin.com/photo/9da63aa910ea14e1501ee1a749d181a6-5",
	        "xchan_photo_s": "https://xyz.macgirvin.com/photo/9da63aa910ea14e1501ee1a749d181a6-6",
	        "xchan_addr": "cloner@xyz.macgirvin.com",
	        "xchan_url": "http://abc.macgirvin.com/channel/cloner",
	        "xchan_connurl": "http://abc.macgirvin.com/poco/cloner",
	        "xchan_follow": "https://xyz.macgirvin.com/follow?f=&url=%s",
	        "xchan_connpage": "",
	        "xchan_name": "Karen",
	        "xchan_network": "zot",
	        "xchan_instance_url": "",
	        "xchan_flags": "0",
	        "xchan_photo_date": "2016-03-31 19:59:20",
	        "xchan_name_date": "2016-01-26 23:23:42",
	        "xchan_hidden": "0",
	        "xchan_orphan": "0",
	        "xchan_censored": "0",
	        "xchan_selfcensored": "0",
	        "xchan_system": "0",
	        "xchan_pubforum": "0",
	        "xchan_deleted": "0"
	    }

	]