---
title: account.updatePasswordSettings
description: account.updatePasswordSettings parameters, return type and example
---
## Method: account.updatePasswordSettings  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|current\_password\_hash|[bytes](../types/bytes.md) | Yes|
|new\_settings|[account\_PasswordInputSettings](../types/account_PasswordInputSettings.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|NEW_SETTINGS_INVALID|The new settings are invalid|


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

$Bool = $MadelineProto->account->updatePasswordSettings(['current_password_hash' => 'bytes', 'new_settings' => account_PasswordInputSettings, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.updatePasswordSettings`

Parameters:

current_password_hash - Json encoded bytes

new_settings - Json encoded account_PasswordInputSettings




Or, if you're into Lua:

```
Bool = account.updatePasswordSettings({current_password_hash='bytes', new_settings=account_PasswordInputSettings, })
```

