---
title: help.getCdnConfig
description: Get CDN configuration
---
## Method: help.getCdnConfig  
[Back to methods index](index.md)


Get CDN configuration



### Return type: [CdnConfig](../types/CdnConfig.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|Timeout|A timeout occurred while fetching data from the bot|


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

$CdnConfig = $MadelineProto->help->getCdnConfig();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - help.getCdnConfig
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getCdnConfig`

Parameters:




Or, if you're into Lua:

```
CdnConfig = help.getCdnConfig({})
```

