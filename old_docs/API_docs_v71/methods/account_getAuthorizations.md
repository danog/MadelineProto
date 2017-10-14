---
title: account.getAuthorizations
description: account.getAuthorizations parameters, return type and example
---
## Method: account.getAuthorizations  
[Back to methods index](index.md)




### Return type: [account\_Authorizations](../types/account_Authorizations.md)

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

$account_Authorizations = $MadelineProto->account->getAuthorizations();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getAuthorizations`

Parameters:




Or, if you're into Lua:

```
account_Authorizations = account.getAuthorizations({})
```

