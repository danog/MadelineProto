---
title: account.deleteAccount
description: Delete this account
---
## Method: account.deleteAccount  
[Back to methods index](index.md)


Delete this account

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|reason|[string](../types/string.md) | Yes|Why are you going away? :(|


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

$Bool = $MadelineProto->account->deleteAccount(['reason' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.deleteAccount`

Parameters:

reason - Json encoded string




Or, if you're into Lua:

```
Bool = account.deleteAccount({reason='string', })
```

