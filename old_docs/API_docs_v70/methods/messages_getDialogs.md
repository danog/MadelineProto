---
title: messages.getDialogs
description: Gets list of chats: you should use $MadelineProto->get_dialogs() instead: https://docs.madelineproto.xyz/docs/DIALOGS.html
---
## Method: messages.getDialogs  
[Back to methods index](index.md)


Gets list of chats: you should use $MadelineProto->get_dialogs() instead: https://docs.madelineproto.xyz/docs/DIALOGS.html

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|exclude\_pinned|[Bool](../types/Bool.md) | Optional|Do not fetch pinned chats|
|offset\_date|[int](../types/int.md) | Yes|end($res['messages'])['date'];|
|offset\_id|[int](../types/int.md) | Yes|end($res['messages'])['id'];|
|offset\_peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|end($res['dialogs'])['peer'];|
|limit|[int](../types/int.md) | Yes|Number of dialogs to fetch|


### Return type: [messages\_Dialogs](../types/messages_Dialogs.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_Dialogs = $MadelineProto->messages->getDialogs(['exclude_pinned' => Bool, 'offset_date' => int, 'offset_id' => int, 'offset_peer' => InputPeer, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getDialogs`

Parameters:

exclude_pinned - Json encoded Bool

offset_date - Json encoded int

offset_id - Json encoded int

offset_peer - Json encoded InputPeer

limit - Json encoded int




Or, if you're into Lua:

```
messages_Dialogs = messages.getDialogs({exclude_pinned=Bool, offset_date=int, offset_id=int, offset_peer=InputPeer, limit=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|INPUT_CONSTRUCTOR_INVALID|The provided constructor is invalid|
|OFFSET_PEER_ID_INVALID|The provided offset peer is invalid|
|SESSION_PASSWORD_NEEDED|2FA is enabled, use a password to login|
|Timeout|A timeout occurred while fetching data from the bot|


