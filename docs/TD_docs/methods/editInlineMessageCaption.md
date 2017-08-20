---
title: editInlineMessageCaption
description: Bots only. Edits caption of an inline message content sent via bot
---
## Method: editInlineMessageCaption  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Edits caption of an inline message content sent via bot

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|inline\_message\_id|[string](../types/string.md) | Yes|Inline message identifier|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|New message reply markup|
|caption|[string](../types/string.md) | Yes|New message content caption, 0-200 characters|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->editInlineMessageCaption(['inline_message_id' => 'string', 'reply_markup' => ReplyMarkup, 'caption' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - editInlineMessageCaption
* params - `{"inline_message_id": "string", "reply_markup": ReplyMarkup, "caption": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/editInlineMessageCaption`

Parameters:

inline_message_id - Json encoded string

reply_markup - Json encoded ReplyMarkup

caption - Json encoded string




Or, if you're into Lua:

```
Ok = editInlineMessageCaption({inline_message_id='string', reply_markup=ReplyMarkup, caption='string', })
```


## Usage of reply_markup

You can provide bot API reply_markup objects here.  


