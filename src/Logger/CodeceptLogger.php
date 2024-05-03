<?php
/**
 * CodeceptLogger
 * 
 * Console logging for Codeception tests.
 * @since v3.0.0
 * @package Tests\WPGraphQL\Logger
 */
namespace Tests\WPGraphQL\Logger;

class CodeceptLogger implements Logger {

    // Possible field anonymous values.
	public const NOT_NULL  = 'codecept_field_value_not_null';
	public const IS_NULL   = 'codecept_field_value_is_null';
	public const NOT_FALSY = 'codecept_field_value_not_falsy';
	public const IS_FALSY  = 'codecept_field_value_is_falsy';

	// Search operation enumerations.
	public const MESSAGE_EQUALS      = 100;
	public const MESSAGE_CONTAINS    = 200;
	public const MESSAGE_STARTS_WITH = 300;
	public const MESSAGE_ENDS_WITH   = 400;

    /**
     * {@inheritDoc}
     */
	public function logData( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			\codecept_debug( json_encode( $data, JSON_PRETTY_PRINT ) );
			return;
		}

		\codecept_debug( $data );
	}
}