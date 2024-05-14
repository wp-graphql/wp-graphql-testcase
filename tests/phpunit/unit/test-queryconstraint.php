<?php

use Tests\WPGraphQL\Constraint\QueryConstraint;
use Tests\WPGraphQL\Logger\PHPUnitLogger;

class QueryConstraintTest extends \WP_UnitTestCase {
    private $logger;
    private $constraint;

    public function setUp(): void {
        parent::setUp();
        $this->logger = new PHPUnitLogger();
    }

	public function tearDown(): void {
		parent::tearDown();
		WPGraphQL::clear_schema();
	}

    public function test_GraphQLResponse() {
        // Create some posts.
        $this->factory()->post->create_many(4);

        // GraphQL query.
        $query = '
            query {
                posts {
                    nodes {
                        id
                    }
                }
            }
        ';

        // Execute query and get response.
        $response = graphql(compact('query'));
        $this->logger->logData($response);

        // Test response against QueryConstraint.
        $constraint = new QueryConstraint($this->logger);
        $this->assertTrue($constraint->matches($response));
    }

    public function test_GraphQLResponseWithErrors() {
        // Create some posts.
        $this->factory()->post->create_many(4);

        // GraphQL query.
        $query = '
            query {
                posts {
                    nodes {
                        id
                        invalidField
                    }
                }
            }
        ';

        // Execute query and get response.
        $response = graphql(compact('query'));
        $this->logger->logData($response);

        // Test response against QueryConstraint.
        $constraint = new QueryConstraint($this->logger);
        $this->assertTrue($constraint->matches($response));
    }

    public function test_InvalidGraphQLResponse() {
        $response1  = [4, 5, 6];
        $constraint = new QueryConstraint($this->logger);
        $this->assertFalse($constraint->matches($response1));

        $response2  = null;
        $constraint = new QueryConstraint($this->logger);
        $this->assertFalse($constraint->matches($response2));

        $response3  = [ 'something' => [] ];
        $constraint = new QueryConstraint($this->logger);
        $this->assertFalse($constraint->matches($response3));
    }

    public function test_FailureDescription() {
        $constraint = new QueryConstraint($this->logger);
        $response = [4, 5, 6];
        $this->assertFalse($constraint->matches($response));
        $this->assertEquals("GraphQL response is invalid: \n\n\t• The GraphQL query response must be provided as an associative array.", $constraint->failureDescription($response));
    }

    public function test_ToString() {
        $constraint = new QueryConstraint($this->logger);
        $this->assertEquals('is a valid WPGraphQL response', $constraint->toString());
    }
}
