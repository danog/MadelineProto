---
title: account.sendConfirmPhoneCode
description: account.sendConfirmPhoneCode parameters, return type and example
---
## Method: account.sendConfirmPhoneCode  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|allow\_flashcall|[Bool](../types/Bool.md) | Optional|
|hash|[string](../types/string.md) | Yes|
|current\_number|[Bool](../types/Bool.md) | Optional|


### Return type: [auth\_SentCode](../types/auth_SentCode.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|HASH_INVALID|The provided hash is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$auth_SentCode = $MadelineProto->account->sendConfirmPhoneCode(['allow_flashcall' => Bool, 'hash' => 'string', 'current_number' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.sendConfirmPhoneCode`

Parameters:

allow_flashcall - Json encoded Bool

hash - Json encoded string

current_number - Json encoded Bool




Or, if you're into Lua:

```
auth_SentCode = account.sendConfirmPhoneCode({allow_flashcall=Bool, hash='string', current_number=Bool, })
```

