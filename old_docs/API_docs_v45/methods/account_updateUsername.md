---
title: account.updateUsername
description: Update this user's username
---
## Method: account.updateUsername  
[Back to methods index](index.md)


Update this user's username

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|username|[string](../types/string.md) | Yes|The new username|


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

$User = $MadelineProto->account->updateUsername(['username' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.updateUsername`

Parameters:

username - Json encoded string




Or, if you're into Lua:

```
User = account.updateUsername({username='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|USERNAME_INVALID|The provided username is not valid|
|USERNAME_NOT_MODIFIED|The username was not modified|
|USERNAME_OCCUPIED|The provided username is already occupied|


