<?php

namespace Chemem\Fauxton\Http;

use Chemem\Fauxton\Config\State;
use \Chemem\Bingo\Functional\{
    Immutable\Collection,
    Functors\Monads\IO,
    Functors\Monads\Reader
};
use function \Chemem\Bingo\Functional\PatternMatching\patternMatch;
use function \Chemem\Bingo\Functional\Algorithms\{
    map, 
    omit,
    head, 
    pluck,
    addKeys, 
    concat, 
    extend, 
    identity, 
    compose,
    isArrayOf, 
    partialLeft, 
    partialRight
};

/**
 * credentialsFromFile :: Optional -> IO
 */

const credentialsFromFile = 'Chemem\\Fauxton\\Http\\credentialsFromFile';

function credentialsFromFile() : IO
{
    $read = compose(
        partialLeft(\Chemem\Bingo\Functional\Algorithms\concat, \DIRECTORY_SEPARATOR, dirname(__DIR__, 2)),
        \Chemem\Fauxton\FileSystem\fileInit, 
        \Chemem\Fauxton\FileSystem\read
    );

    return $read(State::CLIENT_CONFIG_FILE)
        ->map(
            function (array $config) {
                $extract = partialLeft('array_column', $config);

                return extend($config['local'] ? $extract('local') : $extract('cloudant'), [$config['local']]);
            }
        );
}

/**
 * urlGenerator -> String action -> Array credentials -> Array params -> String url
 */

const urlGenerator = 'Chemem\\Fauxton\\Http\\urlGenerator';

function urlGenerator(string $action, array $credentials, array $params) : string
{
    list($user, $pwd, $local) = $credentials; 

    $urlGen = compose(
        partialRight('str_replace', State::COUCH_ACTIONS[$action][$local ? 'local' : 'cloudant'], array_values($params)),
        partialLeft(
            \Chemem\Bingo\Functional\Algorithms\concat, 
            identity('/'), 
            $local ? 
                State::COUCH_URI_LOCAL : 
                str_replace(
                    ['{cloudantUser}', '{cloudantPass}', '{cloudantHost}'], 
                    [$user, $pwd, concat('.', $user, 'cloudant', 'com')], 
                    State::COUCH_URI_CLOUDANT
                )
        )
    );

    return $urlGen(array_keys($params));
}

/**
 * database :: String opt -> String database -> Collection
 */

const database = 'Chemem\\Fauxton\\Http\\database';

function database(string $opt, string $database) : Collection
{
    return credentialsFromFile()
        ->flatMap(
            function (array $credentials) use ($opt, $database) {
                list($user, $pwd, $local) = $credentials;

                return fetch(
                    urlGenerator('dbgen', $credentials, ['{db}' => $database]),
                    patternMatch(
                        [
                            '"delete"' => function () { return 'DELETE'; },
                            '"create"' => function () { return 'PUT'; },
                            '"info"' => function () { return 'GET'; },
                            '_' => function () { return 'PATCH'; }
                        ],
                        $opt
                    ),
                    (!$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)])
                )
                    ->flatMap(function (array $response) { return Collection::from($response); });
            }
        );
}

/**
 * uuids :: Int count -> IO
 */

const uuids = 'Chemem\\Fauxton\\Http\\uuids';

function uuids(int $count) : Collection
{
    return credentialsFromFile()
        ->flatMap(
            function (array $credentials) use ($count) {
                list($user, $pwd, $local) = $credentials;

                return fetch(
                    urlGenerator('uuids', $credentials, ['{count}' => $count]),
                    'GET',
                    !$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)]
                )
                    ->flatMap(function (array $response) { return !isset($response['uuids']) ? Collection::from(...$response['uuids']) : Collection::from($response); });
            }
        );
}

/**
 * allDatabases :: Optional -> Collection
 */

const allDatabases = 'Chemem\\Fauxton\\Http\\allDatabases';

function allDatabases() : Collection
{
    return credentialsFromFile()
        ->flatMap(
            function (array $credentials) {
                list($user, $pwd, $local) = $credentials;

                return fetch(
                    urlGenerator('allDbs', $credentials, []),
                    'GET',
                    !$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)]
                )
                    ->flatMap(function ($response) { return !isset($response['error'], $response['code']) ? Collection::from(...$response) : Collection::from($response); });
            }
        );
}

/**
 * allDocs -> String database -> Array params -> Collection
 */

const allDocs = 'Chemem\\Fauxton\\Http\\allDocs';

