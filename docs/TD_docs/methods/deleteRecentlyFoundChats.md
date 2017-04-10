---
title: deleteRecentlyFoundChats
description: Clears list of recently found chats
---
## Method: deleteRecentlyFoundChats  
[Back to methods index](index.md)


Clears list of recently found chats

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$Ok = $MadelineProto->deleteRecentlyFoundChats();
```

Or, if you're into Lua:

```
Ok = deleteRecentlyFoundChats({})
```

