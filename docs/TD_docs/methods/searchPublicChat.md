---
title: searchPublicChat
description: Searches public chat by its username. Currently only private and channel chats can be public. Returns chat if found, otherwise some error is returned
---
## Method: searchPublicChat  
[Back to methods index](index.md)


Searches public chat by its username. Currently only private and channel chats can be public. Returns chat if found, otherwise some error is returned

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|username|[string](../types/string.md) | Yes|Username to be resolved|


### Return type: [Chat](../types/Chat.md)

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

$Chat = $MadelineProto->searchPublicChat(['username' => string, ]);
```

Or, if you're into Lua:

```
Chat = searchPublicChat({username=string, })
```

