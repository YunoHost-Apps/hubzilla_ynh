[h2]Hooks[/h2]

Hooks allow plugins/addons to "hook into" the code at many points and alter the behaviour or otherwise perform independent actions when an activity takes place or when certain data structures are accessed. There are many hooks which allow you to tie into the software at most any point and do something slightly different than the default thing. These hooks are passed two variables. The first is the App structure which contains details about the entire state of the page request as we build the resulting page. The second is unique to the specific hook that is called and provides specific detail about what is happening in the software at the time the hook is invoked.

[zrl=[baseurl]/help/hooks]Generated index of all hooks and the files which call them[/zrl]

[zrl=[baseurl]/help/hook/module_mod_aftercontent]module_mod_aftercontent[/zrl]
	General purpose hook for any module, executed after mod_content(). Replace 'module' with module name, e.g. 'photos_mod_aftercontent'.

[zrl=[baseurl]/help/hook/module_mod_content]module_mod_content[/zrl]
	General purpose hook for any module, executed before mod_content(). Replace 'module' with module name, e.g. 'photos_mod_content'.

[zrl=[baseurl]/help/hook/module_mod_init]module_mod_init[/zrl]
	General purpose hook for any module, executed before mod_init(). Replace 'module' with module name, e.g. 'photos_mod_init'.

[zrl=[baseurl]/help/hook/module_mod_post]module_mod_post[/zrl]
	General purpose hook for any module, executed before mod_post(). Replace 'module' with module name, e.g. 'photos_mod_post'.

[zrl=[baseurl]/help/hook/about_hook]about_hook[/zrl]
	Called from the siteinfo page

[zrl=[baseurl]/help/hook/accept_follow]accept_follow[/zrl]
	Called when accepting a connection (friend request)

[zrl=[baseurl]/help/hook/account_downgrade]account_downgrade[/zrl]
	Called when an account has expired, indicating a potential downgrade to "basic" service class

[zrl=[baseurl]/help/hook/account_settings]account_settings[/zrl]
	Called when generating the account settings form

[zrl=[baseurl]/help/hook/settings_account]account_settings_post[/zrl]
	Called when posting from the account settings form

[zrl=[baseurl]/help/hook/activity_received]activity_received[/zrl]
	Called when an activity (post, comment, like, etc.) has been received from a zot source

[zrl=[baseurl]/help/hook/admin_aside]admin_aside[/zrl]
	Called when generating the admin page sidebar widget

[zrl=[baseurl]/help/hook/affinity_labels]affinity_labels[/zrl]
	Used to generate alternate labels for the affinity slider.

[zrl=[baseurl]/help/hook/api_perm_is_allowed]api_perm_is_allowed[/zrl]
	Called when perm_is_allowed() is executed from an API call.

[zrl=[baseurl]/help/hook/app_menu]app_menu[/zrl]
	Called when generating the app_menu dropdown (may be obsolete)

[zrl=[baseurl]/help/hook/atom_author]atom_author[/zrl]
	Called when generating an author or owner element for an Atom ActivityStream feed

[zrl=[baseurl]/help/hook/atom_entry]atom_entry[/zrl]
	Called when generating each item entry of an Atom ActivityStreams feed

[zrl=[baseurl]/help/hook/atom_feed]atom_feed[/zrl]
	Called when generating an Atom ActivityStreams feed

[zrl=[baseurl]/help/hook/atom_feed_end]atom_feed_end[/zrl]
	Called when generation of an Atom ActivityStreams feed is completed

[zrl=[baseurl]/help/hook/attach_upload_file]attach_upload_file[/zrl]
	Called when uploading a file

[zrl=[baseurl]/help/hook/authenticate]authenticate[/zrl]
	Can provide alternate authentication mechanisms

[zrl=[baseurl]/help/hook/bb2diaspora]bb2diaspora[/zrl]
	called when converting bbcode to markdown

[zrl=[baseurl]/help/hook/bbcode]bbcode[/zrl]
	Called at end of converting bbcode to HTML

[zrl=[baseurl]/help/hook/bbcode_filter]bbcode_filter[/zrl]
	Called when beginning to convert bbcode to HTML

[zrl=[baseurl]/help/hook/bb_translate_video]bb_translate_video[/zrl]
	Called when extracting embedded services from bbcode video elements (rarely used)

[zrl=[baseurl]/help/hook/change_channel]change_channel[/zrl]
	Called when logging in to a channel (either during login or afterward through the channel manager)

