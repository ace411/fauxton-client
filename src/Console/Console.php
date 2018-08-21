<?php

namespace Chemem\Fauxton\Console;

use \Chemem\Fauxton\Config\State;
use \JakubOnderka\PhpConsoleColor\ConsoleColor;
use \Chemem\Bingo\Functional\Functors\Monads\{IO, Reader};
use function \Chemem\Bingo\Functional\PatternMatching\patternMatch;
use function \Chemem\Fauxton\Http\{
    uuids,
    index, 
    allDocs, 
    getDoc, 
    modify,
    search, 
    database,
    allDatabases
};
use function \Chemem\Bingo\Functional\Algorithms\{
    omit, 
    pluck, 
    compose, 
    concat, 
    identity, 
    extend, 
    partialLeft, 
    partialRight, 
    curryRightN
};

const fetch = 'Chemem\\Fauxton\\Console\\fetch';

function fetch() : IO
{
    return printPrompt('prompt')
        ->map(function (int $strlen) { return $strlen > 0 ? getLine() : identity('input error'); });
}

const parse = 'Chemem\\Fauxton\\Console\\parse';

function parse(IO $parsable) : IO
{
    return $parsable
        ->map(
            function (string $input) {
                $txtInput = compose('fgets', 'trim');
                $formatMultiple = compose($txtInput, partialLeft('explode', ', '));
                $read = compose(
                    partialLeft(\Chemem\Bingo\Functional\Algorithms\concat, '/', dirname(__DIR__, 2)),
                    \Chemem\Fauxton\FileSystem\fileInit,
                    \Chemem\Fauxton\FileSystem\read
                );

                return patternMatch(
                    [
                        '["local", user, pwd]' => function (string $user, string $pwd) use ($read) {
                            return $read(State::CLIENT_CONFIG_FILE)
                                ->map(
                                    function (array $config) use ($user, $pwd) {
                                        return [
                                            'username' => ['local' => $user, 'cloudant' => $config['username']['cloudant']],
                                            'password' => ['local' => $pwd, 'cloudant' => $config['password']['cloudant']],
                                            'local' => true
                                        ];
                                    }
                                )
                                ->flatMap(
                                    function (array $config) {
                                        $write = compose(
                                            partialLeft(\Chemem\Bingo\Functional\Algorithms\concat, '/', dirname(__DIR__, 2)),
                                            \Chemem\Fauxton\FileSystem\fileInit,
                                            curryRightN(2, \Chemem\Fauxton\FileSystem\write)($config)
                                        );

                                        return $write(State::CLIENT_CONFIG_FILE)
                                            ->flatMap(function (bool $res) { return $res ? 'Configuration updated' : 'Configuration not updated'; });
                                    }
                                );
                        },
                        '["cloudant", user, pwd]' => function (string $user, string $pwd) use ($read) {
                            return $read(State::CLIENT_CONFIG_FILE)
                                ->map(
                                    function (array $config) use ($user, $pwd) {
                                        return [
                                            'username' => ['cloudant' => $user, 'local' => $config['username']['local']],
                                            'password' => ['cloudant' => $pwd, 'local' => $config['password']['local']],
                                            'local' => false
                                        ];
                                    }
                                )
                                ->flatMap(
                                    function (array $config) {
                                        $write = compose(
                                            partialLeft(\Chemem\Bingo\Functional\Algorithms\concat, '/', dirname(__DIR__, 2)),
                                            \Chemem\Fauxton\FileSystem\fileInit,
                                            curryRightN(2, \Chemem\Fauxton\FileSystem\write)($config)
                                        );

                                        return $write(State::CLIENT_CONFIG_FILE)
                                            ->flatMap(function (bool $res) { return $res ? 'Configuration updated' : 'Configuration not updated'; });
                                    }
                                );
                        },
                        '["use", option]' => function (string $option) use ($read) {
                            return patternMatch(
                                [
                                    '"local"' => function () use ($read) {
                                        return $read(State::CLIENT_CONFIG_FILE)
                                            ->map(partialRight(\Chemem\Bingo\Functional\Algorithms\extend, ['local' => true]));
                                    },
                                    '"cloudant"' => function () use ($read) {
                                        return $read(State::CLIENT_CONFIG_FILE)
                                            ->map(partialRight(\Chemem\Bingo\Functional\Algorithms\extend, ['local' => false]));
                                    },
                                    '_' => function () use ($read) { return $read(State::CLIENT_CONFIG_FILE); }
                                ],
                                $option
                            )
                                ->flatMap(
                                    function (array $config) {
                                        $write = compose(
                                            partialLeft(\Chemem\Bingo\Functional\Algorithms\concat, '/', dirname(__DIR__, 2)),
                                            \Chemem\Fauxton\FileSystem\fileInit,
                                            curryRightN(2, \Chemem\Fauxton\FileSystem\write)($config)
                                        );

                                        return $write(State::CLIENT_CONFIG_FILE)
                                            ->flatMap(function (bool $res) { return $res ? 'Configuration updated' : 'Configuration not updated'; });
                                    }
                                );
                        },
                        '["new", item]' => function (string $item) use ($txtInput, $formatMultiple) {
                            return patternMatch(
                                [
                                    '"index"' => function () use ($txtInput, $formatMultiple) { 
                                        return IO::of(
                                            function () {
                                                $prompt = compose(
                                                    partialRight(\Chemem\Bingo\Functional\Algorithms\pluck, 'index'),
                                                    partialLeft('printf', '%s')
                                                );

                                                return $prompt(State::CONSOLE_FEATURES);
                                            }
                                        )
                                            ->map(function (int $len, array $query = []) use ($txtInput) { return extend($query, ['name' => $len > 0 ? $txtInput(\STDIN) : identity('')]); })
                                            ->map(
                                                function (array $query) use ($txtInput) {
                                                    $action = compose(
                                                        partialRight(\Chemem\Bingo\Functional\Algorithms\pluck, 'db'),
                                                        partialLeft('printf', '%s'),
                                                        function (int $len) use ($query, $txtInput) { return extend(['db' => $len > 0 ? $txtInput(\STDIN) : identity('')], $query); }
                                                    );

                                                    return $action(State::CONSOLE_FEATURES);
                                                }
                                            )
                                            ->flatMap(
                                                function (array $query) use ($formatMultiple) {
                                                    $action = compose(
                                                        partialRight(\Chemem\Bingo\Functional\Algorithms\pluck, 'dbFields'),
                                                        partialLeft('printf', '%s'),
                                                        function (int $len) use ($query, $formatMultiple) {
                                                            $query['index']['fields'] = $len > 0 ? $formatMultiple(\STDIN) : identity([]);
                                                            
                                                            return index('create', pluck($query, 'db'), omit($query, 'db'));
                                                        }
                                                    );

                                                    return $action(State::CONSOLE_FEATURES);
                                                }
                                            ); 
                                    }, //new view
                                    '"db"' => function () use ($txtInput) { 
                                        return IO::of(
                                            function () {
                                                $prompt = compose(
                                                    partialRight(\Chemem\Bingo\Functional\Algorithms\pluck, 'db'),
                                                    partialLeft('printf', '%s')
                                                );

                                                return $prompt(State::CONSOLE_FEATURES);
                                            }
                                        )
                                            ->flatMap(function (int $len) use ($txtInput) { return $len > 0 ? database('create', $txtInput(\STDIN)) : identity('Could not create db'); }); 
                                    }, //new db
                                    '_' => function () { return 'NaN'; } 
                                ],
                                $item
                            );
                        },
                        '["docs", database]' => function (string $database) { return allDocs($database, ['include_docs' => 'true']); },
                        '["doc", database, docId]' => function (string $database, string $docId) { return getDoc($database, $docId); },
                        '["search", database]' => function (string $database) use ($txtInput, $formatMultiple) {
                            return IO::of(printf('%s', 'Selector: '))
                                ->map(
                                    function (int $len, array $query = []) use ($txtInput) {
                                        $format = compose($txtInput, partialRight('json_decode', true));
                                        $query['selector'] = $len > 0 ? $format(\STDIN) : identity('');
                                        return $query;
                                    }
                                )
                                ->map(
                                    function (array $query) use ($formatMultiple) {
                                        printf('%s', concat(':', 'Fields', ' '));
                                        $query['fields'] = $formatMultiple(\STDIN);
                                        return $query; 
                                    }
                                )
                                ->flatMap(
                                    function (array $query) use ($database) {
                                        $action = compose(
                                            partialRight(\Chemem\Bingo\Functional\Algorithms\extend, ['limit' => 25, 'skip' => 0]),
                                            partialLeft(\Chemem\Fauxton\Http\search, $database)
                                        );

                                        return $action($query);
                                    }
                                );
                        },
                        '["uuids", count]' => function (string $count) { return uuids((is_numeric($count) ? (int) $count : 1)); },
                        '["input", "error"]' => function () { return 'Console error'; },
                        '["config"]' => function () use ($read) { return $read(State::CLIENT_CONFIG_FILE)->exec(); },
                        '["help"]' => function () { return 'Help command'; },
                        '["dbs"]' => function () { return allDatabases(); },
                        '["exit"]' => function () { return color('Exit', 'red'); },
                        '_' => function () { return color('Invalid input', 'red'); }
                    ],
                    explode(' ', $input)
                );
            }
        ); //convert input to output
}

