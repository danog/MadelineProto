---
title: messages.editChatPhoto
description: Edit the photo of a normal chat (not supergroup)
---
## Method: messages.editChatPhoto  
[Back to methods index](index.md)


Edit the photo of a normal chat (not supergroup)

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The ID of the chat|
|photo|[InputChatPhoto](../types/InputChatPhoto.md) | Optional|The new phto|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->messages->editChatPhoto(['chat_id' => InputPeer, 'photo' => InputChatPhoto, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.editChatPhoto
* params - `{"chat_id": InputPeer, "photo": InputChatPhoto, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.editChatPhoto`

Parameters:

chat_id - Json encoded InputPeer

photo - Json encoded InputChatPhoto




Or, if you're into Lua:

```
Updates = messages.editChatPhoto({chat_id=InputPeer, photo=InputChatPhoto, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|
|INPUT_CONSTRUCTOR_INVALID|The provided constructor is invalid|
|INPUT_FETCH_FAIL|Failed deserializing TL payload|
|PEER_ID_INVALID|The provided peer id is invalid|
|PHOTO_EXT_INVALID|The extension of the photo is invalid|


