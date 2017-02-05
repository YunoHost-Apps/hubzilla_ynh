[h3] What is Zot?[/h3]
Zot is the revolutionary protocol that powers $Projectname, providing [b]communications[/b], [b]identity management[/b], and [b]access control[/b] across a fully [b]decentralised[/b] network of independent websites, often called "the grid". The resulting platform is a robust system that supports privacy and security while enabling the kind of rich web services typically seen only in centralized, proprietary solutions.

Consider this typical scenario: 

Jaquelina wishes to share photos with Roberto from her blog at [b]jaquelina.org[/b], but to nobody else. Roberto maintains his own family hub at [b]roberto.net[/b] on a completely independent server. Zot allows Jaquelina to publish her photos using an [i]access control list (ACL)[/i] that includes only Roberto. That means that while Roberto can see the photos when he visits her blog, his brother Marco cannot, and neither can any of his other family members who have accounts on [b]roberto.net[/b].

The magic in this scenario comes from the fact that Roberto never logged in to Jaquelina's website. Instead, he had to login only once using his password on his [i]own[/i] website at [b]roberto.net[/b]. When Roberto visits [b]jaquelina.org[/b], her hub seamlessly authenticates him by remotely querying his server in the background. 

It is not uncommon for servers to have technical problems or become inaccessible for a variety of reasons. Zot provides robustness for Roberto's online activities by allowing him to have [i]clones[/i] of his online identity, or [i]channel[/i], on multiple independent hubs. Imagine that Roberto's server crashes for some reason and he cannot log in there. He simply logs in to one of his clones at [b]gadfly.com[/b], a site operated by his friend Peter. Once authenticated at [b]gadfly.com[/b], Roberto can view Jaquelina's blog as before, without Jaquelina having to grant any additional access!

[h4] Communications[/h4]
Communications and social networking are an integral part of the grid. Any channel (and any services provided by that channel) can make full use of feature-rich social communications on a global scale. These communications may be public or private - and private communications comprise not only fully encrypted transport, but also encrypted storage to help protect against accidental snooping and disclosure by rogue system administrators and internet service providers. 

Zot supports a wide array of background services in the grid, from friend suggestions to directory services. New content and data updates are propagated in the background between hubs across the grid according to access control lists and permissions specified by both sender [i]and[/i] receiver channels. Data is also synchronized between an arbitrary number of channel clones, allowing hub members to access data and continue collaborating seamlessly in the event that their primary hub is inaccessible or offline.

[h4] Identity [/h4]
Zot's identity layer is unique. It provides [b]invisible single sign-on[/b] across all sites in the grid. 

It also provides [b]nomadic identity[/b], so that your communications with friends, family, and or anyone else you're communicating with won't be affected by the loss of your primary communication node - either temporarily or permanently. 

The important bits of your identity and relationships can be backed up to a thumb drive, or your laptop, and may appear at any node in the grid at any time - with all your friends and preferences intact. 

Crucially, these nomadic instances are kept in sync so any instance can take over if another one is compromised or damaged. This protects you against not only major system failure, but also temporary site overloads and governmental manipulation or censorship. 

Nomadic identity, single sign-on, and $Projectname's decentralisation of hubs, we believe, introduce a high degree of degree of [b]resiliency[/b] and [b]persistence[/b] in internet communications, that are sorely needed amidst global trends towards corporate centralization, as well as mass and indiscriminate government surveillance and censorship.

As you browse the grid, viewing channels and their unique content, you are seamlessly authenticated as you go, even across completely different server hubs. No passwords to enter. Nothing to type. You're just greeted by name on every new site you visit. 

How does Zot do that? We call it [b]magic-auth[/b], because $Projectname hides the details of the complexities that go into single sign-on logins, and nomadic identities, from the experience of browsing on the grid.  This is one of the design goals of $Projectname: to increase privacy, and freedom on the web, while reducing the complexity and tedium brought by the need to enter new passwords and login names for every different sight that someone might visit online. You login only once on your home hub (or any nomadic backup hub you have chosen). This allows you to access any authenticated services provided anywhere in the grid - such as shopping, blogs, forums, and access to private information. Your password isn't stored on a thousand different sites; it is stored on servers that you control or that you have chosen to trust.

You cannot be silenced. You cannot be removed from the grid unless you yourself choose to exit it.

[h4] Access Control[/h4]
Zot's identity layer allows you to provide fine-grained permissions to any content you wish to publish - and these permissions extend across the grid. This is like having one super huge website made up of an army of small individual websites - and where each channel in the grid can completely control their privacy and sharing preferences for any web resources they create. 

Currently, $Projectname supports access control for many types of data, including post/comment discussion threads, photo albums, events, cloud files, web pages, wikis, and more. Every object and how it is shared and with whom is completely under your control.

This type of control is trivial on large corporate providers because they own the user database. Within the grid, there is no need for a huge user database on your machine - because the grid [b]is[/b] your user database. It has what is essentially infinite capacity (limited by the total number of hubs online across the internet), and is spread amongst hundreds, and potentially millions of computers. 

Access can be granted or denied for any resource, to any channel, or any group of channels; anywhere within the grid. Others can access your content if you permit them to do so, and they do not even need to have an account on your hub. 

[h3]Technical Introduction[/h3]
Zot is a JSON-based web framework for implementing secure decentralised communications and services. In order to provide this functionality, Zot creates a decentralised globally unique identifier for each hub on the network. This global identifier is not linked inextricably to DNS, providing the requisite mobility. Many existing decentralised communications frameworks provide the communication aspect, but do not provide remote access control and authentication. Additionally most of these are based on 'webfinger', which still binds identity to domain names and cannot support nomadic identity.

The primary issues Zot addresses are 

[list]
[*] completely decentralised communications
[*] independence from DNS-based identity
[*] node mobility
[*] seamless remote authentication
[*] high performance
[/list]
We will rely on DNS-based user@host addresses as a "user-friendly" mechanism to let people know where you are, specifically on a named host with a given username, and communication will be carried out to DNS entities using TCP and the web.

But the underlying protocol will provide an abstraction layer on top of this, so that a communications node (e.g. "identity") can move to an alternate DNS location and (to the best of our ability) gracefully recover from site re-locations and/or maintain pre-existing communication relationships. A side effect of this requirement is the ability to communicate from alternate/multiple DNS locations and service providers and yet maintain a single online identity.

