---
title: changeChatDraftMessage
description: Changes chat draft message
---
## Method: changeChatDraftMessage  
[Back to methods index](index.md)


Changes chat draft message

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|draft\_message|[draftMessage](../types/draftMessage.md) | Yes|New draft message, nullable|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Ok = $MadelineProto->changeChatDraftMessage(['chat_id' => long, 'draft_message' => draftMessage, ]);
```

Or, if you're into Lua:

```
Ok = changeChatDraftMessage({chat_id=long, draft_message=draftMessage, })
```

