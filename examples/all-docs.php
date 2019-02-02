<?php

require __DIR__ . '../vendor/autoload.php';

use \Chemem\Fauxton\Actions;
use \React\EventLoop\Factory;
use \Psr\Http\Message\ResponseInterface;

$loop = Factory::create();

$docs = Actions\allDocs('your_database')->run($loop)->then(function (ResponseInterface $data) {
    echo (string) $data->getBody();
});

$loop->run();