[zrl=[baseurl]/help/hook/channel_remove]channel_remove[/zrl]
	Called when removing a channel

[zrl=[baseurl]/help/hook/chat_message]chat_message[/zrl]
	Called to create a chat message.

[zrl=[baseurl]/help/hook/chat_post]chat_post[/zrl]
	Called when a chat message has been posted

[zrl=[baseurl]/help/hook/check_account_email]check_account_email[/zrl]
	Validate the email provided in an account registration

[zrl=[baseurl]/help/hook/check_account_invite]check_account_invite[/zrl]
	Validate an invitation code when using site invitations	

[zrl=[baseurl]/help/hook/check_account_password]check_account_password[/zrl]
	Used to provide policy control over account passwords (minimum length, character set inclusion, etc.)

[zrl=[baseurl]/help/hook/check_channelallowed]check_channelallowed[/zrl]
	Used to over-ride or bypass the channel black/white block lists

[zrl=[baseurl]/help/hook/check_siteallowed]check_siteallowed[/zrl]
	Used to over-ride or bypass the site black/white block lists

[zrl=[baseurl]/help/hook/comment_buttons]comment_buttons[/zrl]
	Called when rendering the edit buttons for comments

[zrl=[baseurl]/help/hook/connect_premium]connect_premium[/zrl]
	Called when connecting to a premium channel

[zrl=[baseurl]/help/hook/connector_settings]connector_settings[/zrl]
	Called when posting to the features/addon settings page

[zrl=[baseurl]/help/hook/construct_page]construct_page[/zrl]
	General purpose hook to provide content to certain page regions. Called when constructing the Comanche page. 

[zrl=[baseurl]/help/hook/contact_block_end]contact_block_end[/zrl]
	Called when generating the sidebar "Connections" widget

[zrl=[baseurl]/help/hook/contact_edit]contact_edit[/zrl]
	Called when editing a connection via connedit

[zrl=[baseurl]/help/hook/contact_edit_post]contact_edit_post[/zrl]
	Called when posting to connedit

[zrl=[baseurl]/help/hook/contact_select_options]contact_select_options[/zrl]
	Deprecated/unused

[zrl=[baseurl]/help/hook/conversation_start]conversation_start[/zrl]
	Called in the beginning of rendering a conversation (message or message collection or stream)	

[zrl=[baseurl]/help/hook/cover_photo_content_end]cover_photo_content_end[/zrl]
	Called after a cover photo has been uplaoded

[zrl=[baseurl]/help/hook/create_identity]create_identity[/zrl]
	Called when creating a channel

[zrl=[baseurl]/help/hook/cron]cron[/zrl]
	Called when scheduled tasks (poller) is executed

[zrl=[baseurl]/help/hook/cron_daily]cron_daily[/zrl]
	Called when daily scheduled tasks are executed

[zrl=[baseurl]/help/hook/cron_weekly]cron_weekly[/zrl]
	Called when weekly scheduled tasks are executed

[zrl=[baseurl]/help/hook/crypto_methods]crypto_methods[/zrl]
	Called when generating a list of crypto algorithms in the locally preferred order

[zrl=[baseurl]/help/hook/directory_item]directory_item[/zrl]
	Called when generating a directory listing for display

[zrl=[baseurl]/help/hook/discover_channel_webfinger]discover_channel_webfinger[/zrl]
	Called when performing a webfinger lookup

[zrl=[baseurl]/help/hook/display_item]display_item[/zrl]
	Called for each item being displayed in a conversation thread
	
[zrl=[baseurl]/help/hook/display_settings]display_settings[/zrl]
	Called from settings module when displaying the 'display settings' section

[zrl=[baseurl]/help/hook/display_settings_post]display_settings_post[/zrl]
	Called when posting from the settings module 'display settings' form

[zrl=[baseurl]/help/hook/donate_contributors]donate_contributors[/zrl]
	called by the 'donate' addon when generating a list of donation recipients

[zrl=[baseurl]/help/hook/donate_plugin]donate_plugin[/zrl]
	called by the 'donate' addon

[zrl=[baseurl]/help/hook/donate_sponsors]donate_sponsors[/zrl]
	called by the 'donate' addon

[zrl=[baseurl]/help/hook/dreport_is_storable]dreport_is_storable[/zrl]
	called before storing a dreport record to determine whether to store it

[zrl=[baseurl]/help/hook/drop_item]drop_item[/zrl]
	called when an 'item' is removed

