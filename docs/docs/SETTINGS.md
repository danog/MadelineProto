# Settings

```php
$MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);
$MadelineProto->settings = $settings;
```

`$settings` - an array that contains some other arrays, which are the settings for a specific MadelineProto function.  
Here are the default values for the settings arrays and explanations for every setting:  

<hr>
## `$settings['authorization']`

Authorization settings  

### `$settings['authorization']['default_temp_auth_key_expires_in']`

Default: `31557600`  
Description: Validity of temporary keys and the binding of the temporary and permanent keys  

### `$settings['authorization']['rsa_keys'] = [...]`

Default: `["-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6\nlyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS\nan9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw\nEfzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+\n8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n\nSlv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB\n-----END RSA PUBLIC KEY-----", "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAxq7aeLAqJR20tkQQMfRn+ocfrtMlJsQ2Uksfs7Xcoo77jAid0bRt\nksiVmT2HEIJUlRxfABoPBV8wY9zRTUMaMA654pUX41mhyVN+XoerGxFvrs9dF1Ru\nvCHbI02dM2ppPvyytvvMoefRoL5BTcpAihFgm5xCaakgsJ/tH5oVl74CdhQw8J5L\nxI/K++KJBUyZ26Uba1632cOiq05JBUW0Z2vWIOk4BLysk7+U9z+SxynKiZR3/xdi\nXvFKk01R3BHV+GUKM2RYazpS/P8v7eyKhAbKxOdRcFpHLlVwfjyM1VlDQrEZxsMp\nNTLYXb6Sce1Uov0YtNx5wEowlREH1WOTlwIDAQAB\n-----END RSA PUBLIC KEY-----", "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAsQZnSWVZNfClk29RcDTJQ76n8zZaiTGuUsi8sUhW8AS4PSbPKDm+\nDyJgdHDWdIF3HBzl7DHeFrILuqTs0vfS7Pa2NW8nUBwiaYQmPtwEa4n7bTmBVGsB\n1700/tz8wQWOLUlL2nMv+BPlDhxq4kmJCyJfgrIrHlX8sGPcPA4Y6Rwo0MSqYn3s\ng1Pu5gOKlaT9HKmE6wn5Sut6IiBjWozrRQ6n5h2RXNtO7O2qCDqjgB2vBxhV7B+z\nhRbLbCmW0tYMDsvPpX5M8fsO05svN+lKtCAuz1leFns8piZpptpSCFn7bWxiA9/f\nx5x17D7pfah3Sy2pA+NDXyzSlGcKdaUmwQIDAQAB\n-----END RSA PUBLIC KEY-----", "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwqjFW0pi4reKGbkc9pK83Eunwj/k0G8ZTioMMPbZmW99GivMibwa\nxDM9RDWabEMyUtGoQC2ZcDeLWRK3W8jMP6dnEKAlvLkDLfC4fXYHzFO5KHEqF06i\nqAqBdmI1iBGdQv/OQCBcbXIWCGDY2AsiqLhlGQfPOI7/vvKc188rTriocgUtoTUc\n/n/sIUzkgwTqRyvWYynWARWzQg0I9olLBBC2q5RQJJlnYXZwyTL3y9tdb7zOHkks\nWV9IMQmZmyZh/N7sMbGWQpt4NMchGpPGeJ2e5gHBjDnlIf2p1yZOYeUYrdbwcS0t\nUiggS4UeE8TzIuXFQxw7fzEIlmhIaq3FnwIDAQAB\n-----END RSA PUBLIC KEY-----"]`  
Description: Array of RSA keys to use during key exchange.  
*WARNING*: be _very_ careful while modifying the default value of this setting, the security of telegram's MTProto protocol depends on it.


<hr>
## `$settings['connection']`

IP addresses and subdomains of the MTProto datacenters

### `$settings['connection']['ssl_subdomains']`
Default: `[
    1 => 'pluto',
    2 => 'venus',
    3 => 'aurora',
    4 => 'vesta',
    5 => 'flora', // musa oh wait no :(
]`  
Description: Subdomains of web.telegram.org for https protocol

### `$settings['connection']['test']`
Default: `[
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
]`  
Description: test datacenter IPs

### `$settings['connection']['main']`
Default: `[
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
]`  
Description: main datacenter IPs

<hr>
## `$settings['connection_settings']`

Connection settings

### `$settings['connection_settings']['all']`

Connection settings that will be applied to all datacenters

### `$settings['connection_settings']['all']['protocol']`
Default: `'tcp_abridged'`  
Description: MTProto transport protocol to use  
Available MTProto transport protocols (smaller overhead is better):