We will call this overlay network the "grid". Servers attached to this network are known as "hubs" and may support any number of individual identities. 

An identity does not necessarily correspond to a person. It is just something which requires the ability to communicate within the grid. 

The ability to recover will be accomplished by communication to the original location when creating a new or replacement identity, or as a fallback from a stored file describing the identity and their contacts in the case where the old location no longer responds. 

At least on the short term, the mobility of existing content is not a top priority. This may or may not take place at a later stage. The most important things we want to keep are your identity and your friends.  

Addresses which are shared amongst people are user@host, and which describe the [b]current[/b] local account credentials for a given identity. These are DNS based addresses and used as a seed to locate a given identity within the grid. The machine communications will bind this address to a globally unique ID. A single globally unique ID may be attached or bound to any number of DNS locations. Once an identity has been mapped or bound to a DNS location, communications will consist of just knowing the globally unique address, and what DNS (url) is being used currently (in order to call back and verify/complete the current communication). These concepts will be specified in better detail. 

In order for an identity to persist across locations, one must be able to provide or recover 

[list]
[*] the globally unique ID for that identity
[*] the private key assigned to that identity
[*] (if the original server no longer exists) an address book of contacts for that identity.
[/list]
This information will be exportable from the original server via API, and/or downloadable to disk or thumb-drive. 

We may also attempt to recover with even less information, but doing so is prone to account hijacking and will require that your contacts confirm the change. 

In order to implement high performance communications, the data transfer format for all aspects of Zot is JSON. XML communications require way too much overhead.

Bi-directional encryption is based on RSA 4096-bit keys expressed in DER/ASN.1 format using the PKCS#8 encoding variant, with AES-256-CBC used for block encryption of variable length or large items.

Some aspects of well known "federation protocols" (webfinger, salmon, activitystreams, portablecontacts, etc.) may be used in zot, but we are not tied to them and will not be bound by them. $Projectname project is attempting some rather novel developments in decentralised communications and if there is any need to diverge from such "standard protocols" we will do so without question or hesitation. 

In order to create a globally unique ID, we will base it on a whirlpool hash of the identity URL of the origination node and a psuedo-random number, which should provide us with a 256 bit ID with an extremely low probability of collision (256 bits represents approximately 115 quattuorviginitillion or 1.16 X 10^77 unique numbers). This will be represented in communications as a base64url-encoded string. We will not depend on probabilities however and the ID must also be attached to a public key with public key cryptography used to provide an assurance of identity which has not been copied or somehow collided in whirlpool hash space.

Basing this ID on DNS provides a globally unique seed, which would not be the case if it was based completely on psuedo-random number generation.  

We will refer to the encoded globally unique uid string as zot_uid 

As there may be more than one DNS location attached/bound to a given zot_uid identity, delivery processes should deliver to all of them - as we do not know for sure which hub instance may be accessed at any given time. However we will designate one DNS location as "primary" and which will be the preferred location to view web profile information.

We must also provide the ability to change the primary to a new location. A look-up of information on the current primary location may provide a "forwarding pointer" which will tell us to update our records and move everything to the new location. There is also the possibility that the primary location is defunct and no longer responding. In that case, a location which has already been mapped to this zot_uid can seize control, and declare itself to be primary. In both cases the primary designation is automatically approved and moved. A request can also be made from a primary location which requests that another location be removed. 

In order to map a zot_uid to a second (or tertiary) location, we require a secure exchange which verifies that the new location is in possession of the private key for this zot_uid. Security of the private key is thus essential to avoid hijacking of identities.

Communications will then require
[list]
[*] zot_uid (string)
[*] uid_sig 
[*] callback (current location Zot endpoint url)
[*] callback_sig
[*] spec (int)
[/list]
passed with every communique. The spec is a revision number of the applicable Zot spec so that communications can be maintained with hubs using older and perhaps incompatible protocol specifications. Communications are verified using a stored public key which was copied when the connection to this zot_uid was first established. 

Key revocation and replacement must take place from the primary hub. The exact form for this is still being worked out, but will likely be a notification to all other bound hubs to "phone home" (to the primary hub) and request a copy of the new key. This communique should be verified using a site or hub key; as the original identity key may have been compromised and cannot be trusted.   

In order to eliminate confusion, there should be exactly one canonical url for any hub, so that these can be indexed and referenced without ambiguity. 

So as to avoid ambiguity of scheme, it is strongly encouraged that all addresses to be https with a "browser valid" cert and a single valid host component (either www.domain.ext or domain.ext, but not both), which is used in all communications. Multiple URLs may be provided locally, but only one unique URL should be used for all Zot communications within the grid.

Test installation which do not connect to the public grid may use non-SSL, but all traffic flowing over public networks should be safe from session-hijacking, preferably with a "browser recognised" cert.

Where possible, Zot encourages the use of "batching" to minimise network traffic between two hubs. This means that site 'A' can send multiple messages to site 'B' in a single transaction, and also consolidate deliveries of identical messages to multiple recipients on the same hub.

Messages themselves may or may not be encrypted in transit, depending on the private nature of the messages. SSL (strongly encouraged) provides unconditional encryption of the data stream, however there is little point in encrypting public communications which have been designated as having unrestricted visibility. The encryption of data storage and so-called "end-to-end encryption" is outside the scope of zot. It is presumed that hub operators will take adequate safeguards to ensure the security of their data stores and these are functions of application and site integrity as opposed to protocol integrity. 


[h4]Messages[/h4]

Given the constraints listed previously, a Zot communique should therefore be a json array of individual messages. These can be mixed and combined within the same transmission.

Each message then requires:
[list]
[*] type
[*] (optional) recipient list
[/list]
Lack of a recipient list would indicate an unencrypted public or site level message. The recipient list would contain an array of zot_uid with an individual decryption key, and a common iv. The decryption key is encoded with the recipient identity's public key. The iv is encrypted with the sender's private key.

All messages should be digitally signed by the sender.    

