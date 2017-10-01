---
title: account.setPassword
description: account.setPassword parameters, return type and example
---
## Method: account.setPassword  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|current\_password\_hash|[bytes](../types/bytes.md) | Yes|
|new\_salt|[bytes](../types/bytes.md) | Yes|
|new\_password\_hash|[bytes](../types/bytes.md) | Yes|
|hint|[string](../types/string.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->account->setPassword(['current_password_hash' => 'bytes', 'new_salt' => 'bytes', 'new_password_hash' => 'bytes', 'hint' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - account.setPassword
* params - `{"current_password_hash": "bytes", "new_salt": "bytes", "new_password_hash": "bytes", "hint": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.setPassword`

Parameters:

current_password_hash - Json encoded bytes

new_salt - Json encoded bytes

new_password_hash - Json encoded bytes

hint - Json encoded string




Or, if you're into Lua:

```
Bool = account.setPassword({current_password_hash='bytes', new_salt='bytes', new_password_hash='bytes', hint='string', })
```

