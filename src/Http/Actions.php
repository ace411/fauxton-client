<?php

namespace Chemem\Fauxton\Http;

use \Chemem\Fauxton\Config\State;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use function \Chemem\Bingo\Functional\PatternMatching\patternMatch;

const path = 'Chemem\\Fauxton\\Http\\path';

function path(string $file) : string
{
    return A\concat('/', dirname(__DIR__, 2), $file);
}

const credentials = 'Chemem\\Fauxton\\Http\\credentials';

function credentials(string $config) : array
{
    $opts = json_decode($config, true);
    $extract = A\partial('array_column', $opts);
    $local = A\pluck($opts, 'local');

    return A\extend(($local ? $extract('local') : $extract('cloudant')), [$local]);
}

const urlGenerate = 'Chemem\\Fauxton\\Http\\urlGenerate';

function urlGenerate(array $credentials, array $params) : string
{
    list($user, $pwd, $local) = $credentials;
    $fragments = A\head($params);
    $subject = A\compose(
        A\partialRight(A\pluck, A\head(array_keys($params))),
        A\partialRight(A\pluck, ($local ? 'local' : 'cloudant'))
    );

    $gen = A\compose(
        'array_keys',
        A\partialRight('str_replace', $subject(State::COUCH_ACTIONS), array_values($fragments)),
        A\partial(
            A\concat, 
            A\identity('/'), 
            ($local ? 
                State::COUCH_URI_LOCAL : 
                str_replace(
                    ['{cloudantUser}', '{cloudantPass}', '{cloudantHost}'], 
                    [$user, $pwd, A\concat('.', $user, 'cloudant', 'com')],
                    State::COUCH_URI_CLOUDANT
                )
            )
        ),
        A\partialRight('rtrim', '?')
    );
    
    return $gen($fragments);
}

const authHeaders = 'Chemem\\Fauxton\\Http\\execute';

function authHeaders(array $credentials, array $ancillary = []) : array
{
    list($user, $pwd, $local) = $credentials;
    $encode = A\compose(
        A\partial(A\concat, ':', $user),
        'base64_encode'
    );

    return [
        'http' => A\extend($ancillary, [
            'header' => A\extend(
                State::COUCH_REQHEADERS, 
                (!$local ? [] : [A\concat(' ', 'Authorization:', 'Basic', $encode($pwd))])
            )
        ])
    ];
}

const execute = 'Chemem\\Fauxton\\Http\\execute';

function execute(callable $action) : IO
{
    $result = M\mcompose($action, IO\readFile);
    $final = A\compose(path, IO\IO, $result);
    return $final(State::CLIENT_CONFIG_FILE);
}

const precond = 'Chemem\\Fauxton\\Http\\precond';

function precond(string $config, array $ancillary = []) : callable
{
    $cred = credentials($config);
    return A\compose(
        A\partial(urlGenerate, $cred),
        A\partialRight(fetch, authHeaders($cred, $ancillary))
    );
}

const uuids = 'Chemem\\Fauxton\\Http\\uuids';

function uuids(int $count) : IO
{
    return execute(function (string $contents) use ($count) {
        $result = precond($contents);
        return $result(['uuids' => ['{count}' => $count]]);
    });
}

const database = 'Chemem\\Fauxton\\Http\\database';

function database(string $opt, string $database) : IO
{
    return execute(function (string $contents) use ($opt, $database) {
        $result = precond($contents, [
            'method' => patternMatch(
                [
                    '"create"' => function () : string {
                        return 'PUT';
                    },
                    '"delete"' => function () : string {
                        return 'DELETE';
                    },
                    '_' => function () : string {
                        return 'GET';
                    }
                ], 
                $opt
            )
        ]);

        return $result(['db' => ['{db}' => $database]]);
    });
}

const allDbs = 'Chemem\\Fauxton\\Http\\allDbs';

function allDbs() : IO
{
    return execute(function ($contents) {
        $result = precond($contents);
        return $result(['allDbs' => []]);
    });
}

const allDocs = 'Chemem\\Fauxton\\Http\\allDocs';

function allDocs(string $database, array $params = []) : IO
{
    return execute(function (string $contents) use ($database, $params) {
        $result = precond($contents);
        return $result([
            'allDocs' => [
                '{db}' => $database,
                '{params}' => empty($params) ? A\identity('') : \http_build_query($params)
            ]
        ]); 
    });
}

const insert = 'Chemem\\Fauxton\\Http\\insert';

function insert(string $opt, string $database, array $data) : IO
{
    return execute(function (string $contents) use ($opt, $data, $database) {
        $key = ['{db}' => $database];
        $result = precond($contents, [
            'method' => 'POST',
            'content' => \json_encode($data)
        ]);
        return $result(patternMatch(
            [
                '"single"' => function () use ($key, $database) : array {
                    return [
                        'insertSingle' => A\extend($key, [
                            '{docId}' => isset($data['id']) ? A\pluck($data, 'id') : A\head(uuids(1)->exec())
                        ])                             
                    ];
                },
                '"multiple"' => function () use ($key) : array {
                    return ['bulkDocs' => $key];
                },
                '_' => function () : array {
                    return [];
                }
            ],
            $opt
        ));
    });
}

const doc = 'Chemem\\Fauxton\\Http\\doc';

function doc(string $database, string $docId, array $params = []) : IO
{
    return execute(function (string $contents) use ($docId, $params, $database) {
        $result = precond($contents);
        return $result([
            'docById' => [
                '{db}' => $database,
                '{docId}' => $docId,
                '{params}' => $params
            ]
        ]);
    });
}

const search = 'Chemem\\Fauxton\\Http\\search';

function search(string $database, array $query) : IO
{
    return execute(function (string $contents) use ($query, $database) {
        $result = precond($contents, [
            'method' => 'POST',
            'content' => \json_encode($query)
        ]);
        return $result(['search' => ['{db}' => $database]]);
    });
}

const changes = 'Chemem\\Fauxton\\Http\\changes';

function changes(string $database, array $params = []) : IO
{
    return execute(function (string $contents) use ($params, $database) {
        $result = precond($contents);
        return $result([
            'changes' => [
                '{db}' => $database,
                '{params}' => empty($params) ? A\identity('') : \http_build_query($params)
            ]
        ]);
    });
}
