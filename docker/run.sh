#!/bin/bash
# Executing supervisord and mysql_user

/usr/local/bin/composer config -g github-oauth.github.com $GITHUB_AUTH_KEY

cd  /var/www/ && composer install

cp /var/www/config/database.ini.mysql /var/www/config/database.ini

# Configure App
sed -i 's/MYSQL_HOST/'$DATABASE_SERVICE_HOST'/g' /var/www/config/database.ini
sed -i 's/MYSQL_DATABASE/'$MYSQL_DATABASE'/g' /var/www/config/database.ini
sed -i 's/MYSQL_PORT/'$DATABASE_SERVICE_PORT'/g' /var/www/config/database.ini
sed -i 's/MYSQL_USERNAME/root/g' /var/www/config/database.ini
sed -i 's/MYSQL_PASSWORD/'$MYSQL_ROOT_PASSWORD'/g' /var/www/config/database.ini

#/mysql_user.sh
exec supervisord -n
