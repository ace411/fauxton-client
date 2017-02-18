<?php

/**
 * FauxtonClient QueryBuilder class
 * This class contains methods that make it easier to create CouchDB interaction data
 * fauxton-client is a simple wrapper that eases CouchDB interactions
 * 
 * @package fauxton-client
 * @author Lochemem Bruno Michael
 * @link https://www.github.com/fauxton-client
 *
 */

namespace Chemem\Fauxton;

class QueryBuilder
{
    /**
     * Query parameters
     *
     * @access private
     * @var array $params
     *
     */
    
    private static $params = [];
    
    /**
     * Supported query parameters
     *
     * @access private
     * @var array $queryParams
     *
     */
    
    private static $queryParams = [
        'selector', 
        'fields', 
        'limit', 
        'skip', 
        'sort', 
        'use_index', 
        'docs', 
        'index', 
        'name',
        'admins',
        'members'        
    ];
    
    /**
     * Add values to the $params array
     *
     * @param string $type The key for the associative array which must be a supported parameter
     * @param array $params The value(s) to be assigned to the key
     * 
     */
    
    public static function addParams($type, $params = [])
    {
        $checkType = function ($val, $array) {
            if (!in_array($val, $array)) {
                return false;
            }
            return $val;
        };
        $type = $checkType($type, self::$queryParams);
        self::$params[$type] = $params;
    }
    
    /**
     * Reset the $params array of values
     */
    
    public static function removeParams()
    {
        self::$params = [];
    }
    
    /**
     * Get the $params array
     *
     * @return array self::$params The parameters that have been set
     *
     */
    
    public static function getParams()
    {
        return self::$params;
    }
    
    /**
     * Generate an md5 password string
     *
     * @param int $length The intended length of the password
     * @param string $hint The phrase or word you intend to use to generate the hash
     * @return string $hash The md5 password string
     *
     */
    
    public function generatePassword(int $length, $hint)
    {
        $maxLength = is_int($length) ? $length : 32;
        return substr(hash('md5', $hint), 0, $maxLength);        
    }
}