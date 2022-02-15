FROM php:7.4-zts-alpine3.13

# Create dawapack user and group
RUN addgroup --gid 1000 dawapack
RUN adduser -s /bin/bash -u 1000 -G dawapack -s /sbin/nologin -D dawapack

# Container timezone will be UTC
RUN echo "UTC" > /etc/timezone
# Add requirements
RUN apk add --no-cache bash bash-doc bash-completion zip unzip curl openssh supervisor htop

# Add personals
RUN apk add --no-cache vim

# Add XDebug
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-3.0.0

# Add composer
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php \
    && php composer-setup.php --install-dir=/usr/bin --filename=composer \
    && rm -rf composer-setup.php

# Prepare system for mandatories php extentions
RUN apk --update add gcc make g++ zlib-dev
RUN apk add --no-cache php7-dev
RUN docker-php-ext-install pcntl \
    && docker-php-ext-install sockets

# Install pnctl, sockets & parallel extensions via pecl
RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
    && pecl install parallel \
    && pecl clear-cache \
    && apk del .build-dependencies

# Remove auto generated extension ini files
RUN rm -rf /usr/local/etc/php/conf.d/docker-php-ext-*

# Remove auto generated supervisord.conf
RUN rm -f /etc/supervisord.conf

# Copy mandatory sh scripts and conf files
COPY container/php/ /usr/local/etc/php/
COPY container/usr/ /usr/
COPY container/home/ /home/

# Set rights & execute flag
RUN chmod 644 /home/dawapack/.bashrc
RUN chmod 755 /usr/bin/entrypoint.sh
RUN chmod +x /usr/bin/wait-for-it

# Set the work directory
WORKDIR /var/package

## switch to user dawapack
USER 1000:1000

ENTRYPOINT ["/usr/bin/wait-for-it", "rabbitmq:5672", "--", "entrypoint.sh"]
