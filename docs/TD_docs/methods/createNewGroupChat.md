---
title: createNewGroupChat
description: Creates new group chat and send corresponding messageGroupChatCreate, returns created chat
---
## Method: createNewGroupChat  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Creates new group chat and send corresponding messageGroupChatCreate, returns created chat

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_ids|Array of [int](../types/int.md) | Yes|Identifiers of users to add to the group|
|title|[string](../types/string.md) | Yes|Title of new group chat, 0-255 characters|


### Return type: [Chat](../types/Chat.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Chat = $MadelineProto->createNewGroupChat(['user_ids' => [int], 'title' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - createNewGroupChat
* params - `{"user_ids": [int], "title": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/createNewGroupChat`

Parameters:

user_ids - Json encoded  array of int

title - Json encoded string




Or, if you're into Lua:

```
Chat = createNewGroupChat({user_ids={int}, title='string', })
```

