---
title: messages.getFeaturedStickers
description: messages.getFeaturedStickers parameters, return type and example
---
## Method: messages.getFeaturedStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[int](../types/int.md) | Yes|


### Return type: [messages\_FeaturedStickers](../types/messages_FeaturedStickers.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$messages_FeaturedStickers = $MadelineProto->messages->getFeaturedStickers(['hash' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getFeaturedStickers`

Parameters:

hash - Json encoded int




Or, if you're into Lua:

```
messages_FeaturedStickers = messages.getFeaturedStickers({hash=int, })
```

