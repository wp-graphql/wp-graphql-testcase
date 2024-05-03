<?php
/**
 * QuerySuccessfulConstraint 
 *
 * Assertion validating successful WPGraphQL query response.
 * @since v3.0.0
 * @package Tests\WPGraphQL\Constraint
 */

namespace Tests\WPGraphQL\Constraint;

use PHPUnit\Framework\Exception;

class QuerySuccessfulConstraint extends QueryConstraint {

    /**
     * {@inheritDoc}
     */
    public function matches($response): bool {
        // Ensure response is valid.
        if ( ! $this->responseIsValid( $response ) ) {
            return false;
        }

        // Throw if response has errors.
        if ( array_key_exists( 'errors', $response ) ) {
            $this->error_messages[] = 'An error was thrown during the previous GraphQL requested. May need to use "--debug" flag to see contents of previous request.';
            return false;
        }

        // Return true if no validation rules provided.
        if ( empty( $this->validationRules ) ) {
            return true;
        }

        // Check validation rules.
        $passed = true;
        foreach( $this->validationRules as $expected_data ) {
            if ( ! $this->expectedDataFound( $response, $expected_data ) ) {
                $passed = false;
            }
        }

        if ( ! $passed ) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string {
        return 'is a successful WPGraphQL response with no errors.';
    }
}
