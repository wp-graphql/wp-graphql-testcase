<?php
/**
 * QueryConstraint interface
 *
 * Defines shared logic for QueryConstraint classes.
 * @since v3.0.0
 * @package Tests\WPGraphQL\Constraint
 */

namespace Tests\WPGraphQL\Constraint;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\Constraint\Constraint;
use Tests\WPGraphQL\Logger\CodeceptLogger;
use Tests\WPGraphQL\Logger\PHPUnitLogger;
use Tests\WPGraphQL\Utils\Utils;

class QueryConstraint extends Constraint {

    /**
     * Logger for logging debug messages.
     *
     * @var PHPUnitLogger|CodeceptLogger
     */
    protected $logger;

    /**
     * Stores the validation steps for the assertion.
     * 
     * @var array $validationRules 
     */
    protected $validationRules = [];

    /**
     * Stores subset of response data to be evaluated.
     *
     * @var mixed
     */
    private $actual = null;

    /**
     * List of errors trigger during validation.
     *
     * @var string[]
     */
    private $error_messages = [];

    /**
     * Constructor
     *
     * @param array $expected  Expected validation rules.
     */
    public function __construct($logger, array $expected = []) {
        $this->logger = $logger;
        $this->validationRules = $expected;
    }

    /**
	 * Reports an error identified by $message if $response is not a valid GraphQL response object.
	 *
	 * @param array  $response  GraphQL query response object.
	 * @param string $message   References that outputs error message.
	 *
	 * @return bool
	 */
	protected function responseIsValid( $response, &$message = null ) {
		if ( empty( $response ) ) {
            $this->error_messages[] = 'GraphQL query response is invalid.';
            return false;
		}

		if ( array_keys( $response ) === range( 0, count( $response ) - 1 ) ) {
            $this->error_messages[] = 'The GraphQL query response must be provided as an associative array.';
            return false;
		}

		if ( 0 === count( array_intersect( array_keys( $response ), [ 'data', 'errors' ] ) ) ) {
            $this->error_messages[] = 'A valid GraphQL query response must contain a "data" or "errors" object.';
            return false;
		}

		return true;
	}

