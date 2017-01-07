# MadelineProto
[![StyleCI](https://styleci.io/repos/61838413/shield)](https://styleci.io/repos/61838413)
[![Build Status](https://travis-ci.org/danog/MadelineProto.svg?branch=master)](https://travis-ci.org/danog/MadelineProto)  

Created by [Daniil Gentili](https://daniil.it), licensed under AGPLv3.

<img src='https://daniil.it/MadelineProto/logo.png' alt='MadelineProto logo' onmouseover="this.src='https://daniil.it/MadelineProto/logo-hover.png';" onmouseout="this.src='https://daniil.it/MadelineProto/logo.png';" />

Logo created by [Matthew Hesketh](http://matthewhesketh.com) (thanks again!).  

PHP implementation of MTProto, based on [telepy](https://github.com/griganton/telepy_old).

This project can run on PHP 7, PHP 5.6 and HHVM, only 64 bit systems are supported ATM.  

Also note that MadelineProto will perform better if a big math extension like gmp or bcmath is installed.

This project is in beta state.  

The MadelineProto API documentation can be found [here (layer 57)](https://daniil.it/MadelineProto/API_docs/).  

The MadelineProto API documentation (mtproto tl scheme) can be found [here](https://daniil.it/MadelineProto/MTProto_docs/).  

The MadelineProto API documentations (old layers) can be found [here](https://github.com/danog/MadelineProto/tree/master/old_docs).  

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
    'updates'   => [
        'updates_array_limit' => 1000, // How big should be the array containing the updates processed with the default example_update_handler callback
        'callback'            => [$this, 'get_updates_update_handler'], // A callable function that will be called every time an update is received, must accept an array (for the update) as the only parameter
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
var_dump($MadelineProto->get_settings());
```

The settings array can be modified in the instantiated class like this:

```
$MadelineProto = new \danog\MadelineProto\API();
$settings = $MadelineProto->get_settings();
// Make changes to $settings
$MadelineProto->update_settings($settings);
```

### Handling updates

When an update is received, the update callback function (see settings) is called. By default, the get_updates_update_handler MadelineProto method is called. This method stores all incoming updates into an array (its size limit is specified by the updates\_array\_limit parameter in the settings) and can be fetched by running the `get_updates` method.  
This method accepts an array of options as the first parameter, and returns an array of updates (an array containing the update id and an object of type [Update](https://daniil.it/MadelineProto/API_docs/types/Updates.html)). Example:  

```
$MadelineProto = new \danog\MadelineProto\API();
// Login or deserialize

$offset = 0;
while (true) {
    $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 1]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    foreach ($updates as $update) {
        $offset = $update['update_id']; // Just like in the bot API, the offset must be set to the last update_id
        // Parse $update['update'], that is an object of type Update
    }
    var_dump($updates);
}

array(3) {
  [0]=>
  array(2) {
    ["update_id"]=>
    int(0)
    ["update"]=>
    array(5) {
      ["_"]=>
      string(22) "updateNewAuthorization"
      ["auth_key_id"]=>
      int(-8182897590766478746)
      ["date"]=>
      int(1483110797)
      ["device"]=>
      string(3) "Web"
      ["location"]=>
      string(25) "IT, 05 (IP = 79.2.51.203)"
    }
  }
  [1]=>
  array(2) {
    ["update_id"]=>
    int(1)
    ["update"]=>
    array(3) {
      ["_"]=>
      string(23) "updateReadChannelOutbox"
      ["channel_id"]=>
      int(1049295266)
      ["max_id"]=>
      int(8288)
    }
  }
  [2]=>
  array(2) {
    ["update_id"]=>
    int(2)
    ["update"]=>
    array(4) {
      ["_"]=>
      string(23) "updateNewChannelMessage"
      ["message"]=>
      array(12) {
        ["_"]=>
        string(7) "message"
        ["out"]=>
        bool(false)
        ["mentioned"]=>
        bool(false)
        ["media_unread"]=>
        bool(false)
        ["silent"]=>
        bool(false)
        ["post"]=>
        bool(false)
        ["id"]=>
        int(11521)
        ["from_id"]=>
        int(262946027)
        ["to_id"]=>
        array(2) {
          ["_"]=>
          string(11) "peerChannel"
          ["channel_id"]=>
          int(1066910227)
        }
        ["date"]=>
        int(1483110798)
        ["message"]=>
        string(3) "yay"
        ["entities"]=>
        array(1) {
          [0]=>
          array(4) {
            ["_"]=>
            string(24) "messageEntityMentionName"
            ["offset"]=>
            int(0)
            ["length"]=>
            int(3)
            ["user_id"]=>
            int(101374607)
          }
        }
      }
      ["pts"]=>
      int(13010)
      ["pts_count"]=>
      int(1)
    }
  }
}


```

To specify a custom callback change the correct value in the settings. The specified callable must accept one parameter for the update.


### Uploading and downloading files

MadelineProto provides wrapper methods to upload and download files.

Every method described in this section accepts a last optional paramater with a callable function that will be called during the upload/download using the first parameter to pass a floating point number indicating the upload/download status in percentage.  

The upload method returns an [InputFile](https://daniil.it/MadelineProto/API_docs/types/InputFile.html) object that must be used to generate an [InputMedia](https://daniil.it/MadelineProto/API_docs/types/InputMedia.html) object, that can be later sent using the [sendmedia method](https://daniil.it/MadelineProto/API_docs/methods/messages_sendMedia.html).  


```
$inputFile = $MadelineProto->upload('file', 'optional new file name.ext');
// Generate an inputMedia object and store it in $inputMedia, see testing.php
$MadelineProto->messages->sendMedia(['peer' => '@pwrtelegramgroup', 'media' => $inputMedia]);
```


See testing.php for more examples.


There are multiple download methods that allow you to download a file to a directory, to a file or to a stream.  
The first parameter of these functions must always be a [messageMediaPhoto](https://daniil.it/MadelineProto/API_docs/constructors/messageMediaPhoto.html) or a [messageMediaDocument](https://daniil.it/MadelineProto/API_docs/constructors/messageMediaDocument.html) object. These objects are usually received in updates, see `bot.php` for examples


```
$output_file_name = $MadelineProto->download_to_dir($message_media, '/tmp/dldir');
$custom_output_file_name = $MadelineProto->download_to_file($message_media, '/tmp/dldir/customname.ext');
$stream = fopen('php://output', 'w'); // Stream to browser like with echo
$MadelineProto->download_to_stream($message_media, $stream);
```


### Calling mtproto methods and available wrappers

The API documentation can be found [here](https://daniil.it/MadelineProto/API_docs/).  
To call an MTProto method simply call it as if it is a method of the API class, substitute namespace sepators (.) with -> if needed.
Also, an object of type User, InputUser, Chat, InputChannel, Peer or InputPeer must be provided as a parameter to a method, you can substitute it with the user/group/channel's username or bot API id.  

```
$MadelineProto = new \danog\MadelineProto\API();
$checkedPhone = $MadelineProto->auth->checkPhone( // auth.checkPhone becomes auth->checkPhone
    [
        'phone_number'     => '3993838383', // Random invalid number, note that there should be no +
    ]
);
$ping = $MadelineProto->ping([3]); // parameter names can be omitted as long as the order specified by the TL scheme is respected
$message = "Hey! I'm sending this message with MadelineProto!";
$sentMessage = $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $message]);
var_dump($sentMessage);
```

The API class also provides some wrapper methods for logging in as a bot or as a normal user, and for getting inputPeer constructors to use in sendMessage and other methods:

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

An istance of MadelineProto can be safely serialized or unserialized. To serialize MadelineProto to a file, usage of the `\danog\MadelineProto\Serialization` class is recommended:

```  
$MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
// Do stuff
\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto);
```  

That class serializes only if the `$MadelineProto->API->should_serialize` boolean is set to true.
The same operation should be done when serializing to another destination manually, to avoid conflicts with other PHP scripts that are trying to serialize another instance of the class.

### Exceptions

MadelineProto can throw three different exceptions:  
* \danog\MadelineProto\Exception - Default exception, thrown when a php error occures and in a lot of other cases
* \danog\MadelineProto\RPCErrorException - Thrown when an RPC error occurres (an error received via the mtproto API)
* \danog\MadelineProto\TL\Exception - Thrown on TL serialization/deserialization errors


## Contributing

[Here](https://github.com/danog/MadelineProto/projects/1) you can find this project's roadmap.

You can use this scheme of the structure of this project to help yourself:

```
build_docs.php - Builds API docs from TL scheme file
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
        PeerHandler - Manages peers
        UpdateHandler - Handles updates
    TL/
        Exception - Handles exceptions in the TL namespace
        TL - Handles TL serialization and deserialization
        TLConstructor - Stores TL constructors
        TLMethod - Stores TL methods
        TLParams - Parses params
    Wrappers/
        Login - Handles logging in as a bot or a user, logging out
        PeerHandler - Eases getting of input peer objects using usernames or bot API chat ids
        SettingsManager - Eases updating settings
    API - Wrapper class that instantiates the MTProto class, sets the error handler, provides a wrapper for calling mtproto methods directly as class submethods, and uses the simplified wrappers from Wrappers/
    APIFactory - Provides a wrapper for calling namespaced mtproto methods directly as class submethods
    Connection - Handles tcp/udp/http connections and wrapping payloads generated by MTProtoTools/MessageHandler into the right message according to the protocol, stores authorization keys, session id and sequence number
    DataCenter - Handles mtproto datacenters (is a wrapper for Connection classes)
    DebugTools - Various debugging tools
    Exception - Handles exceptions and PHP errors
    RPCErrorException - Handles RPC errors
    MTProto - Handles initial connection, generation of authorization keys, instantiation of classes, writing of client info
    Logger - Static logging class
    prime.py and getpq.py - prime module (python) for p and q generation
    PrimeModule.php - prime module (php) for p and q generation by wrapping the python module, using wolfram alpha or a built in PHP engine
    RSA - Handles RSA public keys and signatures
    Tools - Various tools (positive modulus, string2bin, python-like range)
```

Check out the [Contribution guide](https://github.com/danog/MadelineProto/blob/master/CONTRIBUTING.md) before contributing.

