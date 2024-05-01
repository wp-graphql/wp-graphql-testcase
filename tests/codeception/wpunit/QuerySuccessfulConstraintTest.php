<?php

use Tests\WPGraphQL\Constraint\QuerySuccessfulConstraint;
use Tests\WPGraphQL\Logger\CodeceptLogger;

class QuerySuccessfulConstraintTest extends \Codeception\TestCase\WPTestCase {
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

    public function testValidGraphQLResponse() {
        // Create some posts.
        $this->factory()->post->create_many(4);

        // GraphQL query.
        $query = '
            query {
                posts {
                    nodes {
                        id
                        title
                        content
                    }
                }
            }
        ';

        // Execute query and get response.
        $response = graphql(compact('query'));
        $this->logger->logData($response);

        // Test response against QuerySuccessfulConstraint.
        $constraint = new QuerySuccessfulConstraint($this->logger);
        $this->assertTrue($constraint->matches($response));
    }

    public function testInvalidGraphQLResponse() {
        // Create some posts.
        $this->factory()->post->create_many(4);

        // GraphQL query.
        $query = '
            query {
                posts {
                    nodes {
                        id
                        title
                        content
                        invalidField
                    }
                }
            }
        ';

        // Execute query and get response.
        $response = graphql(compact('query'));
        $this->logger->logData($response);

        // Test response against QuerySuccessfulConstraint.
        $constraint = new QuerySuccessfulConstraint($this->logger);
        $this->assertFalse($constraint->matches($response));
    }

    public function testPassingValidationRules() {
        // Create some posts.
        $this->factory()->post->create( [ 'post_title' => 'hello world', 'post_name' => 'test_post' ] );
        $this->factory()->post->create_many(4);

        // GraphQL query.
        $query = '
            query {
                posts {
                    nodes {
                        id
                        title
                        slug
                    }
                }
            }
        ';

        // Execute query and get response.
        $response = graphql(compact('query'));
        $this->logger->logData($response);

        // Test response against QuerySuccessfulConstraint.
        $constraint = new QuerySuccessfulConstraint(
            $this->logger,
            [
                [
                    'type' => 'NODE',
                    'path' => 'posts.nodes',
                    'expected_value' => [
                        [
                            'type' => 'FIELD',
                            'path' => 'id',
                            'expected_value' => 'phpunit_field_value_not_null',
                        ],
                        [
                            'type' => 'FIELD',
                            'path' => 'title',
                            'expected_value' => 'hello world',
                        ],
                        [
                            'type' => 'FIELD',
                            'path' => 'slug',
                            'expected_value' => 'test_post',
                        ],
                    ],
                    'expected_index' => null,
                ]
            ]
        );
        $this->assertTrue($constraint->matches($response));
    }

    public function testFailingValidationRules() {
        // Create some posts.
        $this->factory()->post->create_many(5);

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

        // Test response against QuerySuccessfulConstraint.
        $constraint = new QuerySuccessfulConstraint(
            $this->logger,
            [
                [
                    'type'           => 'FIELD',
                    'path'           => 'invalidNodePath',
                    'expected_value' => 'phpunit_field_value_not_null',
                ],
                [
                    'type'           => 'NODE',
                    'path'           => 'posts.nodes',
                    'expected_value' => [
                        [
                            'type' => 'FIELD',
                            'path' => 'invalidFieldPath',
                            'expected_value' => 'phpunit_field_value_not_null'
                        ],
                    ],
                    'expected_index' => null,
                ],
                [
                    'type' => 'NODE',
                    'path' => 'posts.nodes',
                    'expected_value' => [
                        [
                            'type' => 'FIELD',
                            'path' => 'id',
                            'expected_value' => 'invalidFieldValue'
                        ],
                    ],
                    'expected_index' => null,
                ]
            ]
        );
        $this->assertFalse($constraint->matches($response));
    }
}