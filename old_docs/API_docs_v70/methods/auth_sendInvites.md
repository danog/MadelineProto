---
title: auth.sendInvites
description: auth.sendInvites parameters, return type and example
---
## Method: auth.sendInvites  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_numbers|Array of [string](../types/string.md) | Yes|
|message|[string](../types/string.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->auth->sendInvites(['phone_numbers' => ['string'], 'message' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - auth.sendInvites
* params - `{"phone_numbers": ["string"], "message": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.sendInvites`

Parameters:

phone_numbers - Json encoded  array of string

message - Json encoded string




Or, if you're into Lua:

```
Bool = auth.sendInvites({phone_numbers={'string'}, message='string', })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [Bool](../types/Bool.md) will be returned instead.


