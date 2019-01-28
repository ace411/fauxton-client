<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use \React\EventLoop\Factory;
use \Chemem\Fauxton\Actions;

$data = '';

$loop = Factory::create();

$ret = Actions\uuids(2)->run($loop)->then(function ($result) use (&$data) {
    $data .= (string) $result->getBody();
});

$loop->run();

echo $data;