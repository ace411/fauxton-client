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

    const idConst = 'Chemem\\Fauxton\\Tests\\ActionsTest::idConst';

    public static function idConst(string $val)
    {
        return preg_match('/([\w\d])+/', $val) && strlen($val) >= 32;
    }

    public function testActionFunctionOutputsPromiseEncapsulatedInReaderMonad()
    {
        $this->forAll(
            Generator\elements(
                array('get', array('uuids' => array('{count}' => 2)), State::COUCH_REQHEADERS),
                array(
                    'post',
                    array('search' => array('{db}' => 'testdb')),
                    State::COUCH_REQHEADERS,
                    array(
                        'selector' => array(
                            '_id' => array('$eq' => 'abc')
                        ),
                        'skip' => 0,
                        'limit' => 25
                    )
                )
            )
        )
            ->then(function (array $opts) {
                $action = Actions\_action(...$opts);

                $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\Reader::class, $action);
                $this->assertInstanceOf(\React\Promise\Promise::class, $action->run($this->eventLoop));
            });
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
}
