---
title: messages.searchGlobal
description: messages.searchGlobal parameters, return type and example
---
## Method: messages.searchGlobal  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|q|[string](../types/string.md) | Yes|
|offset\_date|[int](../types/int.md) | Yes|
|offset\_peer|[InputPeer](../types/InputPeer.md) | Yes|
|offset\_id|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [messages\_Messages](../types/messages_Messages.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|SEARCH_QUERY_EMPTY|The search query is empty|


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

$messages_Messages = $MadelineProto->messages->searchGlobal(['q' => 'string', 'offset_date' => int, 'offset_peer' => InputPeer, 'offset_id' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.searchGlobal`

Parameters:

q - Json encoded string

offset_date - Json encoded int

offset_peer - Json encoded InputPeer

offset_id - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
messages_Messages = messages.searchGlobal({q='string', offset_date=int, offset_peer=InputPeer, offset_id=int, limit=int, })
```

