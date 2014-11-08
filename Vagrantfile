# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "jaredmash/centos-6.5-lemp"

  config.vm.network "forwarded_port", guest: 8080, host: 8180, autocorrect: true
  config.vm.network "private_network", ip: "192.168.33.10"

  config.vm.synced_folder ".", "/var/www/retext_dev", mount_options: ['dmode=777', 'fmode=777']

  config.vm.provider "virtualbox" do |vb|
    # Don't boot with headless mode
    vb.gui = false
  end

  config.vm.provision "shell", inline: $shell
end

$shell = <<-CONTENTS
MARKER_FILE="/usr/local/etc/vagrant_provision_marker"

if [ -f "${MARKER_FILE}" ]; then
	exit 0
fi

# Install PHPUnit
yum install php-phpunit-PHPUnit -y

# Install Mysql 5.6
sudo yum install mysql-server -y

sudo /sbin/service mysqld start
sudo /sbin/chkconfig mysqld on

PASS=`awk 'NF>1{print $NF}' .mysql_secret`
mysql --user="root" --password="$PASS" --execute="SET PASSWORD=password('root')";
mysql --user="root" --password="root" --execute="create user 'retext'@'localhost' identified by 'retext';"
mysql --user="root" --password="root" --execute="create database retext_dev CHARACTER SET 'utf8' COLLATE 'utf8_bin';"
mysql --user="root" --password="root" --execute="grant all on retext_dev.* to 'retext'@'localhost';"
mysql --user="root" --password="root" --execute="create database retext_test CHARACTER SET 'utf8' COLLATE 'utf8_bin';"
mysql --user="root" --password="root" --execute="grant all on retext_test.* to 'retext'@'localhost';"
mysql --user="root" --password="root" --execute="grant all privileges on *.* to 'retext'@'%' identified by 'retext';"
mysql --user="root" --password="root" --execute="flush privileges;"

# Install pdo-mysql
yum install php-mysql -y

# Restart PHP
sudo /sbin/service php-fpm restart


echo '
server {
  listen PORT;
  server_name www.DOMAIN DOMAIN;

  root ROOT;

  access_log /var/log/nginx/DOMAIN.access.log;

  index index.php index.html index.htm;

  location / {
        try_files $uri $uri/ /index.php?$args;
  }

  error_page 404 /404.html;
  error_page 500 502 503 504 /50x.html;
  location = /50x.html {
        root /usr/share/nginx/html;
  }

  # serve static files directly
  location ~* \.(jpg|jpeg|gif|css|png|js|woff|ttf)$ {
        root ROOT/public;
        access_log off;
        expires max;
  }

  location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
  }

  location ~ /\.ht {
        deny  all;
  }
}

' > /usr/local/nginx/virtual_host.template

# Create Site
/usr/local/bin/create_nginx_site.sh retext.dev 8080

# Set the database to MySQL
sudo cp /vagrant/config/database.ini.mysql /vagrant/config/database.ini

# Set the phpunit tests database file to mysql
sudo cp /vagrant/tests/config/database.ini.mysql /vagrant/tests/config/database.ini

cd /vagrant 
phpunit

touch ${MARKER_FILE}
CONTENTS
