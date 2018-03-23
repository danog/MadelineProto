---
title: messages.editChatAdmin
description: Edit admin permissions
---
## Method: messages.editChatAdmin  
[Back to methods index](index.md)


Edit admin permissions

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat ID (no supergroups)|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user ID|
|is\_admin|[CLICK ME Bool](../types/Bool.md) | Yes|Should the user be admin?|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|


### MadelineProto Example:


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

$Bool = $MadelineProto->messages->editChatAdmin(['chat_id' => InputPeer, 'user_id' => InputUser, 'is_admin' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.editChatAdmin`

Parameters:

chat_id - Json encoded InputPeer

user_id - Json encoded InputUser

is_admin - Json encoded Bool




Or, if you're into Lua:

```
Bool = messages.editChatAdmin({chat_id=InputPeer, user_id=InputUser, is_admin=Bool, })
```

