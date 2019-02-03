<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use \React\EventLoop\Factory;
use function \React\Promise\any;
use \Chemem\Fauxton\Actions\Action;
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

$action = Action::init($loop);

$actions = array(
    $action->docKeys('your_database', KEYS, array('include_docs' => 'true')),
    $action->search('your_database', QUERY)
);

any($actions)->then(
    function (ResponseInterface $result) {
        echo (string) $result->getBody();
    },
    function (\Exception $error) {
        echo $error->getMessage();
    }
);

$loop->run();
