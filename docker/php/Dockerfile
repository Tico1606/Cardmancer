FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers expires

COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/cardmancer.ini

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /var/www/html/public/uploads/cards \
    && chown -R www-data:www-data /var/www/html/public/uploads

EXPOSE 80
