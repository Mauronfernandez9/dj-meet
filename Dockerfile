FROM php:8.2-apache-bookworm

RUN docker-php-ext-install mysqli
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

COPY deploy/php.ini /usr/local/etc/php/conf.d/dj-meet.ini
COPY api/ /var/www/html/api/
COPY css/ /var/www/html/css/
COPY img/ /var/www/html/img/
COPY js/ /var/www/html/js/
COPY index.html sala.html /var/www/html/

EXPOSE 80
