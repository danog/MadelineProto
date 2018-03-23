---
title: help.getTermsOfService
description: Get terms of service
---
## Method: help.getTermsOfService  
[Back to methods index](index.md)


Get terms of service



### Return type: [help\_TermsOfService](../types/help_TermsOfService.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$help_TermsOfService = $MadelineProto->help->getTermsOfService();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getTermsOfService`

Parameters:




Or, if you're into Lua:

```
help_TermsOfService = help.getTermsOfService({})
```

