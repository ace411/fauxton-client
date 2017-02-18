<?php

/**
 * FauxtonClient REST API DocumentQueries class
 * This class contains methods that describe document interactions
 * fauxton-client is a simple wrapper that eases CouchDB interactions
 * 
 * @package fauxton-client
 * @author Lochemem Bruno Michael
 * @link https://www.github.com/fauxton-client
 *
 */

namespace Chemem\Fauxton;

class DocumentQueries
{
    use Config\Connect;
    
    /**
     * Create a CouchDB index
     *
     * @param string $dbName The name of the database in which the index will reside
     * @param array $indexOptions An array of index options
     * @return string $response The response object
     *
     */
    
    public function createIndex($dbName, array $indexOptions)
    {
        return $this->executeCurlRequest("{$dbName}/_index", 'POST', json_encode($indexOptions));
    }
    
    /**
     * Show all the indexes in a particular database
     *
     * @param string $dbName
     * @return string $response
     *
     */
    
    public function showIndexes($dbName)
    {
        return $this->executeCurlRequest("{$dbName}/_index", 'GET');
    }
    
    /**
     * Delete an index
     * 
     * @param string $dbName
     * @param string $ddoc The design document id of the index
     * @param string $indexName The name of the index
     * @return string $response
     *
     */
    
    public function deleteIndex($dbName, $ddoc, $indexName)
    {
        return $this->executeCurlRequest("{$dbName}/_index/{$ddoc}/json/{$indexName}", 'DELETE');
    }
    
    /**
     * Perform a mango query which is an alternative to map-reduce
     *
     * @param string $dbName
     * @param array $params Query parameters
     * @return string $response
     *
     */
    
    public function mangoQuery($dbName, array $params)
    {
        return $this->executeCurlRequest("{$dbName}/_find", 'POST', json_encode($params));
    }
}