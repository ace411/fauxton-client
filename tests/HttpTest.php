<?php

namespace Chemem\Fauxton\Tests;

use \Eris\Generator;
use \Chemem\Fauxton\Config\State;
use \Jfalque\HttpMock\Server;
use \GuzzleHttp\Psr7\{Response, Request};
use \Chemem\Fauxton\Http;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Functors\Monads as M;

class HttpTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    public function mockHttpFetch($request, $response) : IO
    {
        //mock request and response
        return Http\execute(function ($contents) use ($request, $response) {
            //$credentials = Http\credentials($contents);
            $mockHttp = function ($url) use ($response) : IO {
                return M\bind(function ($server) use ($url, $response) {
                    list($code, $method, $headers, $resp) = $response;
                    $obj = $server
                        ->whenUri($url)
                        ->return (new Response($code, $headers, $resp)) //code, headers, json_response
                        ->end();

                    $http = $obj->handle(new Request($method, $url))
                        ->getBody()
                        ->getContents();
                    return IO\IO($http);
                }, IO\IO(new Server));
            };
            $http = A\compose(A\partial(Http\urlGenerate, Http\credentials($contents)), $mockHttp);
            return $http($request); //request = urlParams ['dbgen' => ['{db}' => 'dbname']]
        });
    }

    public function testUuidsFunctionOutputsUniqueIdentifiers()
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
            ->then(function ($count, $response) {
                $resp = \json_encode($response);
                $uuids = $this->mockHttpFetch(
                    ['uuids' => ['{count}' => $count]],
                    [200, 'GET', State::COUCH_REQHEADERS, $resp]
                );

                $this->assertInstanceOf(IO::class, $uuids);
                $this->assertInternalType('string', $uuids->exec());
                $this->assertEquals($resp, $uuids->exec());
            });
    }

    public function testCredentialsFunctionExtractsCouchDbAuthCredentials()
    {
        $this->forAll(Generator\constant(Http\path(State::CLIENT_CONFIG_FILE)))
            ->then(function ($path) {
                $cred = M\mcompose(function ($contents) {
                    $res = A\compose(Http\credentials, IO\IO);
                    return $res($contents);
                }, IO\readFile);

                $credentials = $cred(IO\IO($path));

                $this->assertInternalType('array', $credentials->exec());
                $this->assertTrue(count($credentials->exec()) == 3);
            });
    }

    public function testUrlGenerateFunctionOutputsCouchDbAccessUrl()
    {
        $this->forAll(
            Generator\constant(Http\path(State::CLIENT_CONFIG_FILE)),
            Generator\elements(
                ['dbgen' => ['{db}' => 'dummy_data']],
                ['uuids' => ['{count}' => 12]]
            )
        )
            ->then(function ($path, $params) {
                $urlGen = M\mcompose(function ($contents) use ($params) {
                    $res = A\compose(Http\credentials, A\partialRight(Http\urlGenerate, $params), IO\IO);
                    return $res($contents);
                }, IO\readFile);

                $url = $urlGen(IO\IO($path));

                $this->assertInternalType('string', $url->exec());
                $this->assertRegExp('/(http|https*)(:*)(\/{2})([\w\W\d]*)/', $url->exec());
            });
    }

    public function testAuthHeadersOutputsHeadersForHttpRequest()
    {
        $this->forAll(
            Generator\constant(Http\path(State::CLIENT_CONFIG_FILE)),
            Generator\elements(
                ['Content-Type: application/json'],
                ['Accept: application/json']
            )
        )
            ->then(function ($path, $headers) {
                $head = M\mcompose(function ($contents) use ($headers) {
                    $res = A\compose(
                        Http\credentials,
                        A\partialRight('Chemem\\Fauxton\\Http\\authHeaders', $headers),
                        IO\IO
                    );
                    return $res($contents);
                }, IO\readFile);

                $httpHeaders = $head(IO\IO($path));

                $this->assertInternalType('array', $httpHeaders->exec());
            });
    }

    //TODO: execute, precond, database, allDbs, 
    //allDocs, insert, doc, search, changes, modify, ddoc
    public function testDatabaseFunctionManipulatesDatabase()
    {
        $this->forAll(
            Generator\constant('dummy_database'),
            Generator\elements('PUT', 'DELETE', 'GET'),
            Generator\elements(
                ['ok' => true],
                ['ok' => true],
                [
                    "committed_update_seq" => 292786,
                    "compact_running" => false,
                    "data_size" => 65031503,
                    "db_name" => "receipts",
                    "disk_format_version" => 6,
                    "disk_size" => 137433211,
                    "doc_count" => 6146,
                    "doc_del_count" => 64637,
                    "instance_start_time" => "1376269325408900",
                    "purge_seq" => 0,
                    "update_seq" => 292786
                ]
            )
        )
            ->then(function ($database, $method, $response) {
                $resp = \json_encode($response);
                $db = $this->mockHttpFetch(
                    ['dbgen' => ['{db}' => $database]],
                    [200, $method, State::COUCH_REQHEADERS, $resp]
                );

                $this->assertInstanceOf(IO::class, $db);
                $this->assertEquals($resp, $db->exec());
            });
    }

    public function testAllDbsFunctionDisplaysAllAvailableDatabases()
    {
        $this->forAll(Generator\elements(['dummy_database', 'security']))
            ->then(function ($dbs) {
                $res = \json_encode($dbs);
                $dbs = $this->mockHttpFetch(['allDbs' => []], [200, 'GET', State::COUCH_REQHEADERS, $res]);

                $this->assertInstanceOf(IO::class, $dbs);
                $this->assertEquals($res, $dbs->exec());
            });
    }

    public function testAllDocsFunctionOutputsAllDatabaseDocuments()
    {
        $this->forAll(
            Generator\constant('dummy_database'),
            Generator\constant(\json_encode([
                "offset" => 0,
                "rows" => [
                    [
                        "id" => "16e458537602f5ef2a710089dffd9453",
                        "key" => "16e458537602f5ef2a710089dffd9453",
                        "value" => [
                            "rev" => "1-967a00dff5e02add41819138abb3284d"
                        ]
                    ],
                    [
                        "id" => "a4c51cdfa2069f3e905c431114001aff",
                        "key" => "a4c51cdfa2069f3e905c431114001aff",
                        "value" => [
                            "rev" => "1-967a00dff5e02add41819138abb3284d"
                        ]
                    ],
                    [
                        "id" => "a4c51cdfa2069f3e905c4311140034aa",
                        "key" => "a4c51cdfa2069f3e905c4311140034aa",
                        "value" => [
                            "rev" => "5-6182c9c954200ab5e3c6bd5e76a1549f"
                        ]
                    ],
                    [
                        "id" => "a4c51cdfa2069f3e905c431114003597",
                        "key" => "a4c51cdfa2069f3e905c431114003597",
                        "value" => [
                            "rev" => "2-7051cbe5c8faecd085a3fa619e6e6337"
                        ]
                    ],
                    [
                        "id" => "f4ca7773ddea715afebc4b4b15d4f0b3",
                        "key" => "f4ca7773ddea715afebc4b4b15d4f0b3",
                        "value" => [
                            "rev" => "2-7051cbe5c8faecd085a3fa619e6e6337"
                        ]
                    ]
                ],
                "total_rows" => 5
            ]))
        )
            ->then(function ($database, $docs) {
                $result = $this->mockHttpFetch(
                    ['allDocs' => ['{db}' => $database, '{params}' => '']],
                    [200, 'GET', State::COUCH_REQHEADERS, $docs]
                );

                $this->assertInstanceOf(IO::class, $result);
                $this->assertInternalType('string', $result->exec());
                $this->assertEquals($docs, $result->exec());
            });
    }
}
