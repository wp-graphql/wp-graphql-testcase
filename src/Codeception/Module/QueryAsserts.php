<?php

namespace Tests\WPGraphQL\Codeception\Module;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\TestInterface;
use Codeception\Util\Shared\Asserts;
use Tests\WPGraphQL\Constraint\QueryConstraint;
use Tests\WPGraphQL\Constraint\QueryErrorConstraint;
use Tests\WPGraphQL\Constraint\QuerySuccessfulConstraint;
use Tests\WPGraphQL\Logger\CodeceptLogger as Signal;

/**
 * GraphQL Query Asserts Module for Codeception
 */
class QueryAsserts extends Module {

    use Asserts;

    /** @var string|null */
    private $not = null;

    /** @var \Tests\WPGraphQL\Logger\CodeceptLogger */
    private $logger = null;

    /**
     * Initializes the module
     *
     * @return void
     */
    public function _before( TestInterface $test ) {
        $this->logger = new Signal();
    }
    
    /**
	 * Copy of "GraphQLRelay\Relay::toGlobalId()" function.
	 * 
	 * @param string $type  The type of the object.
	 * @param string $id    The ID of the object.
	 *
	 * @return string
	 */
	public function asRelayId($type, $id) {
		return base64_encode( $type . ':' . $id );
	}

	/**
	 * Returns an expected "Field" type data object.
	 *
	 * @param string $path            Path to the data being tested.
	 * @param mixed  $expected_value  Expected value of the object being evaluted.
	 * @return array
	 */
	public function expectField( string $path, $expected_value ) {
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
	public function expectObject( string $path, array $expected_value ) {
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
	public function expectNode( string $path, array $expected_value, $expected_index = null ) {
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
	public function expectEdge( string $path, array $expected_value, $expected_index = null ) {
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
		if ( ! $this->not ) {
			return '';
		}

		$prefix = $this->not;
		$this->not = null;
		return $prefix;
	}

	/**
	 * Returns an expected "location" error data object.
	 *
	 * @param string $path Path to the data being tested.
	 * @return array
	 */
	public function expectErrorPath( string $path ) {
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
	public function expectErrorMessage( string $needle, int $search_type = self::MESSAGE_EQUALS ) {
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
	public function assertResponseIsValid( $response, $message = '' ) {
		$this->assertThat(
			$response,
			new QueryConstraint( $this->logger ),
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
	public function assertQuerySuccessful( array $response, array $expected = [], $message = '' ) {
		$this->assertThat(
			$response,
			new QuerySuccessfulConstraint( $this->logger, $expected ),
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
	public function assertQueryError( array $response, array $expected = [], $message = '' ) {
		$this->assertThat(
			$response,
			new QueryErrorConstraint( $this->logger, $expected ),
			$message
		);
	}
}