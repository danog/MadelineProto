---
title: messages_getInlineBotResults
---
## Method: messages\_getInlineBotResults  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|bot|[InputUser](../types/InputUser.md) | Required|
|peer|[InputPeer](../types/InputPeer.md) | Required|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Optional|
|query|[string](../types/string.md) | Required|
|offset|[string](../types/string.md) | Required|


### Return type: [messages\_BotResults](../types/messages_BotResults.md)

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

$messages_BotResults = $MadelineProto->messages_getInlineBotResults(['bot' => InputUser, 'peer' => InputPeer, 'geo_point' => InputGeoPoint, 'query' => string, 'offset' => string, ]);
```