---
title: account.updateUsername
description: account.updateUsername parameters, return type and example
---
## Method: account.updateUsername  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|username|[string](../types/string.md) | Yes|


### Return type: [User](../types/User.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|USERNAME_INVALID|The provided username is not valid|
|USERNAME_NOT_MODIFIED|The username was not modified|
|USERNAME_OCCUPIED|The provided username is already occupied|


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

$User = $MadelineProto->account->updateUsername(['username' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.updateUsername`

Parameters:

username - Json encoded string




Or, if you're into Lua:

```
User = account.updateUsername({username='string', })
```

