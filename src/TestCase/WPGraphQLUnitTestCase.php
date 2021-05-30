<?php
/**
 * WPGraphQL test case (for PHPUnit)
 *
 * For testing WPGraphQL responses.
 * @since 1.1.0
 * @package Tests\WPGraphQL\TestCase
 */
namespace Tests\WPGraphQL\TestCase;

abstract class WPGraphQLUnitTestCase extends \WP_UnitTestCase {

	use WPGraphQLTestCommon;

	// Search operation enumerations.
	const MESSAGE_EQUALS      = 500;
	const MESSAGE_CONTAINS    = 600;
	const MESSAGE_STARTS_WITH = 700;
	const MESSAGE_ENDS_WITH   = 800;

	/**
	 * Console logging function.
	 *
	 * Use --debug flag to view in console.
	 */
	protected function logData( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			fwrite( STDOUT, json_encode( $data, JSON_PRETTY_PRINT ) );
			return;
		}

		fwrite( STDOUT, $data );
	}
}