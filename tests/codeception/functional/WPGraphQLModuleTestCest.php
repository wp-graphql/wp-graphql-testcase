<?php


namespace Functional;

use \FunctionalTester;
use Tests\WPGraphQL\Logger\CodeceptLogger as Signal;

class WPGraphQLModuleTestCest {
    public function testGetRequest( FunctionalTester $I, $scenario ) {
        $I->wantTo( 'send a GET request to the GraphQL endpoint and return a response' );

        $I->haveManyPostsInDatabase(5);
        $post_id = $I->havePostInDatabase( [ 'post_title' => 'Test Post' ] );

        $query = '{
            posts {
                nodes {
                    id
                    title
                }
            }
        }';

        $response = $I->getRequest( $query );
        $expected = [
            $I->expectNode(
                'posts.nodes',
                [
                    $I->expectField( 'id', $I->asRelayId( 'post', $post_id ) ),
                    $I->expectField( 'title', 'Test Post' )
                ]
            )
        ];

        $I->assertQuerySuccessful( $response, $expected );
    }

    public function testPostRequest( FunctionalTester $I, $scenario ) {
        $I->wantTo( 'send a POST request to the GraphQL endpoint and return a response' );

        $query = 'mutation ( $input: CreatePostInput! ) {
            createPost( input: $input ) {
                post {
                    id
                    title
                }
            }
        }';

        $variables = [
            'input' => [
                'title' => 'Test Post',
                'content' => 'Test Post content',
                'slug' => 'test-post',
                'status' => 'PUBLISH'
            ]
        ];

        $response = $I->postRequest( $query, $variables );
        $expected = [
            $I->expectObject(
                'createPost.post',
                [
                    $I->expectField( 'id', Signal::NOT_NULL ),
                    $I->expectField( 'title', 'Test Post' )
                ]
            )
        ];

        $I->assertQuerySuccessful( $response, $expected );
    }

    public function testBatchRequest( FunctionalTester $I, $scenario ) {
        $I->wantTo( 'send a batch request to the GraphQL endpoint and return a response' );

        $I->haveManyPostsInDatabase(20);

        $operations = [
            [
                'query'     => 'mutation ( $input: CreatePostInput! ) {
                    createPost( input: $input ) {
                        post {
                            id
                            title
                            slug
                            status
                        }
                    }
                }',
                'variables' => [
                    'input' => [
                        'title' => 'Wowwy Zowwy 1',
                        'content' => 'Wowwy Zowwy 1 content',
                        'slug' => 'wowwy-zowwy-1',
                        'status' => 'PUBLISH',
                    ]
                ]
            ],
            [
                'query'     => 'mutation ( $input: CreatePostInput! ) {
                    createPost( input: $input ) {
                        post {
                            id
                            title
                        }
                    }
                }',
                'variables' => [
                    'input' => [
                        'title' => 'Wowwy Zowwy 2',
                        'content' => 'Wowwy Zowwy 2 content',
                        'slug' => 'wowwy-zowwy-2',
                        'status' => 'PUBLISH',
                    ]
                ]
            ],
            [
                'query' => '{
                    posts(first: 2 where: { search: "Wowwy Zowwy" } ) {
                        nodes {
                            id
                            title
                        }
                    }
                }'
            ]
        ];

        $responses = $I->batchRequest( $operations );

        $I->assertQuerySuccessful(
            $responses[0],
            [
                $I->expectObject(
                    'createPost.post',
                    [
                        $I->expectField( 'id', Signal::NOT_NULL ),
                        $I->expectField( 'title', 'Wowwy Zowwy 1' )
                    ]
                )
            ]
        );
    
        $I->assertQuerySuccessful(
            $responses[1],
            [
                $I->expectObject(
                    'createPost.post',
                    [
                        $I->expectField( 'id', Signal::NOT_NULL ),
                        $I->expectField( 'title', 'Wowwy Zowwy 2' )
                    ]
                )
            ]
        );

        $I->assertQuerySuccessful(
            $responses[2],
            [
                $I->expectNode(
                    'posts.nodes',
                    [
                        $I->expectField( 'id', Signal::NOT_NULL ),
                        $I->expectField( 'title', 'Wowwy Zowwy 1' )
                    ],
                ),
                $I->expectNode(
                    'posts.nodes',
                    [
                        $I->expectField( 'id', Signal::NOT_NULL ),
                        $I->expectField( 'title', 'Wowwy Zowwy 2' )
                    ]
                )
            ]
        );
    }

    public function testConcurrentRequests( FunctionalTester $I, $scenario ) {
        $I->wantTo( 'send concurrent requests to the GraphQL endpoint and return a response' );

        $I->haveManyPostsInDatabase(20);

        $operations = [
            [
                'query'     => 'mutation ( $input: CreatePostInput! ) {
                    createPost( input: $input ) {
                        post {
                            id
                            title
                            slug
                            status
                        }
                    }
                }',
                'variables' => [
                    'input' => [
                        'title' => 'Scream 1',
                        'content' => 'Scream 1 content',
                        'slug' => 'scream-1',
                        'status' => 'PUBLISH',
                    ]
                ]
            ],
            [
                'query' => '{
                    posts(where: { search: "Scream" }) {
                        nodes {
                            id
                            title
                        }
                    }
                }'
            ],
            [
                'query'     => 'mutation ( $input: CreatePostInput! ) {
                    createPost( input: $input ) {
                        post {
                            id
                            title
                        }
                    }
                }',
                'variables' => [
                    'input' => [
                        'title' => 'Scream 2',
                        'content' => 'Scream 2 content',
                        'slug' => 'scream-2',
                        'status' => 'PUBLISH',
                    ]
                ]
            ],
            [
                'query' => '{
                    posts(where: { search: "Scream" }) {
                        nodes {
                            id
                            title
                        }
                    }
                }'
            ],
        ];

        $responses = $I->concurrentRequests( $operations, [], 0 );

        $I->assertQuerySuccessful(
            $responses[0],
            [
                $I->expectObject(
                    'createPost.post',
                    [
                        $I->expectField( 'id', Signal::NOT_NULL ),
                        $I->expectField( 'title', 'Scream 1' )
                    ]
                )
            ]
        );

        $I->assertQuerySuccessful(
            $responses[1],
            [
                $I->expectField(
                    'posts.nodes',
                    Signal::IS_FALSY
                )
            ]
        );

        $I->assertQuerySuccessful(
            $responses[2],
            [
                $I->expectObject(
                    'createPost.post',
                    [
                        $I->expectField( 'id', Signal::NOT_NULL ),
                        $I->expectField( 'title', 'Scream 2' )
                    ]
                )
            ]
        );

        $I->assertQuerySuccessful(
            $responses[3],
            [
                $I->expectField(
                    'posts.nodes',
                    Signal::IS_FALSY
                )
            ]
        );
    }

    public function testRequestOptions( FunctionalTester $I ) {
        $I->wantTo( 'send a request to the GraphQL endpoint with custom options' );

        $query = 'mutation ( $input: CreatePostInput! ) {
            createPost( input: $input ) {
                post {
                    id
                    title
                    slug
                    status
                }
            }
        }';

        $variables = [
            'input' => [
                'title' => 'Test Post',
                'content' => 'Test Post content',
                'slug' => 'test-post',
                'status' => 'DRAFT'
            ]
        ];


        $response = $I->postRequest( $query, $variables, [ 'suppress_mod_token' => true ] );

        $I->assertQueryError( $response );

        $response = $I->postRequest( $query, $variables );

        $I->assertQuerySuccessful( $response [ $I->expectField( 'createPost.post.id', Signal::NOT_NULL ) ] );

        $id = $I->lodashGet( $response, 'data.createPost.post.id' );

        $query = '
            query ( $id: ID! ) {
                post( id: $id ) {
                    id
                }
            }
        ';

        $response = $I->postRequest( $query, [ 'id' => $id ], [ 'suppress_mod_token' => true ] );

        $I->assertQueryError( $response );
    }
}
