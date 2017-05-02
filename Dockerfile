# Thanks to Dan Pupius <dan@pupi.us> for this dockerfile, that I adapt for this project

FROM ubuntu:latest

# Install apache, PHP, and supplimentary programs. openssh-server, curl, and lynx-cur are for debugging the container. sudo and docker.io needed to comunicate with the docker daemon. Removed apt-get -y upgrade, according with the dockerfile documentation.
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    apache2 php7.0 php7.0-mysql libapache2-mod-php7.0 curl lynx-cur docker.io sudo
# Ignore the warning "debconf: delaying package configuration, since apt-utils is not installed". Is an issue of APT incorrectly requiring the (unnecessary) package, it isn't able to stop you from anything. 

# Enable apache mods.
RUN a2enmod php7.0
RUN a2enmod rewrite

# Update the PHP.ini file, enable <? ?> tags and quieten logging.
RUN sed -i "s/short_open_tag = Off/short_open_tag = On/" /etc/php/7.0/apache2/php.ini
RUN sed -i "s/error_reporting = .*$/error_reporting = E_ERROR | E_WARNING | E_PARSE/" /etc/php/7.0/apache2/php.ini

# Manually set up the apache environment variables
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

# Expose apache. This means the internal port should be 80. If you change this remember to change also the apache-config.conf
EXPOSE 80

# Copy the php script into the container.
#ADD www /var/www/site

# Update the default apache site with the config we created.
ADD apache-config.conf /etc/apache2/sites-enabled/000-default.conf

# Make possible exec sudo commands from web. This shold be secure because we are inside a container. Be sure that users CANNOT upload php scripts in THIS container!
RUN echo "www-data ALL=NOPASSWD: ALL" >> /etc/sudoers

# By default start up apache in the foreground, override with /bin/bash for interative.
# Launch docker run with "docker run builder 1>/dev/null &" to exec in background and don't recive messages from apache.
CMD /usr/sbin/apache2ctl -D FOREGROUND
