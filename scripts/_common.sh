#!/bin/bash

#=================================================
# COMMON VARIABLES
#=================================================

YNH_PHP_VERSION="7.3"

# dependencies used by the app
pkg_dependencies="php${YNH_PHP_VERSION}-mbstring php${YNH_PHP_VERSION}-cli php${YNH_PHP_VERSION}-imagick php${YNH_PHP_VERSION}-xml php${YNH_PHP_VERSION}-zip php${YNH_PHP_VERSION}-pgsql php${YNH_PHP_VERSION}-mysql php${YNH_PHP_VERSION}-json php${YNH_PHP_VERSION}-gd"
pg_pkg_dependencies="postgresql postgresql-contrib"

#=================================================
# PERSONAL HELPERS
#=================================================

#=================================================
# EXPERIMENTAL HELPERS
#=================================================

#=================================================
# FUTURE OFFICIAL HELPERS
#=================================================
