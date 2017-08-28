---
title: account.getPasswordSettings
description: account.getPasswordSettings parameters, return type and example
---
## Method: account.getPasswordSettings  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|current\_password\_hash|[bytes](../types/bytes.md) | Yes|


### Return type: [account\_PasswordSettings](../types/account_PasswordSettings.md)

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

$account_PasswordSettings = $MadelineProto->account->getPasswordSettings(['current_password_hash' => 'bytes', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getPasswordSettings`

Parameters:

current_password_hash - Json encoded bytes




Or, if you're into Lua:

```
account_PasswordSettings = account.getPasswordSettings({current_password_hash='bytes', })
```

