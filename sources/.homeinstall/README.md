# Hubzilla at Home next to your Router

Run hubzilla-setup.sh for an unattended installation of hubzilla.

The script is known to work with Debian 8.3 stable (Jessie)

+ Home-PC (Debian-8.3.0-amd64)
+ DigitalOcean droplet (Debian 8.3 x64 / 512 MB Memory / 20 GB Disk / NYC3)

# Step-by-Step Overwiew

## Preconditions

Hardware

+ Internet connection and router at home
+ Mini-pc connected to your router
+ USB drive for backups

Software

+ Fresh installation of Debian on your mini-pc
+ Router with open ports 80 and 443 for your Debian

## The basic steps (quick overview)

+ Register your own domain (for example at selfHOST) or a free subdomain (for example at freeDNS)
+ Log on to your new debian (server)
  - apt-get install git
  - mkdir -p /var/www
  - cd /var/www
  - git clone https://github.com/redmatrix/hubzilla.git html
  - cp .homeinstall/hubzilla-config.txt.template .homeinstall/hubzilla-config.txt
  - nano .homeinstall/hubzilla-config.txt
    - Enter your values there: db pass, domain, values for dyn DNS
  - hubzilla-setup.sh as root
    - ... wait, wait, wait until the script is finised
  - reboot
+ Open your domain with a browser and step throught the initial configuration of hubzilla.

# Step-by-Step in Detail

## Preparations Hardware

### Mini-PC

### Recommended: USB Drive for Backups

The installation will create a daily backup.

If the backup process does not find an external device than the backup goes to
the internal disk.

The USB drive must be compatible with an encrpyted filesystem LUKS + ext4.

## Preparations Software

### Install Debian Linux on the Mini-PC

Download the stable Debian at https://www.debian.org/

Create bootable USB drive with Debian on it. You could use the programm
unetbootin, https://en.wikipedia.org/wiki/UNetbootin

Switch of your mini pc, plug in your USB drive and start the mini pc from the
stick. Install Debian. Follow the instructions of the installation.

### Configure your Router

Open the ports 80 and 443 on your router for your Debian

## Preparations Dynamic IP Address

Your Hubzilla must be reachable by a domain that you can type in your browser

    cooldomain.org

You can use subdomains as well

    my.cooldomain.org

There are two way to get a domain

- buy a domain (recommended) or
- register a free subdomain

### Method 1: Get yourself an own Domain (recommended)

...for example at selfHOST.de

### Method 2 Register a (free) Subdomain

Register a free subdomain for example at

- freeDNS
- selfHOST

WATCH THIS: A free subdomain is not the prefered way to get a domain name. Why?

Let's encrpyt issues a limited number of certificates each
day. Possibly other users of this domain will try to issue a certificate
at the same day as you do. So make sure you choose a domain with as less subdomains as
possible.

## Install Hubzilla on your Debian

Login to your debian
(Provided your username is "you" and the name of the mini pc is "debian". You
could take the IP address instead of "debian")

    ssh -X you@debian

Change to root user

    su -l

Install git

    apt-get install git

Make the directory for apache and change diretory to it

    mkdir /var/www
    cd /var/www/

Clone hubzilla from git ("git pull" will update it later)

    git clone https://github.com/redmatrix/hubzilla html

Change to the install script

    cd html/.homeinstall/
    
Copy the template file
    
    cp hubzilla-config.txt.template hubzilla-config.txt

Change the file "hubzilla-config.txt". Read the instructions there and enter your values.

    nano hubzilla-config.txt

Run the script

     ./hubzilla-setup.sh

Wait... The script should not finish with an error message.

In a webbrowser open your domain.
Expected: A test page of hubzilla is shown. All checks there shoulg be
successfull. Go on...
Expected: A page for the Hubzilla server configuration shows up.

Leave db server name "127.0.0.1" and port "0" untouched.

Enter

- DB user name = hubzilla
- DB pass word = This is the password you entered in "hubzilla-config.txt"
- DB name = hubzilla

Leave db type "MySQL" untouched.

Follow the instructions in the next pages.

