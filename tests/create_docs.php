<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Chemem\Fauxton\DocumentActions;
use Chemem\Fauxton\QueryBuilder;
use Chemem\Fauxton\DatabaseActions;

$db = new DatabaseActions;
$doc = new DocumentActions;
$qBuilder = new QueryBuilder;
$db::setReturnType(false); //returns a JSON-decoded json array
$password = $qBuilder->generatePassword(8, '123pass'); //generate a password
$db->useFauxtonLogin('abcUser', $password); //set the authentication details

$ids = $doc->generateMultipleIds(3); //generate 6 distinct ids

//create the following docs
$qBuilder::addParams('docs', [
    [
        '_id' => $ids[0],
        'team' => 'Miami Heat',
        'coach' => 'Erik Spoelstra',
        'arena' => 'American Airlines Arena',
        'founded' => 1988
    ],
    [
        '_id' => $ids[1],
        'team' => 'San Antonio Spurs',
        'coach' => 'Gregg Popovich',
        'arena' => 'AT&T Center',
        'founded' => 1967
    ],
    [
        '_id' => $ids[2],
        'team' => 'Washington Wizards',
        'coach' => 'Scott Brooks',
        'arena' => 'Verizon Center',
        'founded' => 1961
    ]
]);

print_r($doc->createMultipleDocs('nba_info', $qBuilder::getParams()));
