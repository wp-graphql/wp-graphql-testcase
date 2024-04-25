<?php
/**
 * Config file used for the local development environment.
 *
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */

define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'wordpress' );
define( 'DB_PASSWORD', 'password' );
define( 'DB_HOST', 'mysql' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';

define( 'AUTH_KEY',         'value' );
define( 'SECURE_AUTH_KEY',  'value' );
define( 'LOGGED_IN_KEY',    'value' );
define( 'NONCE_KEY',        'value' );
define( 'AUTH_SALT',        'value' );
define( 'SECURE_AUTH_SALT', 'value' );
define( 'LOGGED_IN_SALT',   'value' );
define( 'NONCE_SALT',       'value' );

define( 'ABSPATH', __DIR__ . '/' );

require_once ABSPATH . 'wp-settings.php';