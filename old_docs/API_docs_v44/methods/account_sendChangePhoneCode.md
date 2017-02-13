---
title: account.sendChangePhoneCode
description: account.sendChangePhoneCode parameters, return type and example
---
## Method: account.sendChangePhoneCode  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_number|[string](../types/string.md) | Required|


### Return type: [account\_SentChangePhoneCode](../types/account_SentChangePhoneCode.md)

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

$account_SentChangePhoneCode = $MadelineProto->account->sendChangePhoneCode(['phone_number' => string, ]);
```
