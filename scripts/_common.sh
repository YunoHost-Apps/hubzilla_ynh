#!/bin/bash

#=================================================
# COMMON VARIABLES
#=================================================
# PHP APP SPECIFIC
#=================================================

#=================================================
# PERSONAL HELPERS
#=================================================
mariadb-to-pg() {

        ynh_print_info --message="Migrating to PostgreSQL database..."

        # Retrieve MySQL user and password
        mysqlpwd=$(ynh_app_setting_get --app=$app --key=mysqlpwd)

        mysql_db_user="$db_user"
        if ynh_mysql_connect_as --user="mmuser" --password="$mysqlpwd" 2> /dev/null <<< ";"; then
            # On old instances db_user is `mmuser`
            mysql_db_user="mmuser"
        fi

        # Use pgloader to migrate database content from MariaDB to PostgreSQL
        tmpdir="$(mktemp -d)"

        cat <<EOT > $tmpdir/commands.load
LOAD DATABASE
     FROM mysql://$mysql_db_user:$mysqlpwd@127.0.0.1:3306/$db_name
     INTO postgresql://$db_user:$db_pwd@127.0.0.1:5432/$db_name

WITH include no drop, truncate, create no tables,
     create no indexes, preserve index names, no foreign keys,
     data only, workers = 16, concurrency = 1, prefetch rows = 10000

SET MySQL PARAMETERS
net_read_timeout = '90',
net_write_timeout = '180'

;
EOT
        pgloader $tmpdir/commands.load

        # Remove the MariaDB database
        ynh_mysql_remove_db --db_user=$mysql_db_user --db_name=$db_name
}

#=================================================
# EXPERIMENTAL HELPERS
#=================================================

#=================================================
# FUTURE OFFICIAL HELPERS
#=================================================
