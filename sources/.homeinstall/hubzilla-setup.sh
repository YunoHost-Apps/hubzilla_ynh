#!/bin/bash
#
# How to use
# ----------
# 
# This file automates the installation of hubzilla under Debian Linux
#
# 1) Copy the file "hubzilla-config.txt.template" to "hubzilla-config.txt"
#       Follow the instuctions there
# 
# 2) Switch to user "root" by typing "su -"
# 
# 3) Run with "./hubzilla-setup.sh"
#       If this fails check if you can execute the script.
#       - To make it executable type "chmod +x hubzilla-setup.sh"
#       - or run "bash hubzilla-setup.sh"
# 
# 
# What does this script do basically?
# -----------------------------------
# 
# This file automates the installation of hubzilla under Debian Linux
# - install
#        * apache webserer, 
#        * php,  
#        * mysql - the database for hubzilla,  
#        * phpmyadmin,  
#        * git to download and update hubzilla itself
# - download hubzilla core and addons
# - configure cron
#        * "poller.php" for regular background prozesses of hubzilla
#        * to_do "apt-get update" and "apt-get dist-upgrade" to keep linux
#          up-to-date
#        * to_do backup hubzillas database and files (rsnapshot)
# - configure dynamic ip with cron
# - to_do letsencrypt
# - to_do redirection to https
# 
# 
# Discussion
# ----------
# 
# Security - password  is the same for mysql-server, phpmyadmin and hubzilla db
# - The script runs into installation errors for phpmyadmin if it uses
#   different passwords. For the sake of simplicity one singel password.
# 
# Security - suhosin for PHP
# - The script does not install suhosin.
# - Is the security package suhosin usefull or not usefull?
#
# Hubzilla - email verification
# - The script switches off email verification off in all htconfig.tpl.
#   Example: /var/www/html/view/en/htconfig.tpl
# - Is this a silly idea or not?
#
# 
# Remove Hubzilla (for a fresh start using the script)
# ----------------------------------------------------
#
# You could use /var/www/hubzilla-remove.sh
# that is created by hubzilla-setup.sh.
#
# The script will remove (almost everything) what was installed by the script.
# After the removal you could run the script again to have a fresh install
# of all applications including hubzilla and its database.
# 
# How to restore from backup
# --------------------------
#
# Daily backup
# - - - - - - 
# 
# The installation
# - writes a script /var/www/hubzilla-daily.sh
# - creates a daily cron that runs the hubzilla-daily.sh
#
# hubzilla-daily.sh makes a (daily) backup of all relevant files
# - /var/lib/mysql/ > hubzilla database
# - /var/www/html/ > hubzilla from github
# - /var/www/letsencrypt/ > certificates
# 
# hubzilla-daily.sh writes the backup
# - either to an external disk compatible to LUKS+ext4 (see hubzilla-config.txt)
# - or to /var/cache/rsnapshot in case the external disk is not plugged in
# 
# Restore backup
# - - - - - - - 
# 
# This was not tested yet.
# Bacically you can copy the files from the backup to the server.
# 
# Credits
# -------
#
# The script is based on Thomas Willinghams script "debian-setup.sh"
# which he used to install the red#matrix.
#
# The script uses another script from https://github.com/lukas2511/letsencrypt.sh
#
# The documentation for bash is here
# https://www.gnu.org/software/bash/manual/bash.html
#
function check_sanity {
    # Do some sanity checking.
    print_info "Sanity check..."
    if [ $(/usr/bin/id -u) != "0" ]
    then
        die 'Must be run by root user'
    fi

    if [ -f /etc/lsb-release ]
    then
        die "Distribution is not supported"
    fi
    if [ ! -f /etc/debian_version ]
    then
        die "Ubuntu is not supported"
    fi
}

function check_config {
    print_info "config check..."
    # Check for required parameters
    if [ -z "$db_pass" ]
    then
        die "db_pass not set in $configfile"
    fi     
    if [ -z "$le_domain" ]
    then
        die "le_domain not set in $configfile"
    fi   
    # backup is important and should be checked
	if [ -n "$backup_device_name" ]
	then
        device_mounted=0
		if fdisk -l | grep -i "$backup_device_name.*linux"
		then
		    print_info "ok - filesystem of external device is linux"
	        if [ -n "$backup_device_pass" ]
	        then
	            echo "$backup_device_pass" | cryptsetup luksOpen $backup_device_name cryptobackup
	            if [ ! -d /media/hubzilla_backup ]
	            then
	                mkdir /media/hubzilla_backup
	            fi
	            if mount /dev/mapper/cryptobackup /media/hubzilla_backup
	            then
                    device_mounted=1
	                print_info "ok - could encrypt and mount external backup device"
                	umount /media/hubzilla_backup
	            else
            		print_warn "backup to external device will fail because encryption failed"
	            fi
                cryptsetup luksClose cryptobackup
            else
	            if mount $backup_device_name /media/hubzilla_backup
	            then
                    device_mounted=1
	                print_info "ok - could mount external backup device"
                	umount /media/hubzilla_backup
	            else
            		print_warn "backup to external device will fail because mount failed"
	            fi
            fi
		else
        	print_warn "backup to external device will fail because filesystem is either not linux or 'backup_device_name' is not correct in $configfile"
		fi
        if [ $device_mounted == 0 ]
        then
            die "backup device not ready"
        fi
	fi
}

