<?php

namespace Chemem\Fauxton\Tests;

use \Eris\Generator;
use \Chemem\Fauxton\Config\State;
use \Jfalque\HttpMock\Server;
use \GuzzleHttp\Psr7\{Response, Request};
use function \Chemem\Fauxton\Http\{fetch, urlGenerator, credentialsFromFile};
use function \Chemem\Bingo\Functional\Algorithms\{concat, pluck, extend, addKeys, toException, partialRight, arrayKeysExist};

class HttpTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    public function mockFetch(string $action, string $method, string $code, array $urlParams, array $response)
    {
        return credentialsFromFile()
            ->map(
                function ($credentials) use ($code, $action, $method, $urlParams, $response) {
                    $uri = urlGenerator($action, $credentials, $urlParams);
                    $response = (new Server)
                        ->whenUri($uri)
                            ->return(
                                new Response(
                                    $code, 
                                    pluck(State::COUCH_CURLOPTS_DEFAULT, \CURLOPT_HTTPHEADER),
                                    json_encode($response)
                                )
                            )
                            ->end();

                    return $response->handle(new Request($method, $uri))
                        ->getBody()
                        ->getContents();
                }
            );
    }

    public function testFetchFunctionMakesRequestToRemoteResource()
    {
        $this->limitTo(5)
            ->forAll(
                Generator\elements(
                    'https://jsonplaceholder.typicode.com/posts/1',
                    'https://reqres.in/api/unknown/2'
                )
            )
            ->then(
                function ($url) {
                    $action = fetch($url);
                    $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $action);
                    $this->assertInternalType('array', $action->exec());
                }
            );
    }

    public function testUrlGeneratorCreatesCouchDbSpecificUrisForLocalInstallation()
    {
        $this->forAll(
            Generator\elements('uuids', 'dbgen'),
            Generator\elements(['{count}' => 12], ['{db}' => 'dummy_data'])
        )
            ->then(
                function ($endpoint, $param) {
                    $url = urlGenerator($endpoint, ['user', 'pwd', true], $param);

                    $this->assertInternalType('string', $url);
                    $this->assertTrue(arrayKeysExist(parse_url($url), 'scheme', 'host', 'port', 'path'));
                }
            );
    }

    public function testUrlGeneratorCreatesCouchDbSpecificUrisForCloudant()
    {
        $db = ['{db}' => 'dummy_data'];
        $this->forAll(
            Generator\elements('bulkDocs', 'insertSingle'),
            Generator\elements($db, extend($db, ['{docId}' => 'abc12345']))
        )
            ->then(
                function ($endpoint, $param) {
                    $url = urlGenerator($endpoint, ['xxx-bluemix', 'abc123', false], $param);

                    $this->assertInternalType('string', $url);
                    $this->assertTrue(arrayKeysExist(parse_url($url), 'user', 'pass', 'scheme', 'host', 'path'));
                }
            );
    }

    public function testDatabaseFunctionMock()
    {
        $this->forAll(
            Generator\elements('PUT', 'GET', 'DELETE', 'PATCH'),
            Generator\elements(
                ['ok' => true],
                [
                    'committed_update_seq' => 292786,
                    'compact_running' => false,
                    'data_size' => 65031503,
                    'db_name' => 'receipts',
                    'disk_format_version' => 6,
                    'disk_size' => 137433211,
                    'doc_count' => 6146,
                    'doc_del_count' => 64637,
                    'instance_start_time' => '1376269325408900',
                    'purge_seq' => 0,
                    'update_seq' => 292786
                ],
                ['ok' => true],
                [
                    'code' => 400,
                    'error' => 'Bad Request'
                ]
            ),
            Generator\elements(200, 200, 200, 400)
        )
            ->then(
                function ($method, $response, $code) {
                    $database = $this->mockFetch('dbgen', $method, $code, ['{db}' => 'blog_posts'], $response);
                    
                    $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $database);
                    $this->assertInternalType('array', $database->flatMap(partialRight('json_decode', true)));
                }
            );
    }

    public function testUuidsFunctionMock()
    {
        $this->forAll(
            Generator\choose(1, 3),
            Generator\elements(
                ['uuids' => ['75480ca477454894678e22eec6002413']],
                [
                    'uuids' => [
                        '75480ca477454894678e22eec600250b',
                        '75480ca477454894678e22eec6002c41'
                    ]
                ],
                [
                    'uuids' => [
                        '75480ca477454894678e22eec6003b90',
                        '75480ca477454894678e22eec6003fca',
                        '75480ca477454894678e22eec6004bef',
                    ]
                ]    
            )
        )
            ->then(
                function ($count, $response) {
                    $uuids = $this->mockFetch('uuids', 'GET', 200, ['{count}' => $count], $response);

                    $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $uuids);
                    $this->assertArrayHasKey('uuids', $uuids->flatMap(partialRight('json_decode', true)));
                }
            );
    }

    public function testAllDatabasesFunctionMock()
    {
        $allDbs = $this->mockFetch('allDbs', 'GET', 200, [], ['_users', 'contacts', 'docs', 'invoices', 'locations']);

        $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $allDbs);
        $this->assertInternalType('array', $allDbs->flatMap(partialRight('json_decode', true)));
    }
}
