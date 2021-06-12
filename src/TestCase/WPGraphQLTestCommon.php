<?php
/**
 * WPGraphQL test utility functions/assertions.
 *
 * @since 1.1.0
 * @package Tests\WPGraphQL\TestCase
 */

namespace Tests\WPGraphQL\TestCase;

use PHPUnit\Framework\Constraint\IsTrue;

/**
 * Traits
 */
trait WPGraphQLTestCommon {

	/**
	 * Console logging function.
	 *
	 * Use --debug flag to view in console.
	 */
	abstract public static function logData( $data );

	/**
	 * Wrapper for the "graphql()" function.
	 *
	 * @return array
	 */
	public function graphql() {
		$results = graphql( ...func_get_args() );

		// use --debug flag to view.
		static::logData( $results );

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
		$loader = \WPGraphQL::get_app_context()->getLoader( $loader_name );
		$loader->clearAll();
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
	 * Returns an expected "Object" type data object.
	 *
	 * @param string     $path            Path to the data being tested.
	 * @param mixed|null $expected_value  Expected value of the object being evaluted.
	 * @return array
	 */
	public function expectedObject( string $path, $expected_value ) {
		$type = $this->get_not() . 'FIELD';
		return compact( 'type', 'path', 'expected_value' );
	}

	/**
	 * Returns an expected "Node" type data object.
	 *
	 * @param string       $path            Path to the data being tested.
	 * @param mixed|null   $expected_value  Expected value of the node being evaluted.
	 * @param integer|null $expected_index  Expected index of the node being evaluted.
	 * @return array
	 */
	public function expectedNode( string $path, $expected_value = null, $expected_index = null ) {
		$type = $this->get_not() . 'NODE';
		return compact( 'type', 'path', 'expected_value', 'expected_index' );
	}

	/**
	 * Returns an expected "Edge" type data object.
	 *
	 * @param string       $path            Path to the data being tested.
	 * @param mixed|null   $expected_value  Expected value of the edge being evaluted.
	 * @param integer|null $expected_index  Expected index of the edge being evaluted.
	 * @return array
	 */
	public function expectedEdge( string $path, $expected_value = null, $expected_index = null ) {
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
		$prefix = $this->not ? '!' : '';
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
	 * Returns default message if no message provided.
	 *
	 * @param string $message  Message.
	 * @param string $default  Default message.
	 * @return void
	 */
	protected static function message( $message, $default ) {
		return ! empty( $message ) ? $message : $default;
	}

	/**
	 * Reports an error identified by $message if $response is not a valid GraphQL response object.
	 *
	 * @param array  $response  GraphQL query response object.
	 * @param string $message   Error message. 
	 */
	public static function assertIsValidQueryResponse( $response, $message = '' ) {
		// Validate format.
		static::assertThat(
			is_array( $response ),
			static::isTrue(),
			! empty( $message )
				? $message
				: 'The GraphQL query response must be provided as an associative array.'
		);

		// Assert that response not empty.
		static::assertThat(
			! empty( $response ),
			static::isTrue(),
			! empty( $message )
				? $message
				: 'GraphQL query response is empty.'
		);

		// Validate content.
		static::assertThat(
			in_array( 'data', array_keys( $response ), true ) || in_array( 'errors', array_keys( $response ), true ),
			static::isTrue(),
			! empty( $message )
				? $message
				: 'A valid GraphQL query response must contain a "data" or "errors" object.'
		);
	}

	/**
	 * Reports an error identified by $message if $response does not contain data defined
	 * in $expected_data.
	 *
	 * @param array  $response       GraphQL query response object
	 * @param array  $expected_data  Expected data object to be evaluated.
	 * @param string $message        Error message.
	 */
	public static function assertExpectedDataFound( array $response, array $expected_data, string $current_path = null, $message = '' ) {
		// Throw if "$expected_data" invalid.
		if ( empty( $expected_data['type'] ) ) {
			static::logData( array( 'INVALID_DATA_OBJECT' => $expected_data ) );
			throw new \Exception( 'Invalid data object provided for evaluation.' );
		}

		// Deconstruct $expected_data.
		extract( $expected_data );

		// Get flags.
		$check_order = isset( $expected_index ) && ! is_null( $expected_index );

		// Set current path in response.
		if ( empty( $current_path ) ) {
			$path = "data.{$path}";
		} else {
			$path = "{$current_path}.{$path}";
		}

		// Add index to path if provided.
		$full_path   = $check_order ? "{$path}.{$expected_index}" : "{$path}";

		// Get data at path for evaluation.
		$actual_data = static::getPossibleDataAtPath( $response, $full_path, $is_group );

		// Only check data existence, if no "$expected_value" provided.
		if ( is_null( $expected_value ) ) {
			static::assertThat(
				! is_null( $actual_data ),
				static::isTrue(),
				static::message( $message, sprintf( 'No data found at path "%s"', $full_path ) )
			);
			if ( is_array( $actual_data ) ) {
				static::assertThat(
					! empty( $actual_data ),
					static::isTrue(),
					static::message( $message, sprintf( 'Data object found at path "%s" empty.', $full_path ) )
				);
			}
			return;
		}

		// Replace string 'null' value with real NULL value for data evaluation.
		if ( is_string( $expected_value ) && 'null' === strtolower( $expected_value ) ) {
			$expected_value = null;
		}

		$assert_same = ! static::startsWith( '!', $type );

		// Evaluate expected data.
		switch( true ) {
			case static::endsWith( 'FIELD', $type ):
				$matcher = 'doesFieldMatch';
				if ( $is_group ) {
					$matcher = 'doesFieldMatchGroup';
				}
				static::{$matcher}(
					$actual_data,
					$expected_value,
					$assert_same,
					true,
					static::message(
						$message,
						sprintf(
							'Data found at path "%1$s" %2$s the provided value',
							$path,
							$assert_same ? 'doesn\'t match' : 'shouldn\'t match'
						)
					)
				);
				break;
			case static::endsWith( 'NODE', $type ):
			case static::endsWith( 'EDGE', $type ):
				// Handle nested rules recursively.
				if ( is_array( $expected_value ) && static::isNested( $expected_value ) ) {
					foreach ( $expected_value as $recursive_assertion ) {
						$next_path  = ! $check_order ? "{$full_path}.#" : $full_path;
						$next_path .= static::endsWith( 'EDGE', $type ) ? '.node' : '';
						static::assertExpectedDataFound( $response, $recursive_assertion, $next_path, $message );
					}
					return;
				}

				if ( $check_order ) {
					static::doesFieldMatch(
						$actual_data,
						$expected_value,
						$assert_same,
						true,
						static::message(
							$message,
							sprintf(
								'Data found at path "%1$s" %2$s the provided value',
								$full_path,
								$assert_same ? 'doesn\'t match' : 'shouldn\'t match'
							)
						)
					);
					break;
				}

				static::doesFieldMatchGroup(
					$actual_data,
					$expected_value,
					$assert_same,
					true,
					static::message(
						$message,
						sprintf(
							'%1$s found in %2$s list at path "%3$s"',
							$assert_same ? 'Undesired data ' : 'Expected data not ',
							strtolower( $type ),
							$full_path
						)
					)
				);				
				break;
			default:
				static::logData( array( 'INVALID_DATA_OBJECT', $expected_data ) );
				throw new \Exception( 'Invalid data object provided for evaluation.' );
		}
	}

	/**
	 * Checks if the provided is a expected data rule object.
	 *
	 * @param array $expected_data
	 * @return boolean
	 */
	public static function isNested( array $expected_data ) {
		$rule_keys = array( 'type', 'path', 'expected_value' );
		return ! empty( $expected_data[0] )
			&& 3 === count( array_intersect( array_keys( $expected_data[0] ), $rule_keys ) );
	}

	/**
	 * Asserts if $expected_value matches $data.
	 * 
	 * @param array  $data            Data object be evaluted.
	 * @param mixed  $expected_value  Value $data is expected to evalute to.
	 * @param bool   $same            Whether $expected_value and $data should be equal or different.
	 * @param boot   $fatal           Stop on failure.
	 * @param string $message         Error message to be display if assertion fails.
	 */
	public static function doesFieldMatch( $data, $expected_value, $same, $fatal = true, $message = '' ) {
		// Get data/value type and log assertion.
		$log_type   = is_array( $data ) ? 'ACTUAL_DATA_OBJECT' : 'ACTUAL_DATA';
		$value_type = $same ? 'DESIRED_VALUE': 'UNDESIRED_VALUE';
		static::logData(
			array(
				$value_type => $expected_value,
				$log_type   => $data,
			)
		);

		$condition = $same
			? $data === $expected_value
			: $data !== $expected_value;
		if ( empty( $fatal ) ) {
			return $condition;
		} else {
			static::assertThat( $condition, static::isTrue(), $message );
		}
	}

	/**
	 * Asserts if $expected_value matches one of the entries in $data.
	 * 
	 * @param array  $data            Data object be evaluted.
	 * @param mixed  $expected_value  Value $data is expected to evalute to.
	 * @param bool   $same            Whether $expected_value and $data should be equal or different.
	 * @param boot   $fatal           Stop on failure.
	 * @param string $message         Error message to be display if assertion fails.
	 */
	public static function doesFieldMatchGroup( $data, $expected_value, $same, $fatal = true, $message = '' ) {
		$item_type  = $same ? 'WANTED VALUE' : 'UNWANTED VALUE';

		// Log data objects before the coming assertion.
		$assertion_log = array(
			$item_type           => $expected_value,
			'VALUES_AT_LOCATION' => $data,
		);
		static::logData( $assertion_log );

		foreach ( $data as $item ) {
			// If match found, Assert true.
			$field_passes = static::doesFieldMatch(
				$item,
				$expected_value,
				$same,
				false,
				$message
			);

			if ( $field_passes && $same ) {
				static::assertThat( true, static::isTrue(), $message );
				return;
			} elseif ( ! $field_passes && ! $same ) {
				static::assertThat( false, static::isTrue(), $message );						
			}
		}

		if ( $same ) {
			static::assertThat( false, static::isTrue(), $message );
		}

		static::assertThat( true, static::isTrue(), $message );
	}

	/**
	 * Reports an error identified by $message if $response does not contain error defined
	 * in $expected_data.
	 *
	 * @param array  $response       GraphQL query response object
	 * @param array  $expected_data  Expected data object to be evaluated.
	 * @param string $message        Error message.
	 */
	public static function assertExpectedErrorFound( array $response, array $expected_data, $message = '' ) {
		// Deconstruct $expected_data.
		extract( $expected_data );

		switch( $type ) {
			case 'ERROR_PATH':
				$target_path = array_map(
					function( $v ) {
						return is_numeric( $v ) ? absint( $v ) : $v;
					},
					explode( '.', $path )
				);
				foreach ( $response['errors'] as $error ) {
					if ( empty( $error['path'] ) ) {
						continue;
					}

					// If match found, Assert true.
					if ( $target_path === $error['path'] ) {
						static::assertThat( true, static::isTrue(), $message );
						break 2;
					}
				}
				static::assertThat(
					false,
					static::isTrue(),
					static::message(
						$message,
						sprintf( 'No errors found that occured at path "%1$s"', $path )
					)
				);
				break;
			case 'ERROR_MESSAGE':
				foreach ( $response['errors'] as $error ) {
					if ( empty( $error['message'] ) ) {
						continue;
					}

					// If match found, Assert true.
					if ( static::findSubstring( $needle, $error['message'], $search_type ) ) {
						static::assertThat( true, static::isTrue(), $message );
						break 2;
					}
				}

				$search_type_messages = array(
					self::MESSAGE_EQUALS      => 'equals',
					self::MESSAGE_CONTAINS    => 'contains',
					self::MESSAGE_STARTS_WITH => 'starts with',
					self::MESSAGE_ENDS_WITH   => 'ends with',
				);
				static::assertThat(
					false,
					static::isTrue(),
					static::message(
						$message,
						sprintf(
							'No errors found with a message that %1$s "%2$s"',
							$search_type_messages[ $search_type ],
							$needle
						)
					)
				);
				break;
			default:
				throw new \Exception( 'Invalid data object provided for evaluation.' );
		}
	}

	/**
	 * Reports an error identified by $message if $response does not contain all data
	 * and specifications defined in the $expected array.
	 *
	 * @param array  $response  GraphQL query response.
	 * @param array  $expected  List of expected data objects.
	 * @param string $message   Error message.
	 */
	public static function assertQuerySuccessful( array $response, array $expected, $message = '' ) {
		static::assertIsValidQueryResponse( $response, $message );
		static::assertThat(
			! in_array( 'errors', array_keys( $response ) ),
			static::isTrue(),
			! empty( $message )
				? $message
				: 'An error was thrown during the previous GraphQL requested. May need to use "--debug" flag to see contents of previous request.'
		);

		foreach( $expected as $expected_data ) {
			static::assertExpectedDataFound( $response, $expected_data, '', $message );
		}
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
	public function assertQueryError( array $response, array $expected, $message = '' ) {
		static::assertIsValidQueryResponse( $response, $message );
		
		// Confirm query throw errors found.
		static::assertThat(
			in_array( 'errors', array_keys( $response ) ),
			static::isTrue(),
			! empty( $message )
				? $message
				: 'No errors was thrown during the previous GraphQL requested. May need to use "--debug" flag to see contents of previous request.'
		);
		
		// Process expected data.
		foreach( $expected as $expected_data ) {
			// Throw if "$expected_data" invalid.
			if ( empty( $expected_data['type'] ) ) {
				static::logData( array( 'INVALID_DATA_OBJECT' => $expected_data ) );
				throw new \Exception( 'Invalid data object provided for evaluation.' );
			} elseif ( static::startsWith( 'ERROR_', $expected_data['type'] ) ) {
				static::assertExpectedErrorFound( $response, $expected_data, $message );
			} else {
				static::assertExpectedDataFound( $response, $expected_data, '', $message );
			}
		}
	}

	/**
	 * The value returned for undefined resolved values.
	 *
	 * Clone of the "get" function from the Lodash JS libra
	 *
	 * @param array  $object   The object to query.
	 * @param string $path     The path of the property to get.
	 * @param mixed  $default  The value returned for undefined resolved values.
	 * @return void
	 */
	protected static function lodashGet( array $data, string $string, $default = null ) {
		$arrStr = explode( '.', $string );
		if ( ! is_array( $arrStr ) ) {
			$arrStr = [ $arrStr ];
		}

		$result = $data;
		foreach ( $arrStr as $lvl ) {
			if ( ! is_null( $lvl ) && isset( $result[ $lvl ] ) ) {
				$result = $result[ $lvl ];
			} else {
				$result = $default;
			}
		}

		return $result;
	}

	/**
	 * Returns array of possible values for paths where "#" is being used instead of numeric index
	 * in $path.
	 *
	 * @param array $data        Data to be search
	 * @param string $path       Formatted lodash path.
	 * @param boolean $is_group  Function passback.
	 * 
	 * @return mixed
	 */
	protected static function getPossibleDataAtPath( array $data, string $path, &$is_group = false ) {
		$branches = explode( '.#', $path );

		if ( 1 < count( $branches ) ) {
			$is_group      = true;
			$possible_data = self::lodashGet( $data, $branches[0] );
			
			// Loop throw top branches and build out the possible data options.
			if ( ! empty( $possible_data ) && is_array( $possible_data ) ) {
				foreach ( $possible_data as &$next_data ) {
					if ( 2 === count( $branches ) ) {
						$next_data = self::lodashGet( $next_data, ltrim( $branches[1], '.' ) );
					} else {
						$next_data = self::getPossibleDataAtPath(
							$next_data,
							ltrim( implode( '.#', array_slice( $branches, 1 ) ), '.' ),
							$is_group
						);
					}
				}
			}

			return $possible_data;
		}

		return self::lodashGet( $data, $path, null );
	}

	/**
	 * Processes substring searches
	 *
	 * @param string $needle       String being searched for.
	 * @param string $haystack     String being searched.
	 * @param int    $search_type  Search operation enumeration.
	 * 
	 * @return boolean
	 */
	protected static function findSubstring( $needle, $haystack, $search_type ) {
		switch( $search_type ) {
			case self::MESSAGE_EQUALS:
				return $needle === $haystack;
			case self::MESSAGE_CONTAINS:
				return false !== strpos( $haystack, $needle );
			case self::MESSAGE_STARTS_WITH:
				return static::startsWith( $needle, $haystack );
			case self::MESSAGE_ENDS_WITH:
				return static::endsWith( $needle, $haystack );
		}
	}

	/**
	 * Simple string startsWith function
	 *
	 * @param string $needle    String to search for
	 * @param string $haystack  String being searched.
	 * 
	 * @return bool
	 */
	protected static function startsWith( $needle, $haystack ) {
		$len = strlen( $needle );
		return ( substr( $haystack, 0, $len ) === $needle );
	}

	/**
	 * Simple string endsWith function
	 *
	 * @param string $needle    String to search for
	 * @param string $haystack  String being searched.
	 * 
	 * @return bool
	 */
	protected static function endsWith( $needle, $haystack ) {
		$len = strlen( $needle );
		if ( $len === 0 ) {
			return true;
		}
		return ( substr( $haystack, -$len ) === $needle );
	}

	/**
	 * Wrapper for IsTrue constraint.
	 *
	 * @return IsTrue
	 */
	public static function isTrue(): IsTrue {
        return new IsTrue;
    }
}