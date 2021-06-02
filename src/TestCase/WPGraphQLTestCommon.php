<?php
/**
 * WPGraphQL test utility functions/assertions.
 *
 * @since 1.1.0
 * @package Tests\WPGraphQL\TestCase
 */

namespace Tests\WPGraphQL\TestCase;

/**
 * Traits
 */
trait WPGraphQLTestCommon {

	/**
	 * Console logging function.
	 *
	 * Use --debug flag to view in console.
	 */
	abstract public function logData( $data );

	/**
	 * Wrapper for the "graphql()" function.
	 *
	 * @return array
	 */
	public function graphql() {
		$results = graphql( ...func_get_args() );

		// use --debug flag to view.
		$this->logData( $results );

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
		$type = $this->get_not() . 'OBJECT';
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
	private function maybe_print_message( $message, $default ) {
		return ! empty( $message ) ? $message : $default;
	}

	/**
	 * Reports an error identified by $message if $response is not a valid GraphQL response object.
	 *
	 * @param array  $response  GraphQL query response object.
	 * @param string $message   Error message. 
	 */
	public function assertIsValidQueryResponse( $response, $message = '' ) {
		// Validate format.
		$this->assertIsArray(
			$response,
			$this->maybe_print_message(
				$message,
				'The GraphQL query response must be provided as an associative array.'
			)
		);

		// Assert that response not empty.
		$this->assertNotEmpty(
			$response,
			$this->maybe_print_message(
				$message,
				'GraphQL query response is empty.'
			)
		);

		// Validate content.
		$this->assertTrue(
			in_array( 'data', array_keys( $response ), true ) || in_array( 'errors', array_keys( $response ), true ),
			$this->maybe_print_message(
				$message,
				'A valid GraphQL query response must contain a "data" or "errors" object.'
			)
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
	public function assertExpectedDataFound( array $response, array $expected_data, $message = '' ) {
		// Throw if "$expected_data" invalid.
		if ( empty( $expected_data['type'] ) ) {
			$this->logData( array( 'INVALID_DATA_OBJECT' => $expected_data ) );
			throw new \Exception( 'Invalid data object provided for evaluation.' );
		}

		// Deconstruct $expected_data.
		extract( $expected_data );

		// Get flags.
		$check_order = isset( $expected_index ) && ! is_null( $expected_index );

		$actual_path = $check_order ? "data.{$path}.{$expected_index}" : "data.$path";
		$actual_data = $this->lodashGet( $response, $actual_path );

		// Only check data existence, if no "$expected_value" provided.
		if ( is_null( $expected_value ) ) {
			$this->assertNotNull(
				$actual_data,
				$this->maybe_print_message(
					$message,
					sprintf( 'No data found at path "%s"', $actual_path )
				)
			);
			if ( is_array( $actual_data ) ) {
				$this->assertNotEmpty(
					$actual_data,
					$this->maybe_print_message(
						$message,
						sprintf( 'Data object found at path "%s" empty.', $actual_path )
					)
				);
			}
			return;
		}

		// Replace string 'null' value with real NULL value for data evaluation.
		if ( is_string( $expected_value ) && 'null' === strtolower( $expected_value ) ) {
			$expected_value = null;
		}

		// Evaluate expected data.
		switch( true ) {
			case $this->endsWith( 'OBJECT', $type ):
				// Log assertion.
				$actual_log_type = is_array( $actual_data ) ? 'ACTUAL_DATA_OBJECT' : 'ACTUAL_DATA';
				$assertion_log = array(
					'EXPECTED_VALUE' => $expected_value,
					$actual_log_type => $actual_data,
				);
				$this->logData( $assertion_log );

				$assert_same       = ! $this->startsWith( '!', $type );
				$assertion         = $assert_same ? 'assertSame' : 'assertNotSame';
				$assertion_message = $assert_same ? 'doesn\'t match' : 'shouldn\'t match';
				// Execute assertion.
				$this->$assertion(
					$expected_value,
					$actual_data,
					$this->maybe_print_message(
						$message,
						sprintf( 'Data found at path "%1$s" %2$s the provided value', $actual_path, $assertion_message )
					)
				);
				break;
			case $this->endsWith( 'NODE', $type ):
			case $this->endsWith( 'EDGE', $type ):
				if ( $check_order ) {
					// Log assertion.
					$actual_log_type = is_array( $actual_data ) ? 'ACTUAL_DATA_OBJECT' : 'ACTUAL_DATA';
					$assertion_log = array(
						"EXPECTED_{$type}_VALUE"   => $expected_value,
						$actual_log_type           => $actual_data,
					);
					$this->logData( $assertion_log );

					$assert_same       = ! $this->startsWith( '!', $type );
					$assertion         = $assert_same ? 'assertSame' : 'assertNotSame';
					$assertion_message = $assert_same ? 'doesn\'t match' : 'shouldn\'t match';
					// Execute assertion
					$this->$assertion(
						$expected_value,
						$actual_data,
						$this->maybe_print_message(
							$message,
							sprintf( 'Data found at path "%1$s" %2$s the provided value', $actual_path, $assertion_message )
						)
					);
					break;
				}
				// Log data objects before the coming assertion.
				$assertion_log = array(
					"NEEDLE_NODE"    => $expected_value,
					"HAYSTACK_NODES" => $actual_data,
				);
				$this->logData( $assertion_log );

				$assert_same       = ! $this->startsWith( '!', $type );
				foreach ( $actual_data as $index => $actual_node ) {
					// If match found, Assert true.
					if ( $expected_value === $actual_node ) {
						if ( $assert_same ) {
							$this->assertTrue( true );
							break 2;
						} else {
							$this->assertTrue(
								false,
								$this->maybe_print_message(
									$message,
									sprintf( 'Undesired data found in %1$s list at path "%2$s.%3$d"', strtolower( $type ), $actual_path, $index )
								)
							);
						}
					}
				}

				if ( $assert_same ) {
					$this->assertTrue(
						false,
						$this->maybe_print_message(
							$message,
							sprintf( 'Expected data not found in the %1$s list at path "%2$s"', strtolower( $type ), $actual_path )
						)
					);
				}

				$this->assertTrue( true );
				
				break;
			default:
				throw new \Exception( 'Invalid data object provided for evaluation.' );
		}
	}

	/**
	 * Reports an error identified by $message if $response does not contain error defined
	 * in $expected_data.
	 *
	 * @param array  $response       GraphQL query response object
	 * @param array  $expected_data  Expected data object to be evaluated.
	 * @param string $message        Error message.
	 */
	public function assertExpectedErrorFound( array $response, array $expected_data, $message = '' ) {
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
						$this->assertTrue( true );
						break 2;
					}
				}
				$this->assertTrue(
					false,
					$this->maybe_print_message(
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
					if ( $this->findSubstring( $needle, $error['message'], $search_type ) ) {
						$this->assertTrue( true );
						break 2;
					}
				}

				$search_type_messages = array(
					self::MESSAGE_EQUALS      => 'equals',
					self::MESSAGE_CONTAINS    => 'contains',
					self::MESSAGE_STARTS_WITH => 'starts with',
					self::MESSAGE_ENDS_WITH   => 'ends with',
				);
				$this->assertTrue(
					false,
					$this->maybe_print_message(
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
	public function assertQuerySuccessful( array $response, array $expected, $message = '' ) {
		$this->assertIsValidQueryResponse( $response, $message );
		foreach( $expected as $expected_data ) {
			$this->assertExpectedDataFound( $response, $expected_data, $message );
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
		$this->assertIsValidQueryResponse( $response, $message );
		
		// Confirm query throw errors found.
		$this->assertArrayHasKey(
			'errors',
			$response,
			$this->maybe_print_message( $message, 'No errors found' )
		);
		
		// Process expected data.
		foreach( $expected as $expected_data ) {
			// Throw if "$expected_data" invalid.
			if ( empty( $expected_data['type'] ) ) {
				$this->logData( array( 'INVALID_DATA_OBJECT' => $expected_data ) );
				throw new \Exception( 'Invalid data object provided for evaluation.' );
			} elseif ( $this->startsWith( 'ERROR_', $expected_data['type'] ) ) {
				$this->assertExpectedErrorFound( $response, $expected_data, $message );
			} else {
				$this->assertExpectedDataFound( $response, $expected_data, $message );
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
	protected function lodashGet( array $data, string $string, $default = null ) {
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
	 * Processes substring searches
	 *
	 * @param string $needle       String being searched for.
	 * @param string $haystack     String being searched.
	 * @param int    $search_type  Search operation enumeration.
	 * 
	 * @return boolean
	 */
	protected function findSubstring( $needle, $haystack, $search_type ) {
		switch( $search_type ) {
			case self::MESSAGE_EQUALS:
				return $needle === $haystack;
			case self::MESSAGE_CONTAINS:
				return false !== strpos( $haystack, $needle );
			case self::MESSAGE_STARTS_WITH:
				return $this->startsWith( $needle, $haystack );
			case self::MESSAGE_ENDS_WITH:
				return $this->endsWith( $needle, $haystack );
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
	protected function startsWith( $needle, $haystack ) {
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
	protected function endsWith( $needle, $haystack ) {
		$len = strlen( $needle );
		if ( $len === 0 ) {
			return true;
		}
		return ( substr( $haystack, -$len ) === $needle );
	}
}