[zrl=[baseurl]/help/hook/enotify]enotify[/zrl]
	called before any notification

[zrl=[baseurl]/help/hook/enotify_mail]enotify_mail[/zrl]
	called when sending a notification email

[zrl=[baseurl]/help/hook/enotify_store]enotify_store[/zrl]
	called when storing a notification record

[zrl=[baseurl]/help/hook/event_created]event_created[/zrl]
	called when an event record is created

[zrl=[baseurl]/help/hook/event_store_event]event_store_event[/zrl]
	called when an event record is created or updated

[zrl=[baseurl]/help/hook/event_updated]event_updated[/zrl]
	called when an event record is modified

[zrl=[baseurl]/help/hook/externals_url_select]externals_url_select[/zrl]
	called when generating a list of random sites to pull public posts from

[zrl=[baseurl]/help/hook/feature_enabled]feature_enabled[/zrl]
	called when 'feature_enabled()' is used

[zrl=[baseurl]/help/hook/feature_settings]feature_settings[/zrl]
	called from settings page when visiting 'addon/feature settings'

[zrl=[baseurl]/help/hook/feature_settings_post]feature_settings_post[/zrl]
	called from settings page when posting from 'addon/feature settings'

[zrl=[baseurl]/help/hook/follow]follow[/zrl]
	called when a follow operation takes place

[zrl=[baseurl]/help/hook/follow_from_feed]follow_from_feed[/zrl]
	called when a follow operation takes place on an RSS feed

[zrl=[baseurl]/help/hook/follow_allow]follow_allow[/zrl]
	called before storing the results of a follow operation

[zrl=[baseurl]/help/hook/gender_selector]gender_selector[/zrl]
	called when creating the 'gender' drop down list (advanced profile)

[zrl=[baseurl]/help/hook/gender_selector_min]gender_selector_min[/zrl]
	called when creating the 'gender' drop down list (normal profile)


[zrl=[baseurl]/help/hook/generate_map]generate_map[/zrl]
	called to generate the HTML for displaying a map location by coordinates

[zrl=[baseurl]/help/hook/generate_named_map]generate_named_map[/zrl]
	called to generate the HTML for displaying a map location by text location

[zrl=[baseurl]/help/hook/get_all_api_perms]get_all_api_perms[/zrl]
	Called when retrieving the permissions for API uses 

[zrl=[baseurl]/help/hook/get_all_perms]get_all_perms[/zrl]
	called when get_all_perms() is used

[zrl=[baseurl]/help/hook/get_best_language]get_best_language[/zrl]
	called when choosing the preferred language for the page

[zrl=[baseurl]/help/hook/get_features]get_features[/zrl]
	Called when get_features() is called

[zrl=[baseurl]/help/hook/get_photo]get_photo[/zrl]
	Called when photo content (except for profile photos) is fetched in mod_photo

[zrl=[baseurl]/help/hook/get_profile_photo]get_profile_photo[/zrl]
	Called when local profile photo content is fetched in mod_photo

[zrl=[baseurl]/help/hook/get_role_perms]get_role_perms[/zrl]
	Called when get_role_perms() is called to obtain permissions for named permission roles

[zrl=[baseurl]/help/hook/global_permissions]global_permissions[/zrl]
	Called when the global permissions list is generated

[zrl=[baseurl]/help/hook/home_content]home_content[/zrl]
	Called from mod_home to replace the content of the home page

[zrl=[baseurl]/help/hook/home_init]home_init[/zrl]
	Called from the home page home_init() function

[zrl=[baseurl]/help/hook/hostxrd]hostxrd[/zrl]
	Called when generating .well-known/hosts-meta for "old webfinger" (used by Diaspora protocol)

[zrl=[baseurl]/help/hook/html2bb_video]html2bb_video[/zrl]
	Called when using the html2bbcode translation to handle embedded media

[zrl=[baseurl]/help/hook/html2bbcode]html2bbcode[/zrl]
	Called when using the html2bbcode translation

[zrl=[baseurl]/help/hook/identity_basic_export]identity_basic_export[/zrl]
	Called when exporting a channel's basic information for backup or transfer

[zrl=[baseurl]/help/hook/import_author_xchan]import_author_xchan[/zrl]
	Called when looking up an author of a post by xchan_hash to ensure they have an xchan record on our site

[zrl=[baseurl]/help/hook/import_channel]import_channel[/zrl]
	Called when importing a channel from a file or API source

