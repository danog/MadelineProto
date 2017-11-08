---
title: messages.sendMedia
description: messages.sendMedia parameters, return type and example
---
## Method: messages.sendMedia  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|reply\_to\_msg\_id|[int](../types/int.md) | Yes|
|media|[InputMedia](../types/InputMedia.md) | Yes|


### Return type: [messages\_StatedMessage](../types/messages_StatedMessage.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|EXTERNAL_URL_INVALID|External URL invalid|
|FILE_PART_0_MISSING|File part 0 missing|
|FILE_PART_6_MISSING|File part 6 missing|
|FILE_PART_LENGTH_INVALID|The length of a file part is invalid|
|FILE_PARTS_INVALID|The number of file parts is invalid|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|MEDIA_CAPTION_TOO_LONG|The caption is too long|
|MEDIA_EMPTY|The provided media object is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|
|PHOTO_EXT_INVALID|The extension of the photo is invalid|
|PHOTO_INVALID_DIMENSIONS|The photo dimensions are invalid|
|USER_BANNED_IN_CHANNEL|You're banned from sending messages in supergroups/channels|
|USER_IS_BLOCKED|User is blocked|
|USER_IS_BOT|Bots can't send messages to other bots|
|WEBPAGE_CURL_FAILED|Failure while fetching the webpage with cURL|
|WEBPAGE_MEDIA_EMPTY|Webpage media empty|
|STORAGE_CHECK_FAILED|Server storage check failed|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|


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

$messages_StatedMessage = $MadelineProto->messages->sendMedia(['peer' => InputPeer, 'reply_to_msg_id' => int, 'media' => InputMedia, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendMedia
* params - `{"peer": InputPeer, "reply_to_msg_id": int, "media": InputMedia, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendMedia`

Parameters:

peer - Json encoded InputPeer

reply_to_msg_id - Json encoded int

media - Json encoded InputMedia




Or, if you're into Lua:

```
messages_StatedMessage = messages.sendMedia({peer=InputPeer, reply_to_msg_id=int, media=InputMedia, })
```

