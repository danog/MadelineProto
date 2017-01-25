---
title: account.getPasswordSettings
description: account.getPasswordSettings parameters, return type and example
---
## Method: account.getPasswordSettings  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|current\_password\_hash|[bytes](../types/bytes.md) | Required|


### Return type: [account\_PasswordSettings](../types/account_PasswordSettings.md)

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

$account_PasswordSettings = $MadelineProto->account->getPasswordSettings(['current_password_hash' => bytes, ]);
```