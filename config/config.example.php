<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SQL Server Connection
    |--------------------------------------------------------------------------
    |
    | Examples:
    |   localhost
    |   192.168.1.50
    |   SERVERNAME\INSTANCE
    |   192.168.1.50,1433
    |
    */

    'database' => [

        'driver' => 'sqlsrv',

        'server' => 'localhost',

        'port' => null,

        'database' => 'AmnDb006',

        /*
         * sql     = SQL Server Authentication
         * windows = Windows Authentication
         */
        'authentication' => 'sql',

        'username' => 'sa',

        'password' => '',

        /*
         * For SQL Server installations using encryption.
         */
        'encrypt' => false,

        /*
         * Useful when using a self-signed SQL Server certificate.
         */
        'trust_server_certificate' => true,

        /*
         * PDO connection timeout in seconds.
         */
        'timeout' => 10,
    ],

];