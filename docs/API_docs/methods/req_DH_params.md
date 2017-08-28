---
title: req_DH_params
description: req_DH_params parameters, return type and example
---
## Method: req\_DH\_params  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|nonce|[int128](../types/int128.md) | Yes|
|server\_nonce|[int128](../types/int128.md) | Yes|
|p|[string](../types/string.md) | Yes|
|q|[string](../types/string.md) | Yes|
|public\_key\_fingerprint|[long](../types/long.md) | Yes|
|encrypted\_data|[string](../types/string.md) | Yes|


### Return type: [Server\_DH\_Params](../types/Server_DH_Params.md)

### Can bots use this method: **YES**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Server_DH_Params = $MadelineProto->req_DH_params(['nonce' => int128, 'server_nonce' => int128, 'p' => 'string', 'q' => 'string', 'public_key_fingerprint' => long, 'encrypted_data' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - req_DH_params
* params - `{"nonce": int128, "server_nonce": int128, "p": "string", "q": "string", "public_key_fingerprint": long, "encrypted_data": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/req_DH_params`

Parameters:

nonce - Json encoded int128

server_nonce - Json encoded int128

p - Json encoded string

q - Json encoded string

public_key_fingerprint - Json encoded long

encrypted_data - Json encoded string




Or, if you're into Lua:

```
Server_DH_Params = req_DH_params({nonce=int128, server_nonce=int128, p='string', q='string', public_key_fingerprint=long, encrypted_data='string', })
```

