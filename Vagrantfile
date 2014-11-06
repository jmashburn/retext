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

touch ${MARKER_FILE}
CONTENTS
