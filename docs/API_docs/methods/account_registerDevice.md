---
title: account.registerDevice
description: account.registerDevice parameters, return type and example
---
## Method: account.registerDevice  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|token\_type|[int](../types/int.md) | Yes|
|token|[string](../types/string.md) | Yes|
|app\_sandbox|[Bool](../types/Bool.md) | Yes|
|other\_uids|Array of [int](../types/int.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|TOKEN_INVALID|The provided token is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->account->registerDevice(['token_type' => int, 'token' => 'string', 'app_sandbox' => Bool, 'other_uids' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.registerDevice`

Parameters:

token_type - Json encoded int

token - Json encoded string

app_sandbox - Json encoded Bool

other_uids - Json encoded  array of int




Or, if you're into Lua:

```
Bool = account.registerDevice({token_type=int, token='string', app_sandbox=Bool, other_uids={int}, })
```

