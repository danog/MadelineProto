---
title: inlineKeyboardButton
description: Represents one button of the inline keyboard
---
## Constructor: inlineKeyboardButton  
[Back to constructors index](index.md)



Represents one button of the inline keyboard

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|text|[string](../types/string.md) | Yes|Text of the button|
|type|[InlineKeyboardButtonType](../types/InlineKeyboardButtonType.md) | Yes|Type of the button|



### Type: [InlineKeyboardButton](../types/InlineKeyboardButton.md)


### Example:

```
$inlineKeyboardButton = ['_' => 'inlineKeyboardButton', 'text' => 'string', 'type' => InlineKeyboardButtonType];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineKeyboardButton", "text": "string", "type": InlineKeyboardButtonType}
```


Or, if you're into Lua:  


```
inlineKeyboardButton={_='inlineKeyboardButton', text='string', type=InlineKeyboardButtonType}

```


