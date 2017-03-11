---
title: inputBotInlineMessageText
description: inputBotInlineMessageText attributes, type and example
---
## Constructor: inputBotInlineMessageText  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|no\_webpage|[Bool](../types/Bool.md) | Optional|
|message|[string](../types/string.md) | Yes|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [InputBotInlineMessage](../types/InputBotInlineMessage.md)


### Example:

```
$inputBotInlineMessageText = ['_' => 'inputBotInlineMessageText', 'no_webpage' => true, 'message' => string, 'entities' => [MessageEntity], 'reply_markup' => ReplyMarkup, ];
```  

Or, if you're into Lua:  


```
inputBotInlineMessageText={_='inputBotInlineMessageText', no_webpage=true, message=string, entities={MessageEntity}, reply_markup=ReplyMarkup, }

```


