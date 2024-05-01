<?php
/**
 * WPGraphQL test case (For Codeception)
 *
 * For testing WPGraphQL responses.
 * @since 1.0.0
 * @package Tests\WPGraphQL\TestCase
 */

namespace Tests\WPGraphQL\TestCase;

use Tests\WPGraphQL\Logger\CodeceptLogger;

/**
 * WPGraphQLTestCase class.
 */
class WPGraphQLTestCase extends \Codeception\TestCase\WPTestCase {

	use WPGraphQLTestCommon;

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
	 * Stores the logger instance.
	 *
	 * @var CodeceptLogger
	 */
	protected $logger;

	/**
	 * {@inheritDoc}
	 */
	protected static function getLogger() {
		return new CodeceptLogger();
	}
}
