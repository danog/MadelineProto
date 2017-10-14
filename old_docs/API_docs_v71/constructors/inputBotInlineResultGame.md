---
title: inputBotInlineResultGame
description: inputBotInlineResultGame attributes, type and example
---
## Constructor: inputBotInlineResultGame  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[string](../types/string.md) | Yes|
|short\_name|[string](../types/string.md) | Yes|
|send\_message|[InputBotInlineMessage](../types/InputBotInlineMessage.md) | Yes|



### Type: [InputBotInlineResult](../types/InputBotInlineResult.md)


### Example:

```
$inputBotInlineResultGame = ['_' => 'inputBotInlineResultGame', 'id' => 'string', 'short_name' => 'string', 'send_message' => InputBotInlineMessage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputBotInlineResultGame", "id": "string", "short_name": "string", "send_message": InputBotInlineMessage}
```


Or, if you're into Lua:  


```
inputBotInlineResultGame={_='inputBotInlineResultGame', id='string', short_name='string', send_message=InputBotInlineMessage}

```


