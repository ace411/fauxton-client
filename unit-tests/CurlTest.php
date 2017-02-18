<?php

/**
 * FauxtonClient CurlTest class
 * This class contains CURL tests for interacting with CouchDB
 * fauxton-client is a simple wrapper that eases CouchDB interactions
 * 
 * @package fauxton-client
 * @author Lochemem Bruno Michael
 * @link https://www.github.com/fauxton-client
 *
 */

class CurlTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests setCouchUrl
     */
    
    public function testUrlReturnFunctionWorks()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $this->assertEquals($db::setCouchUrl('http://weirdpath.com'), 'http://weirdpath.com');
    }
    
    /**
     * Tests getCurlOptions
     */
    
    public function testCurlOptConfigWorks()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $db->setCurlOptions(CURLOPT_URL, 'http://weirdpath.com');
        $curlResult = [
            10002 => 'http://weirdpath.com'
        ];
        $this->assertEquals($db->getCurlOptions(), $curlResult);
    }
    
    /**
     * Tests executeCurlRequest
     */
    
    public function testCurlRequestWorks()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $db::setCouchUrl('http://weirdpath.com');
        $this->assertEquals($db->executeCurlRequest('fictitous_param', 'GET'), false);
    }
    
    /**
     * Tests executeCurlRequest
     */
    
    public function testCouchRespondsToRequest()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $db::setCouchUrl('http://localhost:5984');
        $this->assertNotEquals($db->executeCurlRequest('', 'GET'), false);
    }
    
    /**
     * Tests getCurlOptions
     */
    
    public function testCurlAuthenticationIsSet()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $db->useFauxtonLogin('abcUser', '123pass');
        $this->assertEquals($db->getCurlOptions(), [CURLOPT_USERPWD => 'abcUser:123pass']);
    }
    
    /**
     * Tests getCouchConfigOptions
     */
    
    public function testConfigOptionsAreSet()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $db::setCouchUrl('http://weirdpath.com');
        $db->useFauxtonLogin('abcUser', '123pass');
        $this->assertEquals($db::getCouchConfigOptions(), [
            'couch_username' => 'abcUser',
            'couch_password' => '123pass',
            'couch_url' => 'http://weirdpath.com',
            'return_json' => true
        ]);
    }
    
    /**
     * Tests setReturnType
     */
    
    public function testNonJsonReturnType()
    {
        $db = new \Chemem\Fauxton\DatabaseActions;
        $db::setCouchUrl('http://localhost:5984/');
        $db::setReturnType(false);
        $this->assertNotEquals($db->executeCurlRequest('', 'GET'), false);
    }
}