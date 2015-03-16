#!/bin/bash
# Executing supervisord and mysql_user

/usr/local/bin/composer config -g github-oauth.github.com $GITHUB_AUTH_KEY

cd  /var/www/ && composer install

#/mysql_user.sh
exec supervisord -n
