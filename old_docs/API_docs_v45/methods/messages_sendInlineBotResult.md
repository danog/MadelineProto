---
title: messages.sendInlineBotResult
description: messages.sendInlineBotResult parameters, return type and example
---
## Method: messages.sendInlineBotResult  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|broadcast|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|query\_id|[long](../types/long.md) | Yes|
|id|[string](../types/string.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|INLINE_RESULT_EXPIRED|The inline query expired|
|QUERY_ID_EMPTY|The query ID is empty|


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

$Updates = $MadelineProto->messages->sendInlineBotResult(['broadcast' => Bool, 'peer' => InputPeer, 'reply_to_msg_id' => int, 'query_id' => long, 'id' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendInlineBotResult`

Parameters:

broadcast - Json encoded Bool

peer - Json encoded InputPeer

reply_to_msg_id - Json encoded int

query_id - Json encoded long

id - Json encoded string




Or, if you're into Lua:

```
Updates = messages.sendInlineBotResult({broadcast=Bool, peer=InputPeer, reply_to_msg_id=int, query_id=long, id='string', })
```

