# lemp-centos
#
# This image prived a base for 

FROM jmashburn/lemp-centos

# ENv for setting Username and Passowrd for MySQL
ENV MYSQL_USER root
ENV MYSQL_PASS root

ADD docker/nginx.conf /etc/nginx/nginx.conf
ADD docker/default.conf /etc/nginx/conf.d/default.conf
ADD docker/my.cnf /etc/mysql/my.cnf

RUN rm -rf /var/lib/mysql/*

ADD docker/mysql_user.sh /mysql_user.sh
ADD docker/run.sh /run.sh
RUN chmod 755 /*.sh

RUN git clone https://github.com/jmashburn/retext.git /var/www/html/

RUN /etc/init.d/mysqld start

ADD docker/supervisord.conf /etc/

#VOLUME ["/etc/mysql", "/var/lib/mysql" ]

EXPOSE 80 3306

CMD ["/run.sh"]
