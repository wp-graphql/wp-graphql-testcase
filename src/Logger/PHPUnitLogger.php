<?php
/**
 * PHPUnitLogger
 * 
 * Console logging for PHPUnit tests.
 * @since TBD
 * @package Tests\WPGraphQL\Logger
 */
namespace Tests\WPGraphQL\Logger;

class PHPUnitLogger implements Logger {
    // Possible field anonymous values.
    public const NOT_NULL  = 'phpunit_field_value_not_null';
    public const IS_NULL   = 'phpunit_field_value_is_null';
    public const NOT_FALSY = 'phpunit_field_value_not_falsy';
    public const IS_FALSY  = 'phpunit_field_value_is_falsy';

    // Search operation enumerations.
    public const MESSAGE_EQUALS      = 500;
    public const MESSAGE_CONTAINS    = 600;
    public const MESSAGE_STARTS_WITH = 700;
    public const MESSAGE_ENDS_WITH   = 800;

    /**
     * {@inheritDoc}
     */
	public function logData( $data ) {
        if( ! in_array( '--debug' , $_SERVER['argv'], true ) && ! in_array( '--verbose', $_SERVER['argv'], true ) ) {
            return;
        }

		if ( is_array( $data ) || is_object( $data ) ) {
			fwrite( STDOUT, json_encode( $data, JSON_PRETTY_PRINT ) );
			return;
		}

		fwrite( STDOUT, $data );
	}
}