<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Chemem\Fauxton\DatabaseActions;
use Chemem\Fauxton\QueryBuilder;

$db = new DatabaseActions;
$qBuilder = new QueryBuilder;
$db::setReturnType(false); //returns a JSON-decoded array

$username = $db->getSessionAuthDetails()->userCtx->name; //get the name of the user
$password = $qBuilder->generatePassword(8, '123pass'); //generate a password

if (is_null($username)) {
    if ($db->cookieCreateUser('abcUser', $password)){
        $db->useFauxtonLogin('abcUser', $password); //use the login details
        if (!in_array('nba_info', $db->showAllDatabases())) {
            $db->createDatabase('nba_info'); //create the database nba_info
        }        
        print_r($db->showAllDatabases()); //show the list of created databases
    }
}
