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
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
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

