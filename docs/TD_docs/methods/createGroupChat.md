---
title: createGroupChat
description: Returns existing chat corresponding to the known group
---
## Method: createGroupChat  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns existing chat corresponding to the known group

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|group\_id|[int](../types/int.md) | Yes|Group identifier|


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

$Chat = $MadelineProto->createGroupChat(['group_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - createGroupChat
* params - `{"group_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/createGroupChat`

Parameters:

group_id - Json encoded int




Or, if you're into Lua:

```
Chat = createGroupChat({group_id=int, })
```

