<?php

/**
 * FauxtonClient REST API connection boilerplate
 * fauxton-client is a simple wrapper that eases CouchDB interactions
 * 
 * @package fauxton-client
 * @author Lochemem Bruno Michael
 * @link https://www.github.com/fauxton-client
 *
 */

namespace Chemem\Fauxton\Config;

trait Connect
{
    /**
     * CouchDB username
     *
     * @access private
     * @var string $username
     *
     */
    
    private static $username;
    
    /**
     * CouchDB password
     *
     * @access private
     * @var string $password
     *
     */
    
    private static $password;
    
    /**
     * CouchDB URL
     *
     * @access private
     * @var string $url
     *
     */
    
    private static $url = 'http://localhost:5984/';
    
    /**
     * An array of CURL options for interacting with Fauxton
     *
     * @access private
     * @var array $options
     *
     */
    
    private $curlOptions = [];
    
    /**
     * The json flag to determine whether data should be returned as either json or an array
     *
     * @access private
     * @var bool $json
     *
     */
    
    private static $json = true;
    
    /**
     * Set the return type
     *
     * @param bool $type
     *
     */
    
    public static function setReturnType(bool $type)
    {
        self::$json = is_bool($type) ? $type : true;
    }
    
    /**
     * Method to set the Couch/Fauxton URL
     *
     * @param string $url 
     * @return string self::$url
     *
     */
    
    public static function setCouchUrl($url = null)
    {
        return self::$url = $url;
    }
    
    /**
     * Retrieve the Configuration options set - URL, username and password details
     *
     * @return array $configOptions
     *
     */
    
    public static function getCouchConfigOptions()
    {
        return [
            'couch_username' => self::$username,
            'couch_password' => self::$password,
            'couch_url' => self::$url,
            'return_json' => self::$json
        ];
    }
    
    /**
     * Method to set the CURL options for making requests
     *
     * @param string $option The CURL option like CURLOPT_CUSTOMREQUEST 
     * @param array $curlArray The CURL option value(s)
     *
     */
    
    public function setCurlOptions($option, $curlArray = [])
    {
        $this->curlOptions[$option] = $curlArray;
    }
    
    /**
     * Method to get the CURL options defined
     *
     * @return array $this->curlOptions
     *
     */
    
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }
    
    /**
     * Method to set the Fauxton login details if a user has already been created
     *
     * @param string $username
     * @param string $password
     *
     */
    
    public function useFauxtonLogin($username = null, $password = null)
    {
        self::$username = !is_null($username) ? $username : null;
        self::$password = !is_null($password) ? $password : null;
        if (!isset($username, $password) || !is_null($username) || !is_null($password)) {
            $this->setCurlOptions(CURLOPT_USERPWD, "{$username}:{$password}");    
        }        
    }
    
    /**
     * Execute a request so as to communicate with Couch/Fauxton
     *
     * @param string $endpoint The unique CouchDB HTTP API endpoint
     * @param string $method The request verb which is either GET, PUT, POST or DELETE
     * @param string $data The JSON data to be sent to Couch along with the request headers
     * @return string $result The JSON object returned by CouchDB
     *
     */
    
    public function executeCurlRequest($endpoint, $method, $data = null)
    {
        $ch = curl_init();
        $this->setCurlOptions(CURLOPT_URL, self::$url . $endpoint);
        $this->setCurlOptions(CURLOPT_RETURNTRANSFER, true);
        $this->setCurlOptions(CURLOPT_HTTPHEADER, [
            'Content-type: application/json'
        ]);
        
        switch ($method) {
            case 'GET':
                break;
                
            case 'PUT':
            case 'POST':
                if (!is_null($data)) {
                    $this->setCurlOptions(CURLOPT_POSTFIELDS, $data);
                }
                if ($method === 'PUT') {
                    $this->setCurlOptions(CURLOPT_CUSTOMREQUEST, 'PUT');
                } else {
                    $this->setCurlOptions(CURLOPT_POST, true);
                }
                break;
                
            case 'DELETE':
                $this->setCurlOptions(CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        curl_setopt_array($ch, $this->getCurlOptions());
        $result = curl_exec($ch);
        if (self::$json !== true) {
            $result = json_decode($result);
        }
        curl_close($ch);
        return $result;
    }
}