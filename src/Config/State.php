<?php

/**
 * 
 * fauxton-client state
 * 
 * @package fauxton-client
 * @author Lochemem Bruno Michael
 */

namespace Chemem\Fauxton\Config;

class State 
{
    const CLIENT_CONFIG_FILE = 'fauxton.json';

    const COUCH_URI_LOCAL = 'http://localhost:5984';

    const COUCH_URI_CLOUDANT = 'https://{cloudantUser}:{cloudantPass}@{cloudantHost}';

    const COUCH_REQHEADERS = [
        'Content-Type: application/json',
        'Accept: application/json'
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
        'prompt' => '>>> '
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
        'alldbs' => [
            'doc' => 'alldbs',
            'desc' => 'Shows all available databases'
        ],
        'uuids' => [
            'doc' => 'uuids <count> eg uuids 2',
            'desc' => 'Outputs a specified number of unique ids'
        ],
        'cred' => [
            'doc' => 'cred <type> <username> <password> eg cred local foo foobar',
            'desc' => 'Sets CouchDB username and password'
        ],
        'gzip' => [
            'doc' => 'gzip <database> <file> eg gzip meetups meetups.gz',
            'desc' => 'gzips a database\'s contents'
        ],
        'unzip' => [
            'doc' => 'unzip <file> eg unzip meetups.gz',
            'desc' => 'unzips gzipped file'
        ],
        'use' => [
            'doc' => 'use <option> eg use local',
            'desc' => 'Modifies local parameter in fauxton-client configuration'
        ],
        'alldocs' => [
            'doc' => 'alldocs <database> eg docs nba_players',
            'desc' => 'Outputs all the documents in a database'
        ],
        'doc' => [
            'doc' => 'doc <database> <docId> eg doc nba_players dwayne_wade',
            'desc' => 'Outputs a document\'s contents' 
        ],
        'search' => [
            'doc' => 'search <database> <selector> eg search rap_artists {"name":{"$eq":"bronson"}}',
            'desc' => 'Searches a database (Powered by Mango queries)'
        ],
        'explain' => [
            'doc' => 'explain <command> eg explain alldocs',
            'desc' => 'Provides a description of a command'
        ],
        'db' => [
            'doc' => 'db <database> eg db action_movies',
            'desc' => 'Shows database metadata'
        ]
    ];
}