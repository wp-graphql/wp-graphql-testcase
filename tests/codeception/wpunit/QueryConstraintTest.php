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
}