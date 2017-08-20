---
title: inputBotInlineMessageText
description: inputBotInlineMessageText attributes, type and example
---
## Constructor: inputBotInlineMessageText  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|no\_webpage|[Bool](../types/Bool.md) | Optional|
|message|[string](../types/string.md) | Yes|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|



### Type: [InputBotInlineMessage](../types/InputBotInlineMessage.md)


### Example:

```
$inputBotInlineMessageText = ['_' => 'inputBotInlineMessageText', 'no_webpage' => Bool, 'message' => 'string', 'entities' => [MessageEntity]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputBotInlineMessageText", "no_webpage": Bool, "message": "string", "entities": [MessageEntity]}
```


Or, if you're into Lua:  


```
inputBotInlineMessageText={_='inputBotInlineMessageText', no_webpage=Bool, message='string', entities={MessageEntity}}

```


