@ECHO OFF
wp plugin deactivate --all && wp site empty --yes && wp plugin activate empress && wp db export tests/_data/dump.sql