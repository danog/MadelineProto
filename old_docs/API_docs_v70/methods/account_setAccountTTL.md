---
title: account.setAccountTTL
description: account.setAccountTTL parameters, return type and example
---
## Method: account.setAccountTTL  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ttl|[AccountDaysTTL](../types/AccountDaysTTL.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->account->setAccountTTL(['ttl' => AccountDaysTTL, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - account.setAccountTTL
* params - `{"ttl": AccountDaysTTL, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.setAccountTTL`

Parameters:

ttl - Json encoded AccountDaysTTL




Or, if you're into Lua:

```
Bool = account.setAccountTTL({ttl=AccountDaysTTL, })
```

