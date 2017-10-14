---
title: messages.saveRecentSticker
description: messages.saveRecentSticker parameters, return type and example
---
## Method: messages.saveRecentSticker  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|attached|[Bool](../types/Bool.md) | Optional|
|id|[InputDocument](../types/InputDocument.md) | Yes|
|unsave|[Bool](../types/Bool.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|STICKER_ID_INVALID|The provided sticker ID is invalid|


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

$Bool = $MadelineProto->messages->saveRecentSticker(['attached' => Bool, 'id' => InputDocument, 'unsave' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.saveRecentSticker`

Parameters:

attached - Json encoded Bool

id - Json encoded InputDocument

unsave - Json encoded Bool




Or, if you're into Lua:

```
Bool = messages.saveRecentSticker({attached=Bool, id=InputDocument, unsave=Bool, })
```

