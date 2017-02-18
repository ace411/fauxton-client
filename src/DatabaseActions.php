<?php

/**
 * FauxtonClient REST API DatabaseActions class
 * This class contains methods that describe database interactions
 * fauxton-client is a simple wrapper that eases CouchDB interactions
 * 
 * @package fauxton-client
 * @author Lochemem Bruno Michael
 * @link https://www.github.com/fauxton-client
 *
 */

namespace Chemem\Fauxton;

class DatabaseActions
{
    use Config\Connect;
    
    /**
     * Create a database
     *
     * @param string $dbName The name of the database
     * @return string $response The response object
     *
     */
    
    public function createDatabase($dbName)
    {
        return $this->executeCurlRequest($dbName, 'PUT');
    }
    
    /**
     * Show all the created databases 
     *
     * @return string $response
     *
     */
    
    public function showAllDatabases()
    {
        return $this->executeCurlRequest('_all_dbs', 'GET');
    }
    
    /**
     * Delete a database
     *
     * @param string $dbName The name of the database to delete
     * @return string $response 
     *
     */
    
    public function deleteDatabase($dbName)
    {
        return $this->executeCurlRequest($dbName, 'DELETE');
    }
    
    /**
     * Get Couch Database action updates
     *
     * @param array $updateParams An array of update parameters defined in the CouchDB documentation
     * @return string $response 
     *
     */
    
    public function getActionUpdates(array $updateParams = null)
    {
        $validKeys = ['feed', 'timeout', 'heartbeat'];
        $checkParams = function ($arrayKeys, $matchList, $completeArray) {
            foreach ($arrayKeys as $keys) {
                if (!in_array($key, $matchList)) {
                    return false;
                }
            }
            return $completeArray;
        };
        if (!is_null($updateParams)) {
            $response = $this->executeCurlRequest('_db_updates?'. http_build_query($updateParams), 'GET');
        } else {
            $response = $this->executeCurlRequest('_db_updates', 'GET');
        }
        return $response;
    }
    
    /**
     * Create a user with Couch DB's cookie authentication option
     *
     * @param string $username The username of the user you intend to create
     * @param string $password The password of the user you intend to create
     * @return string $response 
     *
     */
    
    public function cookieCreateUser($username, $password)
    {
        return $this->executeCurlRequest('_session', 'POST', json_encode([
            'name' => $username,
            'password' => $password
        ]));
    }
    
    /**
     * Return complete information about authenticated user
     *
     * @return string $response
     *
     */
    
    public function getSessionAuthDetails()
    {
        return $this->executeCurlRequest('_session', 'GET');
    }
    
    /**
     * Insert users into Couch DB's users database 
     *      -The users database has to be created first
     * @see http://docs.couchdb.org/en/2.0.0/intro/security.html
     *
     * @param string $username
     * @param string $password
     * @param string $type The classification of the user to be created
     * @param array $roles An array of roles to which the created user will be assigned
     * @return string $response
     *
     */
    
    public function authDbInsert($username, $password, $type = 'user', $roles = [])
    {
        $authDetails = [
            'name' => $username,
            'password' => $password
        ];
        $authDetails = array_merge($authDetails, [
            'type' => $type, 
            'roles' => $roles
        ]);
        return $this->executeCurlRequest("_users/org.couchdb.user:{$username}", 'PUT', json_encode($authDetails));
    }
    
    /**
     * Get user details from Couch's authentication database
     *
     * @param string $username
     * @param string $password
     * @return string $response
     *
     */
    
    public function getUserDetails($username, $password)
    {
        return $this->executeCurlRequest("_users/org.couchdb.user:{$username}", 'GET');
    }
    
    /**
     * Check the authentication status of a particular database
     *
     * @param string $dbName
     * @return string $response
     *
     */

    public function checkDatabaseSecurity($dbName)
    {
        return $this->executeCurlRequest("{$dbName}/_security", 'GET');
    }
    
    /**
     * Create a security object for a Couch database
     *
     * @param string $dbName
     * @param array $options The array of security options
     * @return string $response
     *
     */
    
    public function implementDatabaseSecurity($dbName, array $options)
    {
        return $this->executeCurlRequest("{$dbName}/_security", 'PUT', json_encode($options));
    }
}