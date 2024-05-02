<?php
/**
 * WPGraphQL test utility functions/assertions.
 *
 * @since 1.1.0
 * @package Tests\WPGraphQL\TestCase
 */

namespace Tests\WPGraphQL\TestCase;

use Tests\WPGraphQL\Logger\Logger;
use Tests\WPGraphQL\Constraint\QueryConstraint;
use Tests\WPGraphQL\Constraint\QueryErrorConstraint;
use Tests\WPGraphQL\Constraint\QuerySuccessfulConstraint;
use Tests\WPGraphQL\Utils\Utils;

/**
 * trait WPGraphQLTestCommon
 */
trait WPGraphQLTestCommon {
	/**
	 * Console logging function.
	 *
	 * Use --debug flag to view in console.
	 */
	public static function logData( $data ) {
		static::getLogger()->logData( $data );
	}

	/**
	 * Wrapper for the "graphql()" function.
	 *
	 * @return array
	 */
	public function graphql() {
		$results = graphql( ...func_get_args() );

		// use --debug flag to view.
		static::getLogger()->logData( $results );

		return $results;
	}

	/**
	 * Wrapper for the "\WGraphQL::clear_schema()" function.
	 *
	 * @return array
	 */
	public function clearSchema() {
		// Clear schema
		\WPGraphQL::clear_schema();
	}

	/**
	 * A simple helper for clearing a loaders cache. The is good for when
	 * running a query multiple times and wish to ensure that the value returned
	 * isn't a cached value.
	 *
	 * @param string $loader_name  Loader slug name.
	 *
	 * @return void
	 */
	public function clearLoaderCache( $loader_name ) {
		$loader = \WPGraphQL::get_app_context()->get_loader( $loader_name );
		$loader->clear_all();
	}

	/**
	 * Wrapper for the "GraphQLRelay\Relay::toGlobalId()" function.
	 *
	 * @return string
	 */
	public function toRelayId() {
		return \GraphQLRelay\Relay::toGlobalId( ...func_get_args() );
	}

	/**
	 * Returns an expected "Field" type data object.
	 *
	 * @param string $path            Path to the data being tested.
	 * @param mixed  $expected_value  Expected value of the object being evaluted.
	 * @return array
	 */
	public function expectedField( string $path, $expected_value ) {
		$type = $this->get_not() . 'FIELD';
		return compact( 'type', 'path', 'expected_value' );
	}

	/**
	 * Returns an expected "Object" type data object.
	 *
	 * @param string $path            Path to the data being tested.
	 * @param array  $expected_value  Expected value of the object being evaluted.
	 * @return array
	 */
	public function expectedObject( string $path, array $expected_value ) {
		$type = $this->get_not() . 'OBJECT';
		return compact( 'type', 'path', 'expected_value' );
	}

	/**
	 * Returns an expected "Node" type data object.
	 *
	 * @param string       $path            Path to the data being tested.
	 * @param array        $expected_value  Expected value of the node being evaluted.
	 * @param integer|null $expected_index  Expected index of the node being evaluted.
	 * @return array
	 */
	public function expectedNode( string $path, array $expected_value, $expected_index = null ) {
		$type = $this->get_not() . 'NODE';
		return compact( 'type', 'path', 'expected_value', 'expected_index' );
	}

	/**
	 * Returns an expected "Edge" type data object.
	 *
	 * @param string       $path            Path to the data being tested.
	 * @param array        $expected_value  Expected value of the edge being evaluted.
	 * @param integer|null $expected_index  Expected index of the edge being evaluted.
	 * @return array
	 */
	public function expectedEdge( string $path, array $expected_value, $expected_index = null ) {
		$type = $this->get_not() . 'EDGE';
		return compact( 'type', 'path', 'expected_value', 'expected_index' );
	}

	/**
	 * Triggers the "not" flag for the next expect*() call.
	 *
	 * @return WPGraphQLTestCommon
	 */
	public function not() {
		$this->not = '!';
		return $this;
	}

	/**
	 * Clears the "not" flag and return the proper prefix.
	 *
	 * @return string
	 */
	private function get_not() {
		if ( ! isset( $this->not ) ) {
			return '';
		}

		$prefix = $this->not;
		unset( $this->not );
		return $prefix;
	}

	/**
	 * Returns an expected "location" error data object.
	 *
	 * @param string $path Path to the data being tested.
	 * @return array
	 */
	public function expectedErrorPath( string $path ) {
		$type = 'ERROR_PATH';
		return compact( 'type', 'path' );
	}

	/**
	 * Returns an expected "Edge" type data object.
	 *
	 * @param string   $path            Path to the data being tested.
	 * @param int|null $search_type  Expected index of the edge being evaluted.
	 * @return array
	 */
	public function expectedErrorMessage( string $needle, int $search_type = self::MESSAGE_EQUALS ) {
		$type = 'ERROR_MESSAGE';
		return compact( 'type', 'needle', 'search_type' );
	}

	/**
	 * Reports an error identified by $message if $response is not a valid GraphQL Response.
	 *
	 * @param array  $response  GraphQL query response object.
	 * @param string $message   Error message.
	 * @return void
	 */
	public static function assertResponseIsValid( $response, $message = '' ) {
		static::assertThat(
			$response,
			new QueryConstraint( static::getLogger() ),
			$message
		);
	}

	/**
	 * Reports an error identified by $message if $response does not contain all data
	 * and specifications defined in the $expected array.
	 *
	 * @param array  $response  GraphQL query response.
	 * @param array  $expected  List of expected data objects.
	 * @param string $message   Error message.
	 */
	public static function assertQuerySuccessful( array $response, array $expected = [], $message = '' ) {
		static::assertThat(
			$response,
			new QuerySuccessfulConstraint( static::getLogger(), $expected ),
			$message
		);
	}

	/**
	 * Reports an error identified by $message if $response does not contain the error
	 * specifications defined in the $expected array.
	 *
	 * @param array  $response  GraphQL query response.
	 * @param array  $expected  Expected error data.
	 * @param string $message   Error message.
	 * @return void
	 */
	public static function assertQueryError( array $response, array $expected = [], $message = '' ) {
		static::assertThat(
			$response,
			new QueryErrorConstraint( static::getLogger(), $expected ),
			$message
		);
	}

	/**
	 * The value returned for undefined resolved values.
	 *
	 * Clone of the "get" function from the Lodash JS libra
	 *
	 * @param array  $object   The object to query.
	 * @param string $path     The path of the property to get.
	 * @param mixed  $default  The value returned for undefined resolved values.
	 *
	 * @return mixed
	 */
	public static function lodashGet( array $data, string $string, $default = null ) {
		return Utils::lodashGet( $data, $string, $default );
	}

	/**
	 * Returns the logger instance
	 * 
	 * @return Logger
	 */
	abstract protected static function getLogger();
}
