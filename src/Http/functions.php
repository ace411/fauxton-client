<?php

/**
 * 
 * fauxton-client http function helpers
 * 
 * @author Lochemem Bruno Michael
 * @license Apache-2.0
 */

namespace Chemem\Fauxton\Http;

use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use \Chemem\Bingo\Functional\PatternMatching as PM;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use \Chemem\Bingo\Functional\Http;
use \Chemem\Fauxton\Config\State;

const _httpFetch = 'Chemem\\Fauxton\\Http\\_httpFetch';
function _httpFetch(string $uri, callable $request, array $headers) : IO
{
    $request = A\compose(
        $request, 
        A\partialRight(Http\setHeaders, $headers), 
        Http\http, 
        A\partial(M\bind, Http\getResponseBody)
    );

    return $request($uri);
}

const _credentials = 'Chemem\\Fauxton\\Http\\_credentials'; 
function _credentials(string $config) : array
{
    $let = PM\letIn(array('username', 'password', '_', 'local'), json_decode($config, true));

    return $let(array('username', 'password', 'local'), function (array $username, array $password, bool $local) {
        $credentials = A\curry(A\pluck);

        return A\extend(
            array($local), 
            array($local ? $credentials($username)('local') : $credentials($username)('cloudant')),
            array($local ? $credentials($password)('local') : $credentials($password)('cloudant'))
        );
    });
}

const _url = 'Chemem\\Fauxton\\Http\\_url';
function _url(array $credentials, array $opts) : string
{
    $cred = array('local', 'user', 'pass');
    $let = PM\letIn($cred, $credentials);

    return $let($cred, function (bool $local, string $user, string $pass) use ($opts) {
        $frag = A\head($opts);
        $fragments = A\compose(
            _urlFragments($opts, $local),
            A\partial('str_replace', array_keys($frag), array_values($frag)),
            A\partialRight('rtrim', '?')
        );

        return A\concat('/', _schemeHost($local, $user, $pass), $fragments(State::COUCH_ACTIONS));       
    });
}

const _urlFragments = 'Chemem\\Fauxton\\Http\\_urlFragments';
function _urlFragments(array $opts, bool $local) : callable
{
    return A\compose(
        A\partialRight(A\pluck, A\head(array_keys($opts))),
        A\partialRight(A\pluck, $local ? 'local' : 'cloudant')
    );
}

const _schemeHost = 'Chemem\\Fauxton\\Http\\_schemeHost';
function _schemeHost(bool $local, string $user, string $pass) : string
{
    return $local ? 
        State::COUCH_URI_LOCAL : 
        str_replace(
            array('{cloudantUser}', '{cloudantPass}', '{cloudantHost}'),
            array($user, $pass, A\concat('.', $user, 'cloudant', 'com')),
            State::COUCH_URI_CLOUDANT
        );
}

const _authHeaders = 'Chemem\\Fauxton\\Http\\_authHeaders';
function _authHeaders(bool $local, string $user, string $pass) : array
{
    $encode = A\compose(A\partial(A\concat, ':', $user), 'base64_encode');
    return !$local ? array() : array(A\concat(' ', 'Authorization:', 'Basic', $encode($pass)));
}

const _configPath = 'Chemem\\Fauxton\\Http\\_configPath';
function _configPath() : string
{
    $def = A\head(State::CONFIG_PATHS);
    $file = A\filter(function (string $file) use ($def) : bool {
        $strlen = A\partialRight('mb_strlen', 'utf-8');
        return is_file($file) && $strlen($file) > mb_strlen($def);
    }, State::CONFIG_PATHS);

    return !empty($file) ? A\head($file) : $def;
}

const _readConfig = 'Chemem\\Fauxton\\Http\\_readConfig';
function _readConfig() : IO
{
    return IO\readFile(_configPath());
}

const _exec = 'Chemem\\Fauxton\\Http\\_exec';
function _exec(callable $request, array $urlOpts, array $headers = array()) : IO
{
    $_exec = M\bind(function (string $config) use ($request, $urlOpts, $headers) {
        $credentials = _credentials($config); 
        $res = A\compose(
            A\partialRight(_url, $urlOpts),
            A\partialRight(
                _httpFetch, 
                A\extend($headers, State::COUCH_REQHEADERS, _authHeaders(...$credentials)), 
                $request
            ),
            IO\IO
        );
        return $res($credentials);
    }, _readConfig());

    return $_exec->exec();
}
