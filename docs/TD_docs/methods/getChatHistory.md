---
title: getChatHistory
description: Returns messages in a chat. Automatically calls openChat. Returns result in reverse chronological order, i.e. in order of decreasing message.message_id
---
## Method: getChatHistory  
[Back to methods index](index.md)


Returns messages in a chat. Automatically calls openChat. Returns result in reverse chronological order, i.e. in order of decreasing message.message_id

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|from\_message\_id|[long](../types/long.md) | Yes|Identifier of the message near which we need a history, you can use 0 to get results from the beginning, i.e. from oldest to newest|
|offset|[int](../types/int.md) | Yes|Specify 0 to get results exactly from from_message_id or negative offset to get specified message and some newer messages|
|limit|[int](../types/int.md) | Yes|Maximum number of messages to be returned, should be positive and can't be greater than 100. If offset is negative, limit must be greater than -offset. There may be less than limit messages returned even the end of the history is not reached|


### Return type: [Messages](../types/Messages.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Messages = $MadelineProto->getChatHistory(['chat_id' => long, 'from_message_id' => long, 'offset' => int, 'limit' => int, ]);
```

Or, if you're into Lua:

```
Messages = getChatHistory({chat_id=long, from_message_id=long, offset=int, limit=int, })
```