function allDocs(string $database, array $params = []) : Collection
{
    return credentialsFromFile()
        ->flatMap(
            function (array $credentials) use ($params, $database) {
                list($user, $pwd, $local) = $credentials;

                $urlGen = compose(
                    partialLeft(urlGenerator, 'allDocs', $credentials),
                    partialRight('rtrim', '?')
                );

                return fetch(
                    $urlGen([
                        '{db}' => $database,
                        '{params}' => empty($params) ? identity('') : http_build_query($params)
                    ]),
                    'GET',
                    !$local ? identity([]) : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)]
                )
                    ->flatMap(function (array $response) { return isset($response['rows']) ? Collection::from($response['rows']) : Collection::from($response); });
            }
        );
}

/**
 * insert :: String opt -> Array data -> Collection
 */

const insert = 'Chemem\\Fauxton\\Http\\insert';

function insert(string $opt, string $database, array $data) : Collection
{
    return credentialsFromFile()
        ->flatMap(
            function (array $credentials) use ($opt, $data, $database) {
                list($user, $pwd, $local) = $credentials; 
                $fetch = partialRight(
                    fetch, 
                    [\CURLOPT_POSTFIELDS => json_encode($data)] + (!$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)]),
                    'POST'
                );
                
                $url = patternMatch(
                    [
                        '"multiple"' => function () use ($credentials, $database) { return urlGenerator('bulkDocs', $credentials, ['{db}' => $database]); },
                        '"single"' => function () use ($data, $database, $credentials) { 
                            return urlGenerator(
                                'insertSingle', 
                                $credentials,
                                [
                                    '{db}' => $database,
                                    '{docId}' => isset($data['id']) ? $data['id'] : head(uuids(1)->toArray())
                                ] 
                            );  
                        },
                        '_' => function () { return identity(''); }
                    ]
                );

                return empty($url) ? 
                    Collection::from(['error' => 'Invalid URL']) : 
                    $fetch($url)
                        ->flatMap(function (array $response) { return Collection::from($response); });
            }
        );
}

/**
 * modify :: String opt -> String database -> Array data -> Array update -> Collection
 */

const modify = 'Chemem\\Fauxton\\Http\\modify';

function modify(string $opt, string $database, array $data, array $update = []) : Collection
{
    return credentialsFromFile()
        ->flatMap(
            function (array $credentials) use ($database, $opt, $data, $update) {
                list($user, $pwd, $local) = $credentials;
                $action = preg_match('/docs+/', $opt) ? 
                    partialLeft(fetch, urlGenerator('bulkDocs', $credentials, ['{db}' => $database]), 'POST') :
                    partialLeft(
                        fetch, 
                        compose(
                            partialLeft(urlGenerator, 'deleteDoc', $credentials),
                            partialRight('rtrim', '?')
                        )([
                            '{db}' => $database,
                            '{docId}' => isset($data['_id']) ? $data['_id'] : '',
                            '{rev}' => isset($data['_rev']) ? $data['_rev'] : ''
                        ])    
                    );

                return patternMatch(
                    [
                        '["docs", "update"]' => function () use ($data, $update, $action, $local, $user, $pwd) {
                            $post = compose(
                                function (array $docs) { return isset($docs['docs']) ? pluck($docs, 'docs') : identity([]); },
                                function (array $docs) use ($update) {
                                    return isArrayOf($update) == 'array' ? 
                                        count($update) == 1 ?
                                            map(function ($doc) use ($update) { return extend($doc, ...$update); }, $docs) :
                                            array_map(function ($doc, $upDoc) { return extend($doc, $upDoc); }, $docs, $update) :
                                        idenity($docs);
                                },
                                function (array $docs) { return json_encode(['docs' => $docs]); }
                            );

                            return $action(
                                [\CURLOPT_POSTFIELDS => $post($data)] + (!$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)])
                            );
                        },
                        '["docs", "delete"]' => function () use ($data, $action, $local, $user, $pwd) {
                            $post = compose(
                                function (array $docs) { return isset($docs['docs']) ? pluck($docs, 'docs') : identity([]); },
                                function (array $docs) { return map(function ($doc) { return extend($doc, ['_deleted' => true]); }, $docs); },
                                function (array $docs) { return json_encode(['docs' => $docs]); }
                            );

                            return $action(
                                [\CURLOPT_POSTFIELDS => $post($data)] + (!$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)])
                            );
                        },
                        '["doc", "update"]' => function () use ($data, $update, $action, $local, $user, $pwd) {
                            $post = compose(
                                partialRight(\Chemem\Bingo\Functional\Algorithms\omit, '_rev', '_id'),
                                partialRight(\Chemem\Bingo\Functional\Algorithms\extend, $update),
                                'json_encode'
                            );

                            return $action(
                                'PUT',
                                [\CURLOPT_POSTFIELDS => $post($data)] + (!$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)])
                            );
                        },
                        '["doc", "delete"]' => function () use ($action, $user, $pwd, $local) {
                            return $action(
                                'DELETE',
                                !$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)]
                            );
                        },
                        '_' => function () { return IO::of(['error' => 'Invalid function option']); }
                    ],
                    explode('-', $opt)
                )
                    ->flatMap(function (array $response) { return Collection::from($response); });                
            }
        );
}

