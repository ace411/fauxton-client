<?php declare(strict_types=1);

/**
 *
 * fauxton-client-supported CouchDB actions
 *
 * @author Lochemem Bruno Michael
 * @license Apache-2.0
 */

namespace Chemem\Fauxton\Actions;

use \Chemem\Fauxton\Http;
use \React\Promise\Promise;
use \Chemem\Bingo\Functional\Algorithms as A;
use function \Chemem\Bingo\Functional\PatternMatching\patternMatch;

class Action
{
    const _queryParams = 'Chemem\\Fauxton\\Actions\\Action::_queryParams';

    const _resolve = 'Chemem\\Fauxton\\Actions\\Action::_resolve';

    const _withDb = 'Chemem\\Fauxton\\Actions\\Action::_withDb';

    private $loop;
    
    public function __construct($loop)
    {
        $this->loop = $loop;
    }

    public static function init($loop) : Action
    {
        return new static($loop);
    }

    public static function _resolve($loop, ...$opts) : Promise
    {
        return \React\Promise\resolve(Http\_exec($loop, ...$opts));
    }

    public static function _queryParams(string $action, array $params, array $qParams = array()) : array
    {
        return array(
            $action => A\extend($params, array(
                '{params}' => empty($qParams) ? '' : http_build_query($qParams)
            ))
        );
    }

    public static function _reject(string $message) : Promise
    {
        return new \React\Promise\Promise(function ($resolve, $reject) use ($message) {
            return $reject($message);
        });
    }

    public static function _withDb($loop, string $method, string $opt, string $database, array $params = []) : Promise
    {
        return self::_resolve($loop, $method, array(
            $opt => array(
                '{db}' => $database
            )
        ), $params);
    }

    public function uuids(int $count) : Promise
    {
        return self::_resolve($this->loop, 'get', ['uuids' => ['{count}' => $count]]);
    }

    public function allDbs() : Promise
    {
        return self::_resolve($this->loop, 'get', ['allDbs' => []]);
    }

    public function database(string $database, string $option = 'view') : Promise
    {
        $action = A\partial(self::_resolve, $this->loop);
        $urlOpts = array(
            'dbgen' => array(
                '{db}' => $database
            )
        );        
        
        return patternMatch(array(
            '"create"' => function () use ($action, $urlOpts) {
                return $action('put', $urlOpts);
            },
            '"view"' => function () use ($action, $urlOpts) {
                return $action('get', $urlOpts);
            },
            '_' => function () {
                return self::_reject('One of either "create" or "view" is allowed.');
            }
        ), $option);
    }

    public function allDocs(string $database, array $params = []) : Promise
    {
        $docs = A\compose(
            A\partial(self::_queryParams, 'allDocs', array('{db}' => $database)),
            A\partial(self::_resolve, $this->loop, 'get')
        );

        return $docs($params);
    }

    public function docKeys(string $database, array $keys, array $params = array()) : Promise
    {
        $resolve = A\partial(self::_resolve, $this->loop, 'post');
        $keys = A\compose(
            A\partial(self::_queryParams, 'allDocs', array('{db}' => $database)),
            A\partialRight($resolve, isset($keys['keys']) ? $keys : array('keys' => $keys))
        );

        return $keys($params);
    }

    public function doc(string $database, string $docId, array $params = array()) : Promise
    {
        $doc = A\compose(
            A\partial(self::_queryParams, 'docById', array(
                '{db}' => $database,
                '{docId}' => $docId
            )),
            A\partial(self::_resolve, $this->loop, 'get')
        );

        return $doc($params);
    }

    public function search(string $database, array $query) : Promise
    {
        return self::_withDb($this->loop, 'post', 'search', $database, $query);
    }

    public function createIndex(string $database, array $params) : Promise
    {
        return self::_withDb($this->loop, 'post', 'index', $database, $params);
    }

    public function getIndexes(string $database) : Promise
    {
        return self::_withDb($this->loop, 'get', 'index', $database);
    }

    public function insertSingle(string $database, array $data) : Promise
    {
        return self::_withDb($this->loop, 'post', 'dbgen', $database, $data);
    }

    public function insertMultiple(string $database, array $data) : Promise
    {
        return !isset($data['docs']) ?
            self::_reject('"docs" key is missing. Schema is {"docs": [{data}]}') :
            self::_withDb($this->loop, 'post', 'bulkDocs', $database, $data);
    }

    public function updateSingle(string $database, string $rev, string $docId, array $update) : Promise
    {
        return self::_resolve($this->loop, 'put', array(
            'deleteDoc' => array(
                '{db}' => $database,
                '{rev}' => $rev,
                '{docId}' => $docId
            )
        ), $update);
    }

    public function updateMultiple(string $database, array $data) : Promise
    {
        $check = A\partialRight(A\every, function (array $data) : bool {
            return count(A\filter(A\partialRight(A\arrayKeysExist, '_id', '_rev'), $data)) == count($data);
        });
        
        return !$check($data) ?
            self::_reject('"_rev" and "_id" keys are required for all fields.') :
            $this->insertMultiple($database, $data);
    }

    public function deleteSingle(string $database, string $rev, string $docId) : Promise
    {
        return self::_resolve($this->loop, 'delete', array(
            'deleteDoc' => array(
                '{db}' => $database,
                '{rev}' => $rev,
                '{docId}' => $docId
            )
        ));
    }

    public function deleteMultiple(string $database, array $data) : Promise
    {
        $delete = A\compose(A\partial(A\map, function (array $list) {
            return !is_array($list) ? $list : A\map(A\partialRight(A\extend, array('_deleted' => true)), $list);
        }), A\partial(self::_resolve, $this->loop, 'post', array('bulkdocs' => array('{db}' => $database))));

        return isset($data['docs']) ? $delete($data) : self::_reject('"docs" key is missing. Schema is {"docs": [{data}]}');
    }

    public function changes(string $database, array $params) : Promise
    {
        $changes = A\compose(
            A\partial(self::_queryParams, 'changes', array('{db}' => $database)),
            A\partial(self::_resolve, $this->loop, 'get')
        );
        
        return $changes($params);
    }

    public function createDesignDoc(string $database, string $ddoc, array $docData) : Promise
    {
        return self::_resolve($this->loop, 'put', array(
            'ddoc' => array(
                '{db}' => $database,
                '{ddoc}' => $ddoc
            )
        ), $docData);
    }

    public function deleteDesignDoc(string $database, string $ddoc) : Promise
    {
        return self::_resolve($this->loop, 'delete', array(
            'ddoc' => array(
                '{db}' => $database,
                '{ddoc}' => $ddoc
            )
        ));
    }
}
