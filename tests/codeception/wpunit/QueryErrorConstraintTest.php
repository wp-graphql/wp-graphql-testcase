<?php

use Tests\WPGraphQL\Constraint\QueryErrorConstraint;
use Tests\WPGraphQL\Logger\CodeceptLogger;

class QueryErrorConstraintTest extends \Codeception\TestCase\WPTestCase {
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

        // Test response against QueryErrorConstraint.
        $constraint = new QueryErrorConstraint($this->logger);
        $this->assertTrue($constraint->matches($response));
    }

    public function testGraphQLResponseWithoutErrors() {
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

        // Test response against QueryErrorConstraint.
        $constraint = new QueryErrorConstraint($this->logger);
        $this->assertFalse($constraint->matches($response));
    }

    public function testPassingValidationRules() {
        // Register broken field
        register_graphql_field( 'Post', 'invalidField', [
            'type' => 'String',
            'resolve' => function() {
                throw new \GraphQL\Error\UserError('Explosion!');
            }
        ]);

        // Create some posts.
        $this->factory()->post->create([
            'post_title' => 'Hello, World!',
            'post_content' => 'This is a test post.'
        ]);

        // GraphQL query.
        $query = '
            query {
                posts {
                    nodes {
                        id
                        title
                        invalidField
                    }
                }
            }
        ';

        // Execute query and get response.
        $response = graphql(compact('query'));
        $this->logger->logData($response);

        // Test response against QueryErrorConstraint.
        $constraint = new QueryErrorConstraint(
            $this->logger,
            [
                [
                    'type'           => 'FIELD',
                    'path'           => 'posts.nodes.0.title',
                    'expected_value' => 'Hello, World!'
                ],
                [
                    'type'           => '!FIELD',
                    'path'           => 'posts.nodes.#.title',
                    'expected_value' => 'Does not exist'
                ],
                [
                    'type'        => 'ERROR_MESSAGE',
                    'needle'      => 'Explosion!',
                    'search_type' => 600,
                ],
                [
                    'type' => 'ERROR_PATH',
                    'path' => 'posts.nodes.0.invalidField', 
                ],
            ]
        );
        $this->assertTrue($constraint->matches($response));
    }

    public function testFailingValidationRules() {
        // Register broken field
        register_graphql_field( 'Post', 'invalidField', [
            'type' => 'String',
            'resolve' => function() {
                throw new \GraphQL\Error\UserError('Explosion!');
            }
        ]);

        // Create some posts.
        $this->factory()->post->create();

        // GraphQL query.
        $query = '
            query {
                posts {
                    nodes {
                        id
                        title
                        invalidField
                    }
                }
            }
        ';

        // Execute query and get response.
        $response = graphql(compact('query'));
        $this->logger->logData($response);

        // Test response against QueryErrorConstraint.
        $constraint = new QueryErrorConstraint(
            $this->logger,
            [
                [
                    'type'           => 'FIELD',
                    'path'           => 'posts.nodes.#.content',
                    'expected_value' => 'Does not exist'
                ],
                [
                    'type'        => 'ERROR_MESSAGE',
                    'needle'      => 'Invalid error message.',
                    'search_type' => 600,
                ],
                [
                    'type' => 'ERROR_PATH',
                    'path' => 'posts.nodes.#.id', 
                ],
            ]
        );
        $this->assertFalse($constraint->matches($response));
    }
}