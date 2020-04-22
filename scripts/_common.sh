#!/bin/bash

#=================================================
# COMMON VARIABLES
#=================================================

# dependencies used by the app
pkg_dependencies="php-mbstring php-cli php-imagick php-xml php-zip"

#=================================================
# PERSONAL HELPERS
#=================================================

#=================================================
# EXPERIMENTAL HELPERS
#=================================================

ynh_smart_mktemp () {
        local min_size="${1:-300}"
        # Transform the minimum size from megabytes to kilobytes
        min_size=$(( $min_size * 1024 ))

        # Check if there's enough free space in a directory
        is_there_enough_space () {
                local free_space=$(df --output=avail "$1" | sed 1d)
                test $free_space -ge $min_size
        }

        if is_there_enough_space /tmp; then
                local tmpdir=/tmp
        elif is_there_enough_space /var; then
                local tmpdir=/var
        elif is_there_enough_space /; then
                local tmpdir=/   
        elif is_there_enough_space /home; then
                local tmpdir=/home
        else
		ynh_die "Insufficient free space to continue..."
        fi

        echo "$(sudo mktemp --directory --tmpdir="$tmpdir")"
}
#=================================================
# FUTURE OFFICIAL HELPERS
#=================================================
