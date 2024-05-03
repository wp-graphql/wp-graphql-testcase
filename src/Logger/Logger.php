<?php
/**
 * Logger interface
 * 
 * Defines shared logic for Logger classes.
 * @since v3.0.0
 * @package Tests\WPGraphQL\Logger
 */

namespace Tests\WPGraphQL\Logger;

interface Logger {
    /**
	 * Console logging function.
	 *
	 * Use --debug flag to view in console.
	 */
    public function logData( $data );
}