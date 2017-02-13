---
title: account.updatePasswordSettings
description: account.updatePasswordSettings parameters, return type and example
---
## Method: account.updatePasswordSettings  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|current\_password\_hash|[bytes](../types/bytes.md) | Required|
|new\_settings|[account\_PasswordInputSettings](../types/account_PasswordInputSettings.md) | Required|


### Return type: [Bool](../types/Bool.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->account->updatePasswordSettings(['current_password_hash' => bytes, 'new_settings' => account_PasswordInputSettings, ]);
```
