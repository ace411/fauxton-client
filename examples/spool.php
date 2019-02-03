<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use \React\EventLoop\Factory;
use \Chemem\Fauxton\Actions\Action;
use \Psr\Http\Message\ResponseInterface;

$data = '';

$loop = Factory::create();

$action = Action::init($loop)->uuids(2)->then(
    function (ResponseInterface $result) use (&$data) {
        $data .= (string) $result->getBody();
    },
    function (\Exception $error) use (&$data) {
        $data .= $error->getMessage();
    }
);

$loop->run();

echo $data;
