# Hubzilla Hub for YunoHost

[![Install Hubzilla with YunoHost](https://install-app.yunohost.org/install-with-yunohost.png)](https://install-app.yunohost.org/?app=hubzilla)


## Hubzilla
[Hubzilla](http://hubzilla.org) is a powerful platform for creating interconnected websites featuring a decentralized identity, communications, and permissions framework built using common webserver technology.


Current snapshot in *sources*:

* https://github.com/redmatrix/hubzilla: 3.0.1 (commit 9bf366b7d4ec09cef818b3e26448742fd33343a1)
* https://github.com/redmatrix/hubzilla-addons: 3.0.1 (commit 5f38b56a6a17a0c5dfaf370df8e3e50f91d3dc97)

## To-Do's
- [X] Installation and remove script.
- [X] Ldap integration.
- [X] Upgrade script.
- [X] Backup and restore script(Need to be tested,but hopefully will work).
- [X] Remove the admin email,path and is_public form installation form.
- [X] Stop modification of php.ini : exec().
- [X] Make changes to nginx configuration accouding to Hubzilla official guide.
- [ ] Force redirection to https by default.

## Important Notes

Before installing, read the [Hubzilla installation instructions](https://github.com/redmatrix/hubzilla/blob/master/install/INSTALL.txt) for important information about

- SSL certificate validation requirement (now with support for [Let's Encrypt!](https://letsencrypt.org)). See Installation section below.
- Dedicated domain (must install under web root like **https://hub.example.com/** not **https://example.com/hub/** )
- Required packages (all of these are not yet installed by this YunoHost installer package). This YunoHost package installs the following additional packages:
  - php5-cli
  - php5-imagick
  - php5-gd
  - php5-mcrypt
- This package requires a **system-wide change to php.ini** that enables the `exec()` perimission. [See the PHP manual for more information](php.net/manual/function.exec.php).



## Installation

### Register a new domain and add it to YunoHost
Hubzilla requires a dedicated domain, so obtain one and add it using the YunoHost admin panel. **Domains -> Add domain**. As Hubzilla uses the full domain and is installed on the root, you can create a subdomain such as hubzilla.domain.tld. Don't forget to update your DNS if you manage them manually.

Hubzilla requires browser-approved SSL certificates. If you have certificates not issued by [Let's Encrypt](https://letsencrypt.org/), install them manually as usual.

#### YunoHost >= 2.5 :
Once the dedicated domain has been added to YunoHost, go again to the admin panel, go to domains then select your domain and click on "Install Let's Encrypt certificate".

#### Yunohost < 2.5 :
For older versions of YunoHost, once you have added the new domain, SSH into your YunoHost server and perform the following steps:

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
Use the YunoHost admin panel to install Hubzilla by entering the GitHub repo address in the custom app URL

		https://github.com/YunoHost-Apps/hubzilla_ynh

Make sure to select your domain from the previous section as the application domain.

When installation is complete, you will need to visit your new hub's page and login with the admin account which was entered at the time of installation process. You should then be able to create your first channel and have the admin rights for the hub.

**For normal YunoHost users:** Normal LDAP users can login through Ldap authentication and create there channels.

**If the admin cannot access the admin settings:** If you cannot access the admin settings at `https://hub.example.com/admin` when you log in as the administrator, or you want to grant admin rights to any other user(s) on the hub, then you have to manually add 4096 to the account_roles under accounts for that user in the database through phpMYAdmin.
