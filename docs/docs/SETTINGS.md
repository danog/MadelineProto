
### Settings

The constructor accepts an optional parameter, which is the settings array. This array contains some other arrays, which are the settings for a specific MadelineProto function.  
Here are the default values for the settings arrays and explanations for every setting:

```
[
    'authorization' => [ // Authorization settings
        'default_temp_auth_key_expires_in' => 31557600, // validity of temporary keys and the binding of the temporary and permanent keys
        'rsa_keys' => [
            "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6\nlyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS\nan9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw\nEfzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+\n8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n\nSlv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB\n-----END RSA PUBLIC KEY-----",
            "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAxq7aeLAqJR20tkQQMfRn+ocfrtMlJsQ2Uksfs7Xcoo77jAid0bRt\nksiVmT2HEIJUlRxfABoPBV8wY9zRTUMaMA654pUX41mhyVN+XoerGxFvrs9dF1Ru\nvCHbI02dM2ppPvyytvvMoefRoL5BTcpAihFgm5xCaakgsJ/tH5oVl74CdhQw8J5L\nxI/K++KJBUyZ26Uba1632cOiq05JBUW0Z2vWIOk4BLysk7+U9z+SxynKiZR3/xdi\nXvFKk01R3BHV+GUKM2RYazpS/P8v7eyKhAbKxOdRcFpHLlVwfjyM1VlDQrEZxsMp\nNTLYXb6Sce1Uov0YtNx5wEowlREH1WOTlwIDAQAB\n-----END RSA PUBLIC KEY-----",
            "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAsQZnSWVZNfClk29RcDTJQ76n8zZaiTGuUsi8sUhW8AS4PSbPKDm+\nDyJgdHDWdIF3HBzl7DHeFrILuqTs0vfS7Pa2NW8nUBwiaYQmPtwEa4n7bTmBVGsB\n1700/tz8wQWOLUlL2nMv+BPlDhxq4kmJCyJfgrIrHlX8sGPcPA4Y6Rwo0MSqYn3s\ng1Pu5gOKlaT9HKmE6wn5Sut6IiBjWozrRQ6n5h2RXNtO7O2qCDqjgB2vBxhV7B+z\nhRbLbCmW0tYMDsvPpX5M8fsO05svN+lKtCAuz1leFns8piZpptpSCFn7bWxiA9/f\nx5x17D7pfah3Sy2pA+NDXyzSlGcKdaUmwQIDAQAB\n-----END RSA PUBLIC KEY-----",
            "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwqjFW0pi4reKGbkc9pK83Eunwj/k0G8ZTioMMPbZmW99GivMibwa\nxDM9RDWabEMyUtGoQC2ZcDeLWRK3W8jMP6dnEKAlvLkDLfC4fXYHzFO5KHEqF06i\nqAqBdmI1iBGdQv/OQCBcbXIWCGDY2AsiqLhlGQfPOI7/vvKc188rTriocgUtoTUc\n/n/sIUzkgwTqRyvWYynWARWzQg0I9olLBBC2q5RQJJlnYXZwyTL3y9tdb7zOHkks\nWV9IMQmZmyZh/N7sMbGWQpt4NMchGpPGeJ2e5gHBjDnlIf2p1yZOYeUYrdbwcS0t\nUiggS4UeE8TzIuXFQxw7fzEIlmhIaq3FnwIDAQAB\n-----END RSA PUBLIC KEY-----",
        ], // RSA public keys
    ],
    'connection' => [ // List of datacenters/subdomains where to connect
        'ssl_subdomains' => [ // Subdomains of web.telegram.org for https protocol
            1 => 'pluto',
            2 => 'venus',
            3 => 'aurora',
            4 => 'vesta',
            5 => 'flora', // musa oh wait no :(
        ],
        'test' => [ // Test datacenters
            'ipv4' => [ // ipv4 addresses
                2 => [ // The rest will be fetched automatically
                    'ip_address' => '149.154.167.40',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false,
                ],
            ],
            'ipv6' => [ // ipv6 addresses
                2 => [ // The rest will be fetched automatically
                    'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000e',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false,
                ],
            ],
        ],
        'main' => [ // Main datacenters
            'ipv4' => [ // ipv4 addresses
                2 => [ // The rest will be fetched automatically
                    'ip_address' => '149.154.167.51',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false,
                ],
            ],
            'ipv6' => [ // ipv6 addresses
                2 => [ // The rest will be fetched automatically
                    'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000a',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false,
                ],
            ],
        ],
    ],
    'connection_settings' => [ // connection settings
        'all' => [ // These settings will be applied on every datacenter that hasn't a custom settings subarray...
            'protocol' => 'tcp_full', // can be tcp_full, tcp_abridged, tcp_intermediate, http, https, obfuscated2, udp (unsupported)
            'test_mode' => false, // decides whether to connect to the main telegram servers or to the testing servers (deep telegram)
            'ipv6' => $this - > ipv6, // decides whether to use ipv6, ipv6 attribute of API attribute of API class contains autodetected boolean
            'timeout' => 2, // timeout for sockets
            'proxy' => '\Socket', // The proxy class to use
            'proxy_extra' => [], // Extra parameters to pass to the proxy class using setExtra
            'pfs'         => true, // Should we use PFS for this socket?
        ],
    ],
    'app_info' => [ // obtained in https://my.telegram.org
        'api_id'          => you should put an API id in the settings array you provide
        'api_hash'        => you should put an API hash in the settings array you provide
        'device_model' => $device_model,
        'system_version' => $system_version,
        'app_version' => 'Unicorn',
        'lang_code' => $lang_code,
    ],
    'tl_schema' => [ // TL scheme files
        'layer' => 75, // layer version
        'src' => [
            'mtproto' => __DIR__.'/TL_mtproto_v1.json', // mtproto TL scheme
            'telegram' => __DIR__.'/TL_telegram_v75.tl', // telegram TL scheme
            'secret' => __DIR__.'/TL_secret.tl', // secret chats TL scheme
            'calls' => __DIR__.'/TL_calls.tl', // calls TL scheme
            'botAPI' => __DIR__.'/TL_botAPI.tl', // bot API TL scheme for file ids
        ],
    ],
    'logger' => [ // Logger settings
        /*
         * logger modes:
         * 0 - No logger
         * 1 - Log to the default logger destination
         * 2 - Log to file defined in second parameter
         * 3 - Echo logs
         * 4 - Call callable provided in logger_param. logger_param must accept two parameters: array $message, int $level
         *     $message is an array containing the messages the log, $level, is the logging level
         */
        'logger' => 1, // write to
        'logger_param' => '/tmp/MadelineProto.log',
        'logger' => 3, // overwrite previous setting and echo logs
        'logger_level' => Logger::VERBOSE, // Logging level, available logging levels are: ULTRA_VERBOSE, VERBOSE, NOTICE, WARNING, ERROR, FATAL_ERROR. Can be provided as last parameter to the logging function.
        'rollbar_token'      => 'f9fff6689aea4905b58eec75f66c791d' // You can provide a token for the rollbar log management system
    ],
    'max_tries' => [
        'query' => 5, // How many times should I try to call a method or send an object before throwing an exception
        'authorization' => 5, // How many times should I try to generate an authorization key before throwing an exception
        'response' => 5, // How many times should I try to get a response of a query before throwing an exception
    ],
    'flood_timeout' => [
        'wait_if_lt' => 20, // Sleeps if flood block time is lower than this
    ],
    'msg_array_limit' => [ // How big should be the arrays containing the incoming and outgoing messages?
        'incoming' => 200,
        'outgoing' => 200,
        'call_queue' => 200,
    ],
    'peer' => [
        'full_info_cache_time' => 3600, // Full peer info cache validity
        'full_fetch' => false, // Should madeline fetch the full member list of every group it meets?
        'cache_all_peers_on_startup' => false, // Should madeline fetch the full chat list on startup?
    ],
    'requests' => [
        'gzip_encode_if_gt' => 500, // Should I try using gzip encoding for requests bigger than N bytes? Set to -1 to disable.
    ],
    'updates' => [
        'handle_updates' => true, // Should I handle updates?
        'handle_old_updates' => true, // Should I handle old updates on startup?
        'getdifference_interval' => 30, // Manual difference polling interval
        'callback' => 'get_updates_update_handler', // A callable function that will be called every time an update is received, must accept an array (for the update) as the only parameter
    ],
    'secret_chats' => [
        'accept_chats' => true, // Should I accept secret chats? Can be true, false or on array of user ids from which to accept chats
    ],
    'serialization' => [
        'serialization_interval' => 30, // Automatic serialization interval
    ],
    'threading' => [
        'allow_threading' => false, // Should I use threading, if it is enabled?
        'handler_workers' => 10, // How many workers should every message handler pool of each socket reader have
    ],
    'pwr' => [
        'pwr' => false, // Need info ?
        'db_token' => false, // Need info ?
        'strict' => false, // Need info ?
        'requests' => true, // Should I get info about unknown peers from PWRTelegram?
    ],
];
```



You can provide part of any subsetting array, that way the remaining arrays will be automagically set to default and undefined values of specified subsetting arrays will be set to the default values.   
Example:  

```
$settings = [
    'authorization' => [ // Authorization settings
        'default_temp_auth_key_expires_in' => 86400, // a day
    ]
]
```

Becomes:  

```
$settings = [
    'authorization' => [ // Authorization settings
        'default_temp_auth_key_expires_in' => 86400,
        'rsa_keys'                          => array with default rsa keys
    ]
    // The remaining subsetting arrays are the set to default
]
```

Note that only settings arrays or values of a settings array will be set to default.

The settings array can be accessed and modified in the instantiated class by accessing the `settings` attribute of the API class:

```
$yoursettings = ['updates' => ['handle_updates' => false]]; // disable update handlig
$MadelineProto = new \danog\MadelineProto\API($yoursettings);
var_dump($MadelineProto->settings);
$MadelineProto->settings['updates']['handle_updates'] = true; // reenable update fetching
```