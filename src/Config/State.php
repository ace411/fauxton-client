<?php

/**
 *
 * fauxton-client immutable data
 *
 * @author Lochemem Bruno Michael
 * @license Apache-2.0
 */

namespace Chemem\Fauxton\Config;

class State
{
    const CLIENT_CONFIG_FILE = 'fauxton.json';

    const COUCH_URI_LOCAL = 'http://localhost:5984';

    const COUCH_URI_CLOUDANT = 'https://{cloudantUser}:{cloudantPass}@{cloudantHost}';

    const COUCH_REQHEADERS = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ];

    const COUCH_TLS = array(
        'verify_peer' => true,
        'disable_compression' => true,
        'ciphers' => 'HIGH:!TLSv1.2:!TLSv1.0:!SSLv3:!SSLv2'
    );

    const CONFIG_PATHS = [
        __DIR__ . '/../../fauxton.json',
        __DIR__ . '/../../../fauxton.json',
        __DIR__ . '/../../../../fauxton.json',
        __DIR__ . '/../../../../../fauxton.json'
    ];

    const COUCH_ACTIONS = [
        'uuids' => [
            'local' => '_uuids?count={count}',
            'cloudant' => '_uuids?count={count}'
        ],
        'dbgen' => [
            'local' => '{db}',
            'cloudant' => '{db}'
        ],
        'insertSingle' => [
            'local' => '{db}/{docId}',
            'cloudant' => '{db}/{docId}'
        ],
        'allDbs' => [
            'local' => '_all_dbs',
            'cloudant' => '_all_dbs'
        ],
        'bulkDocs' => [
            'local' => '{db}/_bulk_docs',
            'cloudant' => '{db}/_bulk_docs'
        ],
        'allDocs' => [
            'local' => '{db}/_all_docs?{params}',
            'cloudant' => '{db}/_all_docs?{params}'
        ],
        'deleteDoc' => [
            'local' => '{db}/{docId}?rev={rev}',
            'cloudant' => '{db}/{docId}?rev={rev}'
        ],
        'docById' => [
            'local' => '{db}/{docId}?{params}',
            'cloudant' => '{db}/{docId}?{params}'
        ],
        'index' => [
            'local' => '{db}/_index',
            'cloudant' => '{db}/_index'
        ],
        'search' => [
            'local' => '{db}/_find',
            'cloudant' => '{db}/_find'
        ],
        'ddoc' => [
            'local' => '{db}/_design/{ddoc}',
            'cloudant' => '{db}/_design/{ddoc}'
        ],
        'changes' => [
            'local' => '{db}/_changes?{params}',
            'cloudant' => '{db}/_changes?{params}'
        ]
    ];
}
