<?php

/**
 * 
 * fauxton-client console command parser
 * 
 * @author Lochemem Bruno Michael
 * @license Apache-2.0
 */

namespace Chemem\Fauxton\Console;

use Chemem\Fauxton\Config\State;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use \Mmarica\DisplayTable;
use \Chemem\Fauxton\{Http, Actions};
use \JakubOnderka\PhpConsoleColor\ConsoleColor;
use \Chemem\Bingo\Functional\PatternMatching as PM;

const _replPrompt = 'Chemem\\Fauxton\\Console\\_replPrompt';
function _replPrompt() : IO
{
    return M\bind(function (string $prompt) {
        $ret = A\compose(A\partial('printf', '%s'), IO\IO);
        return $ret($prompt);
    }, IO\IO(State::CONSOLE_PROMPT));
}

const _style = 'Chemem\\Fauxton\\Console\\_style';
function _style(string $text, string $style = 'none') : string
{
    $color = new ConsoleColor;

    return in_array($style, $color->getPossibleStyles()) && $color->isSupported() ?
        $color->apply($style, $text) :
        A\identity($text);
}

function _header() : callable
{
    return A\compose(A\partial(A\map, 'array_keys'), A\flatten, A\unique);
}

function _formatList(array $data) : string
{
    $format = A\compose(
        'json_encode',
        A\partial('preg_replace', '/[{\}\[\]\"]+/', ''),
        A\partial('str_replace', ',', ', ')
    );

    return $format($data);
}

function _jsonRow(array $row) : array
{
    return A\map(function ($val) {
        return !is_array($val) ? $val : _formatList($val);
    }, $row);
}

const _row = 'Chemem\\Fauxton\\Console\\_row';
function _row(array $keys, array $data) : array
{
    return A\map(function (array $data) use ($keys) {
        $fill = A\fold(function ($acc, $key) use ($data) {
            if (!isset($data[$key])) {
                $acc[$key] = 'null';
            }
            return $acc;
        }, $keys, array());
        $rows = A\extend(_jsonRow($data), $fill);
        ksort($rows);
        return $rows;
    }, $data);
}

const _headerRow = 'Chemem\\Fauxton\\Console\\_headerRow';
function _headerRow(array $data) : array
{
    $headers = _header()($data);
    asort($headers);
    return array_values($headers);
}

function _dataRows(array $data) : array
{
    $sort = A\compose(_headerRow, A\partialRight(_row, $data), A\partial(A\map, 'array_values'));
    return $sort($data);
}

function _table(callable $transform, array $data) : IO
{
    $ret = $transform($data);
    return IO\IO(
        DisplayTable::create()
            ->headerRow(_headerRow($ret))
            ->dataRows(_dataRows($ret))
            ->toText()
            ->mysqlBorder()
            ->generate()
    );
}

const _toConstant = 'Chemem\\Fauxton\\Console\\_toConstant';
function _toConstant(IO $operation) : IO
{
    return M\bind(function ($result) {
        return IO\IO(function () use ($result) {
            return function () use ($result) {
                return A\constantFunction($result);
            };
        });
    }, $operation);
}

function _catchResponse(IO $response) : IO
{
    $catch = A\compose(_toConstant, IO\catchIO);
    return $catch($response);
}

const _output = 'Chemem\\Fauxton\\Console\\_output';
function _output(callable $action, IO $response) : IO
{
    return M\bind(function (callable $output) use ($action) {
        $list = json_decode($output(), true);
        return $list == null ? IO\IO($list) : _table($action, $list);
    }, _catchResponse($response));
}

function _printConfig(string $opt) : IO
{
    return _output(function (array $config) use ($opt) {
        $pluck = A\partial(A\pluck, $config);
        return PM\patternMatch(array(
            '"credentials"' => function () use ($pluck) {
                return array(
                    'username' => $pluck('username'),
                    'password' => $pluck('password')
                );
            },
            '"console"' => function () use ($pluck) {
                return array($pluck('console'));
            },
            '"local"' => function () use ($pluck) {
                return array(array('local' => $pluck('local')));
            },
            '"https"' => function () use ($pluck) {
                return array('https' => $pluck('https'));
            },
            '_' => function () use ($config) {
                return array(array('error' => 'Options are https, local, console, and credentials'));
            }
        ), $opt);
    }, Http\_readConfig());
}

function _cliOpts(string $opt, array $merge = array()) : IO
{
    return M\bind(function (string $content) use ($opt, $merge) {
        $config = A\compose(
            A\partialRight('json_decode', true), 
            A\partialRight(A\pluck, 'console'), 
            A\partialRight(A\pluck, $opt),
            A\partialRight(A\extend, $merge),
            IO\IO
        );
        return $config($content);
    }, Http\_readConfig());
}