    /**
	 * Evaluates the response "data" against a validation rule.
	 *
	 * @param array  $response       GraphQL query response object
	 * @param array  $expected_data  Validation Rule.valid rule object provided for evaluation.
	 *
	 * @return bool
	 */
	protected function expectedDataFound( array $response, array $expected_data, string $current_path = null ) {
		// Throw if "$expected_data" invalid.
		if ( empty( $expected_data['type'] ) ) {
			$this->logger->logData( [ 'INVALID_DATA_OBJECT' => $expected_data ] );
			$this->error_messages[] = "Invalid rule object provided for evaluation: \n\t " . json_encode( $expected_data, JSON_PRETTY_PRINT );
			return false;
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
		$full_path = $check_order ? "{$path}.{$expected_index}" : "{$path}";

		// Get data at path for evaluation.
		$actual_data = $this->getPossibleDataAtPath( $response, $full_path, $is_group );

		// Set actual data for final assertion
		$this->actual = $actual_data;

		// Handle if "$expected_value" set to field value constants.
		$reverse             = str_starts_with( $type, '!' );
		$possible_constraint = is_array( $expected_value ) ? $expected_value : "{$expected_value}";
		switch( $possible_constraint ) {
			case $this->logger::IS_NULL:
				// Set "expected_value" to "null" for later comparison.
				$expected_value = null;
				break;

			case $this->logger::NOT_FALSY:
				// Fail if data found at path is a falsy value (null, false, []).
				if ( empty( $actual_data ) && ! $reverse ) {
					$this->error_messages[] = sprintf(
                        'Expected data at path "%s" not to be falsy value. "%s" Given',
                        $full_path,
                        is_array( $actual_data ) ? '[]' : (string) $actual_data
                    );

					return false;
				} elseif ( ! empty( $actual_data ) && $reverse ) {
					$this->error_messages[] = sprintf(
						'Expected data at path "%s" to be falsy value. "%s" Given',
						$full_path,
						is_array( $actual_data ) ? "\n\n" . json_encode( $actual_data, JSON_PRETTY_PRINT ) : $actual_data
					);

					return false;
				}

				// Return true because target value not falsy.
				return true;

			case $this->logger::IS_FALSY:
				// Fail if data found at path is not falsy value (null, false, 0, []).
				if ( ! empty( $actual_data ) && ! $reverse ) {
					$this->error_messages[] = sprintf(
						'Expected data at path "%s" to be falsy value. "%s" Given',
						$full_path,
						is_array( $actual_data ) ? "\n\n" .json_encode( $actual_data, JSON_PRETTY_PRINT ) : $actual_data
					);

					return false;
				} elseif ( empty( $actual_data ) && $reverse ) {
					$this->error_messages[] = sprintf(
						'Expected data at path "%s" not to be falsy value. "%s" Given',
						$full_path,
						is_array( $actual_data ) ? "\n\n" .json_encode( $actual_data, JSON_PRETTY_PRINT ) : $actual_data
					);

					return false;
				}

				// Return true because target value is falsy.
				return true;

			case $this->logger::NOT_NULL:
			default: // Check if "$expected_value" is not null if comparing to provided value.
				// Fail if no data found at path.
				if ( is_null( $actual_data ) && ! $reverse ) {
					$this->error_messages[] = sprintf( 'No data found at path "%s"', $full_path );

					return false;
				} elseif (
					! is_null( $actual_data )
					&& $reverse
					&& $expected_value === $this->logger::NOT_NULL
				) {
					$this->error_messages[] = sprintf( 'Unexpected data found at path "%s"', $full_path );

					return false;
				}

				// Return true because target value not null.
				if ( $expected_value === $this->logger::NOT_NULL ) {
					return true;
				}
		}

		$match_wanted   = ! str_starts_with( $type, '!' );
		$is_field_rule  = str_ends_with( $type, 'FIELD' );
		$is_object_rule = str_ends_with( $type, 'OBJECT' );
		$is_node_rule   = str_ends_with( $type, 'NODE' );
		$is_edge_rule   = str_ends_with( $type, 'EDGE' );

		// Set matcher and constraint.
		$matcher                 = ( ( $is_group && $is_field_rule ) || ( ! $check_order && ! $is_field_rule ) )
			? 'doesFieldMatchGroup'
			: 'doesFieldMatch';

		// Evaluate rule by type.
		switch( true ) {
			case $is_field_rule:
				// Fail if matcher fails
				if ( ! $this->{$matcher}( $actual_data, $expected_value, $match_wanted, $path ) ) {
					$this->error_messages[] = sprintf(
                        'Data found at path "%1$s" %2$s the provided value',
                        $path,
                        $match_wanted ? 'doesn\'t match' : 'shouldn\'t match'
                    );

					return false;
				}

				// Pass if matcher passes.
				return true;
			case $is_object_rule:
			case $is_node_rule:
			case $is_edge_rule:
				// Handle nested rules recursively.
				if ( is_array( $expected_value ) && $this->isNested( $expected_value ) ) {
					foreach ( $expected_value as $nested_rule ) {
						$next_path           = ( $check_order || $is_object_rule ) ? $full_path : "{$full_path}.#";
						$next_path          .= $is_edge_rule ? '.node' : '';
						$nested_rule_passing = $this->expectedDataFound( $response, $nested_rule, $next_path );

						if ( ! $nested_rule_passing ) {
							return false;
						}
					}
					return true;
				}

				// Fail if matcher fails.
				if ( ! $this->{$matcher}( $actual_data, $expected_value, $match_wanted, $path ) ) {
					if ( $check_order ) {
						$this->error_messages[] = sprintf(
                            'Data found at path "%1$s" %2$s the provided value',
                            $full_path,
                            $match_wanted ? 'doesn\'t match' : 'shouldn\'t match'
                        );
					} else {
						$this->error_messages[] = sprintf(
                            '%1$s found in %2$s list at path "%3$s"',
                            $match_wanted ? 'Unexpected data ' : 'Expected data not ',
                            strtolower( $type ),
                            $full_path
                        );
					}

					return false;
				}

				// Pass if matcher passes.
				return true;
			default:
				$this->logger->logData( ['INVALID_DATA_OBJECT', $expected_data ] );
				$this->error_messages[] = "Invalid data object provided for evaluation. \n\t" . json_encode( $expected_data, JSON_PRETTY_PRINT );
				return false;
		}
	}

    /**
	 * Evaluates the response "errors" against a validation rule.
	 *
	 * @param array  $response       GraphQL query response object
	 * @param array  $expected_data  Expected data object to be evaluated.
	 * @param string $message        Error message.
     * 
     * @throws Exception Invalid data object provided for evaluation.
     * 
     * @return bool
	 */
	protected function expectedErrorFound( array $response, array $expected_data ) {
		$search_type_messages = [
			$this->logger::MESSAGE_EQUALS      => 'equals',
			$this->logger::MESSAGE_CONTAINS    => 'contains',
			$this->logger::MESSAGE_STARTS_WITH => 'starts with',
			$this->logger::MESSAGE_ENDS_WITH   => 'ends with',
		];

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

				// Set constraint.
				$this->actual = $this->getPossibleDataAtPath( $response['errors'], '.#.path' );
				$this->logger->logData(
					[
						'TARGET_ERROR_PATH' => $target_path,
						'POSSIBLE_ERRORS'   => $this->actual,
					]
				);
				foreach ( $response['errors'] as $error ) {
					if ( empty( $error['path'] ) ) {
						continue;
					}

					// Pass if match found.
					// TODO: Add support for group path searches using "#" for index.
					if ( $target_path === $error['path'] ) {
						$this->logger->logData(
							[
								'TARGET_ERROR_PATH' => $target_path,
								'CURRENT_PATH'      => $error['path'],
							]
						);
						return true;
					}
				}

				// Fail if no match found.
				$this->error_messages[] = sprintf( 'No errors found that occured at path "%1$s"', $path );
				return false;
			case 'ERROR_MESSAGE':
				$this->logger->logData(
					[
						'TARGET_ERROR_MESSAGE' => $needle,
						'SEARCH_TYPE'          => $search_type_messages[ $search_type ],
						'POSSIBLE_ERRORS'      => $response['errors'],
					]
				);
				foreach ( $response['errors'] as $error ) {
					// Set constraint.
					$this->actual = $this->getPossibleDataAtPath( $response['errors'], '.#.message' );
					
					if ( empty( $error['message'] ) ) {
						continue;
					}

					// Pass if match found.
					$this->logger->logData(
						[
							'TARGET_ERROR_MESSAGE'  => $needle,
							'SEARCH_TYPE'           => $search_type_messages[ $search_type ],
							'CURRENT_ERROR_MESSAGE' => $error['message'],
						]
					);
					if ( $this->findSubstring( $error['message'], $needle, $search_type ) ) {
						return true;
					}
				}

				// Fail if no match found.
				$this->error_messages[] = sprintf(
                    'No errors found with a message that %1$s "%2$s"',
                    $search_type_messages[ $search_type ],
                    $needle
                );

				return false;
			default:
				$this->logger->logData( ['INVALID_DATA_OBJECT', $expected_data ] );
				$this->error_messages[] = "Invalid data object provided for evaluation. \n\t" . json_encode( $expected_data, JSON_PRETTY_PRINT );
				return false;
		}
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
	protected function getPossibleDataAtPath( array $data, string $path, &$is_group = false ) {
		$branches = explode( '.#', $path );

		if ( 1 < count( $branches ) ) {
			$is_group      = true;
			$possible_data = $this->lodashGet( $data, $branches[0] );

			// Loop throw top branches and build out the possible data options.
			if ( ! empty( $possible_data ) && is_array( $possible_data ) ) {
				foreach ( $possible_data as &$next_data ) {
					if ( ! is_array( $next_data ) ) {
						continue;
					}

					$next_data = $this->getPossibleDataAtPath(
						$next_data,
						ltrim( implode( '.#', array_slice( $branches, 1 ) ), '.' ),
						$is_group
					);
				}
			}

			return $possible_data;
		}

		return $this->lodashGet( $data, $path, null );
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
	protected function lodashGet( array $data, string $string, $default = null ) {
		return Utils::lodashGet( $data, $string, $default );
	}

    /**
	 * Checks if the provided is a expected data rule object.
	 *
	 * @param array $expected_data
	 *
	 * @return bool
	 */
	protected function isNested( array $expected_data ) {
		$rule_keys = [ 'type', 'path', 'expected_value' ];

		return ! empty( $expected_data[0] )
			&& is_array( $expected_data[0] )
			&& 3 === count( array_intersect( array_keys( $expected_data[0] ), $rule_keys ) );
	}

    /**
	 * Asserts if $expected_value matches $data.
	 *
	 * @param array  $data            Data object be evaluted.
	 * @param mixed  $expected_value  Value $data is expected to evalute to.
	 * @param bool   $match_wanted    Whether $expected_value and $data should be equal or different.
	 * @param string $path The path of the property to get.
	 *
	 * @return bool
	 */
	protected function doesFieldMatch( $data, $expected_value, $match_wanted, $path ) {
		// Get data/value type and log assertion.
		$log_type   = is_array( $data ) ? 'ACTUAL_DATA_OBJECT' : 'ACTUAL_DATA';
		$value_type = $match_wanted ? 'WANTED_VALUE': 'UNWANTED_VALUE';
		$this->logger->logData(
			array(
				'PATH'      => $path,
				$value_type => $expected_value,
				$log_type   => $data,
			)
		);

		// If match wanted, matching condition set other not matching condition is set.
		$condition = $match_wanted
			? $data === $expected_value
			: $data !== $expected_value;

		// Return condtion.
		return $condition;
	}

	/**
	 * Asserts if $expected_value matches one of the entries in $data.
	 *
	 * @param array  $data            Data object be evaluted.
	 * @param mixed  $expected_value  Value $data is expected to evalute to.
	 * @param bool   $match_wanted    Whether $expected_value and $data should be equal or different.
	 * @param string $path The path of the property to get.
	 *
	 * @return bool
	 */
	protected function doesFieldMatchGroup( $data, $expected_value, $match_wanted, $path ) {
		$item_type  = $match_wanted ? 'WANTED VALUE' : 'UNWANTED VALUE';

		// Log data objects before the coming assertion.
		$assertion_log = [
			'PATH'               => $path,
			$item_type           => $expected_value,
			'VALUES_AT_LOCATION' => $data,
		];
		$this->logger->logData( $assertion_log );

		if ( ! is_array( $data ) ) {
			return $this->doesFieldMatch(
				$data,
				$expected_value,
				$match_wanted,
				$path
			);
		}

		// Loop through possible node/edge values for the field.
		foreach ( $data as $item ) {
			// Check if field value matches $expected_value.
			$field_matches = $this->doesFieldMatch(
				$item,
				$expected_value,
				$match_wanted,
				$path
			);

			// Pass if match found and match wanted.
			if ( $field_matches && $match_wanted ) {
				return true;

				// Fail if match found and no matches wanted.
			} elseif ( ! $field_matches && ! $match_wanted ) {
				return false;
			}
		}

		// Fail if no matches found but matches wanted.
		if ( $match_wanted ) {
			return false;
		}

		// Pass if no matches found and no matches wanted.
		return true;
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
	protected function findSubstring( $haystack, $needle, $search_type ) {
		switch( $search_type ) {
			case $this->logger::MESSAGE_EQUALS:
				return $needle === $haystack;
			case $this->logger::MESSAGE_CONTAINS:
				return false !== strpos( $haystack, $needle );
			case $this->logger::MESSAGE_STARTS_WITH:
				return str_starts_with( $haystack, $needle );
			case $this->logger::MESSAGE_ENDS_WITH:
				return str_ends_with( $haystack, $needle );
		}
	}

    /**
     * Evaluates the response against the validation rules.
     *
     * @param array $response
     *
     * @throws Exception
     * 
     * @return boolean
     */
    public function matches($response): bool {
        // Ensure response is valid.
        if ( ! $this->responseIsValid( $response ) ) {
            return false;
        }

        return true;
    }

    public function failureDescription($other): string {
        return "GraphQL response failed validation: \n\n\t• " . implode( "\n\n\t• ", $this->error_messages );
    }

    /**
     * Returns a string representation of the constraint object.
     *
     * @return string
     */
    public function toString(): string {
        return 'is a valid WPGraphQL response';
    }
}