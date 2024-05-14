#!/bin/bash


work_dir=$(pwd)

cd "${WP_ROOT_FOLDER}" || exit

# Run WordPress docker entrypoint.
# shellcheck disable=SC1091
. docker-entrypoint.sh 'apache2'

set +u

# Ensure mysql is loaded
wait-for-it -s -t 300 "${TEST_SITE_DB_HOST}:${TEST_SITE_DB_PORT:-3306}" -- echo "Application database is operationally..."

if [ -f "${WP_ROOT_FOLDER}/wp-config.php" ]; then
	echo "Deleting old wp-config.php"
	rm -rf "${WP_ROOT_FOLDER}/wp-config.php"
fi

echo "Creating wp-config.php..."
wp config create \
	--path="${WP_ROOT_FOLDER}" \
	--dbname="${TEST_SITE_DB_NAME}" \
	--dbuser="${TEST_SITE_DB_USER}" \
	--dbpass="${TEST_SITE_DB_PASSWORD}" \
	--dbhost="${TEST_SITE_DB_HOST}" \
	--dbprefix="${TEST_TABLE_PREFIX}" \
	--skip-check \
	--quiet \
	--allow-root

# Install WP if not yet installed
echo "Installing WordPress..."
wp core install \
    --path="${WP_ROOT_FOLDER}" \
    --url="${TEST_SITE_WP_URL}" \
    --title='Test' \
    --admin_user="${TEST_SITE_ADMIN_USERNAME}" \
    --admin_password="${TEST_SITE_ADMIN_PASSWORD}" \
    --admin_email="${TEST_SITE_ADMIN_EMAIL}" \
    --allow-root

wp plugin activate wp-graphql --allow-root

if [ -f "${PROJECT_DIR}/tests/codeception/_data/dump.sql" ]; then
	rm -rf "${PROJECT_DIR}/tests/codeception/_data/dump.sql"
fi

wp user application-password delete 1 --all --allow-root

app_user="admin"
app_password=$(wp user application-password create 1 testing --porcelain --allow-root)

echo "Creating .env.docker file..."
echo TEST_SITE_ADMIN_APP_PASSWORD="$(echo -n "${app_user}:${app_password}" | base64)" > "$PROJECT_DIR/.env.docker"
echo TEST_SITE_WP_DOMAIN="${TEST_SITE_WP_DOMAIN}" >> "$PROJECT_DIR/.env.docker"
echo TEST_SITE_WP_URL="${TEST_SITE_WP_URL}" >> "$PROJECT_DIR/.env.docker"

echo "Dumping app database..."
wp db export "${PROJECT_DIR}/tests/codeception/_data/dump.sql" \
	--dbuser="${TEST_SITE_DB_USER}" \
	--dbpass="${TEST_SITE_DB_PASSWORD}" \
	--skip-plugins \
	--skip-themes \
	--allow-root

wp config set WP_SITEURL "${TEST_SITE_WP_URL}" --allow-root
wp config set WP_HOME "${TEST_SITE_WP_URL}" --allow-root

echo "Setting pretty permalinks..."
wp rewrite structure '/%year%/%monthnum%/%postname%/' --allow-root

service apache2 start

echo "Running WordPress version: $(wp core version --allow-root) at $(wp option get home --allow-root)"

cd "${work_dir}" || exit

exec "$@"