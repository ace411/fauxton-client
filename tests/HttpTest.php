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
use \Chemem\Bingo\Functional\PatternMatching as PM;

class HttpTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    const DECONSTRUCT = array('code', 'method', 'headers', 'body');

    const _genUrl = 'Chemem\\Fauxton\\Tests\\HttpTest::_genUrl';
    public static function _genUrl(array $request) : IO
    {
        return M\bind(function (string $content) use ($request) {
            $uri = A\compose(Http\_credentials, A\partialRight(Http\_url, $request), IO\IO);
            return $uri($content);
        }, Http\_readConfig());
    }

    const mockHttpFetch = 'Chemem\\Fauxton\\Tests\\HttpTest::mockHttpFetch';
    public static function mockHttpFetch(array $request, array $response) : IO
    {
        return M\bind(function (string $url) use ($response) {
            $let = PM\letIn(self::DECONSTRUCT, $response);
            
            return $let(self::DECONSTRUCT, function ($code, $method, $headers, $body) use ($url) : IO {
                $http = (new Server)
                    ->whenUri($url)
                    ->return (new Response($code, $headers, $body))
                    ->end();

                return IO\IO(
                    $http->handle(new Request($method, $url))
                        ->getBody()
                        ->getContents()
                );
            });
        }, self::_genUrl($request));
    }

    const _strlen = 'Chemem\\Fauxton\\Tests\\HttpTest::_strlen';
    public static function _strlen(string $string) : bool
    {
        return strlen($string) > 5;
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
        $this->forAll(Generator\constant(Http\_readConfig))
            ->then(function (callable $path) {
                $credentials = M\bind(function (string $config) {
                    return IO\IO(Http\_credentials($config));
                }, $path())->exec();

                $this->assertInternalType('array', $credentials);
                $this->assertTrue(count($credentials) == 3);
            });
    }

    public function testUrlFunctionOutputsCouchDbAccessUrl()
    {
        $this->forAll(
            Generator\constant(Http\_readConfig),
            Generator\elements(
                ['dbgen' => ['{db}' => 'dummy_data']],
                ['uuids' => ['{count}' => 12]]
            )
        )
            ->then(function (callable $path, array $params) {
                $url = M\bind(function (string $config) use ($params) {
                    $url = A\compose(Http\_credentials, A\partialRight(Http\_url, $params), IO\IO);
                    return $url($config);
                }, $path())->exec();

                $this->assertInternalType('string', $url);
                $this->assertRegExp('/(http|https*)(:*)(\/{2})([\w\W\d]*)/', $url);
            });
    }

    public function testAuthHeadersOutputsHeadersForHttpRequest()
    {
        $this->forAll(
            Generator\associative([
                'local' => Generator\bool(),
                'user' => Generator\suchThat(self::_strlen, Generator\string()),
                'pass' => Generator\suchThat(self::_strlen, Generator\string())
            ])
        )
            ->then(function (array $credentials) {
                $pluck = A\partial(A\pluck, $credentials);

                $headers = Http\_authHeaders($pluck('local'), $pluck('user'), $pluck('pass'));

                $this->assertInternalType('array', $headers);
            });
    }

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