const convey = 'Chemem\\Fauxton\\Console\\convey';

function convey(IO $parsed) : IO
{
    return $parsed
        ->map(
            function ($output) {
                $print = $output instanceof \Chemem\Bingo\Functional\Immutable\Collection ||
                    is_array($output) ?
                        json_encode($output, JSON_PRETTY_PRINT) :
                        (string) $output;

                return concat(\PHP_EOL, $print, identity(''));
            }
        );
}

const color = 'Chemem\\Fauxton\\Console\\color';

function color(string $text, string $color) : string
{
    $color = new ConsoleColor;
    return $color->isSupported() ? $color->apply($color, $text) : identity($text);
}

const getLine = 'Chemem\\Fauxton\\Console\\getLine';

function getLine() : string
{
    $line = compose('fgets', 'trim');
    return $line(\STDIN);
}

const getListFromLine = 'Chemem\\Fauxton\\Console\\getListFromLine';

function getListFromLine() : array
{
    $list = compose(
        getLine,
        function ($content) { 
            $explode = partialRight('explode', $content);
            return $explode(preg_match('/\s+/', $content) ? ', ' : ',');
        } 
    );

    return $list(\STDIN);
}

const printPrompt = 'Chemem\\Fauxton\\Console\\printPrompt';

function printPrompt(string $key) : IO
{
    $prompt = compose(
        partialRight(\Chemem\Bingo\Functional\Algorithms\pluck, $key),
        partialLeft('printf', '%s')
    );

    return IO::of(function () use ($prompt) { return function (array $opts) use ($prompt) { return $prompt($opts); }; })
        ->ap(IO::of(State::CONSOLE_FEATURES));
}
