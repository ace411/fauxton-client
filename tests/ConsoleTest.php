<?php

namespace Chemem\Fauxton\Tests;

use \Eris\Generator;
use \Chemem\Fauxton\Config\State;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use function \Chemem\Bingo\Functional\PatternMatching\patternMatch;
use function \Chemem\Fauxton\Console\{convey, parse};
use function \Chemem\Bingo\Functional\Algorithms\{
    head, 
    pluck, 
    omit, 
    concat, 
    extend, 
    compose, 
    partialLeft, 
    partialRight,
    arrayKeysExist
};

class ConsoleTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    public function mockStdin(string $prompt, string $input) : IO
    {
        return IO::of([$prompt => $input]);
    }

    public function mockParser(IO $stdin, array $params = [])
    {
        return $stdin
            ->map(
                function (array $input) {
                    $pattern = compose(
                        'array_values',
                        \Chemem\Bingo\Functional\Algorithms\head,
                        partialLeft('explode', ' ')
                    );

                    return $pattern($input);
                }
            )
            ->map(
                function (array $pattern) use ($params) {
                    return patternMatch(
                        [
                            '["new", item]' => function (string $item) use ($params) {
                                return $this->mockStdin(
                                    pluck(State::CONSOLE_FEATURES, 'db'),
                                    pluck($params, 'db') 
                                )
                                    ->flatMap(function ($query) use ($params) { return extend($query, omit($params, 'db')); });
                            },
                            '["uuids", count]' => function (string $count) use ($params) {
                                return (new HttpTest)
                                    ->mockFetch('uuids', 'GET', 200, ['{count}' => (int) $count], $params)
                                    ->exec();
                            },
                            '["docs", database]' => function (string $database) use ($params) {
                                return (new HttpTest)
                                    ->mockFetch('allDocs', 'GET', 200, ['{db}' => $database, '{params}' => '?include_docs=true'], $params)
                                    ->exec();
                            },
                            '["search", database]' => function (string $database) use ($params) {
                                return $this->mockStdin(
                                    pluck(State::CONSOLE_FEATURES, 'search'),
                                    pluck($params, 'search')
                                )
                                    ->flatMap(function ($query) use ($params) { return extend($query, omit($params, 'search')); });
                            },
                            '_' => function () { return 'Invalid Input'; }
                        ],
                        $pattern
                    );
                }
            );
    }

    public function testConveyFunctionEvaluatesToParsedInputString()
    {
        $prompt = pluck(State::CONSOLE_FEATURES, 'prompt');
        
        $this->forAll(
            Generator\elements('new view', 'search dummy_data', 'config'),
            Generator\elements(
                ['db' => 'dummy_docs'],
                ['selector' => '{"selector": {"_id": {"$eq": "abc123"}}'],
                []
            )
        )
            ->then(
                function ($cmd, $params) use ($prompt) {
                    $output = convey($this->mockParser($this->mockStdin($prompt, $cmd), $params));

                    $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $output);
                    $this->assertInternalType('string', $output->exec());
                }
            );
    }

    public function testLocalCommandModifiesFauxtonConfiguration()
    {
        $this->limitTo(5)
            ->forAll(
                Generator\names(),
                Generator\map('md5', Generator\string())
            )
            ->then(
                function ($user, $pwd) {
                    $local = parse(
                        $this->mockStdin(pluck(State::CONSOLE_FEATURES, 'prompt'), concat(' ', 'local', $user, $pwd))
                            ->map(\Chemem\Bingo\Functional\Algorithms\head)
                    );

                    $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $local);
                    $this->assertInternalType('string', $local->exec());
                }
            );
    }

    public function testCloudantCommandModifiesFauxtonConfiguration()
    {
        $this->limitTo(5)
            ->forAll(
                Generator\map(
                    function ($string) {
                        $hash = compose('md5', partialRight(partialLeft(\Chemem\Bingo\Functional\Algorithms\concat, '-'), 'bluemix'));

                        return $hash($string);
                    },
                    Generator\string()
                ),
                Generator\map(partialLeft('hash', 'sha256'), Generator\string())
            )
            ->then(
                function ($user, $pwd) {
                    $cloudant = parse(
                        $this->mockStdin(pluck(State::CONSOLE_FEATURES, 'prompt'), concat(' ', 'local', $user, $pwd))
                            ->map(\Chemem\Bingo\Functional\Algorithms\head)
                    );

                    $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $cloudant);
                    $this->assertInternalType('string', $cloudant->exec());
                }
            );
    }

    public function testUseCommandModifiesFauxtonConfiguration()
    {
        $this->forAll(Generator\elements('local', 'cloudant'))
            ->then(
                function ($opt) {
                    $use = parse(
                        $this->mockStdin(pluck(State::CONSOLE_FEATURES, 'prompt'), concat(' ', 'use', $opt))
                            ->map(\Chemem\Bingo\Functional\Algorithms\head)
                    );

                    $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $use);
                    $this->assertInternalType('string', $use->exec());
                }
            );
    }

    public function testConfigCommandOutputsFauxtonClientConfigurationFileContents()
    {
        $config = parse(
            $this->mockStdin(pluck(State::CONSOLE_FEATURES, 'prompt'), 'config')
                ->map(\Chemem\Bingo\Functional\Algorithms\head)
        );

        $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $config);
        $this->assertTrue(arrayKeysExist($config->exec(), 'username', 'password', 'local'));
    }

    public function testHelpCommandOutputsListOfCommands()
    {
        $help = parse(
            $this->mockStdin(pluck(State::CONSOLE_FEATURES, 'prompt'), 'help')
                ->map(\Chemem\Bingo\Functional\Algorithms\head)
        );

        $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $help);
        $this->assertInternalType('string', $help->exec());
    }

    public function testExplainCommandOutputsCommandAndAccompanyingDescription()
    {
        $this->forAll(Generator\elements(...array_keys(State::CONSOLE_COMMANDS)))
            ->then(
                function ($cmd) {
                    $xtics = parse(
                        $this->mockStdin(pluck(State::CONSOLE_FEATURES, 'prompt'), concat(' ', 'explain', $cmd))
                            ->map(\Chemem\Bingo\Functional\Algorithms\head)
                    );

                    $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $xtics);
                    $this->assertInternalType('string', $xtics->exec());
                    $this->assertContains(
                        concat('- ', '', pluck(State::CONSOLE_COMMANDS[$cmd], 'desc')),
                        $xtics->exec()
                    );
                }
            );
    }

    public function testUuidsCommandOutputsUniqueIds()
    {
        $this->forAll(
            Generator\choose(1, 3),
            Generator\elements(
                ['75480ca477454894678e22eec6002413'],
                [
                    '75480ca477454894678e22eec600250b',
                    '75480ca477454894678e22eec6002c41'
                ],
                [
                    '75480ca477454894678e22eec6003b90',
                    '75480ca477454894678e22eec6003fca',
                    '75480ca477454894678e22eec6004bef'
                ]    
            )
        )
            ->then(
                function ($count, $response) {
                    $uuids = $this->mockParser(
                        $this->mockStdin(pluck(State::CONSOLE_FEATURES, 'prompt'), concat(' ', 'uuids', $count)),
                        $response
                    ); 

                    $this->assertInternalType('string', $uuids->exec());
                    $this->assertContainsOnly('string', $uuids->flatMap(partialRight('json_decode', true)));
                }
            );
    }

    public function testDocsCommandOutputsDatabaseDocuments()
    {
        $this->forAll(
            Generator\elements('blog_posts', 'basketball_players', 'tv_shows'),
            Generator\elements(
                [
                    [
                        'id' => 'fauxton-client-rocks',
                        'key' => '16e458537602f5ef2a710089dffd9453',
                        'value' => [
                            'rev' => '1-967a00dff5e02add41819138abb3284d'
                        ]
                    ],
                    [
                        'id' => 'functional-programming-rocks',
                        'key' => 'a4c51cdfa2069f3e905c431114001aff',
                        'value' => [
                            'rev' => '1-967a00dff5e02add41819138abb3284d'
                        ]
                    ]
                ],
                [
                    [
                        'id' => 'miami-heat-roster',
                        'key' => '24313b25786d0a6bf75e29d628cd5729',
                        'value' => [
                            'rev' => '1-a9a2c7beb67dedf0f8d319cd435f07df'
                        ]
                    ]
                ],
                [
                    [
                        'id' => 'game-of-thrones',
                        'key' => 'e372c0b85e5fd5f92f44a849fcec6ed5',
                        'value' => [
                            'rev' => '1-6ff380e091065cddf91bc8249e513692'
                        ]
                    ]
                ]
            )
        )
            ->then(
                function ($database, $result) {
                    $docs = $this->mockParser(
                        $this->mockStdin(pluck(State::CONSOLE_FEATURES, 'prompt'), concat(' ', 'docs', $database)),
                        $result
                    );

                    $this->assertInternalType('string', $docs->exec());
                    $this->assertContainsOnly('array', $docs->flatMap(partialRight('json_decode', true)));
                }
            );
    }
}