[zrl=[baseurl]/help/hook/import_directory_profile]import_directory_profile[/zrl]
	Called when processing delivery of a profile structure from an external source (usually for directory storage)

[zrl=[baseurl]/help/hook/import_xchan]import_xchan[/zrl]
	Called when processing the result of zot_finger() to store the result

[zrl=[baseurl]/help/hook/item_photo_menu]item_photo_menu[/zrl]
	Called when generating the list of actions associated with a displayed conversation item

[zrl=[baseurl]/help/hook/item_store]item_store[/zrl]
	Called when item_store() stores a record of type item

[zrl=[baseurl]/help/hook/item_store_update]item_store_update[/zrl]
	Called when item_store_update() is called to update a stored item.

[zrl=[baseurl]/help/hook/item_translate]item_translate[/zrl]
	Called from item_store and item_store_update after the post language has been autodetected

[zrl=[baseurl]/help/hook/jot_networks]jot_networks[/zrl]
	Called to generate the list of additional post plugins to enable from the ACL form

[zrl=[baseurl]/help/hook/jot_tool]jot_tool[/zrl]
	Deprecated and possibly obsolete. Allows one to add action buttons to the post editor.

[zrl=[baseurl]/help/hook/load_pdl]load_pdl[/zrl]
	Called when we load a PDL file or description

[zrl=[baseurl]/help/hook/local_dir_update]local_dir_update[/zrl]
	Called when processing a directory update from a channel on the directory server

[zrl=[baseurl]/help/hook/location_move]location_move[/zrl]
	Called when a new location has been provided to a UNO channel (indicating a move rather than a clone)

[zrl=[baseurl]/help/hook/logged_in]logged_in[/zrl]
	Called when authentication by any means has succeeeded

[zrl=[baseurl]/help/hook/logger]logger[/zrl]
	Called when making an entry to the application logfile

[zrl=[baseurl]/help/hook/logging_out]logging_out[/zrl]
	Called when logging out

[zrl=[baseurl]/help/hook/login_hook]login_hook[/zrl]
	Called when generating the login form

[zrl=[baseurl]/help/hook/magic_auth]magic_auth[/zrl]
	Called when processing a magic-auth sequence

[zrl=[baseurl]/help/hook/markdown_to_bb]markdown_to_bb[/zrl]
	Called when processing markdown conversion

[zrl=[baseurl]/help/hook/match_webfinger_location]match_webfinger_location[/zrl]
	Called when processing webfinger requests

[zrl=[baseurl]/help/hook/magic_auth_openid_success]magic_auth_openid_success[/zrl]
	Called when a magic-auth was successful due to openid credentials

[zrl=[baseurl]/help/hook/magic_auth_success]magic_auth_success[/zrl]
	Called when a magic-auth was successful

[zrl=[baseurl]/help/hook/main_slider]main_slider[/zrl]
	Called whne generating the affinity tool

[zrl=[baseurl]/help/hook/marital_selector]marital_selector[/zrl]
	Called when generating the list of choices for the 'marital status' profile dropdown (advanced profile)

[zrl=[baseurl]/help/hook/marital_selector_min]marital_selector_min[/zrl]
	Called when generating the list of choices for the 'marital status' profile dropdown (normal profile)

[zrl=[baseurl]/help/hook/module_loaded]module_loaded[/zrl]
	Called when a module has been successfully locate to server a URL request

[zrl=[baseurl]/help/hook/mood_verbs]mood_verbs[/zrl]
	Called when generating the list of moods

[zrl=[baseurl]/help/hook/nav]nav[/zrl]
	Called when generating the navigation bar

[zrl=[baseurl]/help/hook/network_content_init]network_content_init[/zrl]
	Called when loading cntent for the network page

[zrl=[baseurl]/help/hook/network_ping]network_ping[/zrl]
	Called during a ping request

[zrl=[baseurl]/help/hook/network_tabs]network_tabs[/zrl]
	Called when generating the list of tabs for the network page

[zrl=[baseurl]/help/hook/network_to_name]network_to_name[/zrl]
	Deprecated

[zrl=[baseurl]/help/hook/notifier_end]notifier_end[/zrl]
	Called when a delivery loop has completed

[zrl=[baseurl]/help/hook/notifier_hub]notifier_hub[/zrl]
	Called when a hub is delivered

