<?php

namespace Chemem\Fauxton\Tests;

use \Eris\Generator;
use \Chemem\Fauxton\Actions;
use \Chemem\Fauxton\Config\State;
use \React\EventLoop\Factory;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Psr\Http\Message\ResponseInterface;

class ActionsTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    private $eventLoop;

    private $action;

    public function setUp()
    {
        $this->eventLoop = Factory::create();

        $this->action = Actions\Action::init($this->eventLoop);
    }

    const blockFn = 'Chemem\\Fauxton\\Tests\\ActionsTest::blockFn';

    public static function blockFn() : callable
    {
        return A\toException('Clue\\React\\Block\\await');
    }

    const ID_REGEX = '/([\w\d])+/';

    const REV_REGEX = '/(\d){1}(-){1}([\w\d]*)/';

    const specialConst = 'Chemem\\Fauxton\\Tests\\ActionsTest::specialConst';

    const revConst = 'Chemem\\Fauxton\\Tests\\ActionsTest::revConst';

    const idConst = 'Chemem\\Fauxton\\Tests\\ActionsTest::idConst';

    public static function specialConst(string $val, string $regex, int $limit)
    {
        return preg_match($regex, $val) && strlen($val) >= $limit;
    }

    public static function idConst(string $val) : bool
    {
        return self::specialConst($val, self::ID_REGEX, 32);
    }

    public static function revConst(string $val) : bool
    {
        return self::specialConst($val, self::REV_REGEX, 34);
    }

    /**
     * @eris-repeat 5
     */
    public function testUuidsFunctionResolvesPromise()
    {
        $this->forAll(Generator\choose(1, 5))
            ->then(function (int $count) {
                $promise = $this->action->uuids($count);
                $uuids = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $uuids);
            });
    }

    /**
     * @eris-repeat 5
     */
    public function testAllDbsOutputsListOfDatabasesInStringResponse()
    {
        $this->forAll(Generator\elements([null]))
            ->then(function ($arg) {
                $promise = $this->action->allDbs($arg);
                $allDbs = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $allDbs);
            });
    }

    /**
     * @eris-repeat 5
     */
    public function testAllDocsFunctionOutputsAllDocumentsInDatabase()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\associative([
                'include_docs' => Generator\elements('true', 'false'),
                'descending' => Generator\elements('true', 'false')
            ])
        )
            ->then(function (string $database, array $opts) {
                $promise = $this->action->allDocs($database, $opts);
                $allDocs = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $allDocs);
            });
    }

    /**
     * @eris-ratio 0.1
     * @eris-repeat 5
     */
    public function testDocFunctionOutputsDocContents()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\suchThat(self::idConst, Generator\string()),
            Generator\associative([
                'include_docs' => Generator\elements('true', 'false'),
                'descending' => Generator\elements('true', 'false')
            ])
        )
            ->then(function (string $database, string $docId, array $params) {
                $promise = $this->action->doc($database, $docId, $params);
                $doc = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $doc);
            });
    }

    /**
     * @eris-ratio 0.1
     * @eris-repeat 5
     */
    public function testSearchFunctionOutputsSearchData()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\associative([
                'selector' => Generator\associative([
                    '_id' => Generator\associative([
                        '$eq' => Generator\suchThat(self::idConst, Generator\string())
                    ])
                ]),
                'skip' => Generator\choose(1, 1),
                'limit' => Generator\choose(25, 30)
            ])
        )
            ->then(function (string $database, array $query) {
                $promise = $this->action->search($database, $query);
                $search = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $search);
            });
    }

    /**
     * @eris-repeat 5
     */
    public function testDatabaseOutputsDatabaseRelatedData()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\elements('view', 'create')
        )
            ->then(function (string $database, string $option) {
                $promise = $this->action->database($database, $option);
                $database = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $database);
            });
    }

    /**
     * @eris-repeat 5
     * @eris-ratio 0.1
     */
    public function testInsertSinglePutsDataInDatabase()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\associative([
                '_id' => Generator\suchThat(self::idConst, Generator\string()),
                'user' => Generator\names()
            ])
        )
            ->then(function (string $database, array $data) {
                $promise = $this->action->insertSingle($database, $data);
                $insert = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $insert);
            });
    }

    /**
     * @eris-repeat 5
     */
    public function testInsertMultiplePutsDataInDatabase()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\associative([
                'docs' => Generator\tuple(
                    Generator\associative([
                        'user' => Generator\names() 
                    ]),
                    Generator\associative([
                        'date' => Generator\date()
                    ]),
                )
            ])
        )
            ->then(function (string $database, array $data) {
                $promise = $this->action->insertMultiple($database, $data);
                $insert = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $insert);
            });
    }

    public function testQueryParamsPrintsArrayOfUrlQueryParameters()
    {
        $this->forAll(
            Generator\elements('docById', 'allDocs'),
            Generator\associative([
                '{db}' => Generator\string(),
                '{docId}' => Generator\suchThat(self::idConst, Generator\string())
            ]),
            Generator\associative([
                'include_docs' => Generator\elements('true', 'false'),
                'descending' => Generator\elements('true', 'false')
            ])
        )
            ->then(function (string $action, array $params, array $urlParams) {
                $promise = Actions\Action::_queryParams($action, $params, $urlParams);

                $this->assertInternalType('array', $promise);
            });
    }

    /**
     * @eris-repeat 5
     */
    public function testCreateIndexGeneratesDatabaseSearchQueryIndex()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\associative([
                'index' => Generator\associative([
                    'fields' => Generator\elements('_id', '_rev', 'foo')
                ]),
                'name' => Generator\names('foo-index', 'bar-index')
            ])
        )
            ->then(function (string $database, array $opts) {
                $promise = $this->action->createIndex($database, $opts);
                $index = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $index);
            });
    }

    /**
     * @eris-repeat 5
     */
    public function testGetIndexesOutputsIndexInformation()
    {
        $this->forAll(Generator\constant('testdb'))
            ->then(function (string $database) {
                $promise = $this->action->getIndexes($database);
                $indexes = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $indexes);
            });
    }

    /**
     * @eris-repeat 5
     */
    public function testChangesFunctionOutputsDatabaseChangeLogData()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\associative([
                'descending' => Generator\elements('true', 'false'),
                'include_docs' => Generator\elements('true', 'false'),
                'conflicts' => Generator\elements('true', 'false'),
                'limit' => Generator\choose(1, 5)
            ])
        )
            ->then(function (string $database, array $params) {
                $promise = $this->action->changes($database, $params);
                $changes = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $changes);
            });
    }

    /**
     * @eris-repeat 5
     * @eris-ratio 0.1
     */
    public function testUpdateSingleUpdatesSingleDocumentInDatabase()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\suchThat(self::revConst, Generator\string()),
            Generator\suchThat(self::idConst, Generator\string()),
            Generator\associative([
                'age' => Generator\choose(18, 70),
                'date' => Generator\date()
            ])
        )
            ->then(function (string $database, string $rev, string $docId, array $update) {
                $promise = $this->action->updateSingle($database, $rev, $docId, $update);
                $update = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $update);
            });
    }

    /**
     * @eris-repeat 5
     * @eris-ratio 0.1
     */
    public function testUpdateMultipleUpdatesMultipleDocumentsInDatabase()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\associative([
                'docs' => Generator\tuple(
                        Generator\associative([
                            '_id' => Generator\suchThat(self::idConst, Generator\string()),
                            '_rev' => Generator\suchThat(self::revConst, Generator\string()),
                            'name' => Generator\names()
                        ])
                    )
            ])
        )
            ->then(function (string $database, array $update) {
                $promise = $this->action->updateMultiple($database, $update);
                $update = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $update);
            });
    }

    /**
     * @eris-repeat 5
     * @eris-ratio 0.1
     */
    public function testDeleteSingleDeletesSingleDocument()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\suchThat(self::revConst, Generator\string()),
            Generator\suchThat(self::idConst, Generator\string())
        )
            ->then(function (string $database, string $rev, string $docId) {
                $promise = $this->action->deleteSingle($database, $rev, $docId);
                $delete = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $delete);
            });
    }

    /**
     * @eris-repeat 5
     * @eris-ratio 0.1
     */
    public function testDeleteMultipleDeletesMultipleDocuments()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\associative([
                'docs' => Generator\tuple(
                    Generator\associative([
                        '_id' => Generator\suchThat(self::idConst, Generator\string()),
                        '_rev' => Generator\suchThat(self::revConst, Generator\string())
                    ])
                )
            ])
        )
            ->then(function (string $database, array $data) {
                $promise = $this->action->deleteMultiple($database, $data);
                $delete = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $delete);
            });
    }

    /**
     * @eris-repeat 5
     */
    public function testCreateDesignDocCreatesDesignDocuments()
    {
        $this->forAll(
            Generator\constant('testdb'),
            Generator\elements('id-designdoc', 'rev-designdoc'),
            Generator\associative([
                'views' => Generator\associative([
                    'map' => Generator\elements(
                        'function (doc) { emit(doc._id); }', 
                        'function (doc) { emit(doc._rev, doc.user); }'
                    )
                ]) 
            ])
        )
            ->then(function (string $database, string $ddoc, array $docData) {
                $promise = $this->action->createDesignDoc($database, $ddoc, $docData);
                $ddoc = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $ddoc);
            });
    }
}
