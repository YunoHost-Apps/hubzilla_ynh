#!/bin/bash

#=================================================
# COMMON VARIABLES AND CUSTOM HELPERS
#=================================================

mysql_remove() {
	ynh_mysql_drop_db && ynh_mysql_drop_user --db_user=$db_user --db_name=$db_name
}

mysql_restore() {
	ynh_mysql_create_db --db_user=$db_user --db_name=$db_name --db_pwd=$db_pwd
}
