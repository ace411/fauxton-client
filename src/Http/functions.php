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
use \Chemem\Fauxton\Config\State;
use \React\EventLoop\Factory;
use \Clue\React\Buzz\Browser;
use \React\Promise\Promise;
use \Psr\Http\Message\ResponseInterface;
use \React\Stream\ReadableResourceStream;
use \React\Filesystem\Filesystem;

function _tls() : array
{
    return array(
        'tls' => A\extend(State::COUCH_TLS, array(
            'cafile' => \Composer\CaBundle\CaBundle::getBundledCaBundlePath()
        ))
    );
}

const _fetch = 'Chemem\\Fauxton\\Http\\_fetch';
function _fetch($loop, string $method, ...$opts) : Promise
{
    return (new \ReflectionMethod('Clue\\React\\Buzz\\Browser', $method))
        ->invoke(new Browser($loop, new \React\Socket\Connector($loop, _tls())), ...$opts);
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
    return A\extend(
        State::COUCH_REQHEADERS,
        !$local ? array() : array('Authorization' => A\concat(' ', 'Basic', $encode($pass)))
    );
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
function _readConfig($loop) : Promise
{
    return Filesystem::create($loop)->getContents(_configPath());
}

const _exec = 'Chemem\\Fauxton\\Http\\_exec';
function _exec($loop, string $method, array $urlOpts, array $body = array()) : Promise
{
    return _readConfig($loop)
        ->then(function (string $contents) use ($method, $urlOpts, $body, $loop) {
            $credentials = _credentials($contents);
            return _fetch($loop, $method, _url($credentials, $urlOpts), _authHeaders(...$credentials), empty($body) ? '' : json_encode($body));
        });
}
