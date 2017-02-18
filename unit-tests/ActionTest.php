<?php

/**
 * FauxtonClient ActionTestClass
 * This class contains action tests for both database and document interactions
 * fauxton-client is a simple wrapper that eases CouchDB interactions
 * 
 * @package fauxton-client
 * @author Lochemem Bruno Michael
 * @link https://www.github.com/fauxton-client
 *
 */

class ActionTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests showAllDatabases
     */
    
    public function testShowAllDatabases()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $this->assertNotEquals($db->showAllDatabases(), false);
    }
    
    /**
     * Tests createDatabase
     */
    
    public function testCreateDatabase()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $this->assertNotEquals($db->createDatabase('dummy_info'), false);
    }
    
    /**
     * Tests deleteDatabase
     */
    
    public function testDeleteDatabase()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $this->assertNotEquals($db->deleteDatabase('dummy_info'), false);
    }
    
    /**
     * Tests getActionUpdates
     */
        
    public function testGetActionUpdates()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $this->assertNotEquals($db->getActionUpdates(), false);
        $this->assertNotEquals($db->getActionUpdates(['feed' => 'continuous']), false);
    }
    
    /**
     * Tests cookieCreateUser
     */
    
    public function testCookieDetailCreator()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $this->assertNotEquals($db->cookieCreateUser('abcUser', '123pass'), false);
    }
    
    /**
     * Tests getSessionAuthDetails
     */
    
    public function testGetSessionAuthDetails()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $this->assertNotEquals($db->getSessionAuthDetails(), false);
        $this->assertNotEquals($db->getSessionAuthDetails(), null);        
    }
    
    /**
     * Tests authDbInsert
     */
    
    public function testAuthDbInsertOption()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $this->assertNotEquals($db->authDbInsert('abcUser', '123pass', 'user', []), false);
    }
    
    /**
     * Tests getUserDetails
     */
        
    public function testGetUserDetailsFromUserDb()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $this->assertNotEquals($db->getUserDetails('abcUser', '123pass'), false);
        $this->assertNotEquals($db->getUserDetails('abcUser', '123pass'), null);
    }
    
    /**
     * Tests checkDatabaseSecurity
     */
    
    public function testCheckDatabaseSecurity()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $this->assertNotEquals($db->checkDatabaseSecurity('dummy_info'), false);
        $this->assertNotEquals($db->checkDatabaseSecurity('dummy_info'), null);
    }
    
    /**
     * Tests implementDatabaseSecurity
     */
    
    public function testImplementDatabaseSecurity()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
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
        $this->assertNotEquals($db->implementDatabaseSecurity('dummy_info', $security), false);
    }
    
    /**
     * Tests generateId
     */
    
    public function testIdGenerator()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $this->assertNotEquals($doc->generateId(), false);
        $this->assertNotEquals($doc->generateId(), null);
    }
    
    /**
     * Tests generateMultipleIds
     */
    
    public function testMultipleIdGenerator()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $this->assertNotEquals($doc->generateMultipleIds(3), false);
        $this->assertNotEquals($doc->generateMultipleIds(3), null);
    }
    
    /**
     * Tests createDocument
     */
    
    public function testSingleDocumentCreator()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $this->assertNotEquals($doc->createDocument('SpaghettiWithMeatballs', 'dummy_info', [
            'description' => 'An American-Italian dish composed of pasta and meatballs',
            'ingredients' => [
                'spaghetti',
                'meatballs',
                'tomatoes'
            ],
            'name' => 'Spaghetti with meatballs'
        ]), false);
    }
    
    /**
     * Tests deleteDocument
     */
    
    public function testDeleteSingleDocument()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $docId = 'SpaghettiWithMeatballs';
        $docRev = '1-917fa2381192822767f010b95b45325b';
        $this->assertNotEquals($doc->deleteDocument($docId, $docRev, 'dummy_info'), false);
    }
    
    /**
     * Tests createMultipleDocs
     */
    
    public function testMultipleDocumentCreator()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $multiple = [
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
        $this->assertNotEquals($doc->createMultipleDocs('dummy_info', $multiple), false);
    }
    
    /**
     * Tests updateMultipleDocs
     */
    
    public function testMultipleDocUpdater()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $multiple = [
            'docs' => [
                [
                    '_id' => 'FishStew',
                    '_rev' => '1-6a466d5dfda05e613ba97bd737829d67',
                    'servings' => 4,
                    'subtitle' => 'Delicious with freshly baked bread',
                    'title' => 'FishStew'
                ],
                [
                    '_id' => 'LambStew',
                    '_rev' => '1-648f1b989d52b8e43f05aa877092cc7c',
                    'servings' => 6,
                    'subtitle' => 'Serve with a whole meal scone topping',
                    'title' => 'LambStew'
                ]
            ]
        ];
        $this->assertNotEquals($doc->updateMultipleDocs('dummy_info', $multiple), false);
    }
    
    /**
     * Tests showAllDocuments
     */
    
    public function testShowAllDocuments()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $this->assertNotEquals($doc->showAllDocuments('dummy_info'), false);
        $this->assertNotEquals($doc->showAllDocuments('dummy_info'), null);
    }
    
    /**
     * Tests getDocsByKey
     */
    
    public function testGetDocumentsByKey()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $this->assertNotEquals($doc->getDocsByKey('dummy_info', [
            'keys' => ['Zingylemontart', 'Yogurtraita']
        ]), false);
        $this->assertNotEquals($doc->getDocsByKey('dummy_info', [
            'keys' => ['Zingylemontart', 'Yogurtraita']
        ]), null);
    }
    
    /**
     * Tests getSingleDocument
     */
    
    public function testGetSingleDocument()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $this->assertNotEquals($doc->getSingleDocument('FishStew', 'dummy_info'), false);
        $this->assertNotEquals($doc->getSingleDocument('FishStew', 'dummy_info'), null);
    }
    
    /**
     * Tests updateDocument
     */
    
    public function testUpdateDocument()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $this->assertNotEquals($doc->updateDocument('LambStew', 'dummy_info', [
            '_rev' => '1-648f1b952d52b8e43f05aa877092cc7c',
            'servings' => 18
        ]), false);
    }
    
    /**
     * Tests getDocumentRevisionInfo
     */
    
    public function testGetDocumentRevisionInfo()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $this->assertNotEquals($doc->getDocumentRevisionInfo(uniqid(), 'dummy_info'), false);
    }
    
    /**
     * Tests getSpecificDocumentRevision
     */
    
    public function testGetSpecificDocumentRevision()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $this->assertNotEquals(
            $doc->getSpecificDocumentRevision(
                'SpaghettiWithMeatballs', 
                '5-eeaa298781f60b7bcae0c91bdedd1b87',
                'dummy_info'
            ),             
            false
        );
    }
    
    /**
     * Tests deleteMultipleDocs
     */
    
    public function testMultipleDocDelete()
    {
        $doc = new \Chemem\Fauxton\DocumentActions;
        $multiple = [
            'docs' => [
                [
                    '_id' => 'FishStew',
                    '_rev' => '1-9c65296036141e575d32ba9c034dd3ee',
                    '_deleted' => true,
                ],
                [
                    '_id' => 'LambStew',
                    '_rev' => '1-648f1b952d52b8e43f05aa877092cc7c',
                    '_deleted' => true
                ]
            ]
        ];
        $this->assertNotEquals($doc->deleteMultipleDocs('dummy_info', $multiple), false);
    }
}