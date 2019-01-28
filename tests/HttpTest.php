<?php

namespace Chemem\Fauxton\Tests;

use \Eris\Generator;
use \Chemem\Fauxton\Config\State;
use \Chemem\Fauxton\Http;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use \Chemem\Bingo\Functional\PatternMatching as PM;
use \Psr\Http\Message\ResponseInterface;

class HttpTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    private $eventLoop;

    public function setUp()
    {
        $this->eventLoop = $this->getMockBuilder('React\\EventLoop\\LoopInterface')->getMock();
    }

    public function testUrlFunctionGeneratesAppropriateRequestUrl()
    {
        $this->forAll(
            Generator\string(),
            Generator\string(),
            Generator\bool(),
            Generator\int()
        )
            ->then(function (string $user, string $pwd, bool $local, int $count) {
                $url = Http\_url(array($local, $user, $pwd), array('uuids' => array('{count}' => $count)));

                $this->assertInternalType('string', $url);
                $this->assertRegExp('/(http|https){1}(:){1}(\/){1}([\w\D\W]*)/', $url);
            });
    }

    public function testTlsFunctionOutputsArrayContainingTLSConfiguration()
    {
        $this->forAll(Generator\constant('Chemem\\Fauxton\\Http\\_tls'))
            ->then(function (callable $function) {
                $tlsOpts = $function();

                $this->assertInternalType('array', $tlsOpts);
                $this->assertArrayHasKey('tls', $tlsOpts);
            });
    }

    public function testFetchFunctionOutputsInstanceOfReactPromise()
    {
        $this->forAll(Generator\elements(
            array('get', State::COUCH_URI_LOCAL, State::COUCH_REQHEADERS),
            array(
                'post', 
                A\concat('/', State::COUCH_URI_LOCAL, 'testdb', '_all_docs'), 
                State::COUCH_REQHEADERS, 
                json_encode(array('keys' => array('abc', '123')))
            )
        ))
            ->then(function (array $opts) {
                $req = Http\_fetch($this->eventLoop, ...$opts)->then(function (ResponseInterface $response) {
                    $this->assertInstanceOf(ResponseInterface::class, $response);
                    $this->assertInternalType('string', (string) $response->getBody());
                });

                $this->assertInstanceOf(\React\Promise\Promise::class, $req);
            });
    }
    
    public function testExecFunctionOutputsPromise()
    {
        $this->forAll(
            Generator\elements(
                array('uuids' => array('{count}' => 2)),
                array('allDbs' => array())
            )
        )->then(function (array $urlOpts) {
            $exec = Http\_exec($this->eventLoop, 'get', $urlOpts);

            $this->assertInstanceOf(\React\Promise\Promise::class, $exec);
        });        
    }

    public function testConfigPathOutputsFauxtonJsonConfigurationFilePath()
    {
        $this->forAll(Generator\constant('Chemem\\Fauxton\\Http\\_configPath'))
            ->then(function (callable $function) {
                $config = $function();

                $this->assertInternalType('string', $config);
                $this->assertRegExp('/([.\\w\/])+/', $config);
            });
    }

    public function testReadConfigOutputsInstanceOfReactPromise()
    {
        $this->forAll(Generator\constant('Chemem\\Fauxton\\Http\\_readConfig'))
            ->then(function (callable $function) {
                $config = $function($this->eventLoop);
                $config->then(function ($contents) {
                    $this->assertInternalType('string', $contents);
                });

                $this->assertInstanceOf(\React\Promise\Promise::class, $config);
            });
    }

    public function testCredentialsFunctionOutputsPivotalConfigurationOptions()
    {
        $this->forAll(Generator\constant('Chemem\\Fauxton\\Http\\_credentials'))
            ->then(function (callable $function) {
                $config = M\bind(A\compose($function, IO\IO), IO\readFile(Http\_configPath()))->exec();

                $this->assertInternalType('array', $config);
            });
    }
}