* tcp_abridged: Lightest protocol available
  * Overhead: Very small 
  * Pros:
    * Minimum envelope length: 1 byte (length)
    * Maximum envelope length: 4 bytes (length)

  * Cons:
    * Not all Telegram DCs support it
    * No obfuscation
    * No initial integrity check
    * No transport sequence number


* obfuscated2: Like tcp_abridged, but obfuscated 
  * Overhead: Medium-high
  * Pros:
    * All Telegram DCs support it
    * Minimum envelope length: 1 byte (length)
    * Maximum envelope length: 4 bytes (length)
    * Obfuscation to prevent ISP blocks

  * Cons: 
    * Initial payload of 64 bytes must be sent on every connection
    * Additional round of encryption is required  
    * No initial integrity check
    * No transport sequence number

* tcp_intermediate: I guess they like having multiple protocols
  * Overhead: small
  * Pros:
    * Minimum envelope length: 4 bytes (length)
    * Maximum envelope length: 4 bytes (length)

  * Cons:
    * No obfuscation
    * No initial integrity check
    * Not all Telegram DCs support it
    * No transport sequence number

* tcp_full: The basic MTProto transport protocol, supported by all clients
  * Overhead: medium
  * Pros:
    * All Telegram DCs support it
    * Initial integrity check with crc32
    * Transport sequence number check

  * Cons:
    * Minimum envelope length: 12 bytes (length+seqno+crc)
    * Maximum envelope length: 12 bytes (length+seqno+crc)
    * Initial integrity check with crc32 is not that useful since the TCP protocol already uses it internally
    * Transport sequence number check is also not that useful since transport sequence numbers are not encrypted and thus cannot be used to avoid replay attacks, and MadelineProto already uses MTProto sequence numbers and message ids for that

* http: MTProto over HTTP for browsers and webhosts
  * Overhead: medium
  * Pros:
    * Can be used on restricted webhosts or browsers

  * Cons: 
    * Very big envelope length
    * No Initial integrity check
    * No transport sequence number check

* https: MTProto over HTTPS for browsers and webhosts, very secure
  * Overhead: high
  * Pros:
    * Can be used on restricted webhosts or browsers
    * Provides an additional layer of security by trasmitting data over TLS
    * Integrity checks with HMAC built into TLS
    * Sequence number checks built into TLS
 
  * Cons: 
    * Very big envelope length
    * Requires an additional round of encryption

### `$settings['connection_settings']['all']['test_mode']`
Default: false  
Description: Whether to connect to the main telegram servers or to the testing servers (deep telegram)

### `$settings['connection_settings']['all']['ipv6']`
Default: auto-detected  
Description: Whether to use ipv6 while connecting to the telegram servers

### `$settings['connection_settings']['all']['timeout']`
Default: 2  
Description: Connection, read and write timeout for sockets

### `$settings['connection_settings']['all']['proxy']`
Default: `\Socket`  
Description: The [proxy class](PROXY.html) to use.

### `$settings['connection_settings']['all']['proxy_extra']`
Default: `[]`  
Description: Extra parameters to pass to the proxy class using setExtra

### `$settings['connection_settings']['all']['pfs']`
Default: `true` if `php-gmp` is installed, `false` otherwise  
Description: Whether to use PFS (better security, slower key exchange)


<hr>
## `$settings['app_info']`

Application info

### `$settings['app_info']['api_id']`
No default value, get your own API ID at my.telegram.org

### `$settings['app_info']['api_hash']`
No default value, get your own API hash at my.telegram.org

### `$settings['app_info']['device_model']`
Default: auto-detected  
Description: device model

### `$settings['app_info']['system_version']`
Default: auto-detected  
Description: system version

### `$settings['app_info']['app_version']`
Default: `Unicorn`  
Description: App version

### `$settings['app_info']['lang_code']`
Default: auto-detected  
Description: Language code


<hr>
## `$settings['tl_schema']`

TL scheme files

### `$settings['tl_schema']['layer']`
Default: 75  
Description: layer version

### `$settings['tl_schema']['src']`
Default: `[
    'mtproto' => __DIR__.'/TL_mtproto_v1.json', // mtproto TL scheme
    'telegram' => __DIR__.'/TL_telegram_v75.tl', // telegram TL scheme
    'secret' => __DIR__.'/TL_secret.tl', // secret chats TL scheme
    'calls' => __DIR__.'/TL_calls.tl', // calls TL scheme
    'botAPI' => __DIR__.'/TL_botAPI.tl', // bot API TL scheme for file ids
]`  
Description: scheme files to use


