# lemp-centos
#
# This image prived a base for 

FROM centos:centos6

# Add the nginx and PHP dependent repository
ADD docker/nginx.repo /etc/yum.repos.d/nginx.repo

# Installing nginx
RUN yum install -y nginx

# Installing MySQL
RUN yum install -y mysql-server mysql-client

# Installing PHP
RUN yum -y --enablerepo=remi,remi-php56 install nginx php-fpm php-common

RUN yum -y --enablerepo=remi,remi-php56 install php-cli php-pear php-pdo php-mysqlnd php-pgsql php-gd php-mbstring php-mcrypt php-xml

# Installing supervisor
RUN yum install -y python-setuptools
RUN easy_install pip
RUN pip install supervisor

# ENv for setting Username and Passowrd for MySQL
ENV MYSQL_USER root
ENV MYSQL_PASS root

ADD docker/nginx.conf /etc/nginx/nginx.conf
ADD docker/default.conf /etc/nginx/conf.d/default.conf
ADD docker/my.cnf /etc/mysql/my.cnf

RUN rm -rf /var/lib/mysql/*

ADD docker/mysql_user.sh /mysql_user.sh
ADD run.sh /run.sh
RUN chmod 755 /*.sh

RUN /etc/init.d/mysqld start

ADD docker/supervisord.conf /etc/

VOLUME ["/etc/mysql", "/var/lib/mysql" ]

EXPOSE 80 3306

CMD ["/run.sh"]


   


