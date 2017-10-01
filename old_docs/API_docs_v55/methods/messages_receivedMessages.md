---
title: messages.receivedMessages
description: messages.receivedMessages parameters, return type and example
---
## Method: messages.receivedMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|max\_id|[int](../types/int.md) | Yes|


### Return type: [Vector\_of\_ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md)

### Can bots use this method: **NO**


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

$Vector_of_ReceivedNotifyMessage = $MadelineProto->messages->receivedMessages(['max_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.receivedMessages`

Parameters:

max_id - Json encoded int




Or, if you're into Lua:

```
Vector_of_ReceivedNotifyMessage = messages.receivedMessages({max_id=int, })
```

