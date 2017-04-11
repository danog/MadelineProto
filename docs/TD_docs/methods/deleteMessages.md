---
title: deleteMessages
description: Deletes messages. UpdateDeleteMessages will not be sent for messages deleted through that function
---
## Method: deleteMessages  
[Back to methods index](index.md)


Deletes messages. UpdateDeleteMessages will not be sent for messages deleted through that function

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|message\_ids|Array of [long](../types/long.md) | Yes|Identifiers of messages to delete|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$Ok = $MadelineProto->deleteMessages(['chat_id' => InputPeer, 'message_ids' => [long], ]);
```

Or, if you're into Lua:

```
Ok = deleteMessages({chat_id=InputPeer, message_ids={long}, })
```

