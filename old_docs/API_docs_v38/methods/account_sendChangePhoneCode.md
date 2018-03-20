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
|phone\_number|[CLICK ME string](../types/string.md) | Yes|New phone number|


### Return type: [account\_SentChangePhoneCode](../types/account_SentChangePhoneCode.md)

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

$account_SentChangePhoneCode = $MadelineProto->account->sendChangePhoneCode(['phone_number' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.sendChangePhoneCode`

Parameters:

phone_number - Json encoded string




Or, if you're into Lua:

```
account_SentChangePhoneCode = account.sendChangePhoneCode({phone_number='string', })
```

