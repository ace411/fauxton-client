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
        'Content-Type: application/json',
        'Accept: application/json'
    ];

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

    const CONSOLE_PROMPT = '>>> ';

    const CONSOLE_COMMANDS = [
        'exit' => [
            'cmd' => 'exit',
            'desc' => 'Terminates the Fauxton console'
        ],
        'config' => [
            'cmd' => 'config <option> eg config credentials, config console',
            'desc' => 'Shows elements of the fauxton client configuration file'
        ],
        'alldbs' => [
            'cmd' => 'alldbs',
            'desc' => 'Shows all available databases'
        ],
        'uuids' => [
            'cmd' => 'uuids <count> eg uuids 2',
            'desc' => 'Outputs a specified number of unique ids'
        ],
        'cred' => [
            'cmd' => 'cred <type> <username> <password> eg cred local foo foobar',
            'desc' => 'Sets CouchDB username and password'
        ],
        'gzip' => [
            'cmd' => 'gzip <database> <file> eg gzip meetups meetups.gz',
            'desc' => 'gzips a database\'s contents'
        ],
        'unzip' => [
            'cmd' => 'unzip <file> eg unzip meetups.gz',
            'desc' => 'unzips gzipped file'
        ],
        'use' => [
            'cmd' => 'use <option> eg use local',
            'desc' => 'Modifies local parameter in fauxton-client configuration'
        ],
        'alldocs' => [
            'cmd' => 'alldocs <database> eg docs nba_players',
            'desc' => 'Outputs all the documents in a database'
        ],
        'cmd' => [
            'cmd' => 'doc <database> <docId> eg doc nba_players dwayne_wade',
            'desc' => 'Outputs a document\'s contents' 
        ],
        'search' => [
            'cmd' => 'search <database> <selector> eg search rap_artists {"name":{"$eq":"bronson"}}',
            'desc' => 'Searches a database (Powered by Mango queries)'
        ],
        'explain' => [
            'cmd' => 'explain <command> eg explain alldocs',
            'desc' => 'Provides a description of a command'
        ],
        'db' => [
            'cmd' => 'db <database> eg db action_movies',
            'desc' => 'Shows database metadata'
        ],
        'docs' => [
            'cmd' => 'docs <database> <keys> eg docs action_movies ["JohnWick","MissionImpossible"]',
            'desc' => 'Outputs a set of documents identified by specified keys'
        ]
    ];
}