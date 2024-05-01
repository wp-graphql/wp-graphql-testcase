<?php
/**
 * WPGraphQL test case (for PHPUnit)
 *
 * For testing WPGraphQL responses.
 * @since 1.1.0
 * @package Tests\WPGraphQL\TestCase
 */
namespace Tests\WPGraphQL\TestCase;

use Tests\WPGraphQL\Logger\PHPUnitLogger;
abstract class WPGraphQLUnitTestCase extends \WP_UnitTestCase {

	use WPGraphQLTestCommon;

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
	 * Stores the logger instance.
	 *
	 * @var PHPUnitLogger
	 */
	protected $logger;

	/**
	 * {@inheritDoc}
	 */
	protected static function getLogger() {
		return new PHPUnitLogger();
	}
}
