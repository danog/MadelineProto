---
title: changeChatReportSpamState
description: Reports chat as a spam chat or as not a spam chat. Can be used only if ChatReportSpamState.can_report_spam is true. After this request ChatReportSpamState.can_report_spam became false forever
---
## Method: changeChatReportSpamState  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Reports chat as a spam chat or as not a spam chat. Can be used only if ChatReportSpamState.can_report_spam is true. After this request ChatReportSpamState.can_report_spam became false forever

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|is\_spam\_chat|[Bool](../types/Bool.md) | Yes|If true, chat will be reported as a spam chat, otherwise it will be marked as not a spam chat|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->changeChatReportSpamState(['chat_id' => InputPeer, 'is_spam_chat' => Bool, ]);
```

Or, if you're into Lua:

```
Ok = changeChatReportSpamState({chat_id=InputPeer, is_spam_chat=Bool, })
```

