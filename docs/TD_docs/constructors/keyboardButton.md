---
title: keyboardButton
description: Represents one button of the bot keyboard
---
## Constructor: keyboardButton  
[Back to constructors index](index.md)



Represents one button of the bot keyboard

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|text|[string](../types/string.md) | Yes|Text of the button|
|type|[KeyboardButtonType](../types/KeyboardButtonType.md) | Yes|Type of the button|



### Type: [KeyboardButton](../types/KeyboardButton.md)


### Example:

```
$keyboardButton = ['_' => 'keyboardButton', 'text' => 'string', 'type' => KeyboardButtonType];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "keyboardButton", "text": "string", "type": KeyboardButtonType}
```


Or, if you're into Lua:  


```
keyboardButton={_='keyboardButton', text='string', type=KeyboardButtonType}

```


