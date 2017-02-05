Feature: Zot Access Tokens
Status: Draft
Date: 15 July 2016


Purpose:

In order to facilitate sharing of private resources with non-members or members of federation nodes with limited identification discovery, Hubzilla should provide members with a mechanism to create and manage temporary ("throwaway") logins, aka "Zot Access Tokens". These tokens/credentials may be used to authenticate to a hubzilla site for the sole purpose of accessing privileged or access controlled resources (files, photos, posts, webpages, chatrooms, etc.). 


Scope: 

Zot Access Tokens do not convey membership in the site or network. In particular, they do not provide an account or channel; which may be necessary to interact with the hub owner or with others in the network or federation of networks. In most cases they can only be used to consume restricted resources and do not have an ability to create those resources, however this ability may be provided by custom configurations or in future releases or addons. 

For instance the ability for a temporary login to access a chatroom may provide suitable permission to create chat messages inside that chatroom.


Implementation:

Zot Access Tokens are managed through a "tab" of the settings page. Access to this tab may be controlled by site configuration. On this page, channels may create, edit, list, and remove any access tokens under their control. 

The form to create/edit accepts three parameters, a human readable name, a password or access token, and an optional expiration. Once expired, the access token is no longer valid, may no longer be used, and will be automatically purged from the list of temporary accounts. The password field in the create/edit forms displays the text of the access token and not an obscured password. By default we will create a token using the autoname() function, which generally produces a random character sequence which is "pronounceable", hence easy to convey or remember. This can be changed to any other character sequence which is acceptable to the site password complexity policy. (In most Hubzilla installations this imposes a minimum of three characters, but may be extended by plugin or site policy).


Usage:

We do not specify mechanisms for sharing these tokens with others. Any communication method may be used. Any tokens you have created are added to the Access Control List selector and may be used anywhere that Access Control Lists are provided.

	Example: A visitor arrives at your site. She has an access token you have provided, and attempts to visit one of your photo albums (which is restricted to be viewed only by yourself and one temporary identity). Permission is denied.

The visitor now selects "Login" from the menu navigation bar. This presents a login page. She enters the name and password you have provided her, and she can now view the restricted photo album.


Alternatively, you may share a link to a protected file by adding a parameter "&zat=abc123" to the URL, where the string "abc123" is the access token or password for the temporary login. No further negotiation is required, and the file is presented. 

Zot Acess Tokens are represented internally as an authenticated "observer". Querying the observer in code should return a pseudo or system generated xchan with an unknown protocol and a default profile photo. It will match (successfully) any access control rule which allows authenticated observers. 

Security Considerations:

The URL form of authentication is inherently less secure than using a login, but may be preferable for some uses of this feature. It probably should not be transmitted over non-SSL links. 


Future development:

It might be desirable for future implementations to provide an options for single-use, where the access token is removed promptly following first use.   
   
 