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
|success|[Bool](../types/Bool.md) | Optional|Success?|
|query\_id|[long](../types/long.md) | Yes|Query ID|
|error|[string](../types/string.md) | Optional|Error|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|ERROR_TEXT_EMPTY|The provided error message is empty|


