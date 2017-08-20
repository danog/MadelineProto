---
title: changeAbout
description: Changes about information of logged in user
---
## Method: changeAbout  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes about information of logged in user

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|about|[string](../types/string.md) | Yes|New value of userFull.about, 0-255 characters|


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

$Ok = $MadelineProto->changeAbout(['about' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - changeAbout
* params - `{"about": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/changeAbout`

Parameters:

about - Json encoded string




Or, if you're into Lua:

```
Ok = changeAbout({about='string', })
```

