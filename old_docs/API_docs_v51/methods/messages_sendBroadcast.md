---
title: messages.sendBroadcast
description: messages.sendBroadcast parameters, return type and example
---
## Method: messages.sendBroadcast  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|contacts|Array of [InputUser](../types/InputUser.md) | Yes|
|message|[string](../types/string.md) | Yes|
|media|[InputMedia](../types/InputMedia.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Updates = $MadelineProto->messages->sendBroadcast(['contacts' => [InputUser], 'message' => 'string', 'media' => InputMedia, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendBroadcast
* params - `{"contacts": [InputUser], "message": "string", "media": InputMedia, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendBroadcast`

Parameters:

contacts - Json encoded  array of InputUser

message - Json encoded string

media - Json encoded InputMedia




Or, if you're into Lua:

```
Updates = messages.sendBroadcast({contacts={InputUser}, message='string', media=InputMedia, })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [Updates](../types/Updates.md) will be returned instead.


