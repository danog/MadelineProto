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


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PHONE_NUMBER_INVALID|The phone number is invalid|


### Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$auth_SentCode = $MadelineProto->account->sendChangePhoneCode(['allow_flashcall' => Bool, 'phone_number' => 'string', 'current_number' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



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

