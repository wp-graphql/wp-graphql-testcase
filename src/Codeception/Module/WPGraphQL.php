<?php

namespace Tests\WPGraphQL\Codeception\Module;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\TestInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Tests\WPGraphQL\Logger\CodeceptLogger as Signal;

/**
 * WPGraphQL module for Codeception
 */
class WPGraphQL extends Module {
    /**
     * @var array<string, string>
     */
    protected array $config = [
        'endpoint'    => '',
        'auth_header' => '',
    ];

    protected array $requiredFields = [
        'endpoint',
    ];

    /** @var \GuzzleHttp\Client */
    private $client = null;

    /** @var \Tests\WPGraphQL\Logger\CodeceptLogger */
    private $logger = null;

    protected static $cookieJar = null;

    private function getHeaders() {
        $headers = [ 'Content-Type' => 'application/json' ];
        $auth_header = $this->config['auth_header'];
        if ( ! empty( $auth_header ) ) {
            $headers['Authorization'] = $auth_header;
        }

        return $headers;
    }

    /**
     * Initializes the module
     *
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
     * @return void
     */
    public function _before( TestInterface $test ) {
        $endpoint = $this->config['endpoint'];
        if ( empty( $endpoint ) ) {
            throw new ModuleException( $this, 'Invalid endpoint.' );
        }
        $this->client    = new Client(
            [
                'base_uri' => $endpoint,
                'timeout'  => 300,
            ]
        );
        $this->logger    = new Signal();
        self::$cookieJar = new CookieJar;
    }

    /**
     * Parses the request options and return a formatted request options
     *
     * @param array $selected_options  The request options to parse.
     *
     * @return array
     */
    protected function parseRequestOptions( array $selected_options ) {
        $default_options = [ 'headers' => $this->getHeaders() ];
        if ( empty( $selected_options ) ) {
            return $default_options;
        }

        $request_options = $default_options;
        foreach( $selected_options as $key => $value ) {
            if ( in_array( $key, [ 'headers', 'suppress_mod_token', 'use_cookies' ] ) ) {
                continue;
            }

            $request_options[ $key ] = $value;
        }

        if ( isset( $selected_options['suppress_mod_token'] )
            && true === $selected_options['suppress_mod_token']
            && isset( $request_options['headers']['Authorization'] ) ) {
            unset( $request_options['headers']['Authorization'] );
        }

        if ( isset( $selected_options['use_cookies'] ) && true === $selected_options['use_cookies'] ) {
            $request_options['cookies'] = self::$cookieJar;
        }
        
        if ( ! empty( $selected_options['headers'] ) && is_array( $selected_options['headers'] ) ) {
            $request_options['headers'] = array_merge( $request_options['headers'], $selected_options['headers'] );
        }

        return $request_options;
    }

    /**
     * Sends a GET request to the GraphQL endpoint and returns a response
     *
     * @param string $query             The GraphQL query to send.
     * @param array  $selected_options  Selected options to control various aspects of a request.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint | Invalid query.
     * 
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getRawRequest( string $query, array $selected_options = [] ) {
        $endpoint = $this->config['endpoint'];
        if ( empty( $endpoint ) ) {
            throw new ModuleException( $this, 'Invalid endpoint.' );
        }

        if ( empty( $query ) ) {
            throw new ModuleException( $this, 'Invalid query.' );
        }

        $request_options = $this->parseRequestOptions( $selected_options );

        $this->logger->logData( "GET request to {$endpoint} with query: \n{$query}\n" );
        $this->logger->logData( "With request options: \n" . json_encode( $request_options, JSON_PRETTY_PRINT ) ."\n" );

        $response = $this->client->request( 'GET', "?query={$query}", $request_options );

        if ( empty( $response ) ) {
            throw new ModuleException( $this, 'Invalid response.' );
        }

        $this->logger->logData( Psr7\Message::toString( $response ) );

        return $response;
    }

    /**
     * Sends a GET request to the GraphQL endpoint and returns the query results
     *
     * @param string $query             The GraphQL query to send.
     * @param array  $selected_options  Selected options to control various aspects of a request.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid response | Empty response.
     * 
     * @return array
     */
    public function getRequest( string $query, array $selected_options = [] ) {
        $response = $this->getRawRequest( $query, $selected_options );
        if ( $response->getStatusCode() !== 200 ) {
            throw new ModuleException( $this, 'Invalid response.' );
        }

        if ( empty( $response->getBody() ) ) {
            throw new ModuleException( $this, 'Empty response.' );
        }

        $queryResults = json_decode( $response->getBody(), true );

        return $queryResults;
    }

