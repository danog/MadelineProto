---
title: messages.sendInlineBotResult
description: Send inline bot result obtained with messages.getInlineBotResults to the chat
---
## Method: messages.sendInlineBotResult  
[Back to methods index](index.md)


Send inline bot result obtained with messages.getInlineBotResults to the chat

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|broadcast|[Bool](../types/Bool.md) | Optional|Broadcast this message?|
|silent|[Bool](../types/Bool.md) | Optional|Disable notifications?|
|background|[Bool](../types/Bool.md) | Optional|Disable background notifications?|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Where to send the message|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|Reply to message by ID|
|query\_id|[long](../types/long.md) | Yes|The inline query ID|
|id|[string](../types/string.md) | Yes|The ID of one of the inline results|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->messages->sendInlineBotResult(['broadcast' => Bool, 'silent' => Bool, 'background' => Bool, 'peer' => InputPeer, 'reply_to_msg_id' => int, 'query_id' => long, 'id' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendInlineBotResult`

Parameters:

broadcast - Json encoded Bool

silent - Json encoded Bool

background - Json encoded Bool

peer - Json encoded InputPeer

reply_to_msg_id - Json encoded int

query_id - Json encoded long

id - Json encoded string




Or, if you're into Lua:

```
Updates = messages.sendInlineBotResult({broadcast=Bool, silent=Bool, background=Bool, peer=InputPeer, reply_to_msg_id=int, query_id=long, id='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|INLINE_RESULT_EXPIRED|The inline query expired|
|PEER_ID_INVALID|The provided peer id is invalid|
|QUERY_ID_EMPTY|The query ID is empty|
|WEBPAGE_CURL_FAILED|Failure while fetching the webpage with cURL|
|WEBPAGE_MEDIA_EMPTY|Webpage media empty|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|


