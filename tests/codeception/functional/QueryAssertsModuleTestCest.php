<?php


namespace Functional;

use \FunctionalTester;
use Tests\WPGraphQL\Logger\CodeceptLogger as Signal;

class QueryAssertsModuleTestCest {
    public function testAssertResponseIdValid( FunctionalTester $I ) {
        $data = [
            'data' => [
                'post' => [
                    'id' => 'cG9zdDox'
                ]
            ],
        ];

        $I->assertResponseIsValid( $data );

        $data = [
            'errors' => [
                [ 'message' => 'Invalid ID' ]
            ],
            'data' => null,
        ];

        $I->assertResponseIsValid( $data, false );
    }

    public function testAssertQuerySuccessful( FunctionalTester $I ) {
        $data = [
            'data' => [
                'post' => [
                    'id' => 'cG9zdDox'
                ]
            ],
        ];

        $I->assertQuerySuccessful( $data );

        $expected = [
            $I->expectNode(
                'post',
                [
                    $I->expectField( 'id', $I->asRelayId( 'post', 1 ) )
                ]
            )
        ];

        $I->assertQuerySuccessful( $data, $expected );
    }

    public function testAssertQueryError( FunctionalTester $I ) {
        $data = [
            'errors' => [
                'message'    => "Internal server error",
                'extensions' => [
                    'category' => 'internal',
                ],
                'locations'  => [
                    [
                        'line'   => 2,
                        'column' => 3,
                    ],
                ],
                'path'       => [
                    'post',
                ],
            ],
            'data' => [ 'post' => null ],
        ];

        $I->assertQueryError( $data );
    }
}
