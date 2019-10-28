#!/bin/sh
set -eu

# Enable command tracing if desired
[ -n "${ENTRYPOINT_TRACE:-}" ] && set -x

# Get a list of *.ini files in /etc/php/conf.d
INI_FILES=$(run-parts --list --regex '.ini$' /etc/php/conf.d)

# Find out which directory to put the links into
PHP_CONF_D=$(php -i | awk -F ' => ' \
  '$1 == "Scan this dir for additional .ini files" { print $2 }')

# If there are any files, copy them into PHP's conf directory
if [ -n "$INI_FILES" ]; then
  ln -sfn $INI_FILES "${PHP_CONF_D}/"
fi

# Now hand over to supercronic running as www-data
exec chroot --userspec=www-data / \
  /usr/local/bin/supercronic /etc/crontab

# vim: ai ts=2 sw=2 et sts=2 ft=sh
