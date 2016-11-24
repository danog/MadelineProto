# MadelineProto
[![StyleCI](https://styleci.io/repos/61838413/shield)](https://styleci.io/repos/61838413)
[![Build Status](https://travis-ci.org/danog/MadelineProto.svg?branch=master)](https://travis-ci.org/danog/MadelineProto)  

Created by [Daniil Gentili](https://daniil.it), licensed under AGPLv3.

PHP implementation of MTProto, based on [telepy](https://github.com/griganton/telepy_old).

This project can run on PHP 7, PHP 5.6 and HHVM.  

This project is in beta state.  


## Usage

### Instantiation

```
$madeline = new \danog\MadelineProto\API();
```

### Settings

The constructor accepts an optional parameter, which is the settings array.  
Here you can see its default value and explanations for every setting:
```
$settings = [
    'authorization' => [ // Authorization settings
        'default_temp_auth_key_expires_in' => 31557600, // validity of temporary keys and the binding of the temporary and permanent keys
        'rsa_key'                          => '-----BEGIN RSA PUBLIC KEY-----
MIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6
lyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS
an9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw
Efzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+
8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n
Slv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB
-----END RSA PUBLIC KEY-----', // RSA public key
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
                2 => [ // The rest will be fetched using help.getConfig
                    'ip_address' => '149.154.167.40',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false
                ]
            ],
            'ipv6' => [ // ipv6 addresses
                2 => [ // The rest will be fetched using help.getConfig
                    'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000e',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false
                ]
            ]
        ],
        'main' => [ // Main datacenters
            'ipv4' => [ // ipv4 addresses
                2 => [ // The rest will be fetched using help.getConfig
                    'ip_address' => '149.154.167.51',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false
                 ]
             ],
            'ipv6' => [ // ipv6 addresses
                2 => [ // The rest will be fetched using help.getConfig
                    'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000a',
                    'port' => 443,
                            'media_only' => false,
                            'tcpo_only' => false
                         ]
                     ]
                ],
            ],
            'connection_settings' => [ // connection settings
                'all' => [ // Connection settings will be applied on datacenter ids matching the key of these settings subarrays, if the key is equal to all like in this case that will match all datacenters that haven't a custom settings subarray...
                    'protocol'  => 'tcp_full', // can be tcp_full, tcp_abridged, tcp_intermediate, http (unsupported), https (unsupported), udp (unsupported)
                    'test_mode' => false, // decides whether to connect to the main telegram servers or to the testing servers (deep telegram)
                    'ipv6' => $this->ipv6, // decides whether to use ipv6, ipv6 attribute of API attribute of API class contains autodetected boolean
                    'timeout'      => 10 // timeout for sockets
                ],
            ],
            'app_info' => [ // obtained in https://my.telegram.org
                'api_id'          => 25628,
                'api_hash'        => '1fe17cda7d355166cdaa71f04122873c',
                'device_model'    => php_uname('s'),
                'system_version'  => php_uname('r'),
                'app_version'     => 'Unicorn', // 🌚
                'lang_code'       => 'en',
            ],
            'tl_schema'     => [ // TL scheme files
                'layer'         => 57, // layer version
                'src'           => [
                    'mtproto'  => __DIR__.'/TL_mtproto_v1.json', // mtproto TL scheme
                    'telegram' => __DIR__.'/TL_telegram_v57.json', // telegram TL scheme
                ],
            ],
            'logger'       => [ // Logger settings
                /*
                 * logger modes:
                 * 0 - No logger
                 * 1 - Log to the default logger destination
                 * 2 - Log to file defined in second parameter
                 * 3 - Echo logs
                 */
                'logger'       => 1, // write to
                'logger_param' => '/tmp/MadelineProto.log',
                'logger'       => 3, // overwrite previous setting and echo logs
            ],
            'max_tries'         => [
                'query'         => 5, // How many times should I try to call a method or send an object before throwing an exception
                'authorization' => 5, // How many times should I try to generate an authorization key before throwing an exception
                'response'      => 5,// How many times should I try to get a response of a query before throwing an exception
            ],
            'msg_array_limit'        => [ // How big should be the arrays containing the incoming and outgoing messages?
                'incoming' => 30,
                'outgoing' => 30,
            ],
        ];
```

You can provide only part of any of the subsettings array, the rest will be automagically set to the default values of the specified subsettings array.   
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
        'rsa_key'                          => '-----BEGIN RSA PUBLIC KEY-----
MIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6
lyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS
an9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw
Efzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+
8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n
Slv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB
-----END RSA PUBLIC KEY-----',
    ]
    // The remaining subsetting arrays are the set to the default ones
]
```

The rest of the settings array and of the authorization array will be set to the default values specified in the default array.  
Note that only subsetting arrays or values of a subsetting array will be set to default.

The settings array can be accessed in the instantiated class like this:
```
$MadelineProto = new \danog\MadelineProto\API();
$generated_settings = $MadelineProto->API->settings;
```

### Calling mtproto methods and available wrappers

A list of mtproto methods can be found [here](https://tjhorner.com/tl-schema/#functions).  
To call an MTProto method simply call it as if it is a method of the API class, substitute namespace sepators (.) with -> if needed:
```
$MadelineProto = new \danog\MadelineProto\API();
$checkedPhone = $MadelineProto->auth->checkPhone( // auth.checkPhone becomes auth->checkPhone
    [
        'phone_number'     => '3993838383', // Random invalid number, note that there should be no +
    ]
);
$ping = $MadelineProto->ping([3]); // parameter names can be omitted as long as the order specified by the TL scheme is respected
```

The API class also provides some wrapper methods for logging in as a bot or as a normal user:
```
$sentCode = $MadelineProto->phone_login($number); // Send code
var_dump($sentCode);
echo 'Enter the code you received: ';
$code = '';
for ($x = 0; $x < $sentCode['type']['length']; $x++) {
    $code .= fgetc(STDIN);
}
$authorization = $MadelineProto->complete_phone_login($code); // Complete authorization
var_dump($authorization);

$authorization = $MadelineProto->bot_login($token); // Note that every time you login as a bot or as a user MadelineProto will logout first, so now MadelineProto is logged in as the bot with token $token, not as the user with number $number
var_dump($authorization);
```

### Exceptions

MadelineProto can throw three different exceptions:  
* \danog\MadelineProto\Exception - Default exception, thrown when a php error occures and in a lot of other cases
* \danog\MadelineProto\RPCErrorException - Thrown when an RPC error occurres (an error received via the mtproto API)
* \danog\MadelineProto\TL\Exception - Thrown on TL serialization/deserialization errors


## Contributing

[Here](https://github.com/danog/MadelineProto/projects/1) you can find this project's roadmap.

You can use this scheme of the structure of this project to help yourself:
```
src/danog/MadelineProto/
    MTProtoTools/
        AckHandler - Handles acknowledgement of incoming and outgoing mtproto messages
        AuthKeyHandler - Handles generation of the temporary and permanent authorization keys
        CallHandler - Handles synchronous calls to mtproto methods or objects, also basic response management (waits until the socket receives a response)
        Crypt - Handles ige and aes encryption
        MessageHandler - Handles sending and receiving of mtproto messages (packs TL serialized data with message id, auth key id and encrypts it with Crypt if needed, adds them to the arrays of incoming and outgoing messages)
        MsgIdHandler - Handles message ids (checks if they are valid, adds them to the arrays of incoming and outgoing messages)
        ResponseHandler - Handles the content of responses received, service messages, rpc results, errors, and stores them into the response section of the outgoing messages array)
        SaltHandler - Handles server salts
        SeqNoHandler - Handles sequence numbers (checks validity)
    TL/
        Exception - Handles exceptions in the TL namespace
        TL - Handles TL serialization and deserialization
        TLConstructor - Represents a TL Constructor
        TLMethod - Represents a TL method
    API - Wrapper class that istantiates the MTProto class, sets the error handler, provides a wrapper for calling mtproto methods directly as class submethods, and provides some simplified wrappers for logging in to telegram
    APIFactory - Provides a wrapper for calling namespaced mtproto methods directly as class submethods
    Connection - Handles tcp/udp/http connections and wrapping payloads generated by MTProtoTools/MessageHandler into the right message according to the protocol, stores authorization keys, session id and sequence number
    DataCenter - Handles mtproto datacenters (is a wrapper for Connection classes)
    DebugTools - Various debugging tools
    Exception - Handles exceptions and PHP errors
    RPCErrorException - Handles RPC errors
    MTProto - Extends MTProtoTools, handles initial connection, generation of authorization keys, istantiation of classes, writing of client info
    MTProtoTools - Extends all of the classes in MTProtoTools/
    Logger - Static logging class
    prime.py and getpq.py - prime module (python) for p and q generation
    PrimeModule.php - prime module (php) for p and q generation by wrapping the python module, using wolfram alpha or a built in PHP engine
    RSA - Handles RSA public keys and signatures
    Tools - Various tools (positive modulus, string2bin, python-like range)
```

Check out the [Contribution guide](https://github.com/danog/MadelineProto/blob/master/CONTRIBUTING.md) before contributing.

