<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use \Chemem\Fauxton\Actions;
use \React\EventLoop\Factory;
use function \React\Promise\any;
use \Psr\Http\Message\ResponseInterface;

const KEYS = array(
    'lochbm@live.com',
    'lochbm@gmail.com'
);

const QUERY = array(
    'selector' => array(
        '_id' => array('$regex' => '(?i)lochbm')
    ),
    'skip' => 0,
    'limit' => 25
);

$loop = Factory::create();

$actions = array(
    Actions\docKeys('your_database', KEYS, array('include_docs' => 'true'))->run($loop),
    Actions\search('your_database', QUERY)->run($loop)
);

any($actions)->then(function (ResponseInterface $result) {
    echo (string) $result->getBody();
});

$loop->run();