The type can be one of (this list is extensible):
[list]
[*] post (or activity)
[*] mail
[*] identity
[*] authenticate
[/list]
Identity messages have no recipients and notify the system social graph of an identity update, which could be a new or deleted identity, a new or deleted location, or a change in primary hub. The signature for these messages uses system keys as opposed to identity-specific keys.

Posts include many different types of activities, such as top-level posts, likes/dislikes, comments, tagging activities, etc. These types are indicated within the message sturcture.

authenticate messages result in mutual authentication and browser redirect to protected resources on the remote hub such as the ability to post wall-to-wall messages and view private photo albums and events, etc.

[h4]Discovery[/h4]

A well-known url is used to probe a hub for Zot capabilities and identity lookups, including the discovery of public keys, profile locations, profile photos, and primary hub location. 

The location for this service is /.well-known/zot-info - and must be available at the root of the chosen domain.

To perform a lookup, a POST request is made to the discovery location with the following parameters:

Required:

address  => an address on the target system such as "john"

Optional

target     => the Zot "guid" of the observer for evaluating permissions

target_sig => an RSA signature (base64url encoded) of the guid

key        => The public key needed to verify the signature

token      => a string (possibly random) chosen by the requesting service. If provided, an entry in the discovered packet will be provided called 'signed_token' which consists of the base64url_encoded RSA signature of the concatenation of the string 'token.' and the provided token using the private key of the discovered channel. This can be verified using the provided 'key' entry, and provides assurance that the server is in possession of the private key for the discovered identity. After 2017-01-01 it is [b]required[/b] that a server provide a signed_token [i]if[/i] a token was provided in the request.  

With no target provided, the permissions returned will be generic permissions 
for unknown or unauthenticated observers

