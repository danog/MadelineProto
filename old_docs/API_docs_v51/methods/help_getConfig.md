---
title: help.getConfig
description: Get server configuration
---
## Method: help.getConfig  
[Back to methods index](index.md)


Get server configuration



### Return type: [Config](../types/Config.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Config = $MadelineProto->help->getConfig();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|Timeout|A timeout occurred while fetching data from the bot|


