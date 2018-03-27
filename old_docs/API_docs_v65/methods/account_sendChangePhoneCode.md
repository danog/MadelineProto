---
title: account.sendChangePhoneCode
description: Change the phone number
---
## Method: account.sendChangePhoneCode  
[Back to methods index](index.md)


Change the phone number

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|allow\_flashcall|[Bool](../types/Bool.md) | Optional|Can the code be sent using a flash call instead of an SMS?|
|phone\_number|[string](../types/string.md) | Yes|New phone number|
|current\_number|[Bool](../types/Bool.md) | Optional|Current phone number|


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

$auth_SentCode = $MadelineProto->account->sendChangePhoneCode(['allow_flashcall' => Bool, 'phone_number' => 'string', 'current_number' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.sendChangePhoneCode`

Parameters:

allow_flashcall - Json encoded Bool

phone_number - Json encoded string

current_number - Json encoded Bool




Or, if you're into Lua:

```
auth_SentCode = account.sendChangePhoneCode({allow_flashcall=Bool, phone_number='string', current_number=Bool, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PHONE_NUMBER_INVALID|The phone number is invalid|


