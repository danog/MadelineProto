---
title: messages.sendMessage
description: messages.sendMessage parameters, return type and example
---
## Method: messages.sendMessage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|message|[string](../types/string.md) | Yes|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|


### Return type: [messages\_SentMessage](../types/messages_SentMessage.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BUTTON_DATA_INVALID|The provided button data is invalid|
|BUTTON_TYPE_INVALID|The type of one of the buttons you provided is invalid|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|MESSAGE_EMPTY|The provided message is empty|
|MESSAGE_TOO_LONG|The provided message is too long|
|PEER_ID_INVALID|The provided peer id is invalid|
|RANDOM_ID_DUPLICATE|You provided a random ID that was already used|
|USER_BANNED_IN_CHANNEL|You're banned from sending messages in supergroups/channels|
|USER_IS_BLOCKED|User is blocked|
|USER_IS_BOT|Bots can't send messages to other bots|
|YOU_BLOCKED_USER|You blocked this user|


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

$messages_SentMessage = $MadelineProto->messages->sendMessage(['peer' => InputPeer, 'reply_to_msg_id' => int, 'message' => 'string', 'reply_markup' => ReplyMarkup, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendMessage
* params - `{"peer": InputPeer, "reply_to_msg_id": int, "message": "string", "reply_markup": ReplyMarkup, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendMessage`

Parameters:

peer - Json encoded InputPeer

reply_to_msg_id - Json encoded int

message - Json encoded string

reply_markup - Json encoded ReplyMarkup




Or, if you're into Lua:

```
messages_SentMessage = messages.sendMessage({peer=InputPeer, reply_to_msg_id=int, message='string', reply_markup=ReplyMarkup, })
```


## Usage of reply_markup

You can provide bot API reply_markup objects here.  



## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [messages\_SentMessage](../types/messages_SentMessage.md) will be returned instead.