function die {
    echo "ERROR: $1" > /dev/null 1>&2
    exit 1
}


function update_upgrade {
    print_info "updated and upgrade..."
    # Run through the apt-get update/upgrade first. This should be done before
    # we try to install any package
    apt-get -q -y update && apt-get -q -y dist-upgrade
    print_info "updated and upgraded linux"
}

function check_install {
    if [ -z "`which "$1" 2>/dev/null`" ]
    then
        # export DEBIAN_FRONTEND=noninteractive ... answers from the package
        # configuration database
        # - q ... without progress information
        # - y ... answer interactive questions with "yes"
        # DEBIAN_FRONTEND=noninteractive apt-get --no-install-recommends -q -y install $2
        DEBIAN_FRONTEND=noninteractive apt-get -q -y install $2
        print_info "installed $2 installed for $1"
    else
        print_warn "$2 already installed"
    fi
}

function nocheck_install {
    # export DEBIAN_FRONTEND=noninteractive ... answers from the package configuration database
    # - q ... without progress information
    # - y ... answer interactive questions with "yes"
    # DEBIAN_FRONTEND=noninteractive apt-get --no-install-recommends -q -y install $2
    # DEBIAN_FRONTEND=noninteractive apt-get --install-suggests -q -y install $1
    DEBIAN_FRONTEND=noninteractive apt-get -q -y install $1
    print_info "installed $1"
}


function print_info {
    echo -n -e '\e[1;34m'
    echo -n $1
    echo -e '\e[0m'
}

function print_warn {
    echo -n -e '\e[1;31m'
    echo -n $1
    echo -e '\e[0m'
}

function stop_hubzilla {
    if [ -d /etc/apache2 ]
    then
        print_info "stopping apache webserver..."
        service apache2 stop
    fi
    if [ -f /etc/init.d/mysql ]
    then
        print_info "stopping mysql db..."
        /etc/init.d/mysql stop
    fi
}

function install_apache {
    print_info "installing apache..."
    nocheck_install "apache2 apache2-utils"
}

function install_curl {
    print_info "installing curl..."
    nocheck_install "curl"
}

function install_sendmail {
    print_info "installing sendmail..."
    nocheck_install "sendmail sendmail-bin"
}

function install_php {
    # openssl and mbstring are included in libapache2-mod-php5
    # to_to:  php5-suhosin
    print_info "installing php..."
    nocheck_install "libapache2-mod-php5 php5 php-pear php5-xcache php5-curl php5-mcrypt php5-gd"
    php5enmod mcrypt
}

function install_mysql {
    # http://www.microhowto.info/howto/perform_an_unattended_installation_of_a_debian_package.html
    # 
    # To determine the required package name, key and type you can perform
    # a trial installation then search the configuration database.
    # 
    #     debconf-get-selections | grep mysql-server
    #
    # The command debconf-get-selections is provided by the package
    # debconf-utils, which you may need to install.
    #
    #    apt-get install debconf-utils
    #
    # If you want to supply an answer to a configuration question but do not 
    # want to be prompted for it then this can be arranged by preseeding the
    # DebConf database with the required information.
    #
    #     echo mysql-server-5.5 mysql-server/root_password password xyzzy | debconf-set-selections
    #     echo mysql-server-5.5 mysql-server/root_password_again password xyzzy | debconf-set-selections
    #
    print_info "installing mysql..."
    if [ -z "$mysqlpass" ]
    then
        die "mysqlpass not set in $configfile"
    fi
    echo mysql-server-5.5 mysql-server/root_password password $mysqlpass | debconf-set-selections
    echo mysql-server-5.5 mysql-server/root_password_again password $mysqlpass | debconf-set-selections
    nocheck_install "php5-mysql mysql-server mysql-client"
    php5enmod mcrypt
}