Example of discovery packet for 'mike@zothub.com'
[code nowrap]
	{

    "success": true,
	"signed_token": "KBJrKTq1qrctNuxF3GwVh3GAGRqmgkirlXANPcJZAeWlvSt_9TMV097slR4AYnYCBEushbVqHEJ9Rb5wHTa0HzMbfRo8cRdl2yAirvvv5d98dtwHddQgX1jB0xEypXtmIYMdPGDLvhI1RNdIBhHkkrRcNreRzoy4xD--HM6m1W0-A8PJJJ9BcNxmGPcBtLzW08wzoP9trJ3M7DQ6Gkk6j7iwVsyApw1ZBaDvabGTdc_SFV-Iegtqw3rjzT_xXWsfzMlKBy-019MYn_KS-gu23YzjvGu5tS_zDfkQb8DMUlPLz5yyxM0yOMlUDtG2qQgIJAU2O0X6T5xDdJ6mtolNyhepg845PvFDEqBQGMIH1nc47CNumeudDi8IWymEALhjG_U8KAK7JVlQTJj2EKUb0au1g6fpiBFab5mmxCMtZEX3Jreyak5GOcFFz-WpxuXJD9TdSoIvaBfBFOoJnXkg2zE4RHXeQzZ2FotmrbBG5dm8B-_6byYGoHBc08ZsWze1K96JIeRnLpBaj6ifUDcVHxZMPcGHHT27dvU2PNbgLiBjlAsxhYqkhN5qOHN8XBcg2KRjcMBaI3V0YMxlzXz5MztmZq3fcB1p-ccIoIyMPMzSj3yMB7J9CEU2LYPSTHMdPkIeDE6GaCkQKviaQQJQde346tK_YjA2k7_SOBmvPYE",
    "guid": "sebQ-IC4rmFn9d9iu17m4BXO-kHuNutWo2ySjeV2SIW1LzksUkss12xVo3m3fykYxN5HMcc7gUZVYv26asx-Pg",
    "guid_sig": "Llenlbl4zHo6-g4sa63MlQmTP5dRCrsPmXHHFmoCHG63BLq5CUZJRLS1vRrrr_MNxr7zob_Ykt_m5xPKe5H0_i4pDj-UdP8dPZqH2fqhhx00kuYL4YUMJ8gRr5eO17vsZQ3XxTcyKewtgeW0j7ytwMp6-hFVUx_Cq08MrXas429ZrjzaEwgTfxGnbgeQYQ0R5EXpHpEmoERnZx77VaEahftmdjAUx9R4YKAp13pGYadJOX5xnLfqofHQD8DyRHWeMJ4G1OfWPSOlXfRayrV_jhnFlZjMU7vOdQwHoCMoR5TFsRsHuzd-qepbvo3pzvQZRWnTNu6oPucgbf94p13QbalYRpBXKOxdTXJrGdESNhGvhtaZnpT9c1QVqC46jdfP0LOX2xrVdbvvG2JMWFv7XJUVjLSk_yjzY6or2VD4V6ztYcjpCi9d_WoNHruoxro_br1YO3KatySxJs-LQ7SOkQI60FpysfbphNyvYMkotwUFI59G08IGKTMu3-GPnV1wp7NOQD1yzJbGGEGSEEysmEP0SO9vnN45kp3MiqbffBGc1r4_YM4e7DPmqOGM94qksOcLOJk1HNESw2dQYWxWQTBXPfOJT6jW9_crGLMEOsZ3Jcss0XS9KzBUA2p_9osvvhUKuKXbNztqH0oZIWlg37FEVsDs_hUwUJpv2Ar09k4",
    "key": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA7QCwvuEIwCHjhjbpz3Oc\ntyei/Pz9nDksNbsc44Cm8jxYGMXsTPFXDZYCcCB5rcAhPPdZSlzaPkv4vPVcMIrw\n5cdX0tvbwa3rNTng6uFE7qkt15D3YCTkwF0Y9FVZiZ2Ko+G23QeBt9wqb9dlDN1d\nuPmu9BLYXIT/JXoBwf0vjIPFM9WBi5W/EHGaiuqw7lt0qI7zDGw77yO5yehKE4cu\n7dt3SakrXphL70LGiZh2XGoLg9Gmpz98t+gvPAUEotAJxIUqnoiTA8jlxoiQjeRK\nHlJkwMOGmRNPS33awPos0kcSxAywuBbh2X3aSqUMjcbE4cGJ++/13zoa6RUZRObC\nZnaLYJxqYBh13/N8SfH7d005hecDxWnoYXeYuuMeT3a2hV0J84ztkJX5OoxIwk7S\nWmvBq4+m66usn6LNL+p5IAcs93KbvOxxrjtQrzohBXc6+elfLVSQ1Rr9g5xbgpub\npSc+hvzbB6p0tleDRzwAy9X16NI4DYiTj4nkmVjigNo9v2VPnAle5zSam86eiYLO\nt2u9YRqysMLPKevNdj3CIvst+BaGGQONlQalRdIcq8Lin+BhuX+1TBgqyav4XD9K\nd+JHMb1aBk/rFLI9/f2S3BJ1XqpbjXz7AbYlaCwKiJ836+HS8PmLKxwVOnpLMbfH\nPYM8k83Lip4bEKIyAuf02qkCAwEAAQ==\n-----END PUBLIC KEY-----\n",
    "name": "Mike Macgirvin",
    "name_updated": "2012-12-06 04:59:13",
    "address": "mike@zothub.com",
    "photo_mimetype": "image/jpeg",
    "photo": "https://zothub.com/photo/profile/l/1",
    "photo_updated": "2012-12-06 05:06:11",
    "url": "https://zothub.com/channel/mike",
    "connections_url": "https://zothub.com/poco/mike",
    "target": "",
    "target_sig": "",
    "searchable": false,
    "permissions": {
        "view_stream": true,
        "view_profile": true,
        "view_photos": true,
        "view_contacts": true,
        "view_storage": true,
        "view_pages": true,
        "send_stream": false,
        "post_wall": false,
        "post_comments": false,
        "post_mail": false,
        "post_photos": false,
        "tag_deliver": false,
        "chat": false,
        "write_storage": false,
        "write_pages": false,
        "delegate": false
    },
    "profile": {
        "description": "Freedom Fighter",
        "birthday": "0000-05-14",
        "next_birthday": "2013-05-14 00:00:00",
        "gender": "Male",
        "marital": "It's complicated",
        "sexual": "Females",
        "locale": "",
        "region": "",
        "postcode": "",
        "country": "Australia"
    },
    "locations": [
        {
            "host": "zothub.com",
            "address": "mike@zothub.com",
            "primary": true,
            "url": "https://zothub.com",
            "url_sig": "eqkB_9Z8nduBYyyhaSQPPDN1AhSm5I4R0yfcFxPeFpuu17SYk7jKD7QzvmsyahM5Kq7vDW6VE8nx8kdFYpcNaurqw0_IKI2SWg15pGrhkZfrCnM-g6A6qbCv_gKCYqXvwpSMO8SMIO2mjQItbBrramSbWClUd2yO0ZAceq3Z_zhirCK1gNm6mGRJaDOCuuTQNb6D66TF80G8kGLklv0o8gBfxQTE12Gd0ThpUb5g6_1L3eDHcsArW_RWM2XnNPi_atGNyl9bS_eLI2TYq0fuxkEdcjjYx9Ka0-Ws-lXMGpTnynQNCaSFqy-Fe1aYF7X1JJVJIO01LX6cCs-kfSoz29ywnntj1I8ueYldLB6bUtu4t7eeo__4t2CUWd2PCZkY3PKcoOrrnm3TJP5_yVFV_VpjkcBCRj3skjoCwISPcGYrXDufJxfp6bayGKwgaCO6QoLPtqqjPGLFm-fbn8sVv3fYUDGilaR3sFNxdo9mQ3utxM291XE2Pd0jGgeUtpxZSRzBuhYeOybu9DPusID320QbgNcbEbEImO8DuGIxuVRartzEXQF4WSYRdraZzbOqCzmU0O55P836JAfrWjgxTQkXlYCic-DBk-iE75JeT72smCtZ4AOtoFWCjZAABCw42J7JELY9APixZXWriKtjy6JI0G9d3fs6r7SrXr1JMy0",
            "callback": "https://zothub.com/post",
            "sitekey": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA1IWXwd/BZuevq8jzNFoR\n3VkenduQH2RpR3Wy9n4+ZDpbrUKGJddUGm/zUeWEdKMVkgyllVA/xHdB7jdyKs1X\nuIet9mIdnzvhdLO/JFD5hgbNG2wpSBIUY6aSNeCFTzszqXmuSXMW5U0Ef5pCbzEA\nnhoCoGL1KAgPqyxnGKUlj7q2aDwC9IRNtAqNyFQL67oT91vOQxuMThjlDhbR/29Q\ncYR4i1RzyahgEPCnHCPkT2GbRrkAPjNZAdlnk9UesgP16o8QB3tE2j50TVrbVc/d\nYRbzC56QMPP9UgUsapNeSJBHji75Ip/E5Eg/kfJC/HEQgyCqjCGfb7XeUaeQ7lLO\nqc7CGuMP+Jqr/cE54/aSHg8boTwxkMp11Ykb+ng17fl57MHTM2RJ99qZ1KBkXezR\nuH1lyvjzeJPxEFr9rkUqc4GH74/AgfbgaFvQc8TS7ovEa5I/7Pg04m7vLSEYc6UF\nYJYxXKrzmZT2TDoKeJzaBBx5MFLhW19l68h9dQ8hJXIpTP0hJrpI+Sr6VUAEfFQC\ndIDRiFcgjz6j7T/x8anqh63/hpsyf2PMYph1+4/fxtSCWJdvf+9jCRM8F1IDfluX\n87gm+88KBNaklYpchmGIohbjivJyru41CsSLe0uinQFvA741W00w6JrcrOAX+hkS\nRQuK1dDVwGKoIY85KtTUiMcCAwEAAQ==\n-----END PUBLIC KEY-----\n"
        }
    ],
    "site": {
        "url": "https://zothub.com",
        "directory_mode": "primary",
        "directory_url": "https://zothub.com/dirsearch"
    }

	}
[/code]


Discovery returns a JSON array with the following components:

'success' => ('1' or '')  Operation was successful if '1'. Otherwise an optional 'message' may be present indicating the source of error.

'signed_token' => If a token parameter was provided in the request, it is prepended with the text 'token.' and then RSA signed with the channel private key and base64url encoded and returned as 'signed_token'.

'guid' => the guid of the address on the target system

'guid_sig' => the base64url encoded RSA signature of the guid, signed with the private key associated with that guid.

