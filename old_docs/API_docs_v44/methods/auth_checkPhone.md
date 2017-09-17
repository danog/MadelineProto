---
title: auth.checkPhone
description: auth.checkPhone parameters, return type and example
---
## Method: auth.checkPhone  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_number|[string](../types/string.md) | Yes|


### Return type: [auth\_CheckedPhone](../types/auth_CheckedPhone.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PHONE_NUMBER_BANNED|The provided phone number is banned from telegram|
|PHONE_NUMBER_INVALID|The phone number is invalid|


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

$auth_CheckedPhone = $MadelineProto->auth->checkPhone(['phone_number' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.checkPhone`

Parameters:

phone_number - Json encoded string




Or, if you're into Lua:

```
auth_CheckedPhone = auth.checkPhone({phone_number='string', })
```

