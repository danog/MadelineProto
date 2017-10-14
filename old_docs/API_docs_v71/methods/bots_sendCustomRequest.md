---
title: bots.sendCustomRequest
description: bots.sendCustomRequest parameters, return type and example
---
## Method: bots.sendCustomRequest  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|custom\_method|[string](../types/string.md) | Yes|
|params|[DataJSON](../types/DataJSON.md) | Yes|


### Return type: [DataJSON](../types/DataJSON.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|USER_BOT_INVALID|This method can only be called by a bot|


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

$DataJSON = $MadelineProto->bots->sendCustomRequest(['custom_method' => 'string', 'params' => DataJSON, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - bots.sendCustomRequest
* params - `{"custom_method": "string", "params": DataJSON, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/bots.sendCustomRequest`

Parameters:

custom_method - Json encoded string

params - Json encoded DataJSON




Or, if you're into Lua:

```
DataJSON = bots.sendCustomRequest({custom_method='string', params=DataJSON, })
```

