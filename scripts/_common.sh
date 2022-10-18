#!/bin/bash

#=================================================
# COMMON VARIABLES
#=================================================
# PHP APP SPECIFIC
#=================================================

YNH_PHP_VERSION="8.0"

php_dependencies="php${YNH_PHP_VERSION}-curl php${YNH_PHP_VERSION}-gd php${YNH_PHP_VERSION}-mysql php${YNH_PHP_VERSION}-mbstring php${YNH_PHP_VERSION}-xml php${YNH_PHP_VERSION}-zip php${YNH_PHP_VERSION}-cli php${YNH_PHP_VERSION}-imagick php${YNH_PHP_VERSION}-pgsql php${YNH_PHP_VERSION}-json"

# dependencies used by the app (must be on a single line)
pkg_dependencies="$php_dependencies"

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
