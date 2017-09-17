---
title: account.deleteAccount
description: account.deleteAccount parameters, return type and example
---
## Method: account.deleteAccount  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|reason|[string](../types/string.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->account->deleteAccount(['reason' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.deleteAccount`

Parameters:

reason - Json encoded string




Or, if you're into Lua:

```
Bool = account.deleteAccount({reason='string', })
```

