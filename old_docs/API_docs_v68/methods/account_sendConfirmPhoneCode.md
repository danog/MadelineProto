---
title: account.sendConfirmPhoneCode
description: Send confirmation phone code
---
## Method: account.sendConfirmPhoneCode  
[Back to methods index](index.md)


Send confirmation phone code

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|allow\_flashcall|[Bool](../types/Bool.md) | Optional|Can telegram call you instead of sending an SMS?|
|hash|[string](../types/string.md) | Yes|The hash|
|current\_number|[Bool](../types/Bool.md) | Optional|The current phone number|


### Return type: [auth\_SentCode](../types/auth_SentCode.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$auth_SentCode = $MadelineProto->account->sendConfirmPhoneCode(['allow_flashcall' => Bool, 'hash' => 'string', 'current_number' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|HASH_INVALID|The provided hash is invalid|