'key' => the public key associated with that guid

'name' => channel name

'name_updated' => MySQL style timestamp '2012-01-01 00:00:00' when the name was last changed (UTC)

'address' => "webbie" or user@host address associated with this channel

'photo' => URL of a profile photo for this channel (ideally 175x175)

'photo_mimetype' => content-type of the profile photo

'photo_updated' => MySQL style timestamp when photo was last updated (UTC)  

'url' => location of channel homepage

'connections_url' => location of Portable Contacts (extended for zot) URL for this channel

'target' => if a permissions target was specified, it is mirrored

'target_sig' => if a permissions target was specified, the signature is mirrored.
    
'searchable' => ('1' or '') '1' indicates this entry can be searched in a directory

[h5]Permissions[/h5]


'permisssions' => extensible array of permissions appropriate to this target, values are '1' or ''

  Permissions may include:
[list]
[*] view_stream
[*] view_profile
[*] view_photos
[*] view_contacts
[*] view_storage
[*] view_pages
[*] send_stream
[*] post_wall
[*] post_comments
[*] post_mail
[*] post_photos
[*] tag_deliver
[*] chat
[*] write_storage
[*] write_pages
[*] delegate
[/list]


[h5]Profile[/h5]


'profile' => array of important profile fields
[list]
[*] description
[*] birthday YYYY-MM-DD , all fields are optional, any field (such as year) may be zero
[*] next_birthday => MySQL datetime string representing the next upcoming birthday, converted from the channel's default timezone to UTC. 
[*] gender (free form)
[*] marital (marital status)
[*] sexual (preference)
[*] locale (city)
[*] region (state)
[*] postcode
[*] country
[/list]

[h5]Locations[/h5]


'locations' => array of registered locations (DNS locations) this channel may be visible or may be posting from

Each location is an array of

'host' => DNS hostname, e.g. example.com

'address' => the webbie or user@host identifier associated with this location

'primary' => ('1' or '') whether or not this is the primary location for this channel where files and web pages are generally found

'url' => url of the root of this DNS location e.g. https://example.com

'url_sig' => base64url encoded RSA signature of the URL, signed with the channel private key

'callback' => Zot communications endpoint on this site, usually https://example.com/post

'sitekey' => public key of this site/host


[h5]Site[/h5]


'site' => array providing the directory role of the site responding to this request

'url' => url of this site e.g. https://example.com

'directory_mode' => one of 'primary', 'secondary', 'normal', or 'standalone'

'directory_url' => if this is a directory server or standalone, the URL for the directory search module



[h3]Magic Auth[/h3]

So-called "magic auth" takes place by a special exchange. On the remote computer, a redirection is made to the Zot endpoint with special GET parameters.

Endpoint: https://example.com/post/name

where 'name' is the left hand side of the channel webbie, for instance 'mike' where the webbie is 'mike@zothub.com'

Additionally four parameters are supplied:
[list]
[*] auth => the webbie of the person requesting access
[*] dest => the desired destination URL (urlencoded)
[*] sec  => a random string which is also stored locally for use during the verification phase. 
[*] version => the Zot revision
[/list]
When this packet is received, a Zot message is initiated to the auth identity:

