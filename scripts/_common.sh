#!/bin/bash

#=================================================
# COMMON VARIABLES AND CUSTOM HELPERS
#=================================================
# PHP APP SPECIFIC
#=================================================
YNH_PHP_VERSION="8.2"
mysql_remove() {
	# FIXMEhelpers2.1 ynh_mysql_drop_db && ynh_mysql_drop_user --db_user=$db_user --db_name=$db_name
}

mysql_restore() {
	# FIXMEhelpers2.1 ynh_mysql_create_db --db_user=$db_user --db_name=$db_name --db_pwd=$db_pwd
}