/**
 * getDoc :: String database -> String docId -> Array params -> Collection
 */

const getDoc = 'Chemem\\Fauxton\\Http\\getDoc';

function getDoc(string $database, string $docId, array $params = []) : Collection
{
    return credentialsFromFile()
        ->flatMap(
            function (array $credentials) use ($params, $docId, $database) {
                list($user, $pwd, $local) = $credentials;

                $getDoc = compose(
                    partialLeft(urlGenerator, 'docById', $credentials),
                    partialRight('rtrim', '?'),
                    partialRight(fetch, (!$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)]), 'GET')
                );

                return $getDoc([
                    '{db}' => $database,
                    '{docId}' => $docId,
                    '{params}' => empty($params) ? identity('') : http_build_query($params)
                ])
                    ->flatMap(function ($response) { return Collection::from($response); });
            }
        );
}

/**
 * index :: String opt -> String database -> Array params -> Collection
 */

const index = 'Chemem\\Fauxton\\Http\\index';

function index(string $opt, string $database, array $params = []) : Collection
{
    return credentialsFromFile()
        ->flatMap(
            function (array $credentials) use ($opt, $params, $database) {
                list($user, $pwd, $local) = $credentials;
                $urlGen = partialLeft(urlGenerator, 'index', $credentials);

                $action = patternMatch(
                    [
                        '"create"' => function () use ($user, $pwd, $local, $urlGen, $database, $params) { 
                            return fetch(
                                $urlGen(['{db}' => $database]), 
                                'POST', 
                                [\CURLOPT_POSTFIELDS => json_encode($params)] + 
                                    (!$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)])
                            ); 
                        },
                        '"delete"' => function () use ($params, $user, $pwd, $local, $urlGen, $database) {
                            $url = compose(
                                $urlGen, 
                                partialRight(
                                    partialLeft(\Chemem\Bingo\Functional\Algorithms\concat, '/'), 
                                    (isset($params['name']) ? $params['name'] : identity('/')),
                                    identity('json'),
                                    (isset($params['ddoc']) ? $params['ddoc'] : identity('/'))                                    
                                ),
                                partialRight('rtrim', '/')
                            );

                            return fetch(
                                $url(['{db}' => $database]), 
                                'DELETE', 
                                (!$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)])
                            );
                        },
                        '"list"' => function () use ($local, $user, $pwd, $urlGen, $database) {
                            return fetch(
                                $urlGen(['{db}' => $database]), 
                                'GET', 
                                (!$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)])
                            );
                        },
                        '_' => function () {}
                    ],
                    $opt
                );

                return $action
                    ->flatMap(
                        function ($response) use ($opt) { 
                            return Collection::from(
                                patternMatch(
                                    [
                                        '"list"' => function () use ($response) { return pluck($response, 'indexes'); },
                                        '_' => function () use ($response) { return identity($response); }
                                    ],
                                    $opt
                                )
                            ); 
                        }
                    );
            }
        );
}

/**
 * search :: String database -> Array query -> Collection
 */

const search = 'Chemem\\Fauxton\\Http\\search';

function search(string $database, array $query) : Collection
{
    return credentialsFromFile()
        ->flatMap(
            function (array $credentials) use ($database, $query) {
                list($user, $pwd, $local) = $credentials;
                $urlGen = partialLeft(urlGenerator, 'search', $credentials);
                
                return fetch(
                    $urlGen(['{db}' => $database]),
                    'POST',
                    [\CURLOPT_POSTFIELDS => json_encode($query)] + 
                        (!$local ? [] : [\CURLOPT_HTTPAUTH => true, \CURLOPT_USERPWD => concat(':', $user, $pwd)])
                )
                    ->flatMap(function (array $response) { return Collection::from(isset($response['docs']) ? $response['docs'] : $response); });
            }
        );
}
