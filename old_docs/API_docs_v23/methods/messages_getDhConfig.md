---
title: messages.getDhConfig
description: messages.getDhConfig parameters, return type and example
---
## Method: messages.getDhConfig  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|version|[int](../types/int.md) | Yes|
|random\_length|[int](../types/int.md) | Yes|


### Return type: [messages\_DhConfig](../types/messages_DhConfig.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|RANDOM_LENGTH_INVALID|Random length invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$messages_DhConfig = $MadelineProto->messages->getDhConfig(['version' => int, 'random_length' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getDhConfig`

Parameters:

version - Json encoded int

random_length - Json encoded int




Or, if you're into Lua:

```
messages_DhConfig = messages.getDhConfig({version=int, random_length=int, })
```

