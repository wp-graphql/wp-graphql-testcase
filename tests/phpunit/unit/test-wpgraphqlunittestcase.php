<?php

class WPGraphQLUnitTestCaseTest extends \Tests\WPGraphQL\TestCase\WPGraphQLUnitTestCase
{

    /** @test */
    public function test_AssertQuerySuccessful() {
        // Create posts for later use.
        $post_id          = self::factory()->post->create();
        $unneeded_post_id = self::factory()->post->create();

        // GraphQL query and variables.
        $query     = '
            query ($id: ID!) {
                post( id: $id ) {
                    id
                    databaseId
                }
                posts {
                    nodes {
                        id
                    }
                }
            }
        ';
        $variables = array(
            'id' => $this->toRelayId( 'post', $post_id ),
        );

        // Execute query and get response.
        $response = $this->graphql( compact( 'query', 'variables' ) );

        // Expected data.
        $expected = array(
            $this->expectedObject( 'post.id', $this->toRelayId( 'post', $post_id ) ),
            $this->expectedObject( 'post.databaseId', $post_id ),
            $this->expectedNode(
                'posts.nodes',
                array( 'id' => $this->toRelayId( 'post', $post_id ) )
            )
        );

        // Assert query successful.
        $this->assertQuerySuccessful( $response, $expected );
    }
}
