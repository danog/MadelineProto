---
title: account.getPassword
description: account.getPassword parameters, return type and example
---
## Method: account.getPassword  
[Back to methods index](index.md)




### Return type: [account\_Password](../types/account_Password.md)

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

$account_Password = $MadelineProto->account->getPassword();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getPassword`

Parameters:




Or, if you're into Lua:

```
account_Password = account.getPassword({})
```

