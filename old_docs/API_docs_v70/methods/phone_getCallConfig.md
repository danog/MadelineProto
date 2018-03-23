---
title: phone.getCallConfig
description: Get call configuration
---
## Method: phone.getCallConfig  
[Back to methods index](index.md)


Get call configuration



### Return type: [DataJSON](../types/DataJSON.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$DataJSON = $MadelineProto->phone->getCallConfig();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/phone.getCallConfig`

Parameters:




Or, if you're into Lua:

```
DataJSON = phone.getCallConfig({})
```

