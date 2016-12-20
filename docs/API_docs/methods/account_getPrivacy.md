---
title: account_getPrivacy
description: account_getPrivacy parameters, return type and example
---
## Method: account\_getPrivacy  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|key|[InputPrivacyKey](../types/InputPrivacyKey.md) | Required|


### Return type: [account\_PrivacyRules](../types/account_PrivacyRules.md)

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

$account_PrivacyRules = $MadelineProto->account_getPrivacy(['key' => InputPrivacyKey, ]);
```