function install_phpmyadmin {
    print_info "installing phpmyadmin..."
    if [ -z "$phpmyadminpass" ]
    then
        die "phpmyadminpass not set in $configfile"
    fi
    echo phpmyadmin    phpmyadmin/setup-password password $phpmyadminpass | debconf-set-selections
    echo phpmyadmin    phpmyadmin/mysql/app-pass password $phpmyadminpass | debconf-set-selections
    echo phpmyadmin    phpmyadmin/app-password-confirm password $phpmyadminpass | debconf-set-selections
    echo phpmyadmin    phpmyadmin/mysql/admin-pass    password $phpmyadminpass | debconf-set-selections
    echo phpmyadmin    phpmyadmin/password-confirm    password $phpmyadminpass | debconf-set-selections
    echo phpmyadmin    phpmyadmin/reconfigure-webserver multiselect apache2 | debconf-set-selections
    nocheck_install "phpmyadmin"

    # It seems to be not neccessary to check rewrite.load because it comes
    # with the installation. To be sure you could check this manually by:
    #
    #    nano /etc/apache2/mods-available/rewrite.load
    #
    # You should find the content:
    #
    #    LoadModule rewrite_module /usr/lib/apache2/modules/mod_rewrite.so

    a2enmod rewrite
    if [ ! -f /etc/apache2/apache2.conf ]
    then
        die "could not find file /etc/apache2/apache2.conf"
    fi
    sed -i \
        "s/AllowOverride None/AllowOverride all/" \
        /etc/apache2/apache2.conf
    if [ -z "`grep 'Include /etc/phpmyadmin/apache.conf' /etc/apache2/apache2.conf`" ]
    then
        echo "Include /etc/phpmyadmin/apache.conf" >> /etc/apache2/apache2.conf
    fi
    service apache2 restart
}

function create_hubzilla_db {
    print_info "creating hubzilla database..." 
    if [ -z "$hubzilla_db_name" ]
    then
        die "hubzilla_db_name not set in $configfile"
    fi     
    if [ -z "$hubzilla_db_user" ]
    then
        die "hubzilla_db_user not set in $configfile"
    fi     
    if [ -z "$hubzilla_db_pass" ]
    then
        die "hubzilla_db_pass not set in $configfile"
    fi
    Q1="CREATE DATABASE IF NOT EXISTS $hubzilla_db_name;"
    Q2="GRANT USAGE ON *.* TO $hubzilla_db_user@localhost IDENTIFIED BY '$hubzilla_db_pass';"
    Q3="GRANT ALL PRIVILEGES ON $hubzilla_db_name.* to $hubzilla_db_user@localhost identified by '$hubzilla_db_pass';"
    Q4="FLUSH PRIVILEGES;"
    SQL="${Q1}${Q2}${Q3}${Q4}"     
    mysql -uroot -p$phpmyadminpass -e "$SQL"
}

function run_freedns {
    print_info "run freedns (dynamic IP)..."
    if [ -z "$freedns_key" ]
    then
        print_info "freedns was not started because 'freedns_key' is empty in $configfile"
    else
        if [ -n "$selfhost_user" ]
        then
            die "You can not use freeDNS AND selfHOST for dynamic IP updates ('freedns_key' AND 'selfhost_user' set in $configfile)"
        fi
        wget --no-check-certificate -O - https://freedns.afraid.org/dynamic/update.php?$freedns_key       
    fi
}

function install_run_selfhost {
    print_info "install and start selfhost (dynamic IP)..."
    if [ -z "$selfhost_user" ]
    then
        print_info "selfHOST was not started because 'selfhost_user' is empty in $configfile"
    else
        if [ -n "$freedns_key" ]
        then
            die "You can not use freeDNS AND selfHOST for dynamic IP updates ('freedns_key' AND 'selfhost_user' set in $configfile)"
        fi
        if [ -z "$selfhost_pass" ]
        then
            die "selfHOST was not started because 'selfhost_pass' is empty in $configfile"
        fi
        if [ ! -d $selfhostdir ]
        then
            mkdir $selfhostdir
        fi
        # the old way
        # https://carol.selfhost.de/update?username=123456&password=supersafe
        #
        # the prefered way
        wget --output-document=$selfhostdir/$selfhostscript http://jonaspasche.de/selfhost-updater
        echo "router" > $selfhostdir/device
        echo "$selfhost_user" > $selfhostdir/user
        echo "$selfhost_pass" > $selfhostdir/pass
        bash $selfhostdir/$selfhostscript update
    fi
}

function ping_domain {
    print_info "ping domain $domain..."
    # Is the domain resolved? Try to ping 6 times à 10 seconds 
    COUNTER=0    
    for i in {1..6}
    do
        print_info "loop $i for ping -c 1 $domain ..."     
        if ping -c 4 -W 1 $le_domain    
        then
            print_info "$le_domain resolved"
            break
        else 
            if [ $i -gt 5 ]
            then
                die "Failed to: ping -c 1 $domain not resolved"
            fi            
        fi 
        sleep 10
    done
    sleep 5
}