[code nowrap]
    {
      "type":"auth_check",
      "sender":{
        "guid":"kgVFf_1_SSbyqH-BNWjWuhAvJ2EhQBTUdw-Q1LwwssAntr8KTBgBSzNVzUm9_RwuDpxI6X8me_QQhZMf7RfjdA",
        "guid_sig":"PT9-TApzpm7QtMxC63MjtdK2nUyxNI0tUoWlOYTFGke3kNdtxSzSvDV4uzq_7SSBtlrNnVMAFx2_1FDgyKawmqVtRPmT7QSXrKOL2oPzL8Hu_nnVVTs_0YOLQJJ0GYACOOK-R5874WuXLEept5-KYg0uShifsvhHnxnPIlDM9lWuZ1hSJTrk3NN9Ds6AKpyNRqf3DUdz81-Xvs8I2kj6y5vfFtm-FPKAqu77XP05r74vGaWbqb1r8zpWC7zxXakVVOHHC4plG6rLINjQzvdSFKCQb5R_xtGsPPfvuE24bv4fvN4ZG2ILvb6X4Dly37WW_HXBqBnUs24mngoTxFaPgNmz1nDQNYQu91-ekX4-BNaovjDx4tP379qIG3-NygHTjFoOMDVUvs-pOPi1kfaoMjmYF2mdZAmVYS2nNLWxbeUymkHXF8lT_iVsJSzyaRFJS1Iqn7zbvwH1iUBjD_pB9EmtNmnUraKrCU9eHES27xTwD-yaaH_GHNc1XwXNbhWJaPFAm35U8ki1Le4WbUVRluFx0qwVqlEF3ieGO84PMidrp51FPm83B_oGt80xpvf6P8Ht5WvVpytjMU8UG7-js8hAzWQeYiK05YTXk-78xg0AO6NoNe_RSRk05zYpF6KlA2yQ_My79rZBv9GFt4kUfIxNjd9OiV1wXdidO7Iaq_Q",
        "url":"http:\/\/podunk.edu",
        "url_sig":"T8Bp7j5DHHhQDCFcAHXfuhUfGk2P3inPbImwaXXF1xJd3TGgluoXyyKDx6WDm07x0hqbupoAoZB1qBP3_WfvWiJVAK4N1FD77EOYttUEHZ7L43xy5PCpojJQmkppGbPJc2jnTIc_F1vvGvw5fv8gBWZvPqTdb6LWF6FLrzwesZpi7j2rsioZ3wyUkqb5TDZaNNeWQrIEYXrEnWkRI_qTSOzx0dRTsGO6SpU1fPWuOOYMZG8Nh18nay0kLpxReuHCiCdxjXRVvk5k9rkcMbDBJcBovhiSioPKv_yJxcZVBATw3z3TTE95kGi4wxCEenxwhSpvouwa5b0hT7NS4Ay70QaxoKiLb3ZjhZaUUn4igCyZM0h6fllR5I6J_sAQxiMYD0v5ouIlb0u8YVMni93j3zlqMWdDUZ4WgTI7NNbo8ug9NQDHd92TPmSE1TytPTgya3tsFMzwyq0LZ0b-g-zSXWIES__jKQ7vAtIs9EwlPxqJXEDDniZ2AJ6biXRYgE2Kd6W_nmI7w31igwQTms3ecXe5ENI3ckEPUAq__llNnND7mxp5ZrdXzd5HHU9slXwDShYcW3yDeQLEwAVomTGSFpBrCX8W77n9hF3JClkWaeS4QcZ3xUtsSS81yLrp__ifFfQqx9_Be89WVyIOoF4oydr08EkZ8zwlAsbZLG7eLXY"
      },
      "recipients":{
        {
        "guid":"ZHSqb3yGar3TYV_o9S-JkD-6o_V4DhUcxtv0VeyX8Kj_ENHPI_M3SyAUucU835-mIayGMmTpqJz3ujPkcavVhA",
        "guid_sig":"JsAAXigNghTkkbq8beGMJjj9LBKZn28hZ-pHSsoQuwYWvBJ2lSnfc4r9l--WgO6sucH-SR6RiBo78eWn1cZrh_cRMu3x3LLx4y-tjixg-oOOgtZakkBg4vmOhkKPkci0mFtzvUrpY4YHySqsWTuPwRx_vOlIYIGEY5bRXpnkNCoC8J4EJnRucDrgSARJvA8QQeQQL0H4mWEdGL7wlsZp_2VTC6nEMQ48Piu6Czu5ThvLggGPDbr7PEMUD2cZ0jE4SeaC040REYASq8IdXIEDMm6btSlGPuskNh3cD0AGzH2dMciFtWSjmMVuxBU59U1I6gHwcxYEV6BubWt_jQSfmA3EBiPhKLyu02cBMMiOvYIdJ3xmpGoMY1Cn__vhHnx_vEofFOIErb6nRzbD-pY49C28AOdBA5ffzLW3ss99d0A-6GxZmjsyYhgJu4tFUAa7JUl84tMbq28Tib0HW6qYo6QWw8K1HffxcTpHtwSL5Ifx0PAoGMJsGDZDD1y_r9a4vH5pjqmGrjL3RXJJUy-m4eLV5r7xMWXsxjqu3D8r04_dcw4hwwexpMT1Nwf8CTB0TKb8ElgeOpDFjYVgrqMYWP0XdhatcFtAJI7gsS-JtzsIwON9Kij66-VAkqy_z1IXI0ziyqV1yapSVYoUV1vMScRZ_nMqwiB5rEDx-XLfzko"
        }
      }
      "callback":"\/post",
      "version":1,
      "secret":"1eaa6613699be6ebb2adcefa5379c61a3678aa0df89025470fac871431b70467",
      "secret_sig":"eKV968b1sDkOVdSMi0tLRtOhQw4otA8yFKaVg6cA4I46_zlAQGbFptS-ODiZlSAqR7RiiZQv4E2uXCKar52dHo0vvNKbpOp_ezWYcwKRu1shvAlYytsflH5acnDWL-FKOOgz5zqLLZ6cKXFMoR1VJGG_Od-DKjSwajyV9uVzTry58Hz_w0W2pjxwQ-Xv11rab5R2O4kKSW77YzPG2R5E6Q7HN38FrLtyWD_ai3K9wlsFOpwdYC064dk66X7BZtcIbKtM6zKwMywcfJzvS5_0U5yc5GGbIY_lY6SViSfx9shOKyxkEKHfS29Ynk9ATYGnwO-jnlMqkJC7t149H-sI9hYWMkLuCzaeLP56k2B2TmtnYvE_vHNQjzVhTwuHCIRVr-p6nplQn_P3SkOpYqPi3k_tnnOLa2d3Wtga8ClEY90oLWFJC3j2UkBf_VEOBNcg-t5XO3T-j9O4Sbk96k1Qoalc-QlznbGx4bOVsGkRBBMiH4YUqiiWB_OkFHtdqv7dqGeC-u-B4u9IxzYst46vvmyA3O-Q4APSZ1RY8ITUH0jLTbh6EAV7Oki8pIbOg0t56p-8RlanOZqmFvR-grVSc7Ak1ZcD8NACmvidUpa1B7WEvRcOeffx9lype0bt5XenDnMyx6szevwxZIiM8qGM2lsSk4fu8HI9cW0mLywzZT0"
    }
[/code]

auth_check messages MUST be encrypted with AES256CBC. This message is sent to the origination site, which checks the 'secret' to see if it is the same as the 'sec' which it passed originally. It also checks the secret_sig which is the secret signed by the destination channel's private key and base64url encoded. If everything checks out, a json packet is returned:
[code nowrap]
    { 
      "success":1, 
      "confirm":"q0Ysovd1uQRsur2xG9Tg6bC23ynzw0191SkVd7CJcYoaePy6e_v0vnmPg2xBUtIaHpx_aSuhgAkd3aVjPeaVBmts6aakT6a_yAEy7l2rBydntu2tvrHhoVqRNOmw0Q1tI6hwobk1BgK9Pm0lwOeAo8Q98BqIJxf47yO9pATa0wktOg6a7LMogC2zkkhwOV5oEqjJfeHeo27TiHr1e2WaphfCusjmk27V_FAYTzw05HvW4SPCx55EeeTJYIwDfQwjLfP4aKV-I8HQCINt-2yxJvzH7Izy9AW-7rYU0Il_gW5hrhIS5MTM12GBXLVs2Ij1CCLXIs4cO0x6e8KEIKwIjf7iAu60JPmnb_fx4QgBlF2HLw9vXMwZokor8yktESoGl1nvf5VV5GHWSIKAur3KPS2Tb0ekNh-tIk9u-xob4d9eIf6tge_d3aq1LcAtrDBDLk8AD0bho5zrVuTmZ9k-lBVPr_DRHSV_dlpu088j3ThaBsuV1olHK3vLFRhYCDIO0CqqK5IuhqtRNnRaqhlNN6fQUHpXk2SwHiJ2W36RCYMTnno6ezFk_tN-RA2ly-FomNZoC5FPA9gFwoJR7ZmVFDmUeK3bW-zYTA5vu15lpBPnt7Up_5rZKkr0WQVbhWJmylqOuwuNWbn3SrMQ8rYFZ23Tv300cOfKVgRBaePWQb4" 
    }
