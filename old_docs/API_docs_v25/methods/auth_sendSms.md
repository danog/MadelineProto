---
title: auth.sendSms
description: Send SMS verification code
---
## Method: auth.sendSms  
[Back to methods index](index.md)


Send SMS verification code

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|phone\_number|[CLICK ME string](../types/string.md) | Yes|Phone number|
|phone\_code\_hash|[CLICK ME string](../types/string.md) | Yes|Phone code ash|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


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

$Bool = $MadelineProto->auth->sendSms(['phone_number' => 'string', 'phone_code_hash' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - auth.sendSms
* params - `{"phone_number": "string", "phone_code_hash": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.sendSms`

Parameters:

phone_number - Json encoded string

phone_code_hash - Json encoded string




Or, if you're into Lua:

```
Bool = auth.sendSms({phone_number='string', phone_code_hash='string', })
```

