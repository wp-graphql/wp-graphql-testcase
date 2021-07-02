# Use build args to get the right wordpress + php image
ARG WP_VERSION
ARG PHP_VERSION

FROM wordpress:${WP_VERSION}-php${PHP_VERSION}-apache

ENV PHP_VERSION=${PHP_VERSION}
ENV WP_VERSION=${WP_VERSION}

RUN apt-get update; \
	apt-get install -y --no-install-recommends \
	# WP-CLI dependencies.
	bash less default-mysql-client git \
	# MailHog dependencies.
	msmtp;

COPY php.ini /usr/local/etc/php/php.ini

# Setup xdebug.
RUN	pecl install xdebug; \
	docker-php-ext-enable xdebug

# Install PDO MySQL driver.
RUN docker-php-ext-install \
    pdo_mysql

ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /usr/local/bin/wait_for_it
RUN chmod 755 /usr/local/bin/wait_for_it