[zrl=[baseurl]/help/hook/notifier_normal]notifier_normal[/zrl]
	Called when the notifier is invoked for a 'normal' delivery

[zrl=[baseurl]/help/hook/notifier_process]notifier_process[/zrl]
	Called when the notifier is processing a message/event

[zrl=[baseurl]/help/hook/obj_verbs]obj_verbs[/zrl]
	Called when creating the list of verbs available for profile "things".

[zrl=[baseurl]/help/hook/oembed_action]oembed_action[/zrl]
	Called when deciding if an oembed url is to be filter, blocked, or approved

[zrl=[baseurl]/help/hook/oembed_probe]oembed_probe[/zrl]
	Called when performing an oembed content lookup

[zrl=[baseurl]/help/hook/other_encapsulate]other_encapsulate[/zrl]
	Called when encrypting content for which the algorithm is unknown (see also crypto_methods)

[zrl=[baseurl]/help/hook/other_unencapsulate]other_unencapsulate[/zrl]
	Called when decrypting content for which the algorithm is unknown (see also crypto_methods)

[zrl=[baseurl]/help/hook/page_content_top]page_content_top[/zrl]
	Called when we generate a webpage (before calling the module content function)

[zrl=[baseurl]/help/hook/page_end]page_end[/zrl]
	Called after we have generated the page content

[zrl=[baseurl]/help/hook/page_header]page_header[/zrl]
	Called when generating the navigation bar

[zrl=[baseurl]/help/hook/parse_atom]parse_atom[/zrl]
	Called when parsing an atom/RSS feed item

[zrl=[baseurl]/help/hook/parse_link]parse_link[/zrl]
	Called when probing a URL to generate post content from it

[zrl=[baseurl]/help/hook/pdl_selector]pdl_selector[/zrl]
	Called when creating a layout selection in a form	

[zrl=[baseurl]/help/hook/perm_is_allowed]perm_is_allowed[/zrl]
	Called during perm_is_allowed() to determine if a permission is allowed for this channel and observer

[zrl=[baseurl]/help/hook/permissions_create]permissions_create[/zrl]
	Called when an abook entry (connection) is created

[zrl=[baseurl]/help/hook/permissions_update]permissions_update[/zrl]
	Called when a permissions refresh is transmitted

[zrl=[baseurl]/help/hook/personal_xrd]personal_xrd[/zrl]
	Called when generating the personal XRD for "old webfinger" (Diaspora)

[zrl=[baseurl]/help/hook/photo_post_end]photo_post_end[/zrl]
	Called after uploading a photo

[zrl=[baseurl]/help/hook/photo_upload_begin]photo_upload_begin[/zrl]
	Called when attempting to upload a photo

[zrl=[baseurl]/help/hook/photo_upload_end]photo_upload_end[/zrl]
	Called when a photo upload has been processed

[zrl=[baseurl]/help/hook/photo_upload_file]photo_upload_file[/zrl]
	Called to generate alternate filenames for an upload

[zrl=[baseurl]/help/hook/photo_upload_form]photo_upload_form[/zrl]
	Called when generating a photo upload form

[zrl=[baseurl]/help/hook/poke_verbs]poke_verbs[/zrl]
	Called when generating the list of actions for "poke" module

[zrl=[baseurl]/help/hook/post_local]post_local[/zrl]
	Called when an item has been posted on this machine via mod/item.php (also via API)

[zrl=[baseurl]/help/hook/post_local_end]post_local_end[/zrl]
	Called after a local post operation has completed

[zrl=[baseurl]/help/hook/post_local_start]post_local_start[/zrl]
	Called when a local post operation is commencing

[zrl=[baseurl]/help/hook/post_mail]post_mail[/zrl]
	Called when a mail message has been composed

[zrl=[baseurl]/help/hook/post_mail_end]post_mail_end[/zrl]
	Called when a mail message has been delivered

[zrl=[baseurl]/help/hook/post_remote]post_remote[/zrl]
	Called when an activity arrives from another site

[zrl=[baseurl]/help/hook/post_remote_end]post_remote_end[/zrl]
	Called after processing a remote post

[zrl=[baseurl]/help/hook/post_remote_update]post_remote_update[/zrl]
	Called when processing a remote post that involved an edit or update

[zrl=[baseurl]/help/hook/post_remote_update_end]post_remote_update_end[/zrl]
	Called after processing a remote post that involved an edit or update

[zrl=[baseurl]/help/hook/prepare_body]prepare_body[/zrl]
	Called when generating the HTML for a displayed conversation item

