---
title: changeChatPhoto
description: Changes chat photo. Photo can't be changed for private chats. Photo will not change until change will be synchronized with the server. Photo will not be changed if application is killed before it can send request to the server. - There will be update about change of the photo on success. Otherwise error will be returned
---
## Method: changeChatPhoto  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes chat photo. Photo can't be changed for private chats. Photo will not change until change will be synchronized with the server. Photo will not be changed if application is killed before it can send request to the server. - There will be update about change of the photo on success. Otherwise error will be returned

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|photo|[InputFile](../types/InputFile.md) | Yes|New chat photo. You can use zero InputFileId to delete photo. Files accessible only by HTTP URL are not acceptable|


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

$Ok = $MadelineProto->changeChatPhoto(['chat_id' => InputPeer, 'photo' => InputFile, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - changeChatPhoto
* params - `{"chat_id": InputPeer, "photo": InputFile, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/changeChatPhoto`

Parameters:

chat_id - Json encoded InputPeer

photo - Json encoded InputFile




Or, if you're into Lua:

```
Ok = changeChatPhoto({chat_id=InputPeer, photo=InputFile, })
```

