# Hubzilla Hub for YunoHost

[![Integration level](https://dash.yunohost.org/integration/hubzilla.svg)](https://ci-apps.yunohost.org/jenkins/job/hubzilla%20%28Community%29/lastBuild/consoleFull)

[![Install Hubzilla with YunoHost](https://install-app.yunohost.org/install-with-yunohost.png)](https://install-app.yunohost.org/?app=hubzilla)


## Hubzilla
[Hubzilla](http://hubzilla.org) is a powerful platform for creating interconnected websites featuring a decentralized identity, communications, and permissions framework built using common webserver technology.


Current snapshot in *sources*:

* https://framagit.org/hubzilla/core: 3.8.3 (commit a9fd3608ea50ec4522075b3315a35c8eed9581d7)
* https://framagit.org/hubzilla/addons: 3.8.3 (commit fc752b052c57e4ad209eb44dc0fc821e22f40772)

## To-Do's
- [X] Installation and remove script.
- [X] Ldap integration.
- [X] Upgrade script.
- [X] Backup and restore script(Need to be tested,but hopefully will work).
- [X] Remove the admin email,path and is_public form installation form.
- [X] Stop modification of php.ini : exec().
- [X] Make changes to nginx configuration accouding to Hubzilla official guide.
- [X] Force redirection to https by default.
- [X] Multi-instance.
- [X] Adeed php.log in the root folder for debugging php, with logrotate applied on it.(can be accesssed by admin->logs and entering the php.log).

## Important Notes

Before installing, read the [Hubzilla installation instructions](https://framagit.org/hubzilla/core/blob/master/install/INSTALL.txt) for important information about

- SSL certificate validation requirement (now with support for [Let's Encrypt!](https://letsencrypt.org)). See Installation section below.
- Dedicated domain (must install under web root like **https://hub.example.com/** not **https://example.com/hub/** )


## Installation

### Register a new domain and add it to YunoHost
Hubzilla requires a dedicated domain, so obtain one and add it using the YunoHost admin panel. **Domains -> Add domain**. As Hubzilla uses the full domain and is installed on the root, you can create a subdomain such as hubzilla.domain.tld. Don't forget to update your DNS if you manage them manually.

#### YunoHost >= 2.5 :
Once the dedicated domain has been added to YunoHost, go again to the admin panel, go to domains then select your domain and click on "Install Let's Encrypt certificate".

### Install the Hubzilla application
Use the YunoHost admin panel to install Hubzilla by entering the GitHub repo address in the custom app URL

		https://github.com/YunoHost-Apps/hubzilla_ynh

Make sure to select your domain from the previous section as the application domain.

When installation is complete, you will need to visit your new hub's page and login with the admin account which was entered at the time of installation process. You should then be able to create your first channel and have the admin rights for the hub.

**For normal YunoHost users:** Normal LDAP users can login through Ldap authentication and create there channels.

**If the admin cannot access the admin settings:** If you cannot access the admin settings at `https://hub.example.com/admin` when you log in as the administrator, or you want to grant admin rights to any other user(s) on the hub, then you have to manually add 4096 to the account_roles under accounts for that user in the database through phpMYAdmin.

**For logs:**: Go to admin->logs and enter the file name **php.log**. 
