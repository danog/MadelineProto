---
title: help.getTermsOfService
description: help.getTermsOfService parameters, return type and example
---
## Method: help.getTermsOfService  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|lang\_code|[string](../types/string.md) | Yes|


### Return type: [help\_TermsOfService](../types/help_TermsOfService.md)

### Can bots use this method: **NO**


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

$help_TermsOfService = $MadelineProto->help->getTermsOfService(['lang_code' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getTermsOfService`

Parameters:

lang_code - Json encoded string




Or, if you're into Lua:

```
help_TermsOfService = help.getTermsOfService({lang_code='string', })
```

