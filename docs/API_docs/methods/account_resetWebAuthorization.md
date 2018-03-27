---
title: account.resetWebAuthorization
description: Delete a certain telegram web login authorization
---
## Method: account.resetWebAuthorization  
[Back to methods index](index.md)


Delete a certain telegram web login authorization

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|hash|[long](../types/long.md) | Yes|The authorization's hash|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->account->resetWebAuthorization(['hash' => long, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - account.resetWebAuthorization
* params - `{"hash": long, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.resetWebAuthorization`

Parameters:

hash - Json encoded long




Or, if you're into Lua:

```
Bool = account.resetWebAuthorization({hash=long, })
```

