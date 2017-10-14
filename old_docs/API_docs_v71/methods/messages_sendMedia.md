---
title: messages.sendMedia
description: messages.sendMedia parameters, return type and example
---
## Method: messages.sendMedia  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|silent|[Bool](../types/Bool.md) | Optional|
|background|[Bool](../types/Bool.md) | Optional|
|clear\_draft|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|media|[InputMedia](../types/InputMedia.md) | Yes|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|
|FILE_PART_0_MISSING|File part 0 missing|
|FILE_PART_LENGTH_INVALID|The length of a file part is invalid|
|FILE_PARTS_INVALID|The number of file parts is invalid|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|MEDIA_CAPTION_TOO_LONG|The caption is too long|
|MEDIA_EMPTY|The provided media object is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|
|PHOTO_EXT_INVALID|The extension of the photo is invalid|
|STORAGE_CHECK_FAILED|Server storage check failed|
|USER_IS_BLOCKED|User is blocked|
|USER_IS_BOT|Bots can't send messages to other bots|
|WEBPAGE_CURL_FAILED|Failure while fetching the webpage with cURL|


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

$Updates = $MadelineProto->messages->sendMedia(['silent' => Bool, 'background' => Bool, 'clear_draft' => Bool, 'peer' => InputPeer, 'reply_to_msg_id' => int, 'media' => InputMedia, 'reply_markup' => ReplyMarkup, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendMedia
* params - `{"silent": Bool, "background": Bool, "clear_draft": Bool, "peer": InputPeer, "reply_to_msg_id": int, "media": InputMedia, "reply_markup": ReplyMarkup, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendMedia`

Parameters:

silent - Json encoded Bool

background - Json encoded Bool

clear_draft - Json encoded Bool

peer - Json encoded InputPeer

reply_to_msg_id - Json encoded int

media - Json encoded InputMedia

reply_markup - Json encoded ReplyMarkup




Or, if you're into Lua:

```
Updates = messages.sendMedia({silent=Bool, background=Bool, clear_draft=Bool, peer=InputPeer, reply_to_msg_id=int, media=InputMedia, reply_markup=ReplyMarkup, })
```


## Usage of reply_markup

You can provide bot API reply_markup objects here.  


