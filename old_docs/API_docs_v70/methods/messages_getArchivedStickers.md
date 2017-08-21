---
title: messages.getArchivedStickers
description: messages.getArchivedStickers parameters, return type and example
---
## Method: messages.getArchivedStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|masks|[Bool](../types/Bool.md) | Optional|
|offset\_id|[long](../types/long.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [messages\_ArchivedStickers](../types/messages_ArchivedStickers.md)

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

$messages_ArchivedStickers = $MadelineProto->messages->getArchivedStickers(['masks' => Bool, 'offset_id' => long, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getArchivedStickers
* params - `{"masks": Bool, "offset_id": long, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getArchivedStickers`

Parameters:

masks - Json encoded Bool

offset_id - Json encoded long

limit - Json encoded int




Or, if you're into Lua:

```
messages_ArchivedStickers = messages.getArchivedStickers({masks=Bool, offset_id=long, limit=int, })
```