function configure_cron_freedns {
    print_info "configure cron for freedns..."
    if [ -z "$freedns_key" ]
    then
        print_info "freedns is not configured because freedns_key is empty in $configfile"
    else
        # Use cron for dynamich ip update
        #   - at reboot
        #   - every 30 minutes
        if [ -z "`grep 'freedns.afraid.org' /etc/crontab`" ]
        then
            echo "@reboot root https://freedns.afraid.org/dynamic/update.php?$freedns_key > /dev/null 2>&1" >> /etc/crontab
            echo "*/30 * * * * root wget --no-check-certificate -O - https://freedns.afraid.org/dynamic/update.php?$freedns_key > /dev/null 2>&1" >> /etc/crontab
        else
            print_info "cron for freedns was configured already"
        fi       
    fi
}

function configure_cron_selfhost {
    print_info "configure cron for selfhost..."
    if [ -z "$selfhost_user" ]
    then
        print_info "freedns is not configured because freedns_key is empty in $configfile"
    else
        # Use cron for dynamich ip update
        #   - at reboot
        #   - every 30 minutes
        if [ -z "`grep 'selfhost-updater.sh' /etc/crontab`" ]
        then
            echo "@reboot root bash /etc/selfhost/selfhost-updater.sh update > /dev/null 2>&1" >> /etc/crontab
            echo "*/5 * * * * root /bin/bash /etc/selfhost/selfhost-updater.sh update > /dev/null 2>&1" >> /etc/crontab
        else
            print_info "cron for selfhost was configured already"
        fi        
    fi
}

function install_git {
    print_info "installing git..."
    nocheck_install "git"
}

function install_letsencrypt {
    print_info "installing let's encrypt ..."
    # check if user gave domain
    if [ -z "$le_domain" ]
    then
        die "Failed to install let's encrypt: 'le_domain' is empty in $configfile"
    fi
    # configure apache
    apache_le_conf=/etc/apache2/sites-available/le-default.conf    
    if [ -f $apache_le_conf ]
    then
        print_info "$apache_le_conf exist already"
    else
        cat > $apache_le_conf <<END
# letsencrypt default Apache configuration
Alias /.well-known/acme-challenge /var/www/letsencrypt

<Directory /var/www/letsencrypt>
    Options FollowSymLinks
	Allow from all
</Directory>
END
        a2ensite le-default.conf
        service apache2 restart
    fi
    # download the shell script
    if [ -d $le_dir ]
    then
        print_info "letsenrypt exists already (nothing downloaded > no certificate created and registered)"
        return 0
    fi
    git clone https://github.com/lukas2511/letsencrypt.sh $le_dir
    cd $le_dir
    # create config file for letsencrypt.sh
    echo "WELLKNOWN=$le_dir" > $le_dir/config.sh
    if [ -n "$le_email" ]
    then
        echo "CONTACT_EMAIL=$le_email" >> $le_dir/config.sh
    fi
    # create domain file for letsencrypt.sh
    # WATCH THIS:
    #    - It did not work wit "sub.domain.org www.sub.domain.org".
    #    - So just use "sub.domain.org" only!
    echo "$le_domain" > $le_dir/domains.txt
    # test apache config for letsencrpyt
    url_http=http://$le_domain/.well-known/acme-challenge/domains.txt
    wget_output=$(wget -nv --spider --max-redirect 0 $url_http)
    if [ $? -ne 0 ]
    then
        die "Failed to load $url_http"
    fi
    # run letsencrypt.sh
    # 
    ./letsencrypt.sh --cron --config $le_dir/config.sh
}

