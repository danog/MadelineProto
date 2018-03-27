---
title: req_DH_params
description: Requests Diffie-hellman parameters for key exchange
---
## Method: req\_DH\_params  
[Back to methods index](index.md)


Requests Diffie-hellman parameters for key exchange

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|nonce|[int128](../types/int128.md) | Yes|Random number for cryptographic security|
|server\_nonce|[int128](../types/int128.md) | Yes|Random number for cryptographic security, given by server|
|p|[bytes](../types/bytes.md) | Yes|Factorized p from pq|
|q|[bytes](../types/bytes.md) | Yes|Factorized q from pq|
|public\_key\_fingerprint|[long](../types/long.md) | Yes|Server RSA fingerprint|
|encrypted\_data|[bytes](../types/bytes.md) | Yes|Encrypted key exchange message|


### Return type: [Server\_DH\_Params](../types/Server_DH_Params.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Server_DH_Params = $MadelineProto->req_DH_params(['nonce' => int128, 'server_nonce' => int128, 'p' => 'bytes', 'q' => 'bytes', 'public_key_fingerprint' => long, 'encrypted_data' => 'bytes', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - req_DH_params
* params - `{"nonce": int128, "server_nonce": int128, "p": "bytes", "q": "bytes", "public_key_fingerprint": long, "encrypted_data": "bytes", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/req_DH_params`

Parameters:

nonce - Json encoded int128

server_nonce - Json encoded int128

p - Json encoded bytes

q - Json encoded bytes

public_key_fingerprint - Json encoded long

encrypted_data - Json encoded bytes




Or, if you're into Lua:

```
Server_DH_Params = req_DH_params({nonce=int128, server_nonce=int128, p='bytes', q='bytes', public_key_fingerprint=long, encrypted_data='bytes', })
```

