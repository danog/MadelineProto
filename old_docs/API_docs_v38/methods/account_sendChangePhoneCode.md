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
|phone\_number|[string](../types/string.md) | Yes|New phone number|


### Return type: [account\_SentChangePhoneCode](../types/account_SentChangePhoneCode.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PHONE_NUMBER_INVALID|The phone number is invalid|


