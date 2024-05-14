<?php
/**
 * QueryErrorConstraint 
 *
 * Assertion validating successful WPGraphQL query response.
 * @since v3.0.0
 * @package Tests\WPGraphQL\Constraint
 */

namespace Tests\WPGraphQL\Constraint;

class QueryErrorConstraint extends QueryConstraint {

    /**
     * {@inheritDoc}
     */
    public function matches($response): bool {
        // Ensure response is valid.
        if ( ! $this->responseIsValid( $response ) ) {
            return false;
        }

        // Throw if response has errors.
        if ( ! array_key_exists( 'errors', $response ) ) {
            $this->error_message = "No errors was thrown during the previous GraphQL requested. \n Use \"--debug\" flag to see contents of previous request.";
            return false;
        }

        // Return true if no validation rules provided.
        if ( empty( $this->validationRules ) ) {
            return true;
        }

        // Check validation rules.
        $data_passed  = true;
        $error_passed = true;
        foreach( $this->validationRules as $expected_data ) {
            if ( empty( $expected_data['type'] ) ) {
                $this->logger->logData( array( 'INVALID_DATA_OBJECT' => $expected_data ) );
                $this->error_details[] = 'Invalid data object provided for evaluation.';
                $data_passed = false;
                $error_passed = false;
                continue;
            }

            if ( str_starts_with( $expected_data['type'], 'ERROR_' ) ) {
                if ( ! $this->expectedErrorFound( $response, $expected_data ) ) {
                    $error_passed = false;
                }
                continue;
            }

            if ( ! $this->expectedDataFound( $response, $expected_data ) ) {
                $data_passed = false;
            }
        }

        if ( ! $data_passed || ! $error_passed) {
            $this->error_message = 'The GraphQL response failed the following steps in validation';
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string {
        return 'is a WPGraphQL query response with errors';
    }
}
