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

    public function setUp()
    {
        $this->eventLoop = Factory::create();
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
                $promise = Actions\uuids($count)->run($this->eventLoop);
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
        $this->forAll(Generator\constant(Actions\allDbs))
            ->then(function (callable $function) {
                $promise = $function()->run($this->eventLoop);
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
                $promise = Actions\allDocs($database, $opts)->run($this->eventLoop);
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
                $promise = Actions\doc($database, $docId, $params)->run($this->eventLoop);
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
                $promise = Actions\search($database, $query)->run($this->eventLoop);
                $search = self::blockFn()($promise, $this->eventLoop);

                $this->assertInstanceOf(\React\Promise\Promise::class, $promise);
                $this->assertInternalType('string', $search);
            });
    }
}