    /**
	 * Sends a POST request to the GraphQL endpoint and return a response
	 *
	 * @param string $query             The GraphQL query to send.
	 * @param ?array $variables         The variables to send with the query.
	 * @param ?array $selected_options  Selected options to control various aspects of a request.
	 *
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
	 * @return \Psr\Http\Message\ResponseInterface
	 */
    public function postRawRequest( $query, $variables = [], $selected_options = [] ) {
        $endpoint = $this->config['endpoint'];
        if ( empty( $endpoint ) ) {
            throw new ModuleException( $this, 'Invalid endpoint.' );
        }

        if ( empty( $query ) ) {
            throw new ModuleException( $this, 'Invalid query.' );
        }

        if ( ! is_array( $variables ) ) {
            throw new ModuleException( $this, 'Invalid variables.' );
        }

        $request_options = $this->parseRequestOptions( $selected_options );

        $this->logger->logData( "POST request to {$endpoint} with query: \n{$query}\n" );
        if ( ! empty( $variables ) ) {
            $this->logger->logData( "With variables: \n" . json_encode( $variables, JSON_PRETTY_PRINT ) . "\n" );
        }
        $this->logger->logData( "With request options: \n" . json_encode( $request_options, JSON_PRETTY_PRINT ) ."\n" );

        $response = $this->client->request(
            'POST',
            '',
            array_merge( $request_options, [ 'body' => json_encode( compact( 'query', 'variables' ) ) ] )
        );

        if ( empty( $response ) ) {
            throw new ModuleException( $this, 'Invalid response.' );
        }

        $this->logger->logData( Psr7\Message::toString( $response ) );

        return $response;
    }

    /**
	 * Sends POST request to the GraphQL endpoint and returns the query results
	 *
	 * @param string $query             The GraphQL query to send.
	 * @param ?array $variables         The variables to send with the query.
	 * @param ?array $selected_options  Selected options to control various aspects of a request.
	 *
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
	 * @return array
	 */
	public function postRequest( $query, $variables = [], $selected_options = [] ) {
        $response = $this->postRawRequest( $query, $variables, $selected_options );
        if ( $response->getStatusCode() !== 200 ) {
            throw new ModuleException( $this, 'Invalid response.' );
        }

        if ( empty( $response->getBody() ) ) {
            throw new ModuleException( $this, 'Empty response.' );
        }

        $queryResults = json_decode( $response->getBody(), true );

        return $queryResults;
	}

    /**
     * Sends a batch query request to the GraphQL endpoint and return a response
     *
     * @param object{query: string, variables: ?array}[] $operations        An array of operations to send.
     * @param array                                      $selected_options  Selected options to control various aspects of a request.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function batchRawRequest( $operations, $selected_options = [] ) {
        $endpoint = $this->config['endpoint'];
        if ( empty( $endpoint ) ) {
            throw new ModuleException( $this, 'Invalid endpoint.' );
        }

        if ( empty( $operations ) ) {
            throw new ModuleException( $this, 'Invalid query.' );
        }

        $request_options = $this->parseRequestOptions( $selected_options );

        $this->logger->logData( "POST request to {$endpoint} with operations: \n" . json_encode( $operations, JSON_PRETTY_PRINT ) . "\n" );

        $this->logger->logData( "With request options: \n" . json_encode( $request_options, JSON_PRETTY_PRINT ) ."\n" );

        $response = $this->client->request(
            'POST',
            '',
            array_merge( $request_options, [ 'body' => json_encode( $operations ) ] )
        );

        if ( empty( $response ) ) {
            throw new ModuleException( $this, 'Invalid response.' );
        }

        $this->logger->logData( Psr7\Message::toString( $response ) );

        return $response;
    }

    /**
     * Sends a batch query request to the GraphQL endpoint and returns the query results
     *
     * @param object{query: string, variables: ?array}[] $operations        An array of operations to send.
     * @param array                                      $selected_options  Selected options to control various aspects of a request.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
     * @return array
     */
    public function batchRequest( $operations, $selected_options = [] ) {
        $response = $this->batchRawRequest( $operations, $selected_options );
        if ( $response->getStatusCode() !== 200 ) {
            throw new ModuleException( $this, 'Invalid response.' );
        }

        if ( empty( $response->getBody() ) ) {
            throw new ModuleException( $this, 'Empty response.' );
        }

        $queryResults = json_decode( $response->getBody(), true );

        return $queryResults;
    }

