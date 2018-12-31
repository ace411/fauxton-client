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

const uuids = 'Chemem\\Fauxton\\Actions\\uuids';
function uuids(int $count) : IO
{
    return Http\_exec(_Http\getRequest, array('uuids' => array('{count}' => $count)));
}

const allDbs = 'Chemem\\Fauxton\\Actions\\allDbs';
function allDbs() : IO
{
    return Http\_exec(_Http\getRequest, array('allDbs' => array()));    
}

const allDocs = 'Chemem\\Fauxton\\Actions\\allDocs';
function allDocs(string $database, array $params = array()) : IO
{
    return _getWithParams('allDocs', array('{db}' => $database), $params);
}

const doc = 'Chemem\\Fauxton\\Actions\\doc';
function doc(string $database, string $docId, array $params = array()) : IO
{
    return Http\_exec(_Http\getRequest, array(
        'docById' => array(
            '{db}' => $database,
            '{docId}' => $docId,
            '{params}' => empty($params) ? '' : http_build_query($params)
        )
    ));
}

const search = 'Chemem\\Fauxton\\Actions\\search';
function search(string $database, array $query) : IO
{
    return _post($query)(array(
        'search' => array(
            '{db}' => $database
        )
    ));
}

const database = 'Chemem\\Fauxton\\Actions\\database';
function database(string $database, string $opt = 'view') : IO
{
    $request = PM\patternMatch(array(
        '"create"' => function () : callable {
            return _Http\putRequest;
        },
        '_' => function () : callable {
            return _Http\getRequest;
        }
    ), $opt);

    return Http\_exec($request, array('dbgen' => array('{db}' => $database)));
}

const _getWithParams = 'Chemem\\Fauxton\\Actions\\_getWithParams';
function _getWithParams(string $option, array $urlParams, array $qParams = array()) : IO
{
    return Http\_exec(_Http\getRequest, array(
        $option => A\extend($urlParams, array(
            '{params}' => empty($qParams) ? '' : http_build_query($qParams)
        ))
    ));
}

const _post = 'Chemem\\Fauxton\\Actions\\_post';
function _post(array $data) : callable
{
    return A\partial(Http\_exec, A\compose(_Http\postRequest, A\partialRight(_Http\setRequestBody, $data)));
}

const _put = 'Chemem\\Fauxton\\Actions\\_put';
function _put(array $data) : callable
{
    return A\partial(Http\_exec, A\compose(_Http\putRequest, A\partialRight(_Http\setRequestBody, $data)));
}

const _IOException = 'Chemem\\Fauxton\\Actions\\_IOException';
function _IOException(string $msg) : IO
{
    return IO\IO(function () use ($msg) {
        return function () use ($msg) {
            throw new \Exception($msg);
        };
    });
}

const _insert = 'Chemem\\Fauxton\\Actions\\_insert';
function _insert(string $key, string $database, array $data) : IO
{
    return _post($data)(array(
        $key => array(
            '{db}' => $database
        )
    ));
}

const insertSingle = 'Chemem\\Fauxton\\Actions\\insertSingle';
function insertSingle(string $database, array $data) : IO
{
    return _insert('dbgen', $database, $data);
}

const insertMultiple = 'Chemem\\Fauxton\\Actions\\insertMultiple';
function insertMultiple(string $database, array $data) : IO
{
    return _insert('bulkDocs', $database, $data);
}

const updateSingle = 'Chemem\\Fauxton\\Actions\\updateSingle';
function updateSingle(string $database, string $rev, string $docId, array $update) : IO
{
    return _put($update)(array(
        'deleteDoc' => array(
            '{db}' => $database,
            '{rev}' => $rev,
            '{docId}' => $docId
        )
    ));
}

const updateMultiple = 'Chemem\\Fauxton\\Actions\\updateMultiple';
function updateMultiple(string $database, array $data) : IO
{
    $check = A\partialRight(A\every, function (array $data) : bool {
        return count(A\filter(A\partialRight(A\arrayKeysExist, '_id', '_rev'), $data)) == count($data);
    });
    return !$check($data) ?
        !isset($data['docs']) ? 
            _IOException('"docs" key is missing. Schema is {"docs": [{data}]}') : 
            _IOException('"_rev" and "_id" keys are required for all fields.') :
        insertMultiple($database, $data);
}

const deleteSingle = 'Chemem\\Fauxton\\Actions\\deleteSingle';
function deleteSingle(string $database, string $rev, string $docId) : IO
{
    return Http\_exec(_Http\deleteRequest, array(
        'deleteDoc' => array(
            '{db}' => $database,
            '{rev}' => $rev,
            '{docId}' => $docId
        )
    ));
}

const deleteMultiple = 'Chemem\\Fauxton\\Actions\\deleteMultiple';
function deleteMultiple(string $database, array $data) : IO
{
    $delete = A\compose(A\partial(A\map, function (array $list) {
        return !is_array($list) ? $list : A\map(A\partialRight(A\extend, array('_deleted' => true)), $list);
    }), _post);    

    return !isset($data['docs']) ?
        _IOException('"docs" key is missing. Schema is {"docs": [{data}]}') :
        $delete($data)(array(
            'bulkDocs' => array(
                '{db}' => $database
            )
        ));
}

const changes = 'Chemem\\Fauxton\\Actions\\changes';
function changes(string $database, array $params = array()) : IO
{
    return _getWithParams('changes', array('{db}' => $database), $params);
}

const createDesignDoc = 'Chemem\\Fauxton\\Actions\\createDesignDoc';
function createDesignDoc(string $database, string $ddoc, array $docData) : IO
{
    return _put($docData)(array(
        'ddoc' => array(
            '{db}' => $database,
            '{ddoc}' => $ddoc
        )
    ));
}

const deleteDesignDoc = 'Chemem\\Fauxton\\Actions\\deleteDesignDoc';
function deleteDesignDoc(string $database, string $ddoc) : IO
{
    return Http\_exec(_Http\deleteRequest, array(
        'ddoc' => array(
            '{db}' => $database,
            '{ddoc}' => $ddoc
        )
    ));
}

const toCollection = 'Chemem\\Fauxton\\Actions\\toCollection';
function toCollection(callable $action, IO $response) : Collection
{
    $collection = M\bind(function (string $json) use ($action) {
        $action = A\compose(A\partialRight('json_decode', true), $action);
        return IO\IO(Collection::from(...$action($json)));
    }, $response);

    return $collection->exec();
}
