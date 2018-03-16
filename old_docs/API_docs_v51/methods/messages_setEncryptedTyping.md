---
title: messages.setEncryptedTyping
description: messages.setEncryptedTyping parameters, return type and example
---
## Method: messages.setEncryptedTyping  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|
|typing|[Bool](../types/Bool.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|


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

$Bool = $MadelineProto->messages->setEncryptedTyping(['peer' => InputEncryptedChat, 'typing' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setEncryptedTyping`

Parameters:

peer - Json encoded InputEncryptedChat

typing - Json encoded Bool




Or, if you're into Lua:

```
Bool = messages.setEncryptedTyping({peer=InputEncryptedChat, typing=Bool, })
```

