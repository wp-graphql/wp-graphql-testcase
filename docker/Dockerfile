ARG PHP_VERSION=8.1

FROM wordpress:php${PHP_VERSION}-apache

# See: https://xdebug.org/docs/compat to match the Xdebug version with the PHP version.
ARG XDEBUG_VERSION=3.3.1

RUN apt-get update; \
	apt-get install -y --no-install-recommends \
	# WP-CLI dependencies.
	bash less default-mysql-client git \
	# MailHog dependencies.
	msmtp;

COPY php.ini /usr/local/etc/php/php.ini

RUN	pecl install "xdebug-${XDEBUG_VERSION}"; \
	docker-php-ext-enable xdebug

ENV XDEBUG_MODE=coverage

# Install PDO MySQL driver.
RUN docker-php-ext-install \
	pdo_mysql

ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /usr/local/bin/wait_for_it
RUN chmod 755 /usr/local/bin/wait_for_it