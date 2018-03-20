---
title: auth.resendCode
description: Resend the SMS verification code
---
## Method: auth.resendCode  
[Back to methods index](index.md)


Resend the SMS verification code

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|phone\_number|[CLICK ME string](../types/string.md) | Yes|The phone number|
|phone\_code\_hash|[CLICK ME string](../types/string.md) | Yes|The phone code hash|


### Return type: [auth\_SentCode](../types/auth_SentCode.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PHONE_NUMBER_INVALID|The phone number is invalid|


### MadelineProto Example:


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

$auth_SentCode = $MadelineProto->auth->resendCode(['phone_number' => 'string', 'phone_code_hash' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.resendCode`

Parameters:

phone_number - Json encoded string

phone_code_hash - Json encoded string




Or, if you're into Lua:

```
auth_SentCode = auth.resendCode({phone_number='string', phone_code_hash='string', })
```