function configure_apache_for_https {
    print_info "configuring apache to use httpS ..."
    # letsencrypt.sh
    #
    #   "${BASEDIR}/certs/${domain}/privkey.pem"
    #   "${BASEDIR}/certs/${domain}/cert.pem"
    #   "${BASEDIR}/certs/${domain}/fullchain.pem"
    #
    SSLCertificateFile=${le_dir}/certs/${le_domain}/cert.pem
    SSLCertificateKeyFile=${le_dir}/certs/${le_domain}/privkey.pem
    SSLCertificateChainFile=${le_dir}/certs/${le_domain}/fullchain.pem
    if [ ! -f $SSLCertificateFile ]
    then
        print_warn "Failed to configure apache for httpS: Missing certificate file $SSLCertificateFile" 
        return 0  
    fi
    # make sure that the ssl mode is enabled
    print_info "...configuring apache to use httpS - a2enmod ssl ..."
    a2enmod ssl
    # modify apach' ssl conf file 
    if grep -i "ServerName" $sslconf
    then
        print_info "seems that apache was already configered to use httpS with $sslconf"
    else
        sed -i "s/ServerAdmin.*$/ServerAdmin webmaster@localhost\\n        ServerName ${le_domain}/" $sslconf 
    fi     
    sed -i s#/etc/ssl/certs/ssl-cert-snakeoil.pem#$SSLCertificateFile# $sslconf 
    sed -i s#/etc/ssl/private/ssl-cert-snakeoil.key#$SSLCertificateKeyFile# $sslconf 
    sed -i s#/etc/apache2/ssl.crt/server-ca.crt#$SSLCertificateChainFile# $sslconf 
    sed -i s/#SSLCertificateChainFile/SSLCertificateChainFile/ $sslconf 
    # apply changes
    a2ensite default-ssl.conf
    service apache2 restart
}

function check_https {
    print_info "checking httpS > testing ..."
    url_https=https://$le_domain
    wget_output=$(wget -nv --spider --max-redirect 0 $url_https)
    if [ $? -ne 0 ]
    then
        print_warn "check not ok"
    else
        print_info "check ok"
    fi
}

