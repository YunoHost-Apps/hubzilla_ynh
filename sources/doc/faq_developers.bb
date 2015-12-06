[size=large][b]Frequently Asked Questions For Developers[/b][/size]

[toc]


[h3]What does $a mean?[/h3]
$a is a class defined in boot.php and passed all around $Projectname as a global reference variable. It defines everything necessary for the $Projectname application: Server variables, URL arguments, page structures, layouts, content, installed plugins, output device info, theme info, identity of the observer and (potential) page owner ... 

We don't ever create more than one instance and always modify the elements of the single instance. The mechanics of this are somewhat tricky. If you have a function that is passed $a and needs to modify $a you need to declare it as a reference with '&' e.g. 

[code]function foo(&$a) { $a->something = 'x'; // whatever };

*or* access it within your function  as a global variable via get_app()

function foo() {
    $a = get_app();
    $a->something = 'x';
}


function foo($a) { $a->something = 'x'; }; 

will *not* change the global app state. 

function foo() {
   get_app()->something = 'x';
}
[/code]


An example (large) &$a object showing some of its many members and structures-- in JSON format for easier readability-- is here:

[code]  {
  "category": null,
  "nav_sel": {
    "home": null,
    "community": null,
    "contacts": null,
    "directory": null,
    "settings": null,
    "notifications": null,
    "intros": null,
    "messages": null,
    "register": null,
    "manage": null,
    "profiles": null,
    "network": null,
    "help": "active"
  },
  "argc": 2,
  "install": false,
  "is_mobile": false,
  "timezone": "America/Los_Angeles",
  "sourcename": "",
  "module_loaded": true,
  "contacts": null,
  "interactive": true,
  "config": {
    "system": {
      "max_import_size": 200000,
      "logfile": "/tmp/hubzilla.log",
      "channels_active_monthly_stat": "3",
      "last_expire_day": "4",
      "loglevel": "4",
      "sitename": "Hubzilla",
      "access_policy": 0,
      "directory_mode": 0,
      "debugging": "1",
      "verify_email": 1,
      "register_text": "",
      "urlverify": "687474703a2f2f6875627a696c6c61",
      "register_policy": 2,
      "theme": "redbasic",
      "smarty3_folder": "/home/src/hubzilla/store/[data]/smarty3",
      "channels_total_stat": "4",
      "admin_email": "foo@bar.com",
      "channels_active_halfyear_stat": "3",
      "location_hash": "910792b7bf75296cbf238ae29a5493f3c78805812652d3f0396e88763a26ce1b",
      "local_posts_stat": "63",
      "lastpollcheck": "2015-11-03 07:40:38",
      "baseurl": "http://hubzilla",
      "config_loaded": true,
      "pubkey": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAuR4neYAxuWwZg34fqofU\nZg8y1YSTX39Tzhgcgn7QFCeH600NHJBHWXbPdS5imdYq6W+P1vtKxsVNLI9d01+j\ns3MF3amgEuJH0X+JLLjyittQksyAiukvh/o4MSit8mcYcXs8Dxaybe+KaY09N4ys\ndxKcn6EPlthUiQPJMPitybp4vYkw9LupWZOQWThz9ur6T5wnk9ehBIPFN8gYvKrT\nAG9RFfbq3y59rTOiSHNA2PIUMzo2HEh4QBVCvVolKt7GPhUM4Bze40VRe8ELZTPp\nyehNxEHyhHZfnC+XRVNlvSPXBU2vtE+zcok+5DXsKAqMt8YgFIThNEOLQKvff/lv\nsdGvk6jJZok7+9lKtYfwnNnRWf51aVVuSAO3aIIVLroLyhiji0KA7G5YRHeF1rNL\np88e8peMyUMCX2Svv1wudJzqOfWSvOpY0NLZrdGZXRN2/rXyHPRD/TtS3SNDdd7J\nYQUjyxGjF1/zB3xqvPr09s8tzXqJl9pZNcN9iz58oPBbTuGdUr8CJro/3nVHgkRf\nw7/zhapSW1UaroJjecrC9yWx5QUD3KNU51phsP9iHCFdMyPBdUHjmNvE5f7YJWBh\nO1rRKUoE3i+eHLYAWeYblFX7T+EKOCB2hd3NUrIqDL98OSpfDiZT7rf9PdcWCOY5\nuddm6KzwHjffl5kZd8MM8bMCAwEAAQ==\n-----END PUBLIC KEY-----\n",
      "addon": "converse",
      "lastpoll": "2015-11-04 07:40:01",
      "php_path": "/usr/bin/php",
      "allowed_themes": "redbasic",
      "sellpage": "",
      "prvkey": "-----BEGIN PRIVATE KEY-----\n-----END PRIVATE KEY-----\n",
      "directory_server": "https://red.zottel.red",
      "curl_ssl_ciphers": "ALL:!eNULL",
      "db_version": "1158"
    },
    "config": {
      "config_loaded": true
    },
    "feature": {
      "config_loaded": true
    },
    "2": {
      "redbasic": {
        "schema": "dark",
        "comment_indent": "",
        "toolicon_activecolour": "",
        "item_colour": "",
        "nav_gradient_top": "",
        "nav_active_icon_colour": "",
        "nav_active_gradient_top": "",
        "top_photo": "",
        "converse_width": "",
        "nav_min_opacity": "",
        "body_font_size": "",
        "reply_photo": "",
        "background_colour": "",
        "radius": "",
        "nav_gradient_bottom": "",
        "toolicon_colour": "",
        "nav_active_gradient_bottom": "",
        "nav_icon_colour": "",
        "narrow_navbar": "",
        "nav_bg": "",
        "comment_item_colour": "",
        "config_loaded": true,
        "banner_colour": "",
        "comment_border_colour": "",
        "align_left": "",
        "font_size": "",
        "font_colour": "",
        "nav_bd": "",
        "photo_shadow": "",
        "background_image": "",
        "link_colour": ""
      },
      "system": {
        "network_list_mode": "0",
        "post_joingroup": "0",
        "channel_list_mode": "0",
        "title_tosource": "0",
        "blocktags": "0",
        "photo_path": "%Y-%m",
        "suggestme": "0",
        "autoperms": "0",
        "hide_presence": "0",
        "channel_divmore_height": "400",
        "network_divmore_height": "400",
        "post_profilechange": "0",
        "channel_menu": "",
        "always_show_in_notices": "0",
        "use_browser_location": "0",
        "update_interval": "80000",
        "itemspage": "20",
        "attach_path": "%Y-%m",
        "permissions_role": "social",
        "vnotify": "2047",
        "post_newfriend": "0",
        "config_loaded": true,
        "no_smilies": "0",
        "evdays": "3",
        "user_scalable": "1"
      }
    }
  },
  "layout": {
    "region_aside": "\n&lt;div class=&quot;widget&quot;&gt;&lt;h3&gt;Documentation&lt;/h3&gt;&lt;ul class=&quot;nav nav-pills nav-stacked&quot;&gt;&lt;li&gt;&lt;a href=&quot;help/general&quot;&gt;Project/Site Information&lt;/a&gt;&lt;/li&gt;&lt;li&gt;&lt;a href=&quot;help/members&quot;&gt;For Members&lt;/a&gt;&lt;/li&gt;&lt;li&gt;&lt;a href=&quot;help/admins&quot;&gt;For Administrators&lt;/a&gt;&lt;/li&gt;&lt;li&gt;&lt;a href=&quot;help/develop&quot;&gt;For Developers&lt;/a&gt;&lt;/li&gt;&lt;/ul&gt;&lt;/div&gt;\n"
  },
  "is_sys": false,
  "content": null,
  "cid": null,
  "profile_uid": 0,
  "hooks": {
    "construct_page": [
      [
        "addon/converse/converse.php",
        "converse_content"
      ]
    ]
  },
  "strings": [],
  "js_sources": [
    "jquery.js",
    "library/justifiedGallery/jquery.justifiedGallery.min.js",
    "library/sprintf.js/dist/sprintf.min.js",
    "spin.js",
    "jquery.spin.js",
    "jquery.textinputs.js",
    "autocomplete.js",
    "library/jquery-textcomplete/jquery.textcomplete.js",
    "library/jquery.timeago.js",
    "library/readmore.js/readmore.js",
    "library/jgrowl/jquery.jgrowl_minimized.js",
    "library/cryptojs/components/core-min.js",
    "library/cryptojs/rollups/aes.js",
    "library/cryptojs/rollups/rabbit.js",
    "library/cryptojs/rollups/tripledes.js",
    "acl.js",
    "webtoolkit.base64.js",
    "main.js",
    "crypto.js",
    "library/jRange/jquery.range.js",
    "library/colorbox/jquery.colorbox-min.js",
    "library/jquery.AreYouSure/jquery.are-you-sure.js",
    "library/tableofcontents/jquery.toc.js",
    "library/bootstrap/js/bootstrap.min.js",
    "library/bootbox/bootbox.min.js",
    "library/bootstrap-tagsinput/bootstrap-tagsinput.js",
    "library/datetimepicker/jquery.datetimepicker.js",
    "library/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.js",
    "view/theme/redbasic/js/redbasic.js",
    "mod_help.js"
  ],
  "channel": {
    "channel_hash": "uRy0nF-urp6k_bFrkdtCc2EkBynwpgCJL_FQFoTwyw2Hep7AHkrSt1MZcHWV_8DQucNlHSY1vHgUNS2Fvoirpw",
    "channel_address": "testes",
    "channel_primary": "1",
    "channel_allow_gid": "",
    "xchan_deleted": "0",
    "xchan_connpage": "",
    "channel_r_storage": "1",
    "xchan_pubforum": "0",
    "channel_pubkey": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA7MP/xxsq/srA8I7m+WKf\nHlguwwg0b1tz+I3o+djp7b+wF8q03XPKQpYmKfXIj47vpAOu75nKA4Tn90lLymmk\nSXUHogOqOMy1CHoaVrAw2T2/tAeRoMAjAJ5IxSOAM7Xda0nVUK6FmfxPcvftKf9y\nPmvvFadXpaHT4JGPH0tszDhGXLkqlt9xSkIkpsgMA6emj/7bacc6x8eTdtvzo2e5\n/NyPXvBKH4henmYaKjq/4aIYZcBWYVGt6onxaP2j1cSNbksnOY7GbJl+hy95iFoZ\nDWGxiFwQd+CroiBbdlpVGp13cV/WKp2spZzlzkmCRGYoNbbM5RlgFLnmyTa4XMZE\nwnA3ZUB59MsrUJK+0H/utiZrpX5NQcFl33z8k5zB3pPnhc5S5/P+UJZRnqhet1wQ\n7AZVmdP30D75QD8LZ4SytZ1DHn/N76EsVhSADNMnUfEphs708V33Z0gFWultYDoK\nlvXUf4O0/V8GTufFHb6XdAiy92IUzrormXCpXoOmdOcJdaH9RnotZi/DkuQ0zP+Y\nCvxU9nrjyZvAwAdew//XFDjw4HoThVM4k4jzkIhCTlCao/yRnNM7A/i3OKcXq9wU\n7OZqcRfM9o0BFpZTIoXB7BMtpeioJcBi/7FUaV9U9uYLFuLL0qWa1YxLwfsN9rDk\n6A1gbhD60G9/dAbolp8xAHkCAwEAAQ==\n-----END PUBLIC KEY-----\n",
    "xchan_flags": "0",
    "channel_allow_cid": "",
    "xchan_censored": "0",
    "channel_w_pages": "128",
    "xchan_instance_url": "",
    "xchan_photo_s": "http://hubzilla/photo/profile/s/2",
    "channel_w_stream": "128",
    "channel_timezone": "America/Los_Angeles",
    "xchan_pubkey": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA7MP/xxsq/srA8I7m+WKf\nHlguwwg0b1tz+I3o+djp7b+wF8q03XPKQpYmKfXIj47vpAOu75nKA4Tn90lLymmk\nSXUHogOqOMy1CHoaVrAw2T2/tAeRoMAjAJ5IxSOAM7Xda0nVUK6FmfxPcvftKf9y\nPmvvFadXpaHT4JGPH0tszDhGXLkqlt9xSkIkpsgMA6emj/7bacc6x8eTdtvzo2e5\n/NyPXvBKH4henmYaKjq/4aIYZcBWYVGt6onxaP2j1cSNbksnOY7GbJl+hy95iFoZ\nDWGxiFwQd+CroiBbdlpVGp13cV/WKp2spZzlzkmCRGYoNbbM5RlgFLnmyTa4XMZE\nwnA3ZUB59MsrUJK+0H/utiZrpX5NQcFl33z8k5zB3pPnhc5S5/P+UJZRnqhet1wQ\n7AZVmdP30D75QD8LZ4SytZ1DHn/N76EsVhSADNMnUfEphs708V33Z0gFWultYDoK\nlvXUf4O0/V8GTufFHb6XdAiy92IUzrormXCpXoOmdOcJdaH9RnotZi/DkuQ0zP+Y\nCvxU9nrjyZvAwAdew//XFDjw4HoThVM4k4jzkIhCTlCao/yRnNM7A/i3OKcXq9wU\n7OZqcRfM9o0BFpZTIoXB7BMtpeioJcBi/7FUaV9U9uYLFuLL0qWa1YxLwfsN9rDk\n6A1gbhD60G9/dAbolp8xAHkCAwEAAQ==\n-----END PUBLIC KEY-----\n",
    "channel_w_chat": "128",
    "xchan_connurl": "http://hubzilla/poco/testes",
    "channel_guid_sig": "XXX",
    "xchan_name_date": "2015-10-09 00:45:41",
    "channel_expire_days": "0",
    "xchan_system": "0",
    "xchan_photo_date": "2015-10-09 00:45:41",
    "channel_startpage": "",
    "channel_deny_gid": "",
    "channel_lastpost": "2015-10-09 02:53:23",
    "xchan_photo_m": "http://hubzilla/photo/profile/m/2",
    "channel_passwd_reset": "",
    "xchan_hidden": "0",
    "xchan_selfcensored": "0",
    "xchan_photo_mimetype": "image/jpeg",
    "channel_a_republish": "128",
    "channel_w_tagwall": "128",
    "channel_r_stream": "1",
    "channel_w_comment": "128",
    "channel_system": "0",
    "channel_w_mail": "128",
    "channel_pageflags": "0",
    "xchan_network": "zot",
    "channel_id": "2",
    "xchan_guid": "Ok-ycNKQYMzjokLnIz5OTCF8M5f4CtRT4vJCUeUivJhIOJWk3ORwIQgGx3P5g2Yz79KxQ-rs_Cn2G_jsgM6hmw",
    "channel_removed": "0",
    "channel_dirdate": "2015-10-09 00:46:00",
    "channel_w_storage": "128",
    "channel_w_photos": "0",
    "channel_prvkey": "-----BEGIN PRIVATE KEY----------END PRIVATE KEY-----\n",
    "channel_guid": "Ok-ycNKQYMzjokLnIz5OTCF8M5f4CtRT4vJCUeUivJhIOJWk3ORwIQgGx3P5g2Yz79KxQ-rs_Cn2G_jsgM6hmw",
    "channel_max_friend_req": "0",
    "channel_w_wall": "128",
    "channel_r_abook": "1",
    "channel_max_anon_mail": "0",
    "channel_location": "",
    "channel_a_delegate": "128",
    "channel_deny_cid": "",
    "channel_r_profile": "1",
    "channel_name": "testes",
    "xchan_guid_sig": "XXX",
    "xchan_hash": "uRy0nF-urp6k_bFrkdtCc2EkBynwpgCJL_FQFoTwyw2Hep7AHkrSt1MZcHWV_8DQucNlHSY1vHgUNS2Fvoirpw",
    "channel_notifyflags": "703",
    "channel_theme": "redbasic",
    "channel_w_like": "2",
    "xchan_url": "http://hubzilla/channel/testes",
    "channel_default_group": "",
    "channel_r_photos": "0",
    "channel_account_id": "1",
    "xchan_addr": "testes@hubzilla",
    "channel_r_pages": "1",
    "channel_deleted": "0000-00-00 00:00:00",
    "xchan_orphan": "0",
    "xchan_follow": "http://hubzilla/follow?f=&amp;url=%s",
    "xchan_name": "testes",
    "xchan_photo_l": "http://hubzilla/photo/profile/l/2"
  },
  "page": {
    "content": "&lt;div id=&quot;help-content&quot; class=&quot;generic-content-wrapper&quot;&gt;\n\t&lt;div class=&quot;section-title-wrapper&quot;&gt;\n\t&lt;h2&gt;Hubzilla Documentation&lt;/h2&gt;\n\t&lt;/div&gt;\n\t&lt;div class=&quot;section-content-wrapper&quot;&gt;\n\t&lt;h2&gt;Documentation for Developers&lt;/h2&gt;&lt;br /&gt;&lt;br /&gt;&lt;h3&gt;Technical Documentation&lt;/h3&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/Zot---A-High-Level-Overview&quot; target=&quot;_blank&quot; &gt;A high level overview of Zot&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/zot&quot; target=&quot;_blank&quot; &gt;An introduction to Zot&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/zot_structures&quot; target=&quot;_blank&quot; &gt;Zot Stuctures&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/comanche&quot; target=&quot;_blank&quot; &gt;Comanche Page Descriptions&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/Creating-Templates&quot; target=&quot;_blank&quot; &gt;Creating Comanche Templates&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/Widgets&quot; target=&quot;_blank&quot; &gt;Widgets&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/plugins&quot; target=&quot;_blank&quot; &gt;Plugins&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/hooks&quot; target=&quot;_blank&quot; &gt;Hooks&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/doco&quot; target=&quot;_blank&quot; &gt;Contributing Documentation&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/DerivedTheme1&quot; target=&quot;_blank&quot; &gt;Creating Derivative Themes&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/schema_development&quot; target=&quot;_blank&quot; &gt;Schemas&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/Translations&quot; target=&quot;_blank&quot; &gt;Translations&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/developers&quot; target=&quot;_blank&quot; &gt;Developers&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/intro_for_developers&quot; target=&quot;_blank&quot; &gt;Intro for Developers&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/database&quot; target=&quot;_blank&quot; &gt;Database schema documentation&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/api_functions&quot; target=&quot;_blank&quot; &gt;API functions&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/api_posting&quot; target=&quot;_blank&quot; &gt;Posting to the red# using the API&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/developer_function_primer&quot; target=&quot;_blank&quot; &gt;Red Functions 101&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/doc/html/&quot; target=&quot;_blank&quot; &gt;Code Reference (Doxygen generated - sets cookies)&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/to_do_doco&quot; target=&quot;_blank&quot; &gt;To-Do list for the Red Documentation Project&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/to_do_code&quot; target=&quot;_blank&quot; &gt;To-Do list for Developers&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/roadmap&quot; target=&quot;_blank&quot; &gt;Version 3 roadmap&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/git_for_non_developers&quot; target=&quot;_blank&quot; &gt;Git for Non-Developers&lt;/a&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/dev_beginner&quot; target=&quot;_blank&quot; &gt;Step-for-step manual for beginning developers&lt;/a&gt;&lt;br /&gt;&lt;br /&gt;&lt;h3&gt;Frequently Asked Questions For Developers&lt;/h3&gt;&lt;br /&gt;&lt;a class=&quot;zrl&quot; href=&quot;http://hubzilla/help/faq_developers&quot; target=&quot;_blank&quot; &gt;FAQ For Developers&lt;/a&gt;&lt;br /&gt;&lt;br /&gt;&lt;h3&gt;External Resources&lt;/h3&gt;&lt;br /&gt;&lt;br /&gt;&lt;a href=&quot;https://zothub.com/channel/one&quot; target=&quot;_blank&quot; &gt;Development Channel&lt;/a&gt;&lt;br /&gt;&lt;a href=&quot;https://federated.social/channel/postgres&quot; target=&quot;_blank&quot; &gt;Postgres-specific Hubzilla Admin Support Channel&lt;/a&gt;&lt;br /&gt;\n\t&lt;/div&gt;\n&lt;/div&gt;\n&lt;script&gt;var homebase = &quot;http://hubzilla/channel/testes&quot;;&lt;/script&gt;",
    "page_title": "help",
    "title": "Help: Develop",
    "nav": "\t&lt;div class=&quot;container-fluid&quot;&gt;\n\t\t&lt;div class=&quot;navbar-header&quot;&gt;\n\t\t\t&lt;button type=&quot;button&quot; class=&quot;navbar-toggle&quot; data-toggle=&quot;collapse&quot; data-target=&quot;#navbar-collapse-1&quot;&gt;\n\t\t\t\t&lt;span class=&quot;icon-bar&quot;&gt;&lt;/span&gt;\n\t\t\t\t&lt;span class=&quot;icon-bar&quot;&gt;&lt;/span&gt;\n\t\t\t\t&lt;span class=&quot;icon-bar&quot;&gt;&lt;/span&gt;\n\t\t\t&lt;/button&gt;\n\t\t\t&lt;button id=&quot;expand-tabs&quot; type=&quot;button&quot; class=&quot;navbar-toggle&quot; data-toggle=&quot;collapse&quot; data-target=&quot;#tabs-collapse-1&quot;&gt;\n\t\t\t\t&lt;i class=&quot;icon-circle-arrow-down&quot; id=&quot;expand-tabs-icon&quot;&gt;&lt;/i&gt;\n\t\t\t&lt;/button&gt;\n\t\t\t&lt;button id=&quot;expand-aside&quot; type=&quot;button&quot; class=&quot;navbar-toggle&quot; data-toggle=&quot;offcanvas&quot; data-target=&quot;#region_1&quot;&gt;\n\t\t\t\t&lt;i class=&quot;icon-circle-arrow-right&quot; id=&quot;expand-aside-icon&quot;&gt;&lt;/i&gt;\n\t\t\t&lt;/button&gt;\n\t\t\t\t\t\t\t&lt;img class=&quot;dropdown-toggle fakelink&quot; data-toggle=&quot;dropdown&quot; id=&quot;avatar&quot; src=&quot;http://hubzilla/photo/profile/m/2&quot; alt=&quot;testes@hubzilla&quot;&gt;&lt;span class=&quot;caret&quot; id=&quot;usermenu-caret&quot;&gt;&lt;/span&gt;\n\t\t\t\t\t\t\t\t\t&lt;ul class=&quot;dropdown-menu&quot; role=&quot;menu&quot; aria-labelledby=&quot;avatar&quot;&gt;\n\t\t\t\t\t\t\t\t\t\t\t\t&lt;li role=&quot;presentation&quot;&gt;&lt;a href=&quot;channel/testes&quot; title=&quot;Your posts and conversations&quot; role=&quot;menuitem&quot; id=&quot;channel_nav_btn&quot;&gt;Home&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t\t\t\t\t\t\t&lt;li role=&quot;presentation&quot;&gt;&lt;a href=&quot;profile/testes&quot; title=&quot;Your profile page&quot; role=&quot;menuitem&quot; id=&quot;profile_nav_btn&quot;&gt;View Profile&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t\t\t\t\t\t\t&lt;li role=&quot;presentation&quot;&gt;&lt;a href=&quot;profiles/2&quot; title=&quot;Edit your profile&quot; role=&quot;menuitem&quot; id=&quot;profiles_nav_btn&quot;&gt;Edit Profile&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t\t\t\t\t\t\t&lt;li role=&quot;presentation&quot;&gt;&lt;a href=&quot;photos/testes&quot; title=&quot;Your photos&quot; role=&quot;menuitem&quot; id=&quot;photos_nav_btn&quot;&gt;Photos&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t\t\t\t\t\t\t&lt;li role=&quot;presentation&quot;&gt;&lt;a href=&quot;cloud/testes&quot; title=&quot;Your files&quot; role=&quot;menuitem&quot; id=&quot;cloud_nav_btn&quot;&gt;Files&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t\t\t\t\t\t\t&lt;li role=&quot;presentation&quot;&gt;&lt;a href=&quot;chat/testes/new&quot; title=&quot;Your chatrooms&quot; role=&quot;menuitem&quot; id=&quot;chat_nav_btn&quot;&gt;Chat&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t\t\t\t\t\t\t&lt;li role=&quot;presentation&quot; class=&quot;divider&quot;&gt;&lt;/li&gt;\n\t\t\t\t\t\t\t\t\t\t\t\t&lt;li role=&quot;presentation&quot;&gt;&lt;a href=&quot;settings&quot; title=&quot;Account/Channel Settings&quot; role=&quot;menuitem&quot; id=&quot;settings_nav_btn&quot;&gt;Settings&lt;/a&gt;&lt;/li&gt;\t\t\t\t\t\t&lt;li role=&quot;presentation&quot;&gt;&lt;a href=&quot;manage&quot; title=&quot;Manage Your Channels&quot; role=&quot;menuitem&quot; id=&quot;manage_nav_btn&quot;&gt;Channel Manager&lt;/a&gt;&lt;/li&gt;\t\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t&lt;li role=&quot;presentation&quot; class=&quot;divider&quot;&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li role=&quot;presentation&quot;&gt;&lt;a href=&quot;admin/&quot; title=&quot;Site Setup and Configuration&quot; role=&quot;menuitem&quot; id=&quot;admin_nav_btn&quot;&gt;Admin&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t&lt;li role=&quot;presentation&quot; class=&quot;divider&quot;&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li role=&quot;presentation&quot;&gt;&lt;a href=&quot;logout&quot; title=&quot;End this session&quot; role=&quot;menuitem&quot; id=&quot;logout_nav_btn&quot;&gt;Logout&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t\t\t\t\t\t&lt;/ul&gt;\n\t\t\t\t\t\t\t\t\t&lt;/div&gt;\n\t\t&lt;div class=&quot;collapse navbar-collapse&quot; id=&quot;navbar-collapse-1&quot;&gt;\n\t\t\t&lt;ul class=&quot;nav navbar-nav navbar-left&quot;&gt;\n\t\t\t\t\t\t\n\t\t\t\n\t\t\t\t\t\t\t&lt;li class=&quot; hidden-xs&quot;&gt;\n\t\t\t\t\t&lt;a href=&quot;network&quot; title=&quot;Your grid&quot; id=&quot;network_nav_btn&quot;&gt;&lt;i class=&quot;icon-th&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;net-update badge dropdown-toggle&quot; data-toggle=&quot;dropdown&quot; rel=&quot;#nav-network-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t\t&lt;ul id=&quot;nav-network-menu&quot; role=&quot;menu&quot; class=&quot;dropdown-menu&quot; rel=&quot;network&quot;&gt;\n\t\t\t\t\t\t\n\t\t\t\t\t\t&lt;li id=&quot;nav-network-mark-all&quot;&gt;&lt;a href=&quot;#&quot; onclick=&quot;markRead('network'); return false;&quot;&gt;Mark all grid notifications seen&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li class=&quot;empty&quot;&gt;Loading...&lt;/li&gt;\n\t\t\t\t\t&lt;/ul&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\t&lt;li class=&quot; visible-xs&quot;&gt;\n\t\t\t\t\t&lt;a href=&quot;network&quot; title=&quot;Your grid&quot; &gt;&lt;i class=&quot;icon-th&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;net-update badge&quot; rel=&quot;#nav-network-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\n\t\t\t\t\t\t\t&lt;li class=&quot; hidden-xs&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; href=&quot;channel/testes&quot; title=&quot;Channel home&quot; id=&quot;home_nav_btn&quot;&gt;&lt;i class=&quot;icon-home&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;home-update badge dropdown-toggle&quot; data-toggle=&quot;dropdown&quot; rel=&quot;#nav-home-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t\t&lt;ul id=&quot;nav-home-menu&quot; class=&quot;dropdown-menu&quot; rel=&quot;home&quot;&gt;\n\t\t\t\t\t\t\n\t\t\t\t\t\t&lt;li id=&quot;nav-home-mark-all&quot;&gt;&lt;a href=&quot;#&quot; onclick=&quot;markRead('home'); return false;&quot;&gt;Mark all channel notifications seen&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li class=&quot;empty&quot;&gt;Loading...&lt;/li&gt;\n\t\t\t\t\t&lt;/ul&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\t&lt;li class=&quot; visible-xs&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; href=&quot;channel/testes&quot; title=&quot;Channel home&quot; &gt;&lt;i class=&quot;icon-home&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;home-update badge&quot; rel=&quot;#nav-home-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\n\n\t\t\t\t\t\t\t&lt;li class=&quot; hidden-xs&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; href=&quot;mail/combined&quot; title=&quot;Private mail&quot; id=&quot;mail_nav_btn&quot;&gt;&lt;i class=&quot;icon-envelope&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;mail-update badge dropdown-toggle&quot; data-toggle=&quot;dropdown&quot; rel=&quot;#nav-messages-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t\t&lt;ul id=&quot;nav-messages-menu&quot; class=&quot;dropdown-menu&quot; rel=&quot;messages&quot;&gt;\n\t\t\t\t\t\t&lt;li id=&quot;nav-messages-see-all&quot;&gt;&lt;a href=&quot;mail/combined&quot;&gt;See all private messages&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li id=&quot;nav-messages-mark-all&quot;&gt;&lt;a href=&quot;#&quot; onclick=&quot;markRead('messages'); return false;&quot;&gt;Mark all private messages seen&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li class=&quot;empty&quot;&gt;Loading...&lt;/li&gt;\n\t\t\t\t\t&lt;/ul&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\t&lt;li class=&quot; visible-xs&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; href=&quot;mail/combined&quot; title=&quot;Private mail&quot; &gt;&lt;i class=&quot;icon-envelope&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;mail-update badge&quot; rel=&quot;#nav-messages-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\n\t\t\t\t\t\t\t&lt;li class=&quot; hidden-xs&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; href=&quot;events&quot; title=&quot;Event Calendar&quot; id='events_nav_btn'&gt;&lt;i class=&quot;icon-calendar&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;all_events-update badge dropdown-toggle&quot; data-toggle=&quot;dropdown&quot; rel=&quot;#nav-all_events-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t\t&lt;ul id=&quot;nav-all_events-menu&quot; class=&quot;dropdown-menu&quot; rel=&quot;all_events&quot;&gt;\n\t\t\t\t\t\t&lt;li id=&quot;nav-all_events-see-all&quot;&gt;&lt;a href=&quot;events&quot;&gt;See all events&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li id=&quot;nav-all_events-mark-all&quot;&gt;&lt;a href=&quot;#&quot; onclick=&quot;markRead('all_events'); return false;&quot;&gt;Mark all events seen&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li class=&quot;empty&quot;&gt;Loading...&lt;/li&gt;\n\t\t\t\t\t&lt;/ul&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\t&lt;li class=&quot; visible-xs&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; href=&quot;events&quot; title=&quot;Event Calendar&quot; &gt;&lt;i class=&quot;icon-calendar&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;all_events-update badge&quot; rel=&quot;#nav-all_events-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\n\t\t\t\t\t\t\t&lt;li class=&quot; hidden-xs&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; href=&quot;connections/ifpending&quot; title=&quot;Connections&quot; id=&quot;connections_nav_btn&quot;&gt;&lt;i class=&quot;icon-user&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;intro-update badge dropdown-toggle&quot; data-toggle=&quot;dropdown&quot; rel=&quot;#nav-intros-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t\t&lt;ul id=&quot;nav-intros-menu&quot; class=&quot;dropdown-menu&quot; rel=&quot;intros&quot;&gt;\n\t\t\t\t\t\t&lt;li id=&quot;nav-intros-see-all&quot;&gt;&lt;a href=&quot;&quot;&gt;&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li class=&quot;empty&quot;&gt;Loading...&lt;/li&gt;\n\t\t\t\t\t&lt;/ul&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\t&lt;li class=&quot; visible-xs&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; href=&quot;connections/ifpending&quot; title=&quot;Connections&quot; &gt;&lt;i class=&quot;icon-user&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;intro-update badge&quot; rel=&quot;#nav-intros-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\t\t\n\t\t\t\t\t\t\t&lt;li class=&quot; hidden-xs&quot;&gt;\n\t\t\t\t\t&lt;a href=&quot;notifications/system&quot; title=&quot;Notices&quot; id=&quot;notifications_nav_btn&quot;&gt;&lt;i class=&quot;icon-exclamation&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;notify-update badge dropdown-toggle&quot; data-toggle=&quot;dropdown&quot; rel=&quot;#nav-notify-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t\t&lt;ul id=&quot;nav-notify-menu&quot; class=&quot;dropdown-menu&quot; rel=&quot;notify&quot;&gt;\n\t\t\t\t\t\t&lt;li id=&quot;nav-notify-see-all&quot;&gt;&lt;a href=&quot;notifications/system&quot;&gt;See all notifications&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li id=&quot;nav-notify-mark-all&quot;&gt;&lt;a href=&quot;#&quot; onclick=&quot;markRead('notify'); return false;&quot;&gt;Mark all system notifications seen&lt;/a&gt;&lt;/li&gt;\n\t\t\t\t\t\t&lt;li class=&quot;empty&quot;&gt;Loading...&lt;/li&gt;\n\t\t\t\t\t&lt;/ul&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\t&lt;li class=&quot; visible-xs&quot;&gt;\n\t\t\t\t\t&lt;a href=&quot;notifications/system&quot; title=&quot;Notices&quot;&gt;&lt;i class=&quot;icon-exclamation&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t\t&lt;span class=&quot;notify-update badge&quot; rel=&quot;#nav-notify-menu&quot;&gt;&lt;/span&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\t\t\t&lt;/ul&gt;\n\t\t\t&lt;ul class=&quot;nav navbar-nav navbar-right&quot;&gt;\n\t\t\t\t&lt;li class=&quot;hidden-xs&quot;&gt;\n\t\t\t\t\t&lt;form method=&quot;get&quot; action=&quot;search&quot; role=&quot;search&quot;&gt;\n\t\t\t\t\t\t&lt;div id=&quot;nav-search-spinner&quot;&gt;&lt;/div&gt;&lt;input class=&quot;icon-search&quot; id=&quot;nav-search-text&quot; type=&quot;text&quot; value=&quot;&quot; placeholder=&quot;&amp;#xf002; @name, #tag, ?doc, content&quot; name=&quot;search&quot; title=&quot;Search site @name, #tag, ?docs, content&quot; onclick=&quot;this.submit();&quot;/&gt;\n\t\t\t\t\t&lt;/form&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\t&lt;li class=&quot;visible-xs&quot;&gt;\n\t\t\t\t\t&lt;a href=&quot;/search&quot; title=&quot;Search site @name, #tag, ?docs, content&quot;&gt;&lt;i class=&quot;icon-search&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t&lt;/li&gt;\n\n\t\t\t\t\t\t\t\t\t\t&lt;li class=&quot;&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; href=&quot;directory&quot; title=&quot;Channel Directory&quot; id=&quot;directory_nav_btn&quot;&gt;&lt;i class=&quot;icon-sitemap&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\n\t\t\t\t\t\t\t&lt;li class=&quot;&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; href=&quot;apps&quot; title=&quot;Applications, utilities, links, games&quot; id=&quot;apps_nav_btn&quot;&gt;&lt;i class=&quot;icon-cogs&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\n\t\t\t\t\t\t\t&lt;li class=&quot;active&quot;&gt;\n\t\t\t\t\t&lt;a class=&quot;&quot; target=&quot;hubzilla-help&quot; href=&quot;http://hubzilla/help?f=&amp;cmd=help/develop&quot; title=&quot;Help and documentation&quot; id=&quot;help_nav_btn&quot;&gt;&lt;i class=&quot;icon-question&quot;&gt;&lt;/i&gt;&lt;/a&gt;\n\t\t\t\t&lt;/li&gt;\n\t\t\t\t\t\t&lt;/ul&gt;\n\t\t&lt;/div&gt;\n\t&lt;/div&gt;\n",
    "htmlhead": "&lt;meta http-equiv=&quot;Content-Type&quot; content=&quot;text/html;charset=utf-8&quot; /&gt;\n&lt;base href=&quot;http://hubzilla/&quot; /&gt;\n&lt;meta name=&quot;viewport&quot; content=&quot;width=device-width, height=device-height, initial-scale=1, user-scalable=1&quot; /&gt;\n&lt;meta name=&quot;generator&quot; content=&quot;hubzilla 2015-11-03.1205H&quot; /&gt;\n\n&lt;!--[if IE]&gt;\n&lt;script src=&quot;http://hubzilla/library/html5.js&quot;&gt;&lt;/script&gt;\n&lt;![endif]--&gt;\n\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/library/font_awesome/css/font-awesome.min.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/library/bootstrap/css/bootstrap.min.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/library/bootstrap-tagsinput/bootstrap-tagsinput.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/view/css/bootstrap-red.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/library/datetimepicker/jquery.datetimepicker.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/library/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/library/tiptip/tipTip.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/library/jgrowl/jquery.jgrowl.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/library/jRange/jquery.range.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/view/css/conversation.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/view/css/widgets.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/view/css/colorbox.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/library/justifiedGallery/justifiedGallery.min.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/view/css/default.css&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/view/theme/redbasic/php/style.pcss&quot; type=&quot;text/css&quot; media=&quot;screen&quot;&gt;\r\n\n\n&lt;script&gt;\n\n\tvar aStr = {\n\n\t\t'delitem'     : &quot;Delete this item?&quot;,\n\t\t'comment'     : &quot;Comment&quot;,\n\t\t'showmore'    : &quot;[+] show all&quot;,\n\t\t'showfewer'   : &quot;[-] show less&quot;,\n\t\t'divgrowmore' : &quot;[+] expand&quot;,\n\t\t'divgrowless' : &quot;[-] collapse&quot;,\n\t\t'pwshort'     : &quot;Password too short&quot;,\n\t\t'pwnomatch'   : &quot;Passwords do not match&quot;,\n\t\t'everybody'   : &quot;everybody&quot;,\n\t\t'passphrase'  : &quot;Secret Passphrase&quot;,\n\t\t'passhint'    : &quot;Passphrase hint&quot;,\n\t\t'permschange' : &quot;Notice: Permissions have changed but have not yet been submitted.&quot;,\n\t\t'closeAll'    : &quot;close all&quot;,\n\t\t'nothingnew'  : &quot;Nothing new here&quot;,\n\t\t'rating_desc' : &quot;Rate This Channel (this is public)&quot;,\n\t\t'rating_val'  : &quot;Rating&quot;,\n\t\t'rating_text' : &quot;Describe (optional)&quot;,\n\t\t'submit'      : &quot;Submit&quot;,\n\t\t'linkurl'     : &quot;Please enter a link URL&quot;,\n\t\t'leavethispage' : &quot;Unsaved changes. Are you sure you wish to leave this page?&quot;,\n\n\t\t't01' : &quot;&quot;,\n\t\t't02' : &quot;&quot;,\n\t\t't03' : &quot;ago&quot;,\n\t\t't04' : &quot;from now&quot;,\n\t\t't05' : &quot;less than a minute&quot;,\n\t\t't06' : &quot;about a minute&quot;,\n\t\t't07' : &quot;%d minutes&quot;,\n\t\t't08' : &quot;about an hour&quot;,\n\t\t't09' : &quot;about %d hours&quot;,\n\t\t't10' : &quot;a day&quot;,\n\t\t't11' : &quot;%d days&quot;,\n\t\t't12' : &quot;about a month&quot;,\n\t\t't13' : &quot;%d months&quot;,\n\t\t't14' : &quot;about a year&quot;,\n\t\t't15' : &quot;%d years&quot;,\n\t\t't16' : &quot; &quot;,\n\t\t't17' : &quot;[]&quot;,\n\n\t\t'monthNames' : [ &quot;January&quot;,&quot;February&quot;,&quot;March&quot;,&quot;April&quot;,&quot;May&quot;,&quot;June&quot;,&quot;July&quot;,&quot;August&quot;,&quot;September&quot;,&quot;October&quot;,&quot;November&quot;,&quot;December&quot; ],\n\t\t'monthNamesShort' : [ &quot;Jan&quot;,&quot;Feb&quot;,&quot;Mar&quot;,&quot;Apr&quot;,&quot;May&quot;,&quot;Jun&quot;,&quot;Jul&quot;,&quot;Aug&quot;,&quot;Sep&quot;,&quot;Oct&quot;,&quot;Nov&quot;,&quot;Dec&quot; ],\n\t\t'dayNames' : [&quot;Sunday&quot;,&quot;Monday&quot;,&quot;Tuesday&quot;,&quot;Wednesday&quot;,&quot;Thursday&quot;,&quot;Friday&quot;,&quot;Saturday&quot;],\n\t\t'dayNamesShort' : [&quot;Sun&quot;,&quot;Mon&quot;,&quot;Tue&quot;,&quot;Wed&quot;,&quot;Thu&quot;,&quot;Fri&quot;,&quot;Sat&quot;],\n\t\t'today' : &quot;today&quot;,\n\t\t'month' : &quot;month&quot;,\n\t\t'week' : &quot;week&quot;,\n\t\t'day' : &quot;day&quot;,\n\t\t'allday' : &quot;All day&quot;\n\t};\n\n&lt;/script&gt;\n\t\t\n\n\n&lt;script src=&quot;http://hubzilla/view/js/jquery.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/justifiedGallery/jquery.justifiedGallery.min.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/sprintf.js/dist/sprintf.min.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/view/js/spin.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/view/js/jquery.spin.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/view/js/jquery.textinputs.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/view/js/autocomplete.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/jquery-textcomplete/jquery.textcomplete.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/jquery.timeago.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/readmore.js/readmore.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/jgrowl/jquery.jgrowl_minimized.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/cryptojs/components/core-min.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/cryptojs/rollups/aes.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/cryptojs/rollups/rabbit.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/cryptojs/rollups/tripledes.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/view/js/acl.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/view/js/webtoolkit.base64.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/view/js/crypto.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/jRange/jquery.range.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/colorbox/jquery.colorbox-min.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/jquery.AreYouSure/jquery.are-you-sure.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/tableofcontents/jquery.toc.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/bootstrap/js/bootstrap.min.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/bootbox/bootbox.min.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/bootstrap-tagsinput/bootstrap-tagsinput.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/datetimepicker/jquery.datetimepicker.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/library/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.js&quot; &gt;&lt;/script&gt;\r\n&lt;script src=&quot;http://hubzilla/view/theme/redbasic/js/redbasic.js&quot; &gt;&lt;/script&gt;\r\n\n\n&lt;link rel=&quot;shortcut icon&quot; href=&quot;http://hubzilla/images/hz-32.png&quot; /&gt;\n&lt;link rel=&quot;search&quot;\n         href=&quot;http://hubzilla/opensearch&quot; \n         type=&quot;application/opensearchdescription+xml&quot; \n         title=&quot;Search in the Hubzilla&quot; /&gt;\n\n\n&lt;script&gt;\n\n\tvar updateInterval = 80000;\n\tvar localUser = 2;\n\tvar zid = 'testes@hubzilla';\n\tvar justifiedGalleryActive = false;\n\t\t\n&lt;/script&gt;\n\n\n\n\n&lt;script&gt;$(document).ready(function() {\n\t$(&quot;#nav-search-text&quot;).search_autocomplete('http://hubzilla/acl');\n});\n\n&lt;/script&gt;&lt;script src=&quot;http://hubzilla/view/js/main.js&quot; &gt;&lt;/script&gt;\r\n&lt;link rel=&quot;stylesheet&quot; href=&quot;http://hubzilla/addon/converse/converse.min.js&quot; media=&quot;all&quot; /&gt;&lt;script src=&quot;http://hubzilla/addon/converse/converse.min.js&quot;&gt;&lt;/script&gt;",
    "header": "&lt;div id=&quot;banner&quot; class=&quot;hidden-sm hidden-xs&quot;&gt;Hubzilla&lt;/div&gt;\n\n&lt;ul id=&quot;nav-notifications-template&quot; style=&quot;display:none;&quot; rel=&quot;template&quot;&gt;\n\t&lt;li class=&quot;{5}&quot;&gt;&lt;a href=&quot;{0}&quot; title=&quot;{2} {3}&quot;&gt;&lt;img data-src=&quot;{1}&quot;&gt;&lt;span class=&quot;contactname&quot;&gt;{2}&lt;/span&gt;&lt;span class=&quot;dropdown-sub-text&quot;&gt;{3}&lt;br&gt;{4}&lt;/span&gt;&lt;/a&gt;&lt;/li&gt;\n&lt;/ul&gt;\n"
  },
  "poi": null,
  "force_max_items": 0,
  "module": "help",
  "template_engines": {
    "smarty3": "FriendicaSmartyEngine",
    "internal": "Template"
  },
  "account": {
    "account_flags": "0",
    "account_service_class": "",
    "account_id": "1",
    "account_salt": "9bf8c193c35a56c4c666f47728fe20da",
    "account_expires": "0000-00-00 00:00:00",
    "account_lastlog": "2015-11-04 07:47:55",
    "account_password_changed": "0000-00-00 00:00:00",
    "account_language": "en",
    "account_default_channel": "2",
    "account_password": "",
    "account_parent": "1",
    "account_expire_notified": "0000-00-00 00:00:00",
    "account_reset": "",
    "account_email": "foo@bar.com",
    "account_level": "0",
    "account_roles": "4096",
    "account_external": "",
    "account_created": "2015-10-09 00:44:51"
  },
  "theme_info": [],
  "argv": [
    "help",
    "develop"
  ],
  "template_engine_instance": {
    "smarty3": {}
  },
  "language": "en",
  "pager": {
    "page": 1,
    "itemspage": 60,
    "start": 0,
    "total": 0
  },
  "plugins": [
    "converse"
  ],
  "error": false,
  "pdl": "[region=aside]\n[widget=helpindex][/widget]\n[/region]\n",
  "query_string": "help/develop",
  "cmd": "help/develop",
  "groups": null,
  "videowidth": 425,
  "css_sources": [
    [
      "library/font_awesome/css/font-awesome.min.css",
      "screen"
    ],
    [
      "library/bootstrap/css/bootstrap.min.css",
      "screen"
    ],
    [
      "library/bootstrap-tagsinput/bootstrap-tagsinput.css",
      "screen"
    ],
    [
      "view/css/bootstrap-red.css",
      "screen"
    ],
    [
      "library/datetimepicker/jquery.datetimepicker.css",
      "screen"
    ],
    [
      "library/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css",
      "screen"
    ],
    [
      "library/tiptip/tipTip.css",
      "screen"
    ],
    [
      "library/jgrowl/jquery.jgrowl.css",
      "screen"
    ],
    [
      "library/jRange/jquery.range.css",
      "screen"
    ],
    [
      "view/css/conversation.css",
      "screen"
    ],
    [
      "view/css/widgets.css",
      "screen"
    ],
    [
      "view/css/colorbox.css",
      "screen"
    ],
    [
      "library/justifiedGallery/justifiedGallery.min.css",
      "screen"
    ],
    [
      "default.css",
      "screen"
    ],
    [
      "mod_help.css",
      "screen"
    ],
    [
      "view/theme/redbasic/php/style.pcss",
      "screen"
    ]
  ],
  "is_tablet": false,
  "observer": {
    "xchan_deleted": "0",
    "xchan_connpage": "",
    "xchan_pubforum": "0",
    "xchan_flags": "0",
    "xchan_censored": "0",
    "xchan_instance_url": "",
    "xchan_photo_s": "http://hubzilla/photo/profile/s/2",
    "xchan_pubkey": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA7MP/xxsq/srA8I7m+WKf\nHlguwwg0b1tz+I3o+djp7b+wF8q03XPKQpYmKfXIj47vpAOu75nKA4Tn90lLymmk\nSXUHogOqOMy1CHoaVrAw2T2/tAeRoMAjAJ5IxSOAM7Xda0nVUK6FmfxPcvftKf9y\nPmvvFadXpaHT4JGPH0tszDhGXLkqlt9xSkIkpsgMA6emj/7bacc6x8eTdtvzo2e5\n/NyPXvBKH4henmYaKjq/4aIYZcBWYVGt6onxaP2j1cSNbksnOY7GbJl+hy95iFoZ\nDWGxiFwQd+CroiBbdlpVGp13cV/WKp2spZzlzkmCRGYoNbbM5RlgFLnmyTa4XMZE\nwnA3ZUB59MsrUJK+0H/utiZrpX5NQcFl33z8k5zB3pPnhc5S5/P+UJZRnqhet1wQ\n7AZVmdP30D75QD8LZ4SytZ1DHn/N76EsVhSADNMnUfEphs708V33Z0gFWultYDoK\nlvXUf4O0/V8GTufFHb6XdAiy92IUzrormXCpXoOmdOcJdaH9RnotZi/DkuQ0zP+Y\nCvxU9nrjyZvAwAdew//XFDjw4HoThVM4k4jzkIhCTlCao/yRnNM7A/i3OKcXq9wU\n7OZqcRfM9o0BFpZTIoXB7BMtpeioJcBi/7FUaV9U9uYLFuLL0qWa1YxLwfsN9rDk\n6A1gbhD60G9/dAbolp8xAHkCAwEAAQ==\n-----END PUBLIC KEY-----\n",
    "xchan_connurl": "http://hubzilla/poco/testes",
    "xchan_name_date": "2015-10-09 00:45:41",
    "xchan_system": "0",
    "xchan_photo_date": "2015-10-09 00:45:41",
    "xchan_photo_m": "http://hubzilla/photo/profile/m/2",
    "xchan_hidden": "0",
    "xchan_selfcensored": "0",
    "xchan_photo_mimetype": "image/jpeg",
    "xchan_network": "zot",
    "xchan_guid": "Ok-ycNKQYMzjokLnIz5OTCF8M5f4CtRT4vJCUeUivJhIOJWk3ORwIQgGx3P5g2Yz79KxQ-rs_Cn2G_jsgM6hmw",
    "xchan_guid_sig": "XXX",
    "xchan_hash": "uRy0nF-urp6k_bFrkdtCc2EkBynwpgCJL_FQFoTwyw2Hep7AHkrSt1MZcHWV_8DQucNlHSY1vHgUNS2Fvoirpw",
    "xchan_url": "http://hubzilla/channel/testes",
    "xchan_addr": "testes@hubzilla",
    "xchan_orphan": "0",
    "xchan_follow": "http://hubzilla/follow?f=&amp;url=%s",
    "xchan_name": "testes",
    "xchan_photo_l": "http://hubzilla/photo/profile/l/2"
  },
  "contact": null,
  "identities": null,
  "user": null,
  "videoheight": 350,
  "profile": null,
  "theme_thread_allow": true,
  "data": {
    "pageicon": "/images/hz-32.png"
  }
}[/code]


#include doc/macros/main_footer.bb;

