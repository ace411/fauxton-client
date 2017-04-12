<?php

/**
 * FauxtonClient REST API DocumentActions class
 * This class contains methods that describe document interactions
 * fauxton-client is a simple wrapper that eases CouchDB interactions
 * 
 * @package fauxton-client
 * @author Lochemem Bruno Michael
 * @link https://www.github.com/fauxton-client
 *
 */

namespace Chemem\Fauxton;

class DocumentActions
{
    use Config\Connect;
    
    /**
     * Generate a unique id which can be used as a key
     *
     * @return string $response The response object
     *
     */
    
    public function generateId()
    {
        return $this->executeCurlRequest('_uuids', 'GET');
    }
    
    /**
     * Generate multiple unique ids which can be used as a keys
     *
     * @param int $number The number of unique keys to generate
     * @return string $response The response object
     *
     */
    
    public function generateMultipleIds(int $number)
    {
        return $this->executeCurlRequest("_uuids?count={$number}", 'GET');
    }
    
    /**
     * Create a CouchDB document
     *
     * @param string $id The unique document identifier
     * @param string $dbName The name of the database to which the document will be appended
     * @param array $data An array of document-specific data
     * @return string $response
     *
     */
    
    public function createDocument($id, $dbName, array $data)
    {
        return $this->executeCurlRequest("{$dbName}/{$id}", "PUT", json_encode($data));
    }
    
    /**
     * Delete a CouchDB document
     *
     * @param string $id
     * @param string $rev The revision identifier of the document
     * @param string $dbName
     * @return string $response
     *
     */
    
    public function deleteDocument($id, $rev, $dbName)
    {
        return $this->executeCurlRequest("{$dbName}/{$id}?rev={$rev}", 'DELETE');
    }
    
    /**
     * Get a single CouchDB document
     *
     * @param string $id
     * @param string $dbName
     * @return string $response
     *
     */
    
    public function getSingleDocument($id, $dbName)
    {
        return $this->executeCurlRequest("{$dbName}/{$id}", 'GET');
    }
    
    /**
     * Update a CouchDB document
     *
     * @param string $id
     * @param string $dbName
     * @param array $data 
     * @return string $response
     *
     */
    
    public function updateDocument($id, $dbName, array $data)
    {
        $validateFormat = function ($array) {
            if (!array_key_exists('_rev', $array)) {
                return false;
            } 
            return $array;
        };
        return $this->executeCurlRequest("{$dbName}/{$id}", 'PUT', json_encode($validateFormat($data)));
    }
    
    /**
     * Get the revision information of a CouchDB document
     *
     * @param string $id
     * @param string $dbName
     * @return string $response
     * 
     */
    
    public function getDocumentRevisionInfo($id, $dbName)
    {
        return $this->executeCurlRequest("{$dbName}/{$id}?revs_info=true", 'GET');
    }
    
    /**
     * Get a specific document revision
     *
     * @param string $id
     * @param string $rev
     * @param string $dbName
     * @return string $response
     * 
     */
    
    public function getSpecificDocumentRevision($id, $rev, $dbName)
    {
        return $this->executeCurlRequest("{$dbName}/{$id}?rev={$rev}", 'GET');
    }
    
    /**
     * Create multiple CouchDB documents
     *
     * @param string $dbName 
     * @param array $data
     * @return string $response
     *
     */
    
    public function createMultipleDocs($dbName, array $data)
    {
        return $this->executeCurlRequest("{$dbName}/_bulk_docs", "POST", json_encode($data));
    }
    
    /**
     * Update multiple CouchDB documents
     *
     * @param string $dbName 
     * @param array $data
     * @return string $response
     *
     */
    
    public function updateMultipleDocs($dbName, array $data)
    {
        $validateFormat = function ($array) {
            foreach ($array as $values) {
                if (is_array($values)) {
                    foreach ($values as $val) {
                        if (!isset($val['_id'], $val['_rev'])) {
                            return false;
                        }
                        return $values;
                    }
                }
            } 
        };
        return $this->executeCurlRequest("{$dbName}/_bulk_docs", 'POST', json_encode($validateFormat($data)));
    }
    
    /**
     * Delete multiple CouchDB documents
     *
     * @param string $dbName 
     * @param array $data
     * @return string $response
     *
     */
    
    public function deleteMultipleDocs($dbName, array $data)
    {
        $validateFormat = function ($array) {
            foreach ($array as $values) {
                if (is_array($values)) {
                    foreach ($values as $val) {
                        if (!isset($val['_deleted'], $val['_id'], $val['_rev']) && $val['deleted'] !== true) {
                            return false;
                        }
                        return $values;
                    }
                }
            }    
        };
        return $this->executeCurlRequest("{$dbName}/_bulk_docs", 'POST', json_encode($validateFormat($data)));
    }
    
    /**
     * Show all documents in a Couch database
     *
     * @param string $dbName
     * @return string $response
     *
     */
    
    public function showAllDocuments($dbName)
    {
        return $this->executeCurlRequest("{$dbName}/_all_docs", 'GET');
    }
    
    /**
     * Get a set of documents with similar keys
     *
     * @param string $dbName
     * @param array $keys An array of the keys to be compared
     * @return string $response
     *
     */
    
    public function getDocsByKey($dbName, array $keys)
    {
        return $this->executeCurlRequest("{$dbName}/_all_docs?include_docs=true", 'POST', json_encode($keys));
    }
}
