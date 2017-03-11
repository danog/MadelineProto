---
title: editInlineMessageCaption
description: Bots only. Edits caption of an inline message content sent via bot
---
## Method: editInlineMessageCaption  
[Back to methods index](index.md)


Bots only. Edits caption of an inline message content sent via bot

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|inline\_message\_id|[string](../types/string.md) | Yes|Inline message identifier|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|New message reply markup|
|caption|[string](../types/string.md) | Yes|New message content caption, 0-200 characters|


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

$Ok = $MadelineProto->editInlineMessageCaption(['inline_message_id' => string, 'reply_markup' => ReplyMarkup, 'caption' => string, ]);
```

Or, if you're into Lua:

```
Ok = editInlineMessageCaption({inline_message_id=string, reply_markup=ReplyMarkup, caption=string, })
```

