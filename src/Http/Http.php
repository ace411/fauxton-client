<?php

namespace Chemem\Fauxton\Http;

use Chemem\Fauxton\Config\State;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use \Chemem\Bingo\Functional\Algorithms as A;

const fetch = 'Chemem\\Fauxton\\Http\\fetch';

function fetch(string $url, array $streamOpts = []) : IO
{
    $fetch = A\compose(
        A\partial(A\extend, [
            'ssl' => [
                'ciphers' => 'HIGH',
                'verify_peer' => true,
                'disable_compression' => true,
                'cafile' => path('cacert.pem')
            ]
        ]),
        'stream_context_create', 
        A\partial('file_get_contents', $url, false), 
        IO\IO
    );

    return $fetch($streamOpts);
}
