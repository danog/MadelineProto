---
title: getChatReportSpamState
description: Returns current chat report spam state
---
## Method: getChatReportSpamState  
[Back to methods index](index.md)


Returns current chat report spam state

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|


### Return type: [ChatReportSpamState](../types/ChatReportSpamState.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$ChatReportSpamState = $MadelineProto->getChatReportSpamState(['chat_id' => long, ]);
```

Or, if you're into Lua:

```
ChatReportSpamState = getChatReportSpamState({chat_id=long, })
```

