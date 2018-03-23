---
title: help.getNearestDc
description: Get nearest datacenter
---
## Method: help.getNearestDc  
[Back to methods index](index.md)


Get nearest datacenter



### Return type: [NearestDc](../types/NearestDc.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$NearestDc = $MadelineProto->help->getNearestDc();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getNearestDc`

Parameters:




Or, if you're into Lua:

```
NearestDc = help.getNearestDc({})
```

