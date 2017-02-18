<?php

/**
 * FauxtonClient QueryGeneratorTest class
 * This class contains tests for the QueryBuilder class
 * fauxton-client is a simple wrapper that eases CouchDB interactions
 * 
 * @package fauxton-client
 * @author Lochemem Bruno Michael
 * @link https://www.github.com/fauxton-client
 *
 */

class QueryGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests addParams
     */
    
    public function testDocGenerator()
    {
        $qBuilder = new \Chemem\Fauxton\QueryBuilder;
        $docs = [
            'docs' => [
                [
                    '_id' => 'FishStew',
                    'servings' => 4,
                    'subtitle' => 'Delicious with freshly baked bread',
                    'title' => 'FishStew'
                ],
                [
                    '_id' => 'LambStew',
                    'servings' => 3,
                    'subtitle' => 'Serve with a whole meal scone topping',
                    'title' => 'LambStew'
                ]
            ]
        ];
        $qBuilder::addParams('docs', [
            [
                '_id' => 'FishStew',
                'servings' => 4,
                'subtitle' => 'Delicious with freshly baked bread',
                'title' => 'FishStew'
            ],
            [
                '_id' => 'LambStew',
                'servings' => 3,
                'subtitle' => 'Serve with a whole meal scone topping',
                'title' => 'LambStew'
            ]
        ]);
        $docParams = $qBuilder::getParams();
        $this->assertEquals($docParams, $docs);
    }
    
    /**
     * Tests removeParams
     */
    
    public function testParameterRemoval()
    {
        $qBuilder = new \Chemem\Fauxton\QueryBuilder;
        $qBuilder::addParams('selector', [
            'year' => ['&gt' => 2010]
        ]);
        $this->assertEquals($qBuilder::removeParams(), null);
    }
    
    /**
     * Tests addParams
     */
    
    public function testSearchQueryGenerator()
    {
        $qBuilder = new \Chemem\Fauxton\QueryBuilder;
        $search = [
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
        $qBuilder::addParams('selector', [
            'year' => ['$gt' => 2010]
        ]);
        $qBuilder::addParams('fields', ['_id', '_rev', 'year', 'title']);
        $qBuilder::addParams('sort', [
            ['year' => 'asc']
        ]);
        $qBuilder::addParams('limit', 2);
        $qBuilder::addParams('skip', 0);
        $this->assertEquals($qBuilder::getParams(), $search);
    }
    
    /**
     * Tests addParams
     */
    
    public function testIndexGenerator()
    {
        $qBuilder = new \Chemem\Fauxton\QueryBuilder;
        $index = [
            'index' => [
                'fields' => ['foo', 'bar']
            ],
            'name' => 'foo-index'
        ];
        $qBuilder::removeParams();
        $qBuilder::addParams('index', [
            'fields' => ['foo', 'bar']
        ]);
        $qBuilder::addParams('name', 'foo-index');
        $this->assertEquals($qBuilder::getParams(), $index);
    }
    
    /**
     * Tests addParams
     */
    
    public function testSecurityOptionGenerator()
    {
        $qBuilder = new \Chemem\Fauxton\QueryBuilder;
        $security = [
            'admins' => [
                'names' => ['superuser'],
                'roles' => ['admins']
            ],
            'members' => [
                'names' => ['user1', 'user2'],
                'roles' => ['developers']
            ]
        ];
        $qBuilder::removeParams();
        $qBuilder::addParams('admins', [
            'names' => ['superuser'],
            'roles' => ['admins']
        ]);
        $qBuilder::addParams('members', [
            'names' => ['user1', 'user2'],
            'roles' => ['developers']
        ]);
        $this->assertEquals($qBuilder::getParams(), $security);
    }
    
    /**
     * Tests generatePassword
     */
    
    public function testPasswordGenerator()
    {
        $qBuilder = new \Chemem\Fauxton\QueryBuilder;
        $this->assertNotEquals($qBuilder->generatePassword(10, '123pass'), false);
        $this->assertEquals($qBuilder->generatePassword(10, '123pass'), '96b9c62c86');
    }
}