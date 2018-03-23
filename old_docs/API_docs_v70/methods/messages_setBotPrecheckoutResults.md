---
title: messages.setBotPrecheckoutResults
description: Bots only: set precheckout results
---
## Method: messages.setBotPrecheckoutResults  
[Back to methods index](index.md)


Bots only: set precheckout results

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|success|[CLICK ME Bool](../types/Bool.md) | Optional|Success?|
|query\_id|[CLICK ME long](../types/long.md) | Yes|Query ID|
|error|[CLICK ME string](../types/string.md) | Optional|Error|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|ERROR_TEXT_EMPTY|The provided error message is empty|


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

$Bool = $MadelineProto->messages->setBotPrecheckoutResults(['success' => Bool, 'query_id' => long, 'error' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.setBotPrecheckoutResults
* params - `{"success": Bool, "query_id": long, "error": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setBotPrecheckoutResults`

Parameters:

success - Json encoded Bool

query_id - Json encoded long

error - Json encoded string




Or, if you're into Lua:

```
Bool = messages.setBotPrecheckoutResults({success=Bool, query_id=long, error='string', })
```

