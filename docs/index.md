# MadelineProto
[![StyleCI](https://styleci.io/repos/61838413/shield)](https://styleci.io/repos/61838413)
[![Build Status](https://travis-ci.org/danog/MadelineProto.svg?branch=master)](https://travis-ci.org/danog/MadelineProto)  

Created by [Daniil Gentili](https://daniil.it), licensed under AGPLv3.

PHP implementation of MTProto, based on [telepy](https://github.com/griganton/telepy_old).

This project can run on PHP 7, PHP 5.6 and HHVM.  

Also note that MadelineProto will perform better if a big math extension like gmp o bcmath is installed.

This project is in beta state.  

The API documentation can be found [here](https://daniil.it/MadelineProto/API_docs/).  

## Usage

### Dependencies

This project depends on [PHPStruct](https://github.com/danog/PHPStruct), [phpseclib](https://github.com/phpseclib/phpseclib), https://packagist.org/packages/paragonie/constant_time_encoding and https://packagist.org/packages/paragonie/random_compat

To install dependencies install composer and run:
```
composer update
```
In the cloned repo.


### Instantiation

```
$MadelineProto = new \danog\MadelineProto\API();
```

### Settings

The constructor accepts an optional parameter, which is the settings array. This array contains some other arrays, which are the settings for a specific MadelineProto function.  
Here you can see the default values for the settings\ arrays and explanations for every setting:
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
        'logger'       => 1, // 0 - No logger, 1 - Log to the default logger destination, 2 - Log to file defined in logger_param, 3 - Echo logs
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
        'rsa_key'                          => '-----BEGIN RSA PUBLIC KEY-----
MIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6
lyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS
an9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw
Efzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+
8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n
Slv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB
-----END RSA PUBLIC KEY-----',
    ]
    // The remaining subsetting arrays are the set to default
]
```

Note that only settings arrays or values of a settings array will be set to default.

The settings array can be accessed in the instantiated class like this:
```
$MadelineProto = new \danog\MadelineProto\API();
var_dump($MadelineProto->API->settings);
```

### Calling mtproto methods and available wrappers

The API documentation can be found [here](https://daniil.it/MadelineProto/API_docs/).  
To call an MTProto method simply call it as if it is a method of the API class, substitute namespace sepators (.) with -> if needed:
```
$MadelineProto = new \danog\MadelineProto\API();
$checkedPhone = $MadelineProto->auth->checkPhone( // auth.checkPhone becomes auth->checkPhone
    [
        'phone_number'     => '3993838383', // Random invalid number, note that there should be no +
    ]
);
$ping = $MadelineProto->ping([3]); // parameter names can be omitted as long as the order specified by the TL scheme is respected
$message = "Hey! I'm sending this message with MadelineProto!";
$username = $MadelineProto->contacts->resolveUsername(['username' => 'pwrtelegramgroup']);
var_dump($username);
$peer = ['_' => 'inputPeerChannel', 'channel_id' => $username['peer']['channel_id'], 'access_hash' => $username['chats'][0]['access_hash']];
$sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'random_id' => \danog\PHP\Struct::unpack('<q', \phpseclib\Crypt\Random::string(8))[0]]);
var_dump($sentMessage);
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

See testing.php for more examples.

### Storing sessions

An istance of MadelineProto can be safely serialized or unserialized.  

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
        TLConstructor - Stores TL constructors
        TLMethod - Stores TL methods
        TLParams - Parses params
    API - Wrapper class that instantiates the MTProto class, sets the error handler, provides a wrapper for calling mtproto methods directly as class submethods, and provides some simplified wrappers for logging in to telegram
    APIFactory - Provides a wrapper for calling namespaced mtproto methods directly as class submethods
    Connection - Handles tcp/udp/http connections and wrapping payloads generated by MTProtoTools/MessageHandler into the right message according to the protocol, stores authorization keys, session id and sequence number
    DataCenter - Handles mtproto datacenters (is a wrapper for Connection classes)
    DebugTools - Various debugging tools
    Exception - Handles exceptions and PHP errors
    RPCErrorException - Handles RPC errors
    MTProto - Extends MTProtoTools, handles initial connection, generation of authorization keys, instantiation of classes, writing of client info
    MTProtoTools - Extends all of the classes in MTProtoTools/
    Logger - Static logging class
    prime.py and getpq.py - prime module (python) for p and q generation
    PrimeModule.php - prime module (php) for p and q generation by wrapping the python module, using wolfram alpha or a built in PHP engine
    RSA - Handles RSA public keys and signatures
    Tools - Various tools (positive modulus, string2bin, python-like range)
```

Check out the [Contribution guide](https://github.com/danog/MadelineProto/blob/master/CONTRIBUTING.md) before contributing.