[zrl=[baseurl]/help/hook/prepare_body_final]prepare_body_final[/zrl]
	Called after generating the HTML for a displayed conversation item

[zrl=[baseurl]/help/hook/prepare_body_init]prepare_body_init[/zrl]
	Called before generating the HTML for a displayed conversation item

[zrl=[baseurl]/help/hook/probe_well_known]probe_well_known[/zrl]
	under construction

[zrl=[baseurl]/help/hook/proc_run]proc_run[/zrl]
	Called when invoking PHP sub processes

[zrl=[baseurl]/help/hook/process_channel_sync_delivery]process_channel_sync_delivery[/zrl]
	Called when accepting delivery of a 'sync packet' containing structure and table updates from a channel clone

[zrl=[baseurl]/help/hook/profile_advanced]profile_advanced[/zrl]
	Called when generating an advanced profile page

[zrl=[baseurl]/help/hook/profile_edit]profile_edit[/zrl]
	Called when editing a profile

[zrl=[baseurl]/help/hook/profile_photo_content_end]profile_photo_content_end[/zrl]
	Called when changing a profile photo

[zrl=[baseurl]/help/hook/profile_post]profile_post[/zrl]
	Called when posting an edited profile

[zrl=[baseurl]/help/hook/profile_sidebar]profile_sidebar[/zrl]
	Called when generating the 'channel sidebar' or mini-profile

[zrl=[baseurl]/help/hook/profile_sidebar_enter]profile_sidebar_enter[/zrl]
	Called before generating the 'channel sidebar' or mini-profile

[zrl=[baseurl]/help/hook/profile_tabs]profile_tabs[/zrl]
	Called when generating the tabs for channel related pages (channel,profile,files,etc.)

[zrl=[baseurl]/help/hook/queue_deliver]queue_deliver[/zrl]
	Called when delivering a queued message
 
[zrl=[baseurl]/help/hook/register_account]register_account[/zrl]
	Called when an account has been created

[zrl=[baseurl]/help/hook/render_location]render_location[/zrl]
	Called to generate an ineractive inline map

[zrl=[baseurl]/help/hook/replace_macros]replace_macros[/zrl]
	Called before invoking the template processor

[zrl=[baseurl]/help/hook/reverse_magic_auth]reverse_magic_auth[/zrl]
	Called before invoking reverse magic auth to send you to your own site to authenticate on this site

[zrl=[baseurl]/help/hook/settings_account]settings_account[/zrl]
	Called when generating the account settings form

[zrl=[baseurl]/help/hook/settings_form]settings_form[/zrl]
	Called when generating the channel settings form

[zrl=[baseurl]/help/hook/settings_post]settings_post[/zrl]
	Called when posting from the channel settings form

[zrl=[baseurl]/help/hook/sexpref_selector]sexpref_selector[/zrl]
	Called when generating a dropdown of sexual preference (advanced profile)

[zrl=[baseurl]/help/hook/sexpref_selector_min]sexpref_selector_min[/zrl]
	Called when generating a dropdown of sexual preference (normal profile)

[zrl=[baseurl]/help/hook/smilie]smilie[/zrl]
	Called when translating emoticons

[zrl=[baseurl]/help/hook/stream_item]stream_item[/zrl]
	Called for each item which is rendered for viewing via conversation()

[zrl=[baseurl]/help/hook/tagged]tagged[/zrl]
	Called when a delivery is processed which results in you being tagged

[zrl=[baseurl]/help/hook/validate_channelname]validate_channelname[/zrl]
	Used to validate the names used by a channel

[zrl=[baseurl]/help/hook/webfinger]webfinger[/zrl]
	Called when visiting the webfinger (RFC7033) service

[zrl=[baseurl]/help/hook/well_known]well_known[/zrl]
	Called when accessing the '.well-known' special site addresses

[zrl=[baseurl]/help/hook/zot_best_algorithm]zot_best_algorithm[/zrl]
	Called when negotiating crypto algorithms with remote sites

[zrl=[baseurl]/help/hook/zid]zid[/zrl]
	Called when adding the observer's zid to a URL

[zrl=[baseurl]/help/hook/zid_init]zid_init[/zrl]
	Called when authenticating a visitor who has used zid

[zrl=[baseurl]/help/hook/zot_finger]zot_finger[/zrl]
	Called when a zot-info packet has been requested (this is our webfinger discovery mechanism)