[/code]
'confirm' in this case is the base64url encoded RSA signature of the concatenation of 'secret' with the base64url encoded whirlpool hash of the source guid and guid_sig; signed with the source channel private key. This prevents a man-in-the-middle from inserting a rogue success packet. Upon receipt and successful verification of this packet, the destination site will redirect to the original destination URL and indicate a successful remote login. 

[h3]Zot Structures[/h3]
[h4]Zot Signatures[/h4]
All signed data in Zot is accomplished by performing an RSA sign operation using the private key of the initiator. The binary result is then base64url encoded for transport.
[h4]Zot Encryption[/h4]
Encryption is currently provided by AES256-CBC, the Advanced Encryption Standard using 256-bit keys and the Cipher Block Chaining mode of operation. Additional algorithms MAY be supported. A 32-octet key and 16-octet initialisation vector are randomly generated. The desired data is then encrypted using these generated strings and the result base64url encoded. Then we build an array:

[dl terms="b"]
[*= data]The base64url encoded encrypted data
[*= alg]The chosen algorithm, in this case the string 'aes256cbc'.
[*= key]The randomly generated key, RSA encrypted using the recipients public key, and the result base64url encoded
[*= iv]The randomly generated initialization vector, RSA encrypted using the recipient's public key, and the result base64url encoded
[/dl]

[h4]Basic Zot Packet[/h4]
Used for initiating a dialogue with another Zot site. This packet MAY be encrypted. The presence of an array element 'iv' indicates encryption has been applied. When sending an 'auth_check' packet type, this packet MUST be encrypted, using the public key of the destination site (the site key, as opposed to a sender key).  

[code nowrap]
    {
      "type":"notify",
      "sender":{
        "guid":"kgVFf_1_SSbyqH-BNWjWuhAvJ2EhQBTUdw-Q1LwwssAntr8KTBgBSzNVzUm9_RwuDpxI6X8me_QQhZMf7RfjdA",
        "guid_sig":"PT9-TApzpm7QtMxC63MjtdK2nUyxNI0tUoWlOYTFGke3kNdtxSzSvDV4uzq_7SSBtlrNnVMAFx2_1FDgyKawmqVtRPmT7QSXrKOL2oPzL8Hu_nnVVTs_0YOLQJJ0GYACOOK-R5874WuXLEept5-KYg0uShifsvhHnxnPIlDM9lWuZ1hSJTrk3NN9Ds6AKpyNRqf3DUdz81-Xvs8I2kj6y5vfFtm-FPKAqu77XP05r74vGaWbqb1r8zpWC7zxXakVVOHHC4plG6rLINjQzvdSFKCQb5R_xtGsPPfvuE24bv4fvN4ZG2ILvb6X4Dly37WW_HXBqBnUs24mngoTxFaPgNmz1nDQNYQu91-ekX4-BNaovjDx4tP379qIG3-NygHTjFoOMDVUvs-pOPi1kfaoMjmYF2mdZAmVYS2nNLWxbeUymkHXF8lT_iVsJSzyaRFJS1Iqn7zbvwH1iUBjD_pB9EmtNmnUraKrCU9eHES27xTwD-yaaH_GHNc1XwXNbhWJaPFAm35U8ki1Le4WbUVRluFx0qwVqlEF3ieGO84PMidrp51FPm83B_oGt80xpvf6P8Ht5WvVpytjMU8UG7-js8hAzWQeYiK05YTXk-78xg0AO6NoNe_RSRk05zYpF6KlA2yQ_My79rZBv9GFt4kUfIxNjd9OiV1wXdidO7Iaq_Q",
        "url":"http:\/\/podunk.edu",
        "url_sig":"T8Bp7j5DHHhQDCFcAHXfuhUfGk2P3inPbImwaXXF1xJd3TGgluoXyyKDx6WDm07x0hqbupoAoZB1qBP3_WfvWiJVAK4N1FD77EOYttUEHZ7L43xy5PCpojJQmkppGbPJc2jnTIc_F1vvGvw5fv8gBWZvPqTdb6LWF6FLrzwesZpi7j2rsioZ3wyUkqb5TDZaNNeWQrIEYXrEnWkRI_qTSOzx0dRTsGO6SpU1fPWuOOYMZG8Nh18nay0kLpxReuHCiCdxjXRVvk5k9rkcMbDBJcBovhiSioPKv_yJxcZVBATw3z3TTE95kGi4wxCEenxwhSpvouwa5b0hT7NS4Ay70QaxoKiLb3ZjhZaUUn4igCyZM0h6fllR5I6J_sAQxiMYD0v5ouIlb0u8YVMni93j3zlqMWdDUZ4WgTI7NNbo8ug9NQDHd92TPmSE1TytPTgya3tsFMzwyq0LZ0b-g-zSXWIES__jKQ7vAtIs9EwlPxqJXEDDniZ2AJ6biXRYgE2Kd6W_nmI7w31igwQTms3ecXe5ENI3ckEPUAq__llNnND7mxp5ZrdXzd5HHU9slXwDShYcW3yDeQLEwAVomTGSFpBrCX8W77n9hF3JClkWaeS4QcZ3xUtsSS81yLrp__ifFfQqx9_Be89WVyIOoF4oydr08EkZ8zwlAsbZLG7eLXY"
        "sitekey":"-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAxTeIwXZWrw/S+Ju6gewh
LgkKnNNe2uCUqCqMZoYgJar3T5sHCDhvXc4dDCbDkxVIaA/+V1mURtBV60a3IGjn
OAO0W0XGGLe2ED7G5o9U8T9mVGq8Mauv0v1oQ5wIR1gEAhBavkQ2OUGuF/YKn2nj
HlKsv9HzUAHpcDMUe3Uklc2RhQbMcnJxEgkyjCkDyrTtCZzISkTAocHvpCG1KSog
njUZdiz9UWxvM4rCFkCJvQU4RwRZJb7GA9ul+9JrF7NvUQTx8csRP2weBk1E9yyj
wbe187E0eVj9RXX2Mx3mYhgrTdodxLOVMSXZLg1/SMpeFFl7QBhuM0SiOPg8a7Et
e2iNA/RD4WiUFqCDfafasRa1TOozOm7LA+08lkAh5PeQPJsJbrX0wVVft++Y+5/z
BvcUOP73vcbz7j5hJ7HLsbQtye/UUCfODBFybuDqRM84Aet8rjZohX7vukXdMD4I
2HuB7pjR4uIfyYr0J63ANkvbsn8LR+RnirmHrK5H/OgxxjXcfYbGEQgFxvxhF6lA
FpMu6Do4dx3CIb6pRmZ8bjSImXexJ0BSo9n3gtrz0XYLecsYFlQ9+QQjm83qxyLb
M23in0xqMVsyQvzjNkpImrO/QdbEFRIIMee83IHq+adbyjQR49Z2hNEIZhkLPc3U
2cJJ2HkzkOoF2K37qwIzk68CAwEAAQ==
-----END PUBLIC KEY-----
"
      },
      "recipients":{
         {
            "guid":"lql-1VnxtiO4-WF0h72wLX1Fu8szzHDOXgQaTbELwXW77k8AKFfh-hYr70vqMrc3SSvWN-Flrc5HFhRTWB7ICw",
            "guid_sig":"PafvEL0VpKfxATxlCqDjfOeSIMdmpr3iU7X-Sysa1h5LzDpjSXsjO37tYZL-accb1M5itLlfnW5epkTa5I4flsW21zSY1A2jCuBQUTLLGV7rNyyBy7lgqJUFvAMRx0TfXzP9lcaPqlM9T1tA6jfWOsOmkdzwofGeXBnsjGfjsO2xdGYe6vwjOU0DSavukvzDMnOayB9DekpvDnaNBTxeGLM45Skzr7ZEMcNF7TeXMbnvpfLaALYEKeQs9bGH-UgAG8fBWgzVAzeBfx_XSR1rdixjyiZGP0kq0h35SlmMPcEjliodOBFwMXqpXFB7Ibp4F6o6te2p2ErViJccQVG8VNKB6SbKNXY6bhP5zVcVsJ-vR-p4xXoYJJvzTN7yTDsGAXHOLF4ZrXbo5yi5gFAlIrTLAF2EdWQwxSGyLRWKxG8PrDkzEzX6cJJ0VRcLh5z6OI5QqQNdeghPZbshMFMJSc_ApCPi9_hI4ZfctCIOi3T6bdgTNKryLm5fhy_eqjwLAZTGP-aUBgLZpb1mf2UojBn6Ey9cCyq-0T2RWyk-FcIcbV4qJ-p_8oODqw13Qs5FYkjLr1bGBq82SuolkYrXEwQClxnrfKa4KYc2_eHAXPL01iS9zVnI1ySOCNJshB97Odpooc4wk7Nb2Fo-Q6THU9zuu0uK_-JbK7IIl6go2qA"
         },
      },
      "callback":"\/post",
      "version":"1.2",
      "encryption":{
        "aes256cbc"
      },
      "secret":"1eaa6613699be6ebb2adcefa5379c61a3678aa0df89025470fac871431b70467",
      "secret_sig":"0uShifsvhHnxnPIlDM9lWuZ1hSJTrk3NN9Ds6AKpyNRqf3DUdz81-Xvs8I2kj6y5vfFtm-FPKAqu77XP05r74vGaWbqb1r8zpWC7zxXakVVOHHC4plG6rLINjQzvdSFKCQb5R_xtGsPPfvuE24bv4fvN4ZG2ILvb6X4Dly37WW_HXBqBnUs24mngoTxFaPgNmz1nDQNYQu91-ekX4-BNaovjDx4tP379qIG3-NygHTjFoOMDVUvs-pOPi1kfaoMjmYF2mdZAmVYS2nNLWxbeUymkHXF8lT_iVsJSzyaRFJS1Iqn7zbvwH1iUBjD_pB9EmtNmnUraKrCU9eHES27xTwD-yaaH_GHNc1XwXNbhWJaPFAm35U8ki1Le4WbUVRluFx0qwVqlEF3ieGO84PMidrp51FPm83B_oGt80xpvf6P8Ht5WvVpytjMU8UG7-js8hAzWQeYiK05YTXk-78xg0AO6NoNe_RSRk05zYpF6KlA2yQ_My79rZBv9GFt4kUfIxNjd9OiV1wXdidO7Iaq_Q"
    }
