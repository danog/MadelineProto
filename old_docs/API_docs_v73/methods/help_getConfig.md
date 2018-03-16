---
title: help.getConfig
description: help.getConfig parameters, return type and example
---
## Method: help.getConfig  
[Back to methods index](index.md)




### Return type: [Config](../types/Config.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|Timeout|A timeout occurred while fetching data from the bot|


### Example:


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

$Config = $MadelineProto->help->getConfig();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - help.getConfig
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getConfig`

Parameters:




Or, if you're into Lua:

```
Config = help.getConfig({})
```

