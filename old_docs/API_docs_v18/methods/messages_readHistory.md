---
title: messages.readHistory
description: messages.readHistory parameters, return type and example
---
## Method: messages.readHistory  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|
|max\_id|[CLICK ME int](../types/int.md) | Yes|
|offset|[CLICK ME int](../types/int.md) | Yes|
|read\_contents|[CLICK ME Bool](../types/Bool.md) | Yes|


### Return type: [messages\_AffectedHistory](../types/messages_AffectedHistory.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|
|Timeout|A timeout occurred while fetching data from the bot|


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

$messages_AffectedHistory = $MadelineProto->messages->readHistory(['peer' => InputPeer, 'max_id' => int, 'offset' => int, 'read_contents' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.readHistory`

Parameters:

peer - Json encoded InputPeer

max_id - Json encoded int

offset - Json encoded int

read_contents - Json encoded Bool




Or, if you're into Lua:

```
messages_AffectedHistory = messages.readHistory({peer=InputPeer, max_id=int, offset=int, read_contents=Bool, })
```

