<?php

/**
 * FauxtonClient DocQueryTest class
 * This class contains tests for Couch supported queries specified in the DocumentQueries class
 * fauxton-client is a simple wrapper that eases CouchDB interactions
 * 
 * @package fauxton-client
 * @author Lochemem Bruno Michael
 * @link https://www.github.com/fauxton-client
 *
 */

class DocQueryTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests createIndex
     */
    
    public function testCreateIndex()
    {
        $index = [
            'index' => [
                'fields' => ['foo', 'bar']
            ],
            'name' => 'foo-index'
        ];
        $doc = new \Chemem\Fauxton\DocumentQueries;
        $this->assertNotEquals($doc->createIndex('dummy_info', $index), null);
        $this->assertNotEquals($doc->createIndex('dummy_info', $index), false);
    }
    
    /**
     * Tests showIndexes
     */
    
    public function testShowIndexes()
    {
        $doc = new \Chemem\Fauxton\DocumentQueries;
        $this->assertNotEquals($doc->showIndexes('dummy_info'), false);
        $this->assertNotEquals($doc->showIndexes('dummy_info'), null);
    }
    
    /**
     * Tests deleteIndex
     */
    
    public function testDeleteIndex()
    {
        $doc = new \Chemem\Fauxton\DocumentQueries;
        $this->assertNotEquals(
            $doc->deleteIndex(
                'dummy_info', 
                '_design/a5f4711fc9448864a13c81dc71e660b524d7410c', 
                'foo-index'
            ), 
            false
        );
        $this->assertNotEquals(
            $doc->deleteIndex(
                'dummy_info', 
                '_design/a5f4711fc9448864a13c81dc71e660b524d7410c', 
                'foo-index'
            ), 
            null
        );
    }
    
    /**
     * Tests mangoQuery
     */
    
    public function testMangoQuery()
    {
        $doc = new \Chemem\Fauxton\DocumentQueries;
        $query = [
            'selector' => [
                'year' => ['$gt' => 2010]
            ],
            'fields' => ['_id', '_rev', 'year', 'title'],
            'sort' => [
                ['year' => 'asc']
            ],
            'limit' => 2,
            'skip' => 0
        ];
        $this->assertNotEquals($doc->mangoQuery('dummy_info', $query), false);
        $this->assertNotEquals($doc->mangoQuery('dummy_info', $query), null);
    }
}