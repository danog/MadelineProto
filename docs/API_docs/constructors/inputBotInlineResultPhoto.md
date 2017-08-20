---
title: inputBotInlineResultPhoto
description: inputBotInlineResultPhoto attributes, type and example
---
## Constructor: inputBotInlineResultPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[string](../types/string.md) | Yes|
|type|[string](../types/string.md) | Yes|
|photo|[InputPhoto](../types/InputPhoto.md) | Yes|
|send\_message|[InputBotInlineMessage](../types/InputBotInlineMessage.md) | Yes|



### Type: [InputBotInlineResult](../types/InputBotInlineResult.md)


### Example:

```
$inputBotInlineResultPhoto = ['_' => 'inputBotInlineResultPhoto', 'id' => 'string', 'type' => 'string', 'photo' => InputPhoto, 'send_message' => InputBotInlineMessage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputBotInlineResultPhoto", "id": "string", "type": "string", "photo": InputPhoto, "send_message": InputBotInlineMessage}
```


Or, if you're into Lua:  


```
inputBotInlineResultPhoto={_='inputBotInlineResultPhoto', id='string', type='string', photo=InputPhoto, send_message=InputBotInlineMessage}

```


