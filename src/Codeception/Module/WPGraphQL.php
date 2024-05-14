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
     * Sends a GET request to the GraphQL endpoint and returns a response
     *
     * @param string $query           The GraphQL query to send.
     * @param array  $request_headers The headers to send with the request.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint | Invalid query.
     * 
     * @return array
     */
    public function getRawRequest( $query, $request_headers = [] ) {
        $endpoint = $this->config['endpoint'];
        if ( empty( $endpoint ) ) {
            throw new ModuleException( $this, 'Invalid endpoint.' );
        }

        if ( empty( $query ) ) {
            throw new ModuleException( $this, 'Invalid query.' );
        }

        $headers = array_merge(
            $this->getHeaders(),
            $request_headers
        );

        $this->logger->logData( "GET request to {$endpoint} with query: {$query}" );
        $this->logger->logData( "Headers: " . json_encode( $headers ) );

        $response = $this->client->request( 'GET', "?query={$query}", [ 'headers' => $headers ] );

        if ( empty( $response ) ) {
            throw new ModuleException( $this, 'Invalid response.' );
        }

        $this->logger->logData( $response->getHeaders() );
        $this->logger->logData( $response->getBody() );

        return $response;
    }

    /**
     * Sends a GET request to the GraphQL endpoint and returns the query results
     *
     * @param string $query           The GraphQL query to send.
     * @param array  $request_headers The headers to send with the request.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid response | Empty response.
     * 
     * @return array
     */
    public function getRequest( $query, $request_headers = [] ) {
        $response = $this->getRawRequest( $query, $request_headers );
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
	 * @param string      $query            The GraphQL query to send.
	 * @param array       $variables        The variables to send with the query.
	 * @param string|null $request_headers  The headers to send with the request.
	 *
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
	 * @return array
	 */
    public function postRawRequest( $query, $variables = [], $request_headers = [] ) {
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

        $headers = array_merge(
            $this->getHeaders(),
            $request_headers
        );

        $this->logger->logData( "GET request to {$endpoint} with query: {$query}" );
        $this->logger->logData( "Variables: " . json_encode( $variables ) );
        $this->logger->logData( "Headers: " . json_encode( $headers ) );

        $response = $this->client->request(
            'POST',
            '',
            [
                'headers' => $headers,
                'body'    => json_encode( [ 'query' => $query, 'variables' => $variables ] ),
            ]
        );

        if ( empty( $response ) ) {
            throw new ModuleException( $this, 'Invalid response.' );
        }

        $this->logger->logData( $response->getHeaders() );
        $this->logger->logData( $response->getBody() );

        return $response;
    }

    /**
	 * Sends POST request to the GraphQL endpoint and returns the query results
	 *
	 * @param string      $query            The GraphQL query to send.
	 * @param array       $variables        The variables to send with the query.
	 * @param string|null $request_headers  The headers to send with the request.
	 *
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
	 * @return array
	 */
	public function postRequest( $query, $variables = [], $request_headers = [] ) {
        $response = $this->postRawRequest( $query, $variables, $request_headers );
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
     * @param object{'query': string, 'variables': array} $operations       An array of operations to send.
     * @param array                                       $request_headers  An array of headers to send with the request.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
     * @return array
     */
    public function batchRawRequest( $operations, $request_headers = [] ) {
        $endpoint = $this->config['endpoint'];
        if ( empty( $endpoint ) ) {
            throw new ModuleException( $this, 'Invalid endpoint.' );
        }

        if ( empty( $operations ) ) {
            throw new ModuleException( $this, 'Invalid query.' );
        }

        $headers = array_merge(
            $this->getHeaders(),
            $request_headers
        );

        $response = $this->client->request(
            'POST',
            '',
            [
                'headers' => $headers,
                'body'    => json_encode( $operations ),
            ]
        );

        if ( empty( $response ) ) {
            throw new ModuleException( $this, 'Invalid response.' );
        }

        $this->logger->logData( $response->getHeaders() );
        $this->logger->logData( json_decode( $response->getBody() ) );

        return $response;
    }

    /**
     * Sends a batch query request to the GraphQL endpoint and returns the query results
     *
     * @param object{'query': string, 'variables': array} $operations       An array of operations to send.
     * @param array                                       $request_headers  An array of headers to send with the request.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     * 
     * @return array
     */
    public function batchRequest( $operations, $request_headers = [] ) {
        $response = $this->batchRawRequest( $operations, $request_headers );
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
     * @param {'query': string, 'variables': array} $operations      An array of operations to send.
     * @param array                                 $request_headers An array of headers to send with the request.
     * @param int                                   $stagger         The time in milliseconds to stagger requests.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     *
     * @return array
     */
    public function concurrentRawRequests( $operations, $request_headers = [], $stagger = 800 ) {
        $endpoint = $this->config['endpoint'];
        if ( empty( $endpoint ) ) {
            throw new ModuleException( $this, 'Invalid endpoint.' );
        }

        if ( empty( $operations ) ) {
            throw new ModuleException( $this, 'Invalid query.' );
        }

        $headers = array_merge(
            $this->getHeaders(),
            $request_headers
        );

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
                [
                    'body'     => $body,
                    'delay'    => $delay,
                    'progress' => $progress,
                    'headers'  => $headers,
                ]
            );
        }

        $responses = \GuzzleHttp\Promise\Utils::unwrap( $promises );
        \GuzzleHttp\Promise\Utils::settle( $promises )->wait();

        return $responses;
    }

    /**
     * Sends a concurrent requests to the GraphQL endpoint and returns a response
     *
     * @param {'query': string, 'variables': array} $operations      An array of operations to send.
     * @param array                                 $request_headers An array of headers to send with the request.
     * @param int                                   $stagger         The time in milliseconds to stagger requests.
     * 
     * @throws \Codeception\Exception\ModuleException  Invalid endpoint.
     *
     * @return array
     */
    public function concurrentRequests( $operations, $request_headers = [], $stagger = 800 ) {
        $responses = $this->concurrentRawRequests( $operations, $request_headers, $stagger );
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
            $this->logger->logData( json_decode( $response->getBody() ) );

            $queryResults[] = json_decode( $response->getBody(), true );
        }

        return $queryResults;
    }
}