function install_hubzilla {
    print_info "installing hubzilla..."
    # rm -R /var/www/html/ # for "stand alone" usage
    cd /var/www/
    # git clone https://github.com/redmatrix/hubzilla html # for "stand alone" usage
    cd html/
    git clone https://github.com/redmatrix/hubzilla-addons addon
    mkdir -p "store/[data]/smarty3"
    chmod -R 777 store
    touch .htconfig.php
    chmod ou+w .htconfig.php
    install_hubzilla_plugins
    cd /var/www/
    chown -R www-data:www-data html
	chown root:www-data /var/www/html/
	chown root:www-data /var/www/html/.htaccess
	chmod 0644 /var/www/html/.htaccess
    # try to switch off email registration
    sed -i "s/verify_email.*1/verify_email'] = 0/" /var/www/html/view/*/ht*
    if [ -n "`grep -r 'verify_email.*1' /var/www/html/view/`" ]
    then
        print_warn "Hubzillas registration prozess might have email verification switched on."
    fi
    print_info "installed hubzilla"
}

function install_hubzilla_plugins {
    print_info "installing hubzilla plugins..."
    cd /var/www/html
    plugin_install=.homeinstall/plugin_install.txt
    theme_install=.homeinstall/theme_install.txt
    # overwrite script to update the plugin and themes
    rm -f $plugins_update
    echo "cd /var/www/html" >> $plugins_update
    ###################
    # write plugin file
    if [ ! -f "$plugin_install" ]
    then
        echo "# To install a plugin" >> $plugin_install
        echo "# 1. add the plugin in a new line and run" >> $plugin_install
        echo "# 2. run" >> $plugin_install
        echo "#   cd /var/www/html/.homeinstall" >> $plugin_install
        echo "#   ./hubzilla-setup.sh" >> $plugin_install
        echo "https://gitlab.com/zot/ownmapp.git ownMapp" >> $plugin_install
        echo "https://gitlab.com/zot/hubzilla-chess.git chess" >> $plugin_install
    fi
    # install plugins
    while read -r line; do
        [[ "$line" =~ ^#.*$ ]] && continue
        p_url=$(echo $line | awk -F' ' '{print $1}')
        p_name=$(echo $line | awk -F' ' '{print $2}')
        # basic check of format
	    if [ ${#p_url} -ge 1 ] && [ ${#p_name} -ge 1 ]
	    then
            # install addon
            util/add_addon_repo $line
            util/update_addon_repo $p_name # not sure if this line is neccessary
            echo "util/update_addon_repo $p_name" >> $plugins_update
        else
            print_info "skipping installation of a plugin from file $plugin_install - something wrong with format in line: $line"
	    fi
    done < "$plugin_install"
    ###################
    # write theme file
    if [ ! -f "$theme_install" ]
    then
        echo "# To install a theme" >> $theme_install
        echo "# 1. add the theme in a new line and run" >> $theme_install
        echo "# 2. run" >> $theme_install
        echo "#   cd /var/www/html/.homeinstall" >> $theme_install
        echo "#   ./hubzilla-setup.sh" >> $theme_install
        echo "https://github.com/DeadSuperHero/hubzilla-themes.git DeadSuperHeroThemes" >> $theme_install

    fi
    # install plugins
    while read -r line; do
        [[ "$line" =~ ^#.*$ ]] && continue
        p_url=$(echo $line | awk -F' ' '{print $1}')
        p_name=$(echo $line | awk -F' ' '{print $2}')
        # basic check of format
	    if [ ${#p_url} -ge 1 ] && [ ${#p_name} -ge 1 ]
	    then
            # install addon
            util/add_theme_repo $line
            util/update_theme_repo $p_name # not sure if this line is neccessary
            echo "util/update_theme_repo $p_name" >> $plugins_update
        else
            print_info "skipping installation of a theme from file $theme_install - something wrong with format in line: $line"
	    fi
    done < "$theme_install"
    print_info "installed hubzilla plugins and themes"
}

function rewrite_to_https {
    print_info "configuring apache to redirect http to httpS ..."
    htaccessfile=/var/www/html/.htaccess
    if grep -i "https" $htaccessfile
    then
        print_info "...configuring apache to redirect http to httpS was already done in $htaccessfile"
    else
        sed -i "s#QSA]#QSA]\\n  RewriteCond %{SERVER_PORT} !^443$\\n  RewriteRule (.*) https://%{HTTP_HOST}/$1 [R=301,L]#" $htaccessfile 
    fi     
    service apache2 restart
}

# This will allways overwrite both config files
#   - internal disk
#   - external disk (LUKS + ext4)
#  of rsnapshot for hubzilla
function install_rsnapshot {
    print_info "installing rsnapshot..."
    nocheck_install "rsnapshot"
	# internal disk
    cp -f /etc/rsnapshot.conf $snapshotconfig   
    sed -i "/hourly/s/retain/#retain/" $snapshotconfig 
    sed -i "/monthly/s/#retain/retain/" $snapshotconfig 
    sed -i "s/^cmd_cp/#cmd_cp/" $snapshotconfig
    sed -i "s/^backup/#backup/" $snapshotconfig
    if [ -z "`grep 'letsencrypt' $snapshotconfig`" ]
    then
		echo "backup	/var/lib/mysql/	localhost/" >> $snapshotconfig
		echo "backup	/var/www/html/	localhost/" >> $snapshotconfig
		echo "backup	/var/www/letsencrypt/	localhost/" >> $snapshotconfig
    fi
	# external disk
	if [ -n "$backup_device_name" ] && [ -n "$backup_device_pass" ]
	then
		cp -f /etc/rsnapshot.conf $snapshotconfig_external_device   
		sed -i "s#snapshot_root.*#snapshot_root	$backup_mount_point#" $snapshotconfig_external_device
		sed -i "/hourly/s/retain/#retain/" $snapshotconfig_external_device 
		sed -i "/monthly/s/#retain/retain/" $snapshotconfig_external_device 
		sed -i "s/^cmd_cp/#cmd_cp/" $snapshotconfig_external_device
		sed -i "s/^backup/#backup/" $snapshotconfig_external_device
		if [ -z "`grep 'letsencrypt' $snapshotconfig_external_device`" ]
		then
			echo "backup	/var/lib/mysql/	localhost/" >> $snapshotconfig_external_device
			echo "backup	/var/www/html/	localhost/" >> $snapshotconfig_external_device
			echo "backup	/var/www/letsencrypt/	localhost/" >> $snapshotconfig_external_device
		fi
    else
        print_info "No backup configuration (rsnapshot) for external device configured. Reason: backup_device_name and/or backup_device_pass not given in $configfile"
	fi
}

function install_cryptosetup {
    print_info "installing cryptsetup..."
    nocheck_install "cryptsetup"
}

function configure_cron_daily {
    print_info "configuring cron..."
    # every 10 min for poller.php
    if [ -z "`grep 'poller.php' /etc/crontab`" ]
    then
        echo "*/10 * * * * www-data cd /var/www/html; php include/poller.php >> /dev/null 2>&1" >> /etc/crontab
    fi
    # Run external script daily at 05:30
    # - stop apache and mysql-server
    # - backup hubzilla
    # - update hubzilla core and addon
    # - update and upgrade linux
    # - reboot
echo "#!/bin/sh" > /var/www/$hubzilladaily
echo "#" >> /var/www/$hubzilladaily
echo "echo \" \"" >> /var/www/$hubzilladaily
echo "echo \"+++ \$(date) +++\"" >> /var/www/$hubzilladaily
echo "echo \" \"" >> /var/www/$hubzilladaily
echo "echo \"\$(date) - renew certificat...\"" >> /var/www/$hubzilladaily
echo "bash $le_dir/letsencrypt.sh --cron --config $le_dir/config.sh" >> /var/www/$hubzilladaily
echo "#" >> /var/www/$hubzilladaily
echo "# stop hubzilla" >> /var/www/$hubzilladaily
echo "echo \"\$(date) - stoping apache and mysql...\"" >> /var/www/$hubzilladaily
echo "service apache2 stop" >> /var/www/$hubzilladaily
echo "/etc/init.d/mysql stop # to avoid inconsistancies" >> /var/www/$hubzilladaily
echo "#" >> /var/www/$hubzilladaily
echo "# backup" >> /var/www/$hubzilladaily
echo "echo \"\$(date) - try to mount external device for backup...\"" >> /var/www/$hubzilladaily
echo "backup_device_name=$backup_device_name" >> /var/www/$hubzilladaily
echo "backup_device_pass=$backup_device_pass" >> /var/www/$hubzilladaily
echo "backup_mount_point=$backup_mount_point" >> /var/www/$hubzilladaily
echo "device_mounted=0" >> /var/www/$hubzilladaily
echo "if [ -n \"$backup_device_name\" ]" >> /var/www/$hubzilladaily
echo "then" >> /var/www/$hubzilladaily
echo "    if blkid | grep $backup_device_name" >> /var/www/$hubzilladaily
echo "    then" >> /var/www/$hubzilladaily
	if [ -n "$backup_device_pass" ]
	then
echo "        echo \"decrypting backup device...\"" >> /var/www/$hubzilladaily
echo "        echo "\"$backup_device_pass\"" | cryptsetup luksOpen $backup_device_name cryptobackup" >> /var/www/$hubzilladaily
    fi
echo "        if [ ! -d $backup_mount_point ]" >> /var/www/$hubzilladaily
echo "        then" >> /var/www/$hubzilladaily
echo "            mkdir $backup_mount_point" >> /var/www/$hubzilladaily
echo "        fi" >> /var/www/$hubzilladaily
echo "        echo \"mounting backup device...\"" >> /var/www/$hubzilladaily
	if [ -n "$backup_device_pass" ]
	then
echo "        if mount /dev/mapper/cryptobackup $backup_mount_point" >> /var/www/$hubzilladaily
	else
echo "        if mount $backup_device_name $backup_mount_point" >> /var/www/$hubzilladaily
	fi
echo "        then" >> /var/www/$hubzilladaily
echo "            device_mounted=1" >> /var/www/$hubzilladaily
echo "            echo \"device $backup_device_name is now mounted. Starting backup...\"" >> /var/www/$hubzilladaily
echo "			rsnapshot -c $snapshotconfig_external_device daily" >> /var/www/$hubzilladaily
echo "			rsnapshot -c $snapshotconfig_external_device weekly" >> /var/www/$hubzilladaily
echo "			rsnapshot -c $snapshotconfig_external_device monthly" >> /var/www/$hubzilladaily
echo "			echo \"\$(date) - disk sizes...\"" >> /var/www/$hubzilladaily
echo "			df -h" >> /var/www/$hubzilladaily
echo "			echo \"\$(date) - db size...\"" >> /var/www/$hubzilladaily
echo "			du -h $backup_mount_point | grep mysql/hubzilla" >> /var/www/$hubzilladaily
echo "            echo \"unmounting backup device...\"" >> /var/www/$hubzilladaily
echo "            umount $backup_mount_point" >> /var/www/$hubzilladaily
echo "        else" >> /var/www/$hubzilladaily
echo "            echo \"failed to mount device $backup_device_name\"" >> /var/www/$hubzilladaily
echo "        fi" >> /var/www/$hubzilladaily
	if [ -n "$backup_device_pass" ]
	then
echo "        echo \"closing decrypted backup device...\"" >> /var/www/$hubzilladaily
echo "        cryptsetup luksClose cryptobackup" >> /var/www/$hubzilladaily
	fi
echo "    fi" >> /var/www/$hubzilladaily
echo "fi" >> /var/www/$hubzilladaily
echo "if [ \$device_mounted == 0 ]" >> /var/www/$hubzilladaily
echo "then" >> /var/www/$hubzilladaily
echo "    echo \"device could not be mounted $backup_device_name. Using internal disk for backup...\"" >> /var/www/$hubzilladaily
echo "	rsnapshot -c $snapshotconfig daily" >> /var/www/$hubzilladaily
echo "	rsnapshot -c $snapshotconfig weekly" >> /var/www/$hubzilladaily
echo "	rsnapshot -c $snapshotconfig monthly" >> /var/www/$hubzilladaily
echo "fi" >> /var/www/$hubzilladaily
echo "#" >> /var/www/$hubzilladaily
echo "echo \"\$(date) - db size...\"" >> /var/www/$hubzilladaily
echo "du -h /var/cache/rsnapshot/ | grep mysql/hubzilla" >> /var/www/$hubzilladaily
echo "#" >> /var/www/$hubzilladaily
echo "# update" >> /var/www/$hubzilladaily
echo "echo \"\$(date) - updating letsencrypt.sh...\"" >> /var/www/$hubzilladaily
echo "git -C /var/www/letsencrypt/ pull" >> /var/www/$hubzilladaily
echo "echo \"\$(date) - updating hubhilla core...\"" >> /var/www/$hubzilladaily
echo "git -C /var/www/html/ pull" >> /var/www/$hubzilladaily
echo "echo \"\$(date) - updating hubhilla addons...\"" >> /var/www/$hubzilladaily
echo "git -C /var/www/html/addon/ pull" >> /var/www/$hubzilladaily
echo "bash /var/www/html/$plugins_update" >> /var/www/$hubzilladaily
echo "chown -R www-data:www-data /var/www/html/ # make all accessable for the webserver" >> /var/www/$hubzilladaily
echo "chown root:www-data /var/www/html/.htaccess" >> /var/www/$hubzilladaily
echo "chmod 0644 /var/www/html/.htaccess # www-data can read but not write it" >> /var/www/$hubzilladaily
echo "echo \"\$(date) - updating linux...\"" >> /var/www/$hubzilladaily
echo "apt-get -q -y update && apt-get -q -y dist-upgrade # update linux and upgrade" >> /var/www/$hubzilladaily
echo "echo \"\$(date) - Backup hubzilla and update linux finished. Rebooting...\"" >> /var/www/$hubzilladaily
echo "#" >> /var/www/$hubzilladaily
echo "reboot" >> /var/www/$hubzilladaily

    if [ -z "`grep 'hubzilla-daily.sh' /etc/crontab`" ]
    then
        echo "30 05 * * * root /bin/bash /var/www/$hubzilladaily >> /var/www/html/hubzilla-daily.log 2>&1" >> /etc/crontab
        echo "0 0 1 * * root rm /var/www/html/hubzilla-daily.log" >> /etc/crontab
    fi

    # This is active after either "reboot" or "/etc/init.d/cron reload"
    print_info "configured cron for updates/upgrades"
}

