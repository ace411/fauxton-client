<?php

namespace Chemem\Fauxton\Http;

use Chemem\Fauxton\Config\State;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use function \Chemem\Bingo\Functional\PatternMatching\patternMatch;
use function \Chemem\Bingo\Functional\Algorithms\{concat, dropLeft};

/**
 * fetch :: String url -> String method -> Array curlOpts -> IO
 */

const fetch = 'Chemem\\Fauxton\\Http\\fetch';

function fetch(string $url, string $method = 'GET', array $curlOpts = []) : IO
{
    //fetch data from URL
    return IO::of(\curl_init($url))
        ->map(
            function ($curl) use ($curlOpts, $method) {
                $certOpts = State::COUCH_CURLOPTS_DEFAULT + [\CURLOPT_CAINFO => concat('/', dirname(__DIR__, 2), 'cacert.pem')];

                \curl_setopt_array(
                    $curl,
                    patternMatch(
                        [
                            '"DELETE"' => function () use ($certOpts, $curlOpts) { return $certOpts + $curlOpts + [\CURLOPT_CUSTOMREQUEST => 'DELETE']; },
                            '"PUT"' => function () use ($certOpts, $curlOpts) { return $certOpts + $curlOpts + [\CURLOPT_CUSTOMREQUEST => 'PUT']; },
                            '"POST"' => function () use ($certOpts, $curlOpts) { return $certOpts + $curlOpts + [\CURLOPT_POST => true]; },
                            '_' => function () use ($certOpts) { return $certOpts; }
                        ],
                        $method
                    ) 
                );

                $result = \curl_exec($curl);
                $error = \curl_errno($curl);
                $message = \curl_error($curl);
                \curl_close($curl);

                return [
                    'result' => $result, 
                    'code' => $error, 
                    'message' => $message 
                ];
            }
        )
        ->map(function (array $response) { return $response['code'] === 0 ? json_decode($response['result'], true) : dropLeft($response, 1); });
}