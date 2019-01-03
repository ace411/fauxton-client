<?php

namespace Chemem\Fauxton\Tests;

use \Eris\Generator;
use \Chemem\Fauxton\Config\State;
use \Chemem\Fauxton\Console;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use function \Chemem\Bingo\Functional\PatternMatching\patternMatch;
use \Chemem\Bingo\Functional\Algorithms as A;

class ConsoleTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    public static function mockStdin(string $input) : IO
    {
        return IO\IO($input);
    }

    public static function decodeResponse(IO $response) : IO
    {
        return M\bind(function (string $resp) {
            return Console\formatOutput($resp);
        }, $response); 
    }

    public function mockParser(string $cmd, string $data = '') : IO
    {
        $http = new HttpTest;
        return patternMatch(
            [
                '["cred", type, user, pass]' => function (string $type, string $user, string $pass) {
                    return Console\configRead(function ($contents) use ($type, $user, $pass) {
                        $config = \json_decode($contents, true);
                        $config['username'][$type] = $user;
                        $config['password'][$type] = $pass;
                        return IO\IO(
                            $config['username'][$type] == $user && $config['password'][$type] == $pass ?
                            'Credentials updated' :
                            'Credentials not updated'
                        );
                    });
                },
                '["use", option]' => function (string $option) {
                    return Console\configRead(function ($contents) use ($option) {
                        $config = \json_decode($contents, true);
                        $config['local'] = $option == 'local' ? true : false;
                        return IO\IO('Credentials updated');
                    });
                },
                '["doc", database, docId]' => function (string $database, string $docId) use ($http, $data) {
                    $res = $http->mockHttpFetch(
                        [
                            'docById' => [
                                '{db}' => $database, 
                                '{docId}' => $docId, 
                                '{params}' => 'include_docs=true'
                            ]
                        ],
                        [200, 'GET', State::COUCH_REQHEADERS, $data]    
                    );
                    return self::decodeResponse($res);
                },
                '["db", database]' => function (string $database) use ($http, $data) {
                    $res = $http->mockHttpFetch(
                        ['dbgen' => ['{db}' => $database]],
                        [200, 'GET', State::COUCH_REQHEADERS, $data]
                    );
                    return self::decodeResponse($res);
                },
                '["alldocs", database]' => function (string $database) use ($http, $data) {
                    $res = $http->mockHttpFetch(
                        [
                            'allDocs' => [
                                '{db}' => $database, 
                                '{params}' => 'include_docs=true'
                            ]
                            ],
                        [200, 'GET', State::COUCH_REQHEADERS, $data]
                    );
                    return self::decodeResponse($res);
                },
                '["uuids", count]' => function (string $count) use ($http, $data) {
                    $result = $http->mockHttpFetch(
                        ['uuids' => ['{count}' => $count]],
                        [200, 'GET', State::COUCH_REQHEADERS, $data]
                    );
                    return self::decodeResponse($result);
                },
                '["explain", cmd]' => function (string $cmd) {
                    return Console\execCmd(A\concat(' ', 'explain', $cmd));
                },
                '["help"]' => function () {
                    return Console\execCmd('help');
                },
                '["config"]' => function () {
                    return Console\execCmd('config');
                },
                '["alldbs"]' => function () use ($http, $data) {
                    $result = $http->mockFetch(
                        ['allDbs' => []],
                        [200, 'GET', State::COUCH_REQHEADERS, $data]
                    );
                    return self::decodeResponse($result);
                },
                '_' => function () {
                    return Console\execCmd('cmd');
                }
            ],
            explode(' ', $cmd)
        );
    }

    public function testUnrecognizedInputEvaluatesToUnrecognizedInputMessage()
    {
        $this->forAll(Generator\string())
            ->then(function ($string) {
                $parse = $this->mockParser($string);

                $this->assertInstanceOf(IO::class, $parse);
                $this->assertInternalType('string', $parse->exec());
                $this->assertEquals(Console\color('Input not recognized', 'red'), $parse->exec());
            });
    }

    public function testCredCommandModifiesClientConfiguration()
    {
        $this->forAll(Generator\names(), Generator\string())
            ->then(function ($user, $pwd) {
                $cred = $this->mockParser(A\concat(' ', 'cred', 'local', $user, $pwd));

                $this->assertInstanceOf(IO::class, $cred);
                $this->assertInternalType('string', $cred->exec());
            });
    }

    public function testUuuidsCommandOutputsUuids()
    {
        $this->forAll(
            Generator\choose(1, 2),
            Generator\elements(
                ['uuids' => ['75480ca477454894678e22eec6002413']],
                [
                    'uuids' => [
                        'ba617077f2b71a2c3a53488657000cad',
                        'ba617077f2b71a2c3a534886570017a7'
                    ]
                ]
            )
        )
            ->then(function ($count, $response) {
                $uuids = $this->mockParser(A\concat(' ', 'uuids', (string) $count), \json_encode($response));

                $this->assertInstanceOf(IO::class, $uuids);
                $this->assertInternalType('string', $uuids->exec());
                $this->assertRegExp('/[\.\w\W]+$/', $uuids->exec());
            });
    }

    public function testExplainCommandOutputsDescriptionOfCommand()
    {
        $this->forAll(Generator\elements(...\array_keys(State::CONSOLE_COMMANDS)))
            ->then(function ($cmd) {
                $explanation = $this->mockParser(A\concat(' ', 'explain', $cmd));

                $this->assertInternalType('string', $explanation->exec());
                $this->assertContains(
                    A\pluck(State::CONSOLE_COMMANDS, $cmd)['desc'],
                    $explanation->exec()
                );
            });
    }

    public function testHelpCommandOutputsAllCommands()
    {
        $this->forAll(Generator\constant('help'))
            ->then(function ($cmd) {
                $help = $this->mockParser($cmd);

                $this->assertInternalType('string', $help->exec());
                $this->assertInstanceOf(IO::class, $help);
                $this->assertRegExp('/[\.\w\W]+$/', $help->exec());
            });
    }

    public function testAllDbsCommandOutputsListOfAllDatabases()
    {
        $this->forAll(
            Generator\constant(\json_encode([
                'dummy_database',
                'security'
            ]))
        )
            ->then(function ($response) {
                $dbs = $this->mockParser('allDbs', $response);
                
                $this->assertInstanceOf(IO::class, $dbs);
                $this->assertInternalType('string', $dbs->exec());
                $this->assertRegExp('/[\.\w\W]+$/', $dbs->exec());
            });
    }

    public function testUseFunctionModifiesClientConfiguration()
    {
        $this->forAll(Generator\elements('cloudant', 'local'))
            ->then(function ($option) {
                $result = $this->mockParser(A\concat(' ', 'use', $option));

                $this->assertEquals('Credentials updated', $result->exec());
                $this->assertInternalType('string', $result->exec());
            });
    }

    public function testDocCommandOutputsDocumentContents()
    {
        $this->forAll(
            Generator\constant('dummy_database'),
            Generator\elements('FishStew', 'LambStew'),
            Generator\elements(
                [
                    "_id" => "FishStew",
                    "servings" => 4,
                    "subtitle" => "Delicious with freshly baked bread",
                    "title" => "FishStew"
                ],
                [
                    "_id" => "LambStew",
                    "servings" => 6,
                    "subtitle" => "Serve with a whole meal scone topping",
                    "title" => "LambStew"
                ]
            )
        )
            ->then(function ($database, $docId, $result) {
                $resp = \json_encode($result, \JSON_PRETTY_PRINT);
                $res = $this->mockParser(A\concat(' ', 'doc', $database, $docId), $resp);

                $this->assertInternalType('string', $res->exec());
                $this->assertRegExp('/[\.\w\W]+$/', $res->exec());
            });
    }

    public function testAllDocsCommandOutputsAllDatabaseDocuments()
    {
        $this->forAll(
            Generator\constant('dummy_database'),
            Generator\elements(
                [
                    "_id" => "FishStew",
                    "_rev" => "1-6a466d5dfda05e613ba97bd737829d67",
                    "servings" => 4,
                    "subtitle" => "Delicious with freshly baked bread",
                    "title" => "FishStew"
                ],
                [
                    "_id" => "LambStew",
                    "_rev" => "1-648f1b989d52b8e43f05aa877092cc7c",
                    "servings" => 6,
                    "subtitle" => "Serve with a whole meal scone topping",
                    "title" => "LambStew"
                ]
            )
        )
            ->then(function ($database, $response) {
                $resp = \json_encode($response);
                $req = $this->mockParser(A\concat(' ', 'alldocs', $database), $resp);

                $this->assertInstanceOf(IO::class, $req);
                $this->assertRegExp('/[\.\w\W]+$/', $req->exec());
            });
    }
}