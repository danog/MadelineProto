---
title: messages_getInlineGameHighScores
description: messages_getInlineGameHighScores parameters, return type and example
---
## Method: messages\_getInlineGameHighScores  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[InputBotInlineMessageID](../types/InputBotInlineMessageID.md) | Required|
|user\_id|[InputUser](../types/InputUser.md) | Required|


### Return type: [messages\_HighScores](../types/messages_HighScores.md)

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

$messages_HighScores = $MadelineProto->messages_getInlineGameHighScores(['id' => InputBotInlineMessageID, 'user_id' => InputUser, ]);
```