[/code]

[dl terms="b"]
[*= type] The message type: [b]notify, purge, refresh, force_refresh, auth_check, ping[/b] or [b]pickup[/b]. The packet contents vary by message type. Here we will describe the [b]notify[/b] packet. 
[*= callback]A string to be appended onto the url which identifies the Zot communications endpoint on this system. It is typically the string "/post".
[*= version]The Zot protocol identifier, to allow future protocol revisions to co-exist. 
[*= encryption] array of supported encryption algorithms, order by decreasing preference. If no compatible encryption methods are provided, applications MUST use 'aes256cbc'.
[*= secret]A 64-char string which is randomly generated by the sending site.
[*= secret_sig]The RSA signature of the secret, signed with the sender's private key. 
[*= sender] An array of four components that provide a portable identity. We can contact the URL provided and download a Zot info packet to obtain the public key of the sender, and use that to verify the sender guid and the posting URL signatures.
	[dl terms="b"]
	[*= guid]Typically a 64 character base64url encoded string. This is generated when an identity is created and an attempt is made that it be unique; though this isn't required.
	[*= guid_sig]The RSA signature of the guid, signed by the sender's private key and base64url encoded.
	[*= url]The base url of the location this post is originating from.
	[*= url_sig]The RSA signature of url, signed by the sender's private key and base64url encoded.
	[*= sitekey]The public key of the website specified in the url
	[/dl]
[*= recipients] Only used for private messages. An array of envelope recipients. Each recipient is represented by an array of guid and guid_sig. When recipients are specified, the entire packet is also encapsulated using a negotiated cryptographic algorithm or 'aes256cbc' if none could be negotiated. 
	[dl terms="b"]
	[*= guid]The guid of a private recipient.
	[*= guid_sig]The RSA signature of the guid, signed by the recipient's private key and base64url encoded
    [/dl]
[/dl]
