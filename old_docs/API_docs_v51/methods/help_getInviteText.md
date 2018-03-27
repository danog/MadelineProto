---
title: help.getInviteText
description: Get invitation text
---
## Method: help.getInviteText  
[Back to methods index](index.md)


Get invitation text

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|lang\_code|[string](../types/string.md) | Yes|Language|


### Return type: [help\_InviteText](../types/help_InviteText.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$help_InviteText = $MadelineProto->help->getInviteText(['lang_code' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getInviteText`

Parameters:

lang_code - Json encoded string




Or, if you're into Lua:

```
help_InviteText = help.getInviteText({lang_code='string', })
```

