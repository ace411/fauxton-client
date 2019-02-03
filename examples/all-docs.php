<?php

require __DIR__ . '../vendor/autoload.php';

use \React\EventLoop\Factory;
use \Chemem\Fauxton\Actions\Action;
use \Psr\Http\Message\ResponseInterface;

$loop = Factory::create();

$docs = Action::init($loop)->allDocs('your_database')->then(
    function (ResponseInterface $response) {
        echo $response->getBody();
    },
    function (\Exception $error) {
        echo $error->getMessage();
    }
);

$loop->run();
