---
title: account.setPassword
description: account.setPassword parameters, return type and example
---
## Method: account.setPassword  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|current\_password\_hash|[bytes](../types/bytes.md) | Required|
|new\_salt|[bytes](../types/bytes.md) | Required|
|new\_password\_hash|[bytes](../types/bytes.md) | Required|
|hint|[string](../types/string.md) | Required|


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

$Bool = $MadelineProto->account->setPassword(['current_password_hash' => bytes, 'new_salt' => bytes, 'new_password_hash' => bytes, 'hint' => string, ]);
```
