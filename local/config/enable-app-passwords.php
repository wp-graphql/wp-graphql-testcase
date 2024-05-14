<?php
/**
 * Plugin Name: Enable App Passwords
 * Text Domain: enable-app-passwords
 */

add_filter( 'wp_is_application_passwords_available', '__return_true' );