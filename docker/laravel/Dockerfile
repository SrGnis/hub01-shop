FROM php:8.2-apache

WORKDIR /var/www/laravel

# install packages
RUN apt-get update \
    && apt-get install --no-install-recommends -y \
    supervisor \
    icu-devtools \
    jq \
    libfreetype6-dev libicu-dev libjpeg62-turbo-dev libpng-dev libpq-dev \
    libsasl2-dev libssl-dev libwebp-dev libxpm-dev libzip-dev libzstd-dev \
    unzip \
    zlib1g-dev \
    nodejs npm

# install extensions
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && pecl install --configureoptions='enable-redis-igbinary="yes" enable-redis-lzf="yes" enable-redis-zstd="yes"' igbinary zstd redis \
    && pecl clear-cache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install gd intl pdo_mysql pdo_pgsql zip \
    && docker-php-ext-enable igbinary opcache redis zstd

# apt cleanup
RUN apt-get clean \
    && apt-get autoclean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# install composer
RUN curl -o /usr/local/bin/composer https://getcomposer.org/download/latest-stable/composer.phar \
    && chmod +x /usr/local/bin/composer

# copy files
COPY src /var/www/laravel
COPY docker/laravel/vite.config.js /var/www/laravel/vite.config.js

# install dependencies
RUN composer install --optimize-autoloader --no-interaction --no-dev

# install node modules
RUN npm install

# build assets
RUN npm run build

RUN chown -R www-data:www-data /var/www

# copy files
COPY docker/laravel/config/docker-laravel-entrypoint /usr/local/bin/docker-laravel-entrypoint
COPY docker/laravel/config/laravel.conf /etc/apache2/sites-available/laravel.conf
COPY docker/laravel/config/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/laravel/config/supervisor/ /etc/supervisor/conf.d/
COPY docker/laravel/config/php-overrides.ini /usr/local/etc/php/conf.d/php-overrides.ini

# configure apache
RUN a2enmod rewrite headers \
    && a2ensite laravel \
    && a2dissite 000-default \
    && chmod +x /usr/local/bin/docker-laravel-entrypoint

ENTRYPOINT ["docker-laravel-entrypoint"]
