---
title: editInlineMessageText
description: Bots only. Edits text of an inline text or game message sent via bot
---
## Method: editInlineMessageText  
[Back to methods index](index.md)


Bots only. Edits text of an inline text or game message sent via bot

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|inline\_message\_id|[string](../types/string.md) | Yes|Inline message identifier|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|New message reply markup|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|New text content of the message. Should be of type InputMessageText|


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

$Ok = $MadelineProto->editInlineMessageText(['inline_message_id' => string, 'reply_markup' => ReplyMarkup, 'input_message_content' => InputMessageContent, ]);
```

Or, if you're into Lua:

```
Ok = editInlineMessageText({inline_message_id=string, reply_markup=ReplyMarkup, input_message_content=InputMessageContent, })
```


## Usage of reply_markup

You can provide bot API reply_markup objects here.  


