<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Chemem\Fauxton\DocumentQueries;
use Chemem\Fauxton\QueryBuilder;

$dQuery = new DocumentQueries;
$qBuilder = new QueryBuilder;
$dQuery::setReturnType(false); //returns a JSON-decoded array
$password = $qBuilder->generatePassword(8, '123pass'); //generate a password
$dQuery->useFauxtonLogin('abcUser', $password); //set the authentication details

//add a selector field
$qBuilder::addParams('selector', [
    'founded' => ['$lt' => 1980]
]);
//add the fields whose values will be returned
$qBuilder::addParams('fields', ['_id', '_rev', 'team', 'coach']);
//add sort syntax
$qBuilder::addParams('sort', [
    ['year' => 'asc']
]);
//add a limit to the number of key-value pairs to be returned
$qBuilder::addParams('limit', 10);
//add a skip value
$qBuilder::addParams('skip', 0);

if (!empty($qBuilder::getParams())) {
    print_r($dQuery->mangoQuery('nba_info', $qBuilder::getParams()));
}