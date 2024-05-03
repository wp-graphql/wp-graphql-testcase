<?php

use Tests\WPGraphQL\Constraint\QuerySuccessfulConstraint;
use Tests\WPGraphQL\Logger\PHPUnitLogger;

class QuerySuccessfulConstraintTest extends \WP_UnitTestCase {
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

    public function test_ValidGraphQLResponse() {
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

    public function test_InvalidGraphQLResponse() {
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

    public function test_PassingValidationRules() {
        // Create some posts.
        $this->factory()->post->create( [ 'post_title' => 'hello world', 'post_name' => 'test_post' ] );
        $this->factory()->post->create_many(4);

        // Register null field
        register_graphql_field( 'Post', 'nullField', [
            'type' => 'String',
            'resolve' => function() {
                return null;
            }
        ]);

        // GraphQL query.
        $query = '
            query {
                posts {
                    nodes {
                        id
                        title
                        slug
                        nullField
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
                    'type'           => 'NODE',
                    'path'           => 'posts.nodes',
                    'expected_value' => [
                        [
                            'type'           => 'FIELD',
                            'path'           => 'id',
                            'expected_value' => 'phpunit_field_value_not_null',
                        ],
                        [
                            'type'           => 'FIELD',
                            'path'           => 'id',
                            'expected_value' => 'phpunit_field_value_not_falsy',
                        ],
                        [
                            'type'           => 'FIELD',
                            'path'           => 'title',
                            'expected_value' => 'hello world',
                        ],
                        [
                            'type'           => 'FIELD',
                            'path'           => 'slug',
                            'expected_value' => 'test_post',
                        ],
                        [
                            'type'           => 'FIELD',
                            'path'           => 'nullField',
                            'expected_value' => 'phpunit_field_value_is_null',
                        ],
                    ],
                    'expected_index' => null,
                ]
            ]
        );
        $this->assertTrue($constraint->matches($response));
    }

    public function test_FailingValidationRules() {
        // Create some posts.
        $post_id = $this->factory()->post->create( [ 'post_title' => 'hello world', 'post_name' => 'test_post' ] );

        // Register null field
        register_graphql_field( 'Post', 'nullField', [
            'type' => 'String',
            'resolve' => function() {
                return null;
            }
        ]);

        // GraphQL query.
        $query = '
            query {
                posts {
                    nodes {
                        id
                        databaseId
                        nullField
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
                    'type'           => 'FIELD',
                    'path'           => 'invalidNodePath',
                    'expected_value' => 'phpunit_field_value_not_falsy',
                ],
                [
                    'type'           => 'NODE',
                    'path'           => 'posts.nodes',
                    'expected_value' => [
                        [
                            'type'           => 'FIELD',
                            'path'           => 'invalidFieldPath',
                            'expected_value' => 'phpunit_field_value_not_null'
                        ],
                    ],
                    'expected_index' => null,
                ],
                [
                    'type'           => 'NODE',
                    'path'           => 'posts.nodes',
                    'expected_value' => [
                        [
                            'type'           => 'FIELD',
                            'path'           => 'id',
                            'expected_value' => 'invalidFieldValue'
                        ],
                        [
                            'type'           => 'FIELD',
                            'path'           => 'id',
                            'expected_value' => 'phpunit_field_value_is_null',
                        ],
                        [
                            'type'           => 'FIELD',
                            'path'           => 'id',
                            'expected_value' => 'phpunit_field_value_is_falsy',
                        ],
                        [
                            'type'           => 'FIELD',
                            'path'           => 'nullField',
                            'expected_value' => 'phpunit_field_value_not_null',
                        ],
                        [
                            'type'           => 'FIELD',
                            'path'           => 'nullField',
                            'expected_value' => 'phpunit_field_value_not_falsy',
                        ],
                    ],
                    'expected_index' => null,
                ],
                [
                    'type'           => '!NODE',
                    'path'           => 'posts.nodes',
                    'expected_value' => [
                        [
                            'type'           => 'FIELD',
                            'path'           => 'databaseId',
                            'expected_value' => 'post_id',
                        ],
                    ],
                    'expected_index' => 0,
                ],
                [
                    'type'           => 'INVALID_TYPE',
                    'path'           => '',
                    'expected_value' => [],
                ],
                ['InvalidRuleObject'],
            ]
        );
        $this->assertFalse($constraint->matches($response));
    }
}