---
title: messages.setBotPrecheckoutResults
description: messages.setBotPrecheckoutResults parameters, return type and example
---
## Method: messages.setBotPrecheckoutResults  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|success|[Bool](../types/Bool.md) | Optional|
|query\_id|[long](../types/long.md) | Yes|
|error|[string](../types/string.md) | Optional|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|ERROR_TEXT_EMPTY|The provided error message is empty|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->messages->setBotPrecheckoutResults(['success' => Bool, 'query_id' => long, 'error' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

