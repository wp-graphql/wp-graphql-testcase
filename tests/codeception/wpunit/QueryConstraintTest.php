<?php

use Tests\WPGraphQL\Constraint\QueryConstraint;
use Tests\WPGraphQL\Logger\CodeceptLogger;

class QueryConstraintTest extends \Codeception\TestCase\WPTestCase {
    private $logger;
    private $constraint;

    public function setUp(): void {
        parent::setUp();
        $this->logger = new CodeceptLogger();
    }

	public function tearDown(): void {
		parent::tearDown();
		WPGraphQL::clear_schema();
	}

    public function testGraphQLResponse() {
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

    public function testGraphQLResponseWithErrors() {
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

    public function testInvalidGraphQLResponse() {
        $response1  = [4, 5, 6];
        $constraint = new QueryConstraint($this->logger);
        $this->assertFalse($constraint->matches($response1));

        $response2  = null;
        $constraint = new QueryConstraint($this->logger);
        $this->assertFalse($constraint->matches($response1));

        $response3  = [ 'something' => [] ];
        $constraint = new QueryConstraint($this->logger);
        $this->assertFalse($constraint->matches($response3));
    }

    public function testFailureDescription() {
        $constraint = new QueryConstraint($this->logger);
        $response = [4, 5, 6];
        $this->assertFalse($constraint->matches($response));
        $this->assertEquals("GraphQL response is invalid: \n\n\t• The GraphQL query response must be provided as an associative array.", $constraint->failureDescription($response));
    }

    public function testToString() {
        $constraint = new QueryConstraint($this->logger);
        $this->assertEquals('is a valid WPGraphQL response', $constraint->toString());
    }
}
