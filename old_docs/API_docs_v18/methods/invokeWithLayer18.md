---
title: invokeWithLayer18
description: invokeWithLayer18 parameters, return type and example
---
## Method: invokeWithLayer18  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|query|[!X](../types/!X.md) | Yes|


### Return type: [X](../types/X.md)

### Can bots use this method: **YES**


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

$X = $MadelineProto->invokeWithLayer18(['query' => !X, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - invokeWithLayer18
* params - `{"query": !X, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/invokeWithLayer18`

Parameters:

query - Json encoded !X




Or, if you're into Lua:

```
X = invokeWithLayer18({query=!X, })
```

