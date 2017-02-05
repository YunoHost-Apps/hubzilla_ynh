# YunoHost App for Hubzilla Hub

## Hubzilla
[Hubzilla](http://hubzilla.org) is a powerful platform for creating interconnected websites featuring a decentralized identity, communications, and permissions framework built using common webserver technology. 


Current snapshot in *sources*: 

* https://github.com/redmatrix/hubzilla: 2.0.7
* https://github.com/redmatrix/hubzilla-addons: >2.0 (commit 33a9a7f971097bcf14228b626bfb127559e47830)

## Notes

Before installing, read the [Hubzilla installation instructions](https://github.com/redmatrix/hubzilla/blob/master/install/INSTALL.txt) for important information about 

- SSL certificate validation requirement (now with support for [Let's Encrypt!](https://letsencrypt.org))
- Dedicated domain (must install under web root like **https://hub.example.com/** not **https://example.com/hub/** )
- Required packages (all of these are not yet installed by this YunoHost installer package)



## Installation

### Register a new domain and add it to YunoHost
Hubzilla requires a dedicated domain, so obtain one and add it using the [YunoHost admin](https://reticu.li/yunohost/admin) panel. **Domains -> Add domain**

Once you have added the new domain to YunoHost, SSH into your YunoHost server and perform the following steps:

1. Install [certbot](https://certbot.eff.org/) to make installing free SSL certificates from Let's Encrypt simple.

1. Stop nginx

		service nginx stop
		
1. Run the **certbot** utility with the **certonly** option

		certbot certonly

1. Copy the generated certificate and key into the appropriate location for YunoHost to use

		cp /etc/letsencrypt/live/YOUR_DOMAIN/fullchain.pem /etc/yunohost/certs/YOUR_DOMAIN/crt.pem 
		cp /etc/letsencrypt/live/YOUR_DOMAIN/privkey.pem /etc/yunohost/certs/YOUR_DOMAIN/key.pem 

1. Restart nginx
		
		service nginx start
		
### Install the Hubzilla application
Use the [YunoHost admin](https://reticu.li/yunohost/admin) panel to install Hubzilla by entering the GitHub repo address in the custom app URL

		https://github.com/YunoHost-Apps/hubzilla-yunohost

Make sure to select your domain from the previous section as the application domain. Also set the application to Public.

When installation is complete, you will need to visit your new hub and register a new account using the email address you specified in the app installation form. You should then be able to log in and create your first channel.