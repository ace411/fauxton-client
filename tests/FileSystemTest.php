<?php

namespace Chemem\Fauxton\Tests;

use \Eris\Generator;
use function \Chemem\Fauxton\FileSystem\{fileInit, read, write};
use function \Chemem\Bingo\Functional\Algorithms\{concat};

class FileSystemTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    public function testFileInitValidatesFileContentExistence()
    {
        $this->forAll(
            Generator\map(
                function (string $file) { return concat('/', dirname(__DIR__), $file); },
                Generator\elements('fauxton.json', 'composer.json')
            )
        )
            ->then(
                function (string $file) {
                    $content = fileInit($file);

                    $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $content);
                    $this->assertEquals($file, $content->exec());
                }
            );
    }

    public function testReadFunctionOutputsFileContents()
    {
        $this->forAll(
            Generator\map(
                function (string $file) { return concat('/', dirname(__DIR__), $file); },
                Generator\elements('fauxton.json', 'composer.json')
            )
        )
            ->then(
                function (string $file) {
                    $contents = read(fileInit($file));

                    $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $contents);
                    $this->assertInternalType('array', $contents->exec());
                }
            );
    }

    public function testWriteFunctionAppendsDataToFile()
    {
        $write = write(
            fileInit(concat('/', dirname(__DIR__), 'fauxton.json')),
            [
                'username' => [
                    'local' => '',
                    'cloudant' => ''
                ],
                'password' => [
                    'local' => '',
                    'cloudant' => ''
                ],
                'local' => true
            ]
        );

        $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $write);
        $this->assertEquals(true, $write->exec());
    }
}