<hr>
## `$settings['logger']` 

Logger settings

### `$settings['logger']['logger']`
Default: 3 if running from CLI, 2 if running from browser  
Description: logger mode, available logger modes:

* 0 - No logger
* 1 - Log to the default logger destination
* 2 - Log to file in `$settings['logger']['logger_param']`
* 3 - Echo logs
* 4 - Call callable provided in `$settings['logger']['logger_param']`. logger_param must accept two parameters: array $message, int $level

### `$settings['logger']['param']`
Default: `__DIR__.'/Madeline.log'`  
Description: optional logger parameter, for modes that require it

### `$settings['logger']['logger_level']`
Default: `\danog\MadelineProto\Logger::VERBOSE`  
Description: What logger messages to show

### `$settings['logger']['rollbar_token']`
Description: You can provide a token for the rollbar log management system


<hr>
## `$settings['max_tries']`

Max try settings

### `$settings['max_tries']['query']`
Default: 5  
Description: How many times should I try to call a method or send an object before throwing an exception?

### `$settings['max_tries']['query']`
Default: 5  
Description: How many times should I try to generate an authorization key before throwing an exception?

### `$settings['max_tries']['response']`
Default: 5  
Description: How many times should I try to get a response to a query before throwing an exception?

<hr>
## `$settings['flood_timeout']`

Flood timeout settings

### `$settings['flood_timeout']['wait_if_lt']`
Default: 20  
Description: Sleeps if a `FLOOD_WAIT_` error is received with duration lower than this value

<hr>
## `$settings['secret_chats']`

Secret chat settings

### `$settings['secret_chats']['accept_chats']`
Default: `true`  
Description: Can be true to accept all secret chats, false to not accept any secret chat, or an array of user IDs from which to accepts secret chats

<hr>
## `$settings['upload']`

Upload settings

### `$settings['upload']['allow_automatic_uploads']`
Default: `true`  
Description: If false, [disables automatic upload from file path in constructors](FILES.html)

<hr>
## `$settings['msg_array_limit']`

How big should be the arrays containing the incoming and outgoing messages?

### `$settings['msg_array_limit']['incoming']`
Default: 200  
Description: maximum number of allowed MTProto messages in the incoming message array

### `$settings['msg_array_limit']['outgoing']`
Default: 200  
Description: maximum number of allowed MTProto messages in the outgoing message array

### `$settings['msg_array_limit']['call_queue']`
Default: 200  
Description: maximum number of allowed MTProto messages in any [call queue](USING_METHOD.html#call-queues)


<hr>
## `$settings['peer']`

Peer caching settings

### `$settings['peer']['full_info_cache_time']`
Default: 3600  
Description: Cache validity of full peer info (obtained with [get_full_info](CHAT_INFO.html#get_full_info)) 

### `$settings['peer']['full_fetch']`
Default: false  
Description: Should madeline fetch the full member list of every group it meets?

### `$settings['peer']['cache_all_peers_on_startup']`
Default: false  
Description: Should madeline fetch the full chat list on startup?


<hr>
## `$settings['requests']`

Flood timeout settings

### `$settings['requests']['gzip_encode_if_gt']`
Default: 500  
Description: Should I try using gzip encoding for requests bigger than N bytes? Set to -1 to disable.


<hr>
## `$settings['updates']`

Update handling settings

### `$settings['updates']['handle_updates']`
Default: false  
Description: Should I handle updates?

### `$settings['updates']['handle_old_updates']`
Default: true  
Description: Should I handle old updates on startup?

### `$settings['updates']['getdifference_interval']`
Default: 10  
Description: If positive and bigger than zero, no requests will be sent to the socket to request updates in N seconds, passive update listening will be used instead

### `$settings['updates']['callback']`
Default: `'get_updates_update_handler'`  
Description: A callable function that will be called every time an update is received, must accept an array (for the update) as the only parameter.  

<hr>
## `$settings['serialization']`

Serialization settings

### `$settings['serialization']['serialization_interval']`
Default: 30  
Description: Serialization will be made automatically every N seconds


<hr>

You can provide part of any subsetting array, that way the remaining arrays will be automagically set to default and undefined values of specified subsetting arrays will be set to the default values.   
Example:  

```php
$MadelineProto->settings = [
    'authorization' => [ // Authorization settings
        'default_temp_auth_key_expires_in' => 86400, // a day
    ]
]
```

The settings array can be accessed and modified in the instantiated class by accessing the `settings` attribute of the API class:

```php
$MadelineProto->settings['updates']['handle_updates'] = true; // reenable update fetching
```

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/UPDATES.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/SELF.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>