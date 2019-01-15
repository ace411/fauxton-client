<?php

use \Eris\Generator;

use \Chemem\Fauxton\Console;
use \Chemem\Bingo\Functional\Functors\Monads\IO;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    public function testReplPromptPrintsReplPrompt()
    {
        $this->forAll(Generator\constant(Console\_replPrompt))
            ->then(function (callable $function) {
                $prompt = $function();

                $this->assertInstanceOf(IO::class, $prompt);
                $this->assertInternalType('integer', $prompt->exec());
            });
    }

    public function testStyleFunctionOutputsStyledConsoleOutput()
    {
        $this->forAll(
            Generator\string(),
            Generator\elements('red', 'green', 'blue', 'bold', 'italic')
        )
            ->then(function (string $text, string $style) {
                $styled = Console\_style($text, $style);

                $this->assertInternalType('string', $styled);
            });
    }
}