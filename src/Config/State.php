<?php

namespace Chemem\Fauxton\Config;

class State 
{
    const CLIENT_CONFIG_FILE = 'fauxton.json';

    const COUCH_URI_LOCAL = 'http://localhost:5984';

    const COUCH_URI_CLOUDANT = 'https://{cloudantUser}:{cloudantPass}@{cloudantHost}';

    const COUCH_CURLOPTS_DEFAULT = [
        \CURLOPT_RETURNTRANSFER => true,
        \CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
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

    const CONSOLE_FEATURES = [
        'prompt' => '>>> ',
        'db' => 'Database name: ',
        'index' => 'Index name: ',
        'view' => 'View name: ',
        'dbFields' => 'Fields: ',
        'docId' => 'Document id: ',
        'docRev' => 'Document rev: ',
        'ddoc' => 'Design document: ',
        'search' => 'Search selector: ',
        'map' => 'Map function: ',
        'reduce' => 'Reduce function: ',
        'rereduce' => 'Rereduce function: '
    ];

    const CONSOLE_COMMANDS = [
        'exit' => [
            'doc' => 'exit',
            'desc' => 'Terminates the Fauxton console'
        ],
        'config' => [
            'doc' => 'config',
            'desc' => 'Shows the fauxton client configuration'
        ],
        'dbs' => [
            'doc' => 'dbs',
            'desc' => 'Shows all available databases'
        ],
        'uuids' => [
            'doc' => 'uuids <count> eg uuids 2',
            'desc' => 'Outputs a specified number of unique ids'
        ],
        'local' => [
            'doc' => 'local <username> <password> eg local foo foobar',
            'desc' => 'Sets local CouchDB username and password'
        ],
        'cloudant' => [
            'doc' => 'cloudant <username> <password> eg cloudant xxx-bluemix yyybar',
            'desc' => 'Sets IBM Cloudant username and password'
        ],
        'use' => [
            'doc' => 'use <option> eg use local',
            'desc' => 'Modifies local parameter in fauxton-client configuration'
        ],
        'docs' => [
            'doc' => 'docs <database> eg docs nba_players',
            'desc' => 'Outputs all the documents in a database'
        ],
        'doc' => [
            'doc' => 'doc <database> <docId> eg doc nba_players dwayne_wade',
            'desc' => 'Outputs a document\'s contents' 
        ],
        'search' => [
            'doc' => 'search <database> eg search rap_artists',
            'desc' => '** Searches a database (Powered by Mango queries)'
        ],
        'new' => [
            'doc' => 'new <option> (database|view|index|design-doc) eg new db',
            'desc' => 'Creates one of either a new document, new view, new index, or new design document'
        ]
    ];
}