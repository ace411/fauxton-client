<?php

/**
 *
 * fauxton-client-supported CouchDB actions
 *
 * @author Lochemem Bruno Michael
 * @license Apache-2.0
 */

namespace Chemem\Fauxton\Actions;

use \Chemem\Fauxton\Http;
use \Chemem\Bingo\Functional\Http as _Http;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Immutable\Collection;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use \Chemem\Bingo\Functional\PatternMatching as PM;
use \React\Promise\Promise;
use \Chemem\Bingo\Functional\Functors\Monads\Reader;

const _action = 'Chemem\\Fauxton\\Actions\\_action';
function _action(...$opts) : Reader
{
    return Reader\reader(function ($loop) use ($opts) {
        return \React\Promise\resolve(Http\_exec($loop, ...$opts));
    });
}

const uuids = 'Chemem\\Fauxton\\Actions\\uuids';
function uuids(int $count) : Reader
{
    return _action('get', array('uuids' => array('{count}' => $count)));
}

const allDbs = 'Chemem\\Fauxton\\Actions\\allDbs';
function allDbs() : Reader
{
    return _action('get', array('allDbs' => array()));
}

const allDocs = 'Chemem\\Fauxton\\Actions\\allDocs';
function allDocs(string $database, array $params = array()) : Reader
{
    $docs = A\compose(
        A\partial(_queryParams, 'allDocs', array('{db}' => $database)),
        A\partial(_action, 'get')
    );
    return $docs($params);
}

const _queryParams = 'Chemem\\Fauxton\\Actions\\_queryParams';
function _queryParams(string $action, array $params, array $qParams = array()) : array
{
    return array(
        $action => A\extend($params, array(
            '{params}' => empty($qParams) ? '' : http_build_query($qParams)
        ))
    );
}

const doc = 'Chemem\\Fauxton\\Actions\\doc';
function doc(string $database, string $docId, array $params = array()) : Reader
{
    $doc = A\compose(
        A\partial(_queryParams, 'docById', array(
            '{db}' => $database,
            '{docId}' => $docId
        )),
        A\partial(_action, 'get')
    );

    return $doc($params);
}

const search = 'Chemem\\Fauxton\\Actions\\search';
function search(string $database, array $query) : Reader
{
    return _action('post', array('search' => array('{db}' => $database)), $query);
}

const database = 'Chemem\\Fauxton\\Actions\\database';
function database(string $database, string $opt = 'view') : Reader
{
    $request = PM\patternMatch(array(
        '"create"' => function () : callable {
            return A\partial(_action, 'put');
        },
        '_' => function () : callable {
            return A\partial(_action, 'get');
        }
    ), $opt);

    return $request(array('dbgen' => array('{db}' => $database)));
}

const _insert = 'Chemem\\Fauxton\\Actions\\_insert';
function _insert(string $key, string $database, array $data) : Reader
{
    return _action('post', array(
        $key => array(
            '{db}' => $database
        )
    ), $data);
}

const insertSingle = 'Chemem\\Fauxton\\Actions\\insertSingle';
function insertSingle(string $database, array $data) : Reader
{
    return _insert('dbgen', $database, $data);
}

const insertMultiple = 'Chemem\\Fauxton\\Actions\\insertMultiple';
function insertMultiple(string $database, array $data) : Reader
{
    return _insert('bulkDocs', $database, $data);
}

const updateSingle = 'Chemem\\Fauxton\\Actions\\updateSingle';
function updateSingle(string $database, string $rev, string $docId, array $update) : Reader
{
    return _action('put', array(
        'deleteDoc' => array(
            '{db}' => $database,
            '{rev}' => $rev,
            '{docId}' => $docId
        )
    ), $update);
}

function _readerException(string $msg) : Reader
{
    return Reader\reader(function ($loop) use ($msg) {
        throw new \Exception($msg);
    });
}

const updateMultiple = 'Chemem\\Fauxton\\Actions\\updateMultiple';
function updateMultiple(string $database, array $data) : Reader
{
    $check = A\partialRight(A\every, function (array $data) : bool {
        return count(A\filter(A\partialRight(A\arrayKeysExist, '_id', '_rev'), $data)) == count($data);
    });
    return !$check($data) ?
        !isset($data['docs']) ?
            _readerException('"docs" key is missing. Schema is {"docs": [{data}]}') :
            _readerException('"_rev" and "_id" keys are required for all fields.') :
        insertMultiple($database, $data);
}

const deleteSingle = 'Chemem\\Fauxton\\Actions\\deleteSingle';
function deleteSingle(string $database, string $rev, string $docId) : Reader
{
    return _action('delete', array(
        'deleteDoc' => array(
            '{db}' => $database,
            '{rev}' => $rev,
            '{docId}' => $docId
        )
    ));
}

const deleteMultiple = 'Chemem\\Fauxton\\Actions\\deleteMultiple';
function deleteMultiple(string $database, array $data) : Reader
{
    $delete = A\compose(A\partial(A\map, function (array $list) {
        return !is_array($list) ? $list : A\map(A\partialRight(A\extend, array('_deleted' => true)), $list);
    }), A\partial(_action, 'post', array('bulkdocs' => array('{db}' => $database))));

    return isset($data['docs']) ? $delete($data) : _readerException('"docs" key is missing. Schema is {"docs": [{data}]}');
}

const changes = 'Chemem\\Fauxton\\Actions\\changes';
function changes(string $database, array $params = array()) : Reader
{
    $changes = A\compose(
        A\partial(_queryParams, 'changes', array('{db}' => $database)),
        A\partial(_action, 'get')
    );

    return $changes($params);
}

const createDesignDoc = 'Chemem\\Fauxton\\Actions\\createDesignDoc';
function createDesignDoc(string $database, string $ddoc, array $docData) : Reader
{
    return _action('put', array(
        'ddoc' => array(
            '{db}' => $database,
            '{ddoc}' => $ddoc
        )
    ), $docData);
}

const deleteDesignDoc = 'Chemem\\Fauxton\\Actions\\deleteDesignDoc';
function deleteDesignDoc(string $database, string $ddoc) : Reader
{
    return _action('delete', array(
        'ddoc' => array(
            '{db}' => $database,
            '{ddoc}' => $ddoc
        )
    ));
}

const docKeys = 'Chemem\\Fauxton\\Actions\\docKeys';
function docKeys(string $database, array $keys, array $params = array()) : Reader
{
    return _action(
        'post',
        _queryParams('allDocs', array('{db}' => $database), $params),
        isset($keys['keys']) ? $keys : array('keys' => $keys)
    );
}

const createIndex = 'Chemem\\Fauxton\\Actions\\createIndex';
function createIndex(string $database, array $params) : Reader
{
    return isset($params['index']) && is_array($params['index']) ?
        _action('post', array('index' => array('{db}' => $database)), $params) :
        _readerException('"index" key is missing. Schema is {"index": [data]}');
}

const getIndexes = 'Chemem\\Fauxton\\Actions\\getIndexes';
function getIndexes(string $database) : Reader
{
    return _action('get', array('index' => array('{db}' => $database)));
}
