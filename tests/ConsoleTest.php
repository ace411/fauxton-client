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
}