    /**
     * Sends a concurrent requests to the GraphQL endpoint and returns a response
     *
     * @param {query: string, variables: ?array}[] $operations        An array of operations to send.
     * @param array                                $selected_options  Selected options to control various aspects of a request.
     * @param int                                  $stagger           The time in milliseconds to stagger requests.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     *
     * @return \Psr\Http\Message\ResponseInterface[]
     */
    public function concurrentRawRequests( $operations, $selected_options = [], $stagger = 800 ) {
        $endpoint = $this->config['endpoint'];
        if ( empty( $endpoint ) ) {
            throw new ModuleException( $this, 'Invalid endpoint.' );
        }

        if ( empty( $operations ) ) {
            throw new ModuleException( $this, 'Invalid query.' );
        }

        $request_options = $this->parseRequestOptions( $selected_options );

        $this->logger->logData( "A series of POST requests to {$endpoint} with executed in the following order: \n" . json_encode( $operations, JSON_PRETTY_PRINT ) . "\n" );

        $this->logger->logData( "With request options: \n" . json_encode( $request_options, JSON_PRETTY_PRINT ) ."\n" );

        $client = $this->client;
        $logger = $this->logger;
        
        $iterator = static function ( $ops, $st, $ro ) use ( $client, $logger ) {
            $ops_started = [];
			foreach ( $ops as $index => $op ) {
				yield static function () use ( $st, $index, $ro, $op, $client, $logger, $ops_started ) {
					$body      = json_encode( $op );
					$delay     = $st * $index;
					return $client->postAsync(
                        '',
                        array_merge(
                            $ro,
                            [
                                'body'     => $body,
                                'delay'    => $delay,
                                'progress' => static function ( $downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes ) use ( $index, &$ops_started, $logger ) {
                                    // Log the connection time
                                    if (
                                        $uploadTotal === $uploadedBytes
                                        && 0 === $downloadTotal
                                        && ! isset( $ops_started[ $index ] ) ) {
                                        $logger->logData(
                                            "Session mutation request $index connected @ "
                                                . date_format( date_create('now'), 'Y-m-d H:i:s:v' )
                                        );
                                        $ops_started[ $index ] = true;
                                    }
                                }
                            ]
                        )
                    );
				};
			}
		};
        
        $ops_completed = [];
        $pool = new \GuzzleHttp\Pool(
			$client,
			$iterator( $operations, $stagger, $request_options ),
			[
				'concurrency' => count( $operations ),
				'fulfilled'   => static function ( $response, $index ) use ( $logger, &$ops_completed ) {
                    $logger->logData(
                        "Finished session mutation request $index @ "
                            . date_format( date_create('now'), 'Y-m-d H:i:s:v' ) . "\n"
                    );
                    $logger->logData( Psr7\Message::toString( $response ) );
					$ops_completed[ $index ] = $response;
				},
			]
		);

		$promise = $pool->promise();

        $promise->wait();

        ksort( $ops_completed );

        return $ops_completed;
    }

    /**
     * Sends a concurrent requests to the GraphQL endpoint and returns a response
     *
     * @param {'query': string, 'variables': array} $operations        An array of operations to send.
     * @param array                                 $selected_options  Selected options to control various aspects of a request.
     * @param int                                   $stagger           The time in milliseconds to stagger requests.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     *
     * @return array
     */
    public function concurrentRequests( $operations, $selected_options = [], $stagger = 800 ) {
        $responses = $this->concurrentRawRequests( $operations, $selected_options, $stagger );
        if ( empty( $responses ) ) {
            throw new ModuleException( $this, 'Invalid response.' );
        }

        $queryResults = [];
        foreach ( $responses as $response ) {
            if ( $response->getStatusCode() !== 200 ) {
                throw new ModuleException( $this, 'Invalid response.' );
            }

            if ( empty( $response->getBody() ) ) {
                throw new ModuleException( $this, 'Empty response.' );
            }

            $queryResults[] = json_decode( $response->getBody(), true );
        }

        return $queryResults;
    }
}