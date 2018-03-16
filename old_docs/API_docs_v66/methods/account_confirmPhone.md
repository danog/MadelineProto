---
title: account.confirmPhone
description: account.confirmPhone parameters, return type and example
---
## Method: account.confirmPhone  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_code\_hash|[string](../types/string.md) | Yes|
|phone\_code|[string](../types/string.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CODE_HASH_INVALID|Code hash invalid|
|PHONE_CODE_EMPTY|phone_code is missing|


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

$Bool = $MadelineProto->account->confirmPhone(['phone_code_hash' => 'string', 'phone_code' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.confirmPhone`

Parameters:

phone_code_hash - Json encoded string

phone_code - Json encoded string




Or, if you're into Lua:

```
Bool = account.confirmPhone({phone_code_hash='string', phone_code='string', })
```

