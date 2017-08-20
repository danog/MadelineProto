---
title: deleteChatReplyMarkup
description: Deletes default reply markup from chat. This method needs to be called after one-time keyboard or ForceReply reply markup has been used. UpdateChatReplyMarkup will be send if reply markup will be changed
---
## Method: deleteChatReplyMarkup  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Deletes default reply markup from chat. This method needs to be called after one-time keyboard or ForceReply reply markup has been used. UpdateChatReplyMarkup will be send if reply markup will be changed

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|message\_id|[long](../types/long.md) | Yes|Message identifier of used keyboard|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->deleteChatReplyMarkup(['chat_id' => InputPeer, 'message_id' => long, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - deleteChatReplyMarkup
* params - `{"chat_id": InputPeer, "message_id": long, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/deleteChatReplyMarkup`

Parameters:

chat_id - Json encoded InputPeer

message_id - Json encoded long




Or, if you're into Lua:

```
Ok = deleteChatReplyMarkup({chat_id=InputPeer, message_id=long, })
```

