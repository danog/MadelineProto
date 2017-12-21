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
|PEER_ID_INVALID|The provided peer id is invalid|
|QUERY_ID_EMPTY|The query ID is empty|
|WEBPAGE_CURL_FAILED|Failure while fetching the webpage with cURL|
|WEBPAGE_MEDIA_EMPTY|Webpage media empty|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
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

