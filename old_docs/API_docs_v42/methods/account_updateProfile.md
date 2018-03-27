---
title: account.updateProfile
description: Update profile info
---
## Method: account.updateProfile  
[Back to methods index](index.md)


Update profile info

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|first\_name|[string](../types/string.md) | Yes|The first name|
|last\_name|[string](../types/string.md) | Yes|The last name|


### Return type: [User](../types/User.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$User = $MadelineProto->account->updateProfile(['first_name' => 'string', 'last_name' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.updateProfile`

Parameters:

first_name - Json encoded string

last_name - Json encoded string




Or, if you're into Lua:

```
User = account.updateProfile({first_name='string', last_name='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|ABOUT_TOO_LONG|The provided bio is too long|
|FIRSTNAME_INVALID|The first name is invalid|


