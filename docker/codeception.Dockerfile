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

ENV WP_ROOT_FOLDER="/var/www/html"
ENV WORDPRESS_DB_HOST=${TEST_SITE_DB_HOST}
ENV WORDPRESS_DB_PORT=${TEST_SITE_DB_PORT}
ENV WORDPRESS_DB_USER=${TEST_SITE_DB_USER}
ENV WORDPRESS_DB_PASSWORD=${TEST_SITE_DB_PASSWORD}
ENV WORDPRESS_DB_NAME=${TEST_SITE_DB_NAME}
ENV PLUGINS_DIR="${WP_ROOT_FOLDER}/wp-content/plugins"
ENV PROJECT_DIR="${PLUGINS_DIR}/wp-graphql-testcase"

WORKDIR $PROJECT_DIR

# Set up Apache
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf
RUN a2enmod rewrite

ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /usr/local/bin/wait-for-it
RUN chmod 755 /usr/local/bin/wait-for-it

ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/wp
RUN chmod 755 /usr/local/bin/wp

# Remove exec statement from base entrypoint script.
RUN sed -i '$d' /usr/local/bin/docker-entrypoint.sh

# Set up entrypoint
COPY entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN  chmod 755 /usr/local/bin/app-entrypoint.sh
ENTRYPOINT ["app-entrypoint.sh"]
CMD ["apache2-foreground"]