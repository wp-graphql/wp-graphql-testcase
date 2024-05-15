<?php

namespace Tests\WPGraphQL\Codeception\Module;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\TestInterface;

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
        $this->client = new \GuzzleHttp\Client(
            [
                'base_uri' => $endpoint,
                'timeout'  => 300,
            ]
        );
        $this->logger = new \Tests\WPGraphQL\Logger\CodeceptLogger();
    }

    /**
     * Parses the request options and return a formatted request options
     *
     * @param array $selected_options  The request options to parse.
     * @return void
     */
    protected function parseRequestOptions( array $selected_options ) {
        $default_options = [ 'headers' => $this->getHeaders() ];
        if ( empty( $selected_options ) ) {
            return $default_options;
        }

        $request_options = $default_options;
        foreach( $selected_options as $key => $value ) {
            if ( in_array( $key, [ 'headers', 'suppress_mod_token' ] ) ) {
                continue;
            }

            $request_options[ $key ] = $value;
        }

        if ( isset( $selected_options['suppress_mod_token'] )
            && true === $selected_options['suppress_mod_token']
            && isset( $request_options['headers']['Authorization'] ) ) {
            unset( $request_options['headers']['Authorization'] );
        }
        
        if ( ! empty( $selected_options['headers'] ) && is_array( $selected_options['headers'] ) ) {
            $request_options['headers'] = array_merge( $request_options['headers'], $selected_options['headers'] );
        }

        \codecept_debug( $request_options );

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
     * @return array
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

        $this->logger->logData( $response->getHeaders() );
        $this->logger->logData( (string) $response->getBody() );

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
	 * @param string      $query             The GraphQL query to send.
	 * @param array       $variables         The variables to send with the query.
	 * @param string|null $selected_options  Selected options to control various aspects of a request.
	 *
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
	 * @return array
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

        $this->logger->logData( $response->getHeaders() );
        $this->logger->logData( (string) $response->getBody() );

        return $response;
    }

    /**
	 * Sends POST request to the GraphQL endpoint and returns the query results
	 *
	 * @param string      $query             The GraphQL query to send.
	 * @param array       $variables         The variables to send with the query.
	 * @param string|null $selected_options  Selected options to control various aspects of a request.
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
     * @param object{'query': string, 'variables': array} $operations        An array of operations to send.
     * @param array                                       $selected_options  Selected options to control various aspects of a request.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
     * @return array
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

        $this->logger->logData( $response->getHeaders() );
        $this->logger->logData( (string) $response->getBody() );

        return $response;
    }

    /**
     * Sends a batch query request to the GraphQL endpoint and returns the query results
     *
     * @param object{'query': string, 'variables': array} $operations        An array of operations to send.
     * @param array                                       $selected_options  Selected options to control various aspects of a request.
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
     * @param {'query': string, 'variables': array} $operations        An array of operations to send.
     * @param array                                 $selected_options  Selected options to control various aspects of a request.
     * @param int                                   $stagger           The time in milliseconds to stagger requests.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     *
     * @return array
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

        $promises = [];
        foreach ( $operations as $index => $operation ) {
            $body      = json_encode( $operation );
            $delay     = $stagger * ($index + 1);
            $connected = false;
            $progress  = function ( $downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes ) use ( $index, &$connected ) {
                if ( $uploadTotal === $uploadedBytes && 0 === $downloadTotal && ! $connected ) {
                    $this->logger->logData(
                        "Session mutation request $index connected @ "
                            . date( 'Y-m-d H:i:s', time() )
                    );
                    $connected = true;
                }
            };
            $promises[] = $this->client->postAsync(
                '',
                array_merge( $request_options, compact( 'body', 'progress', 'delay' ) ),
            );
        }

        $responses = \GuzzleHttp\Promise\Utils::unwrap( $promises );
        \GuzzleHttp\Promise\Utils::settle( $promises )->wait();

        return $responses;
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

            $this->logger->logData( $response->getHeaders() );
            $this->logger->logData( (string) $response->getBody() );

            $queryResults[] = json_decode( $response->getBody(), true );
        }

        return $queryResults;
    }
}