function _singleColOutput(string $colName, IO $action) : IO
{
    return _output(function (array $data) use ($colName) : array {
        return A\fold(function (array $acc, string $database) use ($colName) {
            $acc[][$colName] = $database;
            return $acc;
        }, $data, array());
    }, $action);
}

function _searchSelector(string $selector) : IO
{
    return _cliOpts('search', array(
        'selector' => json_decode($selector, true)
    ));
}

function _search(string $database, string $selector) : IO
{
    return M\bind(function (array $query) use ($database) {
        $output = A\compose(
            A\partial(Actions\search, $database),
            A\partial(_output, A\partialRight(A\pluck, 'docs'))
        );
        return $output($query);
    }, _searchSelector($selector));
}

function _databases() : IO
{
    return _singleColOutput('database', Actions\allDbs());
}

function _uuids(int $count) : IO
{
    return _singleColOutput('id', Actions\uuids($count));
}

function _singleDoc(string $database, string $docId) : IO
{
    return _output(function (array $doc) {
        return array($doc);
    }, M\bind(A\partial(Actions\doc, $database, $docId), _cliOpts('alldocs')));
}

function _multiple(callable $action) : IO
{
    return _output(function (array $docs) {
        $ret = A\compose(
            A\partialRight(A\pluck, 'rows'), 
            A\partial(A\map, A\partialRight(A\pluck, 'doc')),
            A\partial(A\filter, function (array $doc) : bool {
                return !key_exists('views', $doc); 
            })
        );
        return $ret($docs);
    }, M\bind($action, _cliOpts('alldocs')));
}

function _dbData(string $database) : IO
{
    return _output(function (array $data) {
        return array(A\addKeys($data, 'doc_count', 'disk_size', 'data_size', 'compact_running', 'doc_del_count'));
    }, Actions\database($database));
}

function _keyDocs(string $database, string $keys) : IO
{
    return _multiple(A\partial(Actions\docKeys, $database, array('keys' => json_decode($keys, true))));
}

function _multipleDocs(string $database) : IO
{
    return _multiple(A\partial(Actions\allDocs, $database));
}

function _cmd() : callable
{
    return A\partialRight(_output, IO\IO(json_encode(State::CONSOLE_COMMANDS)));
}

function _allCmds() : IO
{
    return _cmd()(A\identity);
}

const _explain = 'Chemem\\Fauxton\\Console\\_explain';
function _explain(string $cmd) : IO
{
    return !isset(State::CONSOLE_COMMANDS[$cmd]) ? 
        IO\IO(_style('Command does not exist', 'yellow')) :
        _cmd()(function (array $cmds) use ($cmd) {
            return array(A\pluck($cmds, $cmd));
        });
}

const _writeConfig = 'Chemem\\Fauxton\\Console\\_writeConfig';
function _writeConfig(callable $transform, callable $printMsg) : IO
{
    return M\bind(function (string $content) use ($transform, $printMsg) {
        $write = A\compose(
            A\partialRight('json_decode', true),
            $transform,
            A\partialRight('json_encode', JSON_PRETTY_PRINT), 
            A\partial(IO\writeFile, Http\_configPath()), 
            $printMsg
        );
        return $write($content);
    }, Http\_readConfig());
}

const _writeMsg = 'Chemem\\Fauxton\\Console\\_writeMsg';
function _writeMsg(IO $result, string $success, string $failure) : IO 
{
    return M\bind(function (int $result) use ($success, $failure) {
        return IO\IO($result > 0 ? $success : $failure);
    }, $result);
}

const _gzip = 'Chemem\\Fauxton\\Console\\_gzip';
function _gzip(string $file, string $content) : IO
{
    return IO\IO(function () use ($file, $content) {
        $resource = gzopen($file, 'w9');
        gzwrite($resource, $content);
        return gzclose($resource);
    });
}

const _gUnzip = 'Chemem\\Fauxton\\Console\\_gUnzip';
function _gUnzip(string $file) : IO
{
    $unzip = A\compose('gzfile', A\partial('implode', ''), IO\IO);
    return $unzip($file);
}

const _gzDoc = 'Chemem\\Fauxton\\Console\\_gzDoc';
function _gzDoc(string $database, string $file) : IO
{
    return M\bind(
        A\partial(_gzip, A\concat('.', $file, 'gz')), 
        M\bind(A\partial(Actions\allDocs, $database), _cliOpts('alldocs'))
    );
}

const _gzDocUnzip = 'Chemem\\Fauxton\\Console\\_gzDocUnzip';
function _gzDocUnzip(string $file) : IO
{
    return _multiple(function () use ($file) : IO {
        return _gUnzip(A\concat('.', $file, 'gz')); 
    });
}