FROM php:8.0.1-apache-buster
ENV TZ=America/Sao_Paulo
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

ARG DEBIAN_FRONTEND=noninteractive

RUN docker-php-ext-install pdo pdo_mysql bcmath opcache

RUN apt-get -y update --fix-missing --no-install-recommends
RUN apt-get -y upgrade

# Install useful tools
RUN apt-get -yq install apt-utils nano wget dialog

# Install important libraries
RUN apt-get -y install --fix-missing apt-utils build-essential git curl zip openssl libmemcached-dev zlib1g-dev

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install xdebug
RUN pecl install xdebug-3.1.2
RUN docker-php-ext-enable xdebug

# Other PHP7 Extensions
RUN docker-php-ext-install opcache

# Enable apache modules
RUN a2enmod rewrite headers expires ssl
COPY ./ssl/certs/default-selfsigned.crt /etc/ssl/certs/
COPY ./ssl/certs/default-selfsigned.key /etc/ssl/private/

# Install phpunit
RUN composer global require "phpunit/phpunit"
ENV PATH /root/.composer/vendor/bin:$PATH
RUN ln -s /root/.composer/vendor/bin/phpunit /usr/bin/phpunit

RUN pecl install timezonedb
RUN docker-php-ext-enable timezonedb

RUN apt-get install -y locales locales-all
RUN locale-gen pt_BR
RUN update-locale