function write_uninstall_script {
    print_info "writing uninstall script..."

    cat > /var/www/hubzilla-remove.sh <<END
#!/bin/sh
#
# This script removes Hubzilla.
# You might do this for a fresh start using the script.
# The script will remove (almost everything) what was installed by the script,
# all applications including hubzilla and its database.
#
# Backup the certificates of letsencrypt (you never know)
cp -a /var/www/letsencrypt/ ~/backup_le_certificats
#
# Removal
apt-get remove apache2 apache2-utils libapache2-mod-php5 php5 php-pear php5-xcache php5-curl php5-mcrypt php5-gd php5-mysql mysql-server mysql-client phpmyadmin
apt-get purge apache2 apache2-utils libapache2-mod-php5 php5 php-pear php5-xcache php5-curl php5-mcrypt php5-gd php5-mysql mysql-server mysql-client phpmyadmin
apt-get autoremove
apt-get clean
rm /etc/rsnapshot_hubzilla.conf
rm /etc/rsnapshot_hubzilla_external_device.conf
rm -R /etc/apache2/
rm -R /var/lib/mysql/
rm -R /var/www
rm -R /etc/selfhost/
# uncomment the next line if you want to remove the backups
# rm -R /var/cache/rsnapshot
nano /etc/crontab # remove entries there manually
END
    chmod -x /var/www/hubzilla-remove.sh
}

########################################################################
# START OF PROGRAM 
########################################################################
export PATH=/bin:/usr/bin:/sbin:/usr/sbin

check_sanity

# Read config file edited by user
configfile=hubzilla-config.txt
source $configfile

selfhostdir=/etc/selfhost
selfhostscript=selfhost-updater.sh
hubzilladaily=hubzilla-daily.sh
plugins_update=.homeinstall/plugins_update.sh
snapshotconfig=/etc/rsnapshot_hubzilla.conf
snapshotconfig_external_device=/etc/rsnapshot_hubzilla_external_device.conf
backup_mount_point=/media/hubzilla_backup
le_dir=/var/www/letsencrypt
sslconf=/etc/apache2/sites-available/default-ssl.conf

#set -x    # activate debugging from here

check_config
stop_hubzilla
update_upgrade
install_curl
install_sendmail
install_apache
install_php
install_mysql
install_phpmyadmin
create_hubzilla_db
run_freedns
install_run_selfhost
ping_domain
configure_cron_freedns
configure_cron_selfhost
install_git
install_letsencrypt
configure_apache_for_https
check_https
install_hubzilla
rewrite_to_https
install_rsnapshot
configure_cron_daily
install_cryptosetup
write_uninstall_script

#set +x    # stop debugging from here

