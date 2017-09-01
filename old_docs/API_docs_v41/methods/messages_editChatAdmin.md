---
title: messages.editChatAdmin
description: messages.editChatAdmin parameters, return type and example
---
## Method: messages.editChatAdmin  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|is\_admin|[Bool](../types/Bool.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|


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

$Bool = $MadelineProto->messages->editChatAdmin(['chat_id' => InputPeer, 'user_id' => InputUser, 'is_admin' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



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

