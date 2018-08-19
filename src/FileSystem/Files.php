<?php

namespace Chemem\Fauxton\FileSystem;

use \Chemem\Bingo\Functional\Functors\Monads\IO;
use function \Chemem\Bingo\Functional\PatternMatching\patternMatch;
use function \Chemem\Bingo\Functional\Algorithms\{compose, partialLeft, partialRight};

/**
 * fileInit :: String file -> IO
 */

const fileInit = 'Chemem\\Fauxton\\FileSystem\\fileInit';

function fileInit(string $file)
{
    return IO::of($file)
        ->map(function (string $file) { return is_file($file) ? $file : 'empty'; });
}

/**
 * read :: IO -> IO
 */

const read = 'Chemem\\Fauxton\\FileSystem\\read';

function read(IO $fileInit) : IO
{
    return $fileInit
        ->map(
            function ($file) {
                return patternMatch(
                    [
                        '"empty"' => function () { return json_decode('[]', true); }, 
                        '_' => function () use ($file) {
                            $read = compose(
                                'file_get_contents',
                                partialRight('json_decode', true)
                            );

                            return $read($file);
                        }
                    ],
                    $file
                );
            }
        );
}

/**
 * write :: IO -> String opt -> IO
 */

const write = 'Chemem\\Fauxton\\FileSystem\\write';

function write(IO $fileInit, array $data, string $opt = 'write') : IO
{
    return $fileInit
        ->map(
            function (string $file) use ($opt, $data) {
                return patternMatch(
                    [
                        '"empty"' => function () { return false; },
                        '_' => function () use ($opt, $file, $data) { 
                            $write = partialLeft('file_put_contents', $file);
                            
                            return $write(
                                json_encode($data, \JSON_PRETTY_PRINT), 
                                patternMatch(
                                    [
                                        '"write"' => function () { return \LOCK_EX; },
                                        '"append"' => function () { return \FILE_APPEND | \LOCK_EX; },
                                        '_' => function () { return false; }
                                    ],
                                    $opt
                                )
                            );
                        }
                    ],
                    $file
                );
            }
        );
}
