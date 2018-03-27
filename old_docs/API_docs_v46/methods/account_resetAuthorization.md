---
title: account.resetAuthorization
description: Delete a certain session
---
## Method: account.resetAuthorization  
[Back to methods index](index.md)


Delete a certain session

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|hash|[long](../types/long.md) | Yes|The session hash, obtained from $MadelineProto->account->getAuthorizations|


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

$Bool = $MadelineProto->account->resetAuthorization(['hash' => long, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.resetAuthorization`

Parameters:

hash - Json encoded long




Or, if you're into Lua:

```
Bool = account.resetAuthorization({hash=long, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|HASH_INVALID|The provided hash is invalid|


