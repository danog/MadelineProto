---
title: changeChatTitle
description: Changes chat title. Title can't be changed for private chats. Title will not change until change will be synchronized with the server. Title will not be changed if application is killed before it can send request to the server. - There will be update about change of the title on success. Otherwise error will be returned
---
## Method: changeChatTitle  
[Back to methods index](index.md)


Changes chat title. Title can't be changed for private chats. Title will not change until change will be synchronized with the server. Title will not be changed if application is killed before it can send request to the server. - There will be update about change of the title on success. Otherwise error will be returned

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|title|[string](../types/string.md) | Yes|New title of a chat, 0-255 characters|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Ok = $MadelineProto->changeChatTitle(['chat_id' => long, 'title' => string, ]);
```

Or, if you're into Lua:

```
Ok = changeChatTitle({chat_id=long, title=string, })
```

