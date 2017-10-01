---
title: messages.readEncryptedHistory
description: messages.readEncryptedHistory parameters, return type and example
---
## Method: messages.readEncryptedHistory  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|
|max\_date|[int](../types/int.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MSG_WAIT_FAILED|A waiting call returned an error|


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

$Bool = $MadelineProto->messages->readEncryptedHistory(['peer' => InputEncryptedChat, 'max_date' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.readEncryptedHistory
* params - `{"peer": InputEncryptedChat, "max_date": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.readEncryptedHistory`

Parameters:

peer - Json encoded InputEncryptedChat

max_date - Json encoded int




Or, if you're into Lua:

```
Bool = messages.readEncryptedHistory({peer=InputEncryptedChat, max_date=int, })
```

