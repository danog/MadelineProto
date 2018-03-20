---
title: messages.sendMedia
description: messages.sendMedia parameters, return type and example
---
## Method: messages.sendMedia  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|
|reply\_to\_msg\_id|[CLICK ME int](../types/int.md) | Optional|
|media|[MessageMedia, Update, Message or InputMedia](../types/InputMedia.md) | Optional|
|reply\_markup|[CLICK ME ReplyMarkup](../types/ReplyMarkup.md) | Optional|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|EXTERNAL_URL_INVALID|External URL invalid|
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
|RANDOM_ID_DUPLICATE|You provided a random ID that was already used|
|STORAGE_CHECK_FAILED|Server storage check failed|
|CHAT_SEND_MEDIA_FORBIDDEN|You can't send media in this chat|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|
|Timeout|A timeout occurred while fetching data from the bot|


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$Updates = $MadelineProto->messages->sendMedia(['peer' => InputPeer, 'reply_to_msg_id' => int, 'media' => InputMedia, 'reply_markup' => ReplyMarkup, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendMedia
* params - `{"peer": InputPeer, "reply_to_msg_id": int, "media": InputMedia, "reply_markup": ReplyMarkup, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendMedia`

Parameters:

peer - Json encoded InputPeer

reply_to_msg_id - Json encoded int

media - Json encoded InputMedia

reply_markup - Json encoded ReplyMarkup




Or, if you're into Lua:

```
Updates = messages.sendMedia({peer=InputPeer, reply_to_msg_id=int, media=InputMedia, reply_markup=ReplyMarkup, })
```


## Usage of reply_markup

You can provide bot API reply_markup objects here.  


