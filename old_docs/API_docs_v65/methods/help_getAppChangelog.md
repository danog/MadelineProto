---
title: help.getAppChangelog
description: Get the changelog of this app
---
## Method: help.getAppChangelog  
[Back to methods index](index.md)


Get the changelog of this app

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|prev\_app\_version|[string](../types/string.md) | Yes|Previous app version|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->help->getAppChangelog(['prev_app_version' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getAppChangelog`

Parameters:

prev_app_version - Json encoded string




Or, if you're into Lua:

```
Updates = help.getAppChangelog({prev_app_version='string', })
```

