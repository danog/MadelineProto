---
title: phone.getCallConfig
description: phone.getCallConfig parameters, return type and example
---
## Method: phone.getCallConfig  
[Back to methods index](index.md)




### Return type: [DataJSON](../types/DataJSON.md)

### Can bots use this method: **NO**


### MadelineProto Example:


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

