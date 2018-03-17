---
title: messages.sendInlineBotResult
description: messages.sendInlineBotResult parameters, return type and example
---
## Method: messages.sendInlineBotResult  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|silent|[Bool](../types/Bool.md) | Optional|
|background|[Bool](../types/Bool.md) | Optional|
|clear\_draft|[Bool](../types/Bool.md) | Optional|
|peer|[Username, chat ID or InputPeer](../types/InputPeer.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|query\_id|[long](../types/long.md) | Yes|
|id|[string](../types/string.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|INLINE_RESULT_EXPIRED|The inline query expired|
|PEER_ID_INVALID|The provided peer id is invalid|
|QUERY_ID_EMPTY|The query ID is empty|
|WEBPAGE_CURL_FAILED|Failure while fetching the webpage with cURL|
|WEBPAGE_MEDIA_EMPTY|Webpage media empty|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|


### Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$Updates = $MadelineProto->messages->sendInlineBotResult(['silent' => Bool, 'background' => Bool, 'clear_draft' => Bool, 'peer' => InputPeer, 'reply_to_msg_id' => int, 'query_id' => long, 'id' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendInlineBotResult`

Parameters:

silent - Json encoded Bool

background - Json encoded Bool

clear_draft - Json encoded Bool

peer - Json encoded InputPeer

reply_to_msg_id - Json encoded int

query_id - Json encoded long

id - Json encoded string




Or, if you're into Lua:

```
Updates = messages.sendInlineBotResult({silent=Bool, background=Bool, clear_draft=Bool, peer=InputPeer, reply_to_msg_id=int, query_id=long, id='string', })
```

