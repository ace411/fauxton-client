<?php

namespace Chemem\Fauxton\Tests;

use \Eris\Generator;
use \Chemem\Fauxton\Actions;
use \Chemem\Fauxton\Config\State;
use \React\EventLoop\Factory;
use \Clue\React\Block;
use \Psr\Http\Message\ResponseInterface;

class ActionsTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    private $eventLoop;

    public function setUp()
    {
        $this->eventLoop = Factory::create();
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

    public function testUuidsFunctionResolvesPromise()
    {
        $this->forAll(
            Generator\choose(1, 5)
        )
            ->then(function (int $count) {
                $uuids = Block\awaitAll([
                    Actions\uuids($count)->run($this->eventLoop)
                ], $this->eventLoop);

                $this->assertInternalType('string', $uuids[0]);
            });
    }
}