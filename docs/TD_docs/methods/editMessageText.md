---
title: editMessageText
description: Edits text of text or game message. Non-bots can edit message in a limited period of time. Returns edited message after edit is complete server side
---
## Method: editMessageText  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Edits text of text or game message. Non-bots can edit message in a limited period of time. Returns edited message after edit is complete server side

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat the message belongs to|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Bots only. New message reply markup|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|New text content of the message. Should be of type InputMessageText|


### Return type: [Message](../types/Message.md)

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

$Message = $MadelineProto->editMessageText(['chat_id' => InputPeer, 'message_id' => long, 'reply_markup' => ReplyMarkup, 'input_message_content' => InputMessageContent, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - editMessageText
* params - `{"chat_id": InputPeer, "message_id": long, "reply_markup": ReplyMarkup, "input_message_content": InputMessageContent, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/editMessageText`

Parameters:

chat_id - Json encoded InputPeer

message_id - Json encoded long

reply_markup - Json encoded ReplyMarkup

input_message_content - Json encoded InputMessageContent




Or, if you're into Lua:

```
Message = editMessageText({chat_id=InputPeer, message_id=long, reply_markup=ReplyMarkup, input_message_content=InputMessageContent, })
```


## Usage of reply_markup

You can provide bot API reply_markup objects here.  


