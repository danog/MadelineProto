---
title: account.checkUsername
description: Check if this username is available
---
## Method: account.checkUsername  
[Back to methods index](index.md)


Check if this username is available

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|username|[string](../types/string.md) | Yes|The username to check|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->account->checkUsername(['username' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.checkUsername`

Parameters:

username - Json encoded string




Or, if you're into Lua:

```
Bool = account.checkUsername({username='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|USERNAME_INVALID|The provided username is not valid|


