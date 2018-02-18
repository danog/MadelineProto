---
title: messages.getSavedGifs
description: messages.getSavedGifs parameters, return type and example
---
## Method: messages.getSavedGifs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[int](../types/int.md) | Yes|


### Return type: [messages\_SavedGifs](../types/messages_SavedGifs.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$messages_SavedGifs = $MadelineProto->messages->getSavedGifs(['hash' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getSavedGifs`

Parameters:

hash - Json encoded int




Or, if you're into Lua:

```
messages_SavedGifs = messages.getSavedGifs({hash=int, })
```

