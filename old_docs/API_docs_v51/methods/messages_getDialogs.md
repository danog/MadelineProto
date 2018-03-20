---
title: messages.getDialogs
description: messages.getDialogs parameters, return type and example
---
## Method: messages.getDialogs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset\_date|[CLICK ME int](../types/int.md) | Yes|
|offset\_id|[CLICK ME int](../types/int.md) | Yes|
|offset\_peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|
|limit|[CLICK ME int](../types/int.md) | Yes|


### Return type: [messages\_Dialogs](../types/messages_Dialogs.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|INPUT_CONSTRUCTOR_INVALID|The provided constructor is invalid|
|OFFSET_PEER_ID_INVALID|The provided offset peer is invalid|
|SESSION_PASSWORD_NEEDED|2FA is enabled, use a password to login|
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

$messages_Dialogs = $MadelineProto->messages->getDialogs(['offset_date' => int, 'offset_id' => int, 'offset_peer' => InputPeer, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getDialogs`

Parameters:

offset_date - Json encoded int

offset_id - Json encoded int

offset_peer - Json encoded InputPeer

limit - Json encoded int




Or, if you're into Lua:

```
messages_Dialogs = messages.getDialogs({offset_date=int, offset_id=int, offset_peer=InputPeer, limit=int, })
```

