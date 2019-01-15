<?php

/**
 * 
 * fauxton-client console functions
 * 
 * @author Lochemem Bruno Michael
 * @license Apache-2.0
 */

namespace Chemem\Fauxton\Console;

use Chemem\Fauxton\{Http, Actions};
use Chemem\Fauxton\Config\State;
use JakubOnderka\PhpConsoleColor\ConsoleColor;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use function \Chemem\Bingo\Functional\PatternMatching\patternMatch;

const execCmd = 'Chemem\\Fauxton\\Console\\execCmd';

function execCmd(string $cmd) : IO
{
    return patternMatch(
        [
            '["cred", type, user, pass]' => function (string $type, string $user, string $pass) {
                return _writeConfig(function (array $config) use ($type, $user, $pass) : array {
                    if (!isset($config['username'][$type]) && !isset($config['password'][$type])) {
                        return $config;
                    }
                    $config['username'][$type] = $user;
                    $config['password'][$type] = $pass;
                    return $config;
                }, A\partialRight(_writeMsg, 'Credentials not updated', 'Credentials updated'));
            },
            '["use", option]' => function (string $option) {
                return _writeConfig(function (array $config) use ($option) {
                    $config['local'] = $option == 'local' ? true : false;
                    return $config;
                }, A\partialRight(_writeMsg, 'Configuration not updated', 'Configuration updated'));
            },
            '["gzip", database, file]' => function (string $database, string $file) {
                $gzip = A\compose(
                    A\partialRight(_gzDoc, $file),
                    A\partialRight(_writeMsg, 'Data not zipped', A\concat('', $file, '.gz created'))
                );
                return $gzip($database);
            },
            '["unzip", file]' => function (string $file) {
                return _gzDocUnzip($file);
            },
            '["search", database, selector]' => function (string $database, string $selector) {
                return _search($database, $selector);
            },
            '["alldocs", database]' => function (string $database) {
                return _multipleDocs($database);
            },
            '["db", database]' => function (string $database) {
                return _dbData($database);                
            },
            '["config", cmd]' => function (string $cmd) {
                return _printConfig($cmd);
            },
            '["uuids", count]' => function (string $count) {
                return _uuids((int) $count);
            },
            '["doc", database, docId]' => function (string $database, string $docId) {
                return _singleDoc($database, $docId);
            },
            '["docs", database, keys]' => function (string $database, string $keys) {
                return _keyDocs($database, $keys);
            },
            '["explain", cmd]' => function (string $cmd) {
                return _explain($cmd);
            },
            '["alldbs"]' => function () {
                return _databases();
            },
            '["help"]' => function () {
                return _allCmds();
            },
            '["exit"]' => function () {
                M\bind(function (string $msg) {
                    $result = A\compose(A\partial('printf', '%s'), IO\IO);
                    return $result($msg);
                }, IO\IO('Thanks for using the console'));
                exit();
            },
            '_' => function () {
                $output = A\compose(A\partialRight(_style, 'red'), IO\IO);
                return $output('Input not recognized');
            }
        ],
        explode(' ', $cmd)
    );
}
