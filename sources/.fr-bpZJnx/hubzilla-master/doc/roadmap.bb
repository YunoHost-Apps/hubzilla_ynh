

Roadmap for $Projectname V3

*HZ = Hubzilla repository


Crypto
	Convert E2EE to dynamic loading (on demand) using jQuery.getScript() [or other methods] to only load encryption libs when you require them. This should also support multiple encryption libraries (e.g. SJCL, others) triggered from the choice of algorithm and remain pluggable.

Subscriptions and business models
	Build enough into core(/addons) to generate income (or at least try and cover costs) out of the box 

	Resolve the "every photo has an item" confusion, perhaps every file should also - but only if we can explain it and separate them conceptually.

Migration tools
	Friendica importer
	Diaspora importer (channel and connection import done, conversations and photos still in progress and waiting for support from Diaspora)

Webpage design UI improvements
	If practical, separate "conversation" sub-themes from overall themes so one can choose different conversation and content layouts within a base theme. 
	Make webpage building easy, with point-n-click selectors to build PDLs
	bring back WYSIWYG, which ideally requires a JS abstraction layer so we can use any editor and change it based on mimetype

Social Networking Federation
	Friendica native mode?
	Pump.io?
	Others?

Lists
	Create a list object to contain arbitrary things for system use
	Create a list object to contain arbitrary things for personal use

Events
	Recurring events

Zot
	Provide a way to sync web resources. This could be built on DAV except for preserving resource naming (guids) instead of filenames. 

API extensions
	More, more, more.

Evangelism
	More documentation. More, more, more.
	Libzot

DNS abstraction for V3
	Allow a channel to live in an arbitrary "DNS" namespace, for instance "mike@core.hubzilla". Use our directories and zot to find the actual DNS location via redirection. This could potentially allow hubs to be hidden behind tor or alt-roots and accessible